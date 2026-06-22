<?php

function detect_product_upload_mime($tmpPath, $fallbackMime = '')
{
    $imageInfo = @getimagesize($tmpPath);
    if (!empty($imageInfo['mime'])) {
        return normalize_product_upload_mime($imageInfo['mime']);
    }

    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo) {
            $mime = finfo_file($finfo, $tmpPath);
            finfo_close($finfo);
            if ($mime) {
                return normalize_product_upload_mime($mime);
            }
        }
    }

    return normalize_product_upload_mime($fallbackMime);
}

function normalize_product_upload_mime($mime)
{
    $mime = strtolower(trim((string) $mime));
    if (in_array($mime, ['image/jpg', 'image/pjpeg'], true)) {
        return 'image/jpeg';
    }
    return $mime;
}

function is_allowed_product_upload($tmpPath, $originalName, $fallbackMime = '')
{
    $mime = detect_product_upload_mime($tmpPath, $fallbackMime);
    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

    if ($mime === 'image/svg+xml' || $ext === 'svg') {
        return 'image/svg+xml';
    }

    return in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true) ? $mime : '';
}

function move_product_upload_original($tmpPath, $originalName, $baseName, $uploadDir)
{
    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    $ext = preg_replace('/[^a-z0-9]/', '', $ext) ?: 'jpg';
    $filename = $baseName . '.' . $ext;

    return move_uploaded_file($tmpPath, $uploadDir . $filename) ? $filename : null;
}

function save_product_upload($tmpPath, $originalName, $mimeType, $productId, $index, $suffix = '')
{
    $uploadDir = UPLOAD_PRODUCTS_PATH;
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $baseName = 'product_' . (int) $productId . '_' . time() . ($suffix !== '' ? '_' . $suffix : '') . '_' . (int) $index;
    $mimeType = is_allowed_product_upload($tmpPath, $originalName, $mimeType);

    if ($mimeType === '') {
        return null;
    }

    if ($mimeType === 'image/svg+xml') {
        $filename = $baseName . '.svg';
        return move_uploaded_file($tmpPath, $uploadDir . $filename) ? $filename : null;
    }

    if (!function_exists('imagecreatetruecolor') || !function_exists('imagewebp')) {
        return move_product_upload_original($tmpPath, $originalName, $baseName, $uploadDir);
    }

    switch ($mimeType) {
        case 'image/jpeg':
            $source = @imagecreatefromjpeg($tmpPath);
            break;
        case 'image/png':
            $source = @imagecreatefrompng($tmpPath);
            break;
        case 'image/webp':
            $source = @imagecreatefromwebp($tmpPath);
            break;
        default:
            $source = false;
    }

    if (!$source) {
        return move_product_upload_original($tmpPath, $originalName, $baseName, $uploadDir);
    }

    $width = imagesx($source);
    $height = imagesy($source);
    $maxSize = 1200;
    $scale = min(1, $maxSize / max($width, $height));
    $newWidth = max(1, (int) floor($width * $scale));
    $newHeight = max(1, (int) floor($height * $scale));

    $canvas = imagecreatetruecolor($newWidth, $newHeight);
    imagealphablending($canvas, false);
    imagesavealpha($canvas, true);

    if ($mimeType === 'image/jpeg') {
        $white = imagecolorallocate($canvas, 255, 255, 255);
        imagefilledrectangle($canvas, 0, 0, $newWidth, $newHeight, $white);
        imagealphablending($canvas, true);
    }

    imagecopyresampled($canvas, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

    $filename = $baseName . '.webp';
    $saved = imagewebp($canvas, $uploadDir . $filename, 78);
    imagedestroy($source);
    imagedestroy($canvas);

    if ($saved) {
        return $filename;
    }

    return move_product_upload_original($tmpPath, $originalName, $baseName, $uploadDir);
}
