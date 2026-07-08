<?php
/**
 * Generic, secure image upload handling. Reusable by any module that
 * accepts a photo (citizens now; birth certificate scans, national ID
 * photos, and so on later) — nothing in here is citizen-specific.
 */

define('UPLOAD_MAX_BYTES', 2 * 1024 * 1024); // 2MB

const UPLOAD_ALLOWED_MIME = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp',
];

/**
 * Validates one $_FILES entry. Returns a list of error strings; an empty
 * array means the file is safe to pass to storeUploadedImage().
 *
 * A missing file is NOT an error here — an optional photo field simply
 * has nothing to validate. The caller decides whether the field is
 * required and reports that separately.
 */
function validateImageUpload(array $file): array
{
    $errors = [];

    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return $errors;
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'The photo failed to upload. Please try again.';
        return $errors;
    }

    if ($file['size'] > UPLOAD_MAX_BYTES) {
        $errors[] = 'Photo must be smaller than 2MB.';
    }

    // Never trust $file['type'] — it's the client's Content-Type header
    // and is trivial to spoof. finfo reads the file's actual bytes.
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!array_key_exists($mime, UPLOAD_ALLOWED_MIME)) {
        $errors[] = 'Photo must be a JPG, PNG, or WEBP image.';
    } elseif (@getimagesize($file['tmp_name']) === false) {
        // Belt and braces: a file can present a valid image MIME type to
        // finfo while still not being a decodable image (a truncated or
        // deliberately malformed file). getimagesize() actually parses it.
        $errors[] = 'The uploaded file is not a valid image.';
    }

    return $errors;
}

/**
 * Moves an already-validated upload into assets/uploads/{subdir}/ under a
 * random filename and returns that filename (not a full path — the caller
 * already knows the subdir by context, so only the filename is persisted).
 *
 * The filename is never derived from the client's original filename: a
 * client-supplied name could contain path traversal sequences, collide
 * with another citizen's file, or leak information through the name
 * itself. A random name sidesteps all three at once.
 */
function storeUploadedImage(array $file, string $subdir): string
{
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    $extension = UPLOAD_ALLOWED_MIME[$mime] ?? 'jpg';
    $filename = bin2hex(random_bytes(16)) . '.' . $extension;

    $targetDir = ROOT_PATH . '/assets/uploads/' . $subdir;
    if (!is_dir($targetDir) && !mkdir($targetDir, 0755, true) && !is_dir($targetDir)) {
        throw new RuntimeException("Could not create upload directory: {$subdir}");
    }

    if (!move_uploaded_file($file['tmp_name'], $targetDir . '/' . $filename)) {
        throw new RuntimeException('Failed to store the uploaded photo.');
    }

    return $filename;
}

/**
 * Deletes a previously stored upload. Safe to call with null/empty, or
 * with a file that's already gone.
 *
 * basename() strips any directory component before the path is built, so
 * even a corrupted stored value can never delete outside assets/uploads/.
 */
function deleteUploadedImage(?string $filename, string $subdir): void
{
    if (empty($filename)) {
        return;
    }

    $path = ROOT_PATH . '/assets/uploads/' . $subdir . '/' . basename($filename);

    if (is_file($path)) {
        unlink($path);
    }
}
