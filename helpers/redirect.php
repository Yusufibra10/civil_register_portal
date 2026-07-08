<?php
/** Redirect to a path relative to BASE_URL and stop execution. */
function redirect(string $path): void
{
    header('Location: ' . BASE_URL . ltrim($path, '/'));
    exit;
}

/** Redirect back to the referring page, falling back to the home page. */
function redirectBack(): void
{
    $back = $_SERVER['HTTP_REFERER'] ?? BASE_URL;
    header('Location: ' . $back);
    exit;
}
