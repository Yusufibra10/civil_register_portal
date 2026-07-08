<?php
/**
 * General-purpose utility helpers used across every module.
 */

/** Escape a value for safe HTML output. Use this around every echo of user data. */
function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

/**
 * Build an absolute URL to a file under assets/, with a cache-busting
 * ?v=<last-modified-time> query string appended automatically. Without
 * this, browsers honor the 1-month Cache-Control this app sends for CSS/
 * JS/images (see .htaccess) even after the file on disk changes — every
 * visitor would keep serving a stale copy from their own cache until it
 * naturally expires. Tying the version to filemtime() means an edited
 * file gets a new URL automatically, with no version number to remember
 * to bump by hand.
 */
function asset(string $path): string
{
    $relativePath = ltrim($path, '/');
    $fullPath = ROOT_PATH . '/assets/' . $relativePath;
    $version = is_file($fullPath) ? filemtime($fullPath) : time();

    return BASE_URL . 'assets/' . $relativePath . '?v=' . $version;
}

/** Re-populate a form field with the value submitted before a failed validation. */
function old(string $key, string $default = ''): string
{
    $value = $_SESSION['old'][$key] ?? $default;
    return e((string) $value);
}

/** Format a DATE/DATETIME column for display; returns an em-dash for empty values. */
function formatDate(?string $date, string $format = 'd M Y'): string
{
    if (empty($date)) {
        return '—';
    }
    return date($format, strtotime($date));
}

/**
 * Build a human-facing reference number, e.g. generateReferenceNumber('APP') -> APP-2026-4821.
 * Foundation-only: business modules must retry on a UNIQUE-constraint collision
 * before persisting, since random_int does not guarantee uniqueness by itself.
 */
function generateReferenceNumber(string $prefix): string
{
    return sprintf('%s-%s-%04d', $prefix, date('Y'), random_int(1, 9999));
}

/** Turn a DATETIME/TIMESTAMP into "5 minutes ago" / "3 days ago" style text. */
function timeAgo(string $datetime): string
{
    $diff = time() - strtotime($datetime);

    if ($diff < 60) {
        return 'just now';
    }

    $units = [
        31536000 => 'year',
        2592000  => 'month',
        86400    => 'day',
        3600     => 'hour',
        60       => 'minute',
    ];

    foreach ($units as $seconds => $label) {
        $count = intdiv($diff, $seconds);
        if ($count >= 1) {
            return $count . ' ' . $label . ($count > 1 ? 's' : '') . ' ago';
        }
    }

    return 'just now';
}

/** Whole years between a DATE (e.g. date_of_birth) and today. */
function calculateAge(string $date): int
{
    return (int) date_diff(date_create($date), date_create('today'))->y;
}

/** Bootstrap "is-invalid" class for a field, if validation reported an error on it. */
function fieldInvalidClass(array $errors, string $field): string
{
    return empty($errors[$field]) ? '' : 'is-invalid';
}

/** Bootstrap feedback markup for a field's first validation error, if any. */
function fieldErrorHtml(array $errors, string $field): string
{
    if (empty($errors[$field])) {
        return '';
    }
    return '<div class="invalid-feedback d-block">' . e($errors[$field][0]) . '</div>';
}

/**
 * Runs $attempt() up to $maxAttempts times, retrying only when it throws a
 * PDOException that is specifically a duplicate-key violation on
 * $uniqueKeyName. Any other exception — including a duplicate on a
 * *different* unique key — propagates immediately instead of being
 * silently retried, since retrying that would just mask a real error.
 *
 * $attempt must generate its own fresh candidate value on every call
 * (e.g. a new random reference number); retrying with the same value
 * would simply collide again. Caller manages the transaction — this
 * function only controls the retry loop, since PDO does not support
 * nesting beginTransaction() calls.
 *
 * Used by every module that generates a human-facing reference number
 * (citizen_uid, certificate_number, application_number, id_number).
 */
function retryOnDuplicateKey(callable $attempt, string $uniqueKeyName, int $maxAttempts = 5)
{
    for ($i = 1; $i <= $maxAttempts; $i++) {
        try {
            return $attempt();
        } catch (PDOException $e) {
            $isCollision = $e->getCode() === '23000' && str_contains($e->getMessage(), $uniqueKeyName);

            if (!$isCollision || $i >= $maxAttempts) {
                throw $e;
            }
        }
    }

    throw new RuntimeException("Could not generate a unique value for {$uniqueKeyName} after {$maxAttempts} attempts.");
}

/**
 * Has $identifier triggered $action at least $maxAttempts times within the
 * last $windowMinutes? Backed by activity_logs, so any caller that also
 * calls logActivity() with the same $action/$identifier pair is throttled.
 * $identifier is a free-form key — an email for login attempts, an IP
 * address for anonymous public-page lookups.
 */
function isRateLimited(string $action, string $identifier, int $maxAttempts, int $windowMinutes): bool
{
    $cutoff = date('Y-m-d H:i:s', time() - $windowMinutes * 60);
    $stmt = getDB()->prepare(
        'SELECT COUNT(*) FROM activity_logs
         WHERE action = :action AND description = :identifier AND created_at >= :cutoff'
    );
    $stmt->execute(['action' => $action, 'identifier' => $identifier, 'cutoff' => $cutoff]);

    return (int) $stmt->fetchColumn() >= $maxAttempts;
}

/** Insert one row into activity_logs. Call this from any state-changing action. */
function logActivity(?int $userId, string $action, string $module = '', string $description = ''): void
{
    $stmt = getDB()->prepare(
        'INSERT INTO activity_logs (user_id, action, module, description, ip_address, user_agent)
         VALUES (:user_id, :action, :module, :description, :ip, :agent)'
    );
    $stmt->execute([
        'user_id'     => $userId,
        'action'      => $action,
        'module'      => $module,
        'description' => $description,
        'ip'          => $_SERVER['REMOTE_ADDR'] ?? null,
        'agent'       => $_SERVER['HTTP_USER_AGENT'] ?? null,
    ]);
}
