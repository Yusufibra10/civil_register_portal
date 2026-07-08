<?php
/**
 * Data-access layer for the settings key-value store. Read access is
 * cached per-request via getAllSettings(), since a page like
 * includes/sidebar.php may ask for a setting on every single request.
 */

/** Every setting as a flat key => value map. Missing keys simply aren't present — callers supply their own default. */
function getAllSettings(): array
{
    static $settings = null;

    if ($settings === null) {
        $rows = getDB()->query('SELECT setting_key, setting_value FROM settings')->fetchAll();
        $settings = array_column($rows, 'setting_value', 'setting_key');
    }

    return $settings;
}

/** Convenience accessor for one setting, e.g. setting('system_name', 'Civil Registry Portal'). */
function setting(string $key, string $default = ''): string
{
    return getAllSettings()[$key] ?? $default;
}

/**
 * Upserts many settings at once — the settings form saves everything in
 * one submit, not one field at a time. ON DUPLICATE KEY UPDATE keyed off
 * setting_key (UNIQUE) makes this a single insert-or-update per row,
 * with no separate "does this key already exist" lookup needed.
 */
function saveSettings(array $keyValuePairs, string $group, int $updatedBy): void
{
    $stmt = getDB()->prepare(
        'INSERT INTO settings (setting_key, setting_value, setting_group, updated_by)
         VALUES (:setting_key, :setting_value, :setting_group, :updated_by)
         ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_by = VALUES(updated_by)'
    );

    foreach ($keyValuePairs as $key => $value) {
        $stmt->execute([
            'setting_key'   => $key,
            'setting_value' => $value,
            'setting_group' => $group,
            'updated_by'    => $updatedBy,
        ]);
    }
}
