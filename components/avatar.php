<?php
/**
 * renderAvatar(?string $photoFilename, string $subdir, string $firstName, string $lastName, string $size = 'md'): void
 *
 * Renders the citizen's real photo if one has been uploaded, otherwise a
 * colored initials circle — the "default avatar" the brief asks for,
 * without needing a static placeholder image asset.
 */
function renderAvatar(?string $photoFilename, string $subdir, string $firstName, string $lastName, string $size = 'md'): void
{
    $sizeClass = 'avatar-' . $size;

    if (!empty($photoFilename)) {
        $url = asset('uploads/' . $subdir . '/' . $photoFilename);
        printf(
            '<img src="%s" class="avatar %s" alt="Photo of %s %s">',
            e($url),
            e($sizeClass),
            e($firstName),
            e($lastName)
        );
        return;
    }

    $initials = mb_strtoupper(mb_substr($firstName, 0, 1) . mb_substr($lastName, 0, 1));
    printf(
        '<div class="avatar avatar-initials %s">%s</div>',
        e($sizeClass),
        e($initials)
    );
}
