<?php
/**
 * One-time "flash" messages: set before a redirect, read (and cleared) on
 * the next page load. Rendered by components/alert.php.
 */

/** $type maps to a Bootstrap alert variant: success | danger | warning | info. */
function setFlash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

/** Reads and clears all pending flash messages. Call at most once per request. */
function getFlashMessages(): array
{
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $messages;
}
