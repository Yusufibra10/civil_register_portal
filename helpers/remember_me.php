<?php
/**
 * "Remember Me" persistent login using the selector/validator pattern.
 *
 * The cookie holds two random values joined by a colon: a selector (safe
 * to use as an indexed database lookup) and a validator (never stored in
 * plaintext — only its SHA-256 hash is kept). A stolen selector alone is
 * useless without the validator, and a stolen cookie is only useful once:
 * every successful auto-login deletes and reissues the token, so replaying
 * a captured cookie after its legitimate owner has used it again fails.
 */

define('REMEMBER_COOKIE_NAME', 'remember_token');
define('REMEMBER_TOKEN_DAYS', 30);

/** Call after a successful password login when the user checked "Remember Me". */
function issueRememberToken(int $userId): void
{
    $selector  = bin2hex(random_bytes(12));
    $validator = bin2hex(random_bytes(32));
    $ttlSeconds = REMEMBER_TOKEN_DAYS * 86400;

    getDB()->prepare(
        'INSERT INTO remember_tokens (user_id, selector, validator_hash, expires_at)
         VALUES (:user_id, :selector, :validator_hash, :expires_at)'
    )->execute([
        'user_id'        => $userId,
        'selector'       => $selector,
        'validator_hash' => hash('sha256', $validator),
        'expires_at'     => date('Y-m-d H:i:s', time() + $ttlSeconds),
    ]);

    setcookie(REMEMBER_COOKIE_NAME, $selector . ':' . $validator, [
        'expires'  => time() + $ttlSeconds,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => !empty($_SERVER['HTTPS']),
    ]);
}

/**
 * Called once per request (from config.php) when no session exists but a
 * remember-me cookie does. Logs the visitor in and rotates the token on
 * success; silently clears the cookie on any mismatch or expiry.
 */
function attemptRememberLogin(): void
{
    $cookie = $_COOKIE[REMEMBER_COOKIE_NAME] ?? '';

    if (!str_contains($cookie, ':')) {
        return;
    }

    [$selector, $validator] = explode(':', $cookie, 2);

    $stmt = getDB()->prepare(
        'SELECT id, user_id, validator_hash FROM remember_tokens
         WHERE selector = :selector AND expires_at > NOW()'
    );
    $stmt->execute(['selector' => $selector]);
    $token = $stmt->fetch();

    if (!$token || !hash_equals($token['validator_hash'], hash('sha256', $validator))) {
        clearRememberToken();
        return;
    }

    getDB()->prepare('DELETE FROM remember_tokens WHERE id = :id')->execute(['id' => $token['id']]);

    session_regenerate_id(true);
    $_SESSION['user_id']       = (int) $token['user_id'];
    $_SESSION['last_activity'] = time();

    issueRememberToken((int) $token['user_id']);
    logActivity((int) $token['user_id'], 'AUTO_LOGIN', 'auth', 'Logged in automatically via remember-me token.');
}

/** Call on logout, and whenever a remember-me cookie fails validation. */
function clearRememberToken(): void
{
    $cookie = $_COOKIE[REMEMBER_COOKIE_NAME] ?? '';

    if (str_contains($cookie, ':')) {
        [$selector] = explode(':', $cookie, 2);
        getDB()->prepare('DELETE FROM remember_tokens WHERE selector = :selector')
            ->execute(['selector' => $selector]);
    }

    setcookie(REMEMBER_COOKIE_NAME, '', [
        'expires'  => time() - 3600,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => !empty($_SERVER['HTTPS']),
    ]);
}
