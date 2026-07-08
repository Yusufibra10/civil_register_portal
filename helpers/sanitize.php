<?php
/**
 * Input sanitization. Run on incoming request data before it touches
 * validation or the database — trims whitespace and strips markup.
 * This is a defense-in-depth layer; parameterized PDO queries remain the
 * actual defense against SQL injection.
 */
function sanitize($value)
{
    if (is_array($value)) {
        return array_map('sanitize', $value);
    }
    return trim(strip_tags((string) $value));
}

/** Convenience wrapper for sanitizing a whole $_POST / $_GET array at once. */
function sanitizeArray(array $data): array
{
    return array_map('sanitize', $data);
}
