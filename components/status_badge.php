<?php
/**
 * renderStatusBadge(string $label, string $variant): void
 *
 * Generic Bootstrap badge renderer. Each domain (applications, birth
 * certificates, national IDs) defines its own code-to-variant mapping —
 * e.g. applicationStatusVariant() in helpers/applications_repository.php
 * — and this just renders the result consistently everywhere a status
 * appears: lists, profile pages, timelines.
 */
function renderStatusBadge(string $label, string $variant): void
{
    printf('<span class="badge text-bg-%s">%s</span>', e($variant), e($label));
}
