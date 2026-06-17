<?php

function product_image_url($filename)
{
    if (empty($filename)) {
        return BASE_URL . '/assets/images/product-placeholder.svg';
    }

    $safeFilename = basename($filename);
    $filePath = UPLOAD_PRODUCTS_PATH . $safeFilename;

    if (!file_exists($filePath)) {
        return BASE_URL . '/assets/images/product-placeholder.svg';
    }

    return UPLOAD_PRODUCTS_URL . rawurlencode($safeFilename);
}

function render_product_image($filename, $alt, $isPriority = false)
{
    $src = htmlspecialchars(product_image_url($filename));
    $altText = htmlspecialchars($alt);
    $loading = $isPriority ? 'eager' : 'lazy';
    $fetchPriority = $isPriority ? 'high' : 'auto';

    echo '<img src="' . $src . '" alt="' . $altText . '" width="320" height="240" loading="' . $loading . '" decoding="async" fetchpriority="' . $fetchPriority . '">';
}
