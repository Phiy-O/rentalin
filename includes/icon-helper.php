<?php

function icon_path($name)
{
    return dirname(__DIR__) . '/assets/icons/' . basename($name) . '.svg';
}

function render_icon($name, $class = '', $extraAttrs = '', $width = 24, $height = null)
{
    static $cache = [];

    $filename = basename($name);
    $height = $height ?? $width;

    if (!isset($cache[$filename])) {
        $filePath = icon_path($filename);
        if (!file_exists($filePath)) {
            return;
        }
        $cache[$filename] = file_get_contents($filePath);
    }

    $svg = $cache[$filename];

    preg_match('/viewBox="([^"]+)"/', $svg, $matches);
    $viewBox = $matches[1] ?? '0 0 24 24';

    if (preg_match('/<svg\b[^>]*>(.*)<\/svg>/is', $svg, $innerMatches)) {
        $inner = trim($innerMatches[1]);
    } else {
        $inner = '';
    }

    $inner = preg_replace('/<g\b[^>]*id="SVGRepo_(?:bgCarrier|tracerCarrier)"[^>]*>\s*<\/g>\s*/i', '', $inner);
    $classAttr = $class !== '' ? ' class="' . htmlspecialchars($class, ENT_QUOTES, 'UTF-8') . '"' : '';
    $widthAttr = htmlspecialchars((string) $width, ENT_QUOTES, 'UTF-8');
    $heightAttr = htmlspecialchars((string) $height, ENT_QUOTES, 'UTF-8');
    $viewBoxAttr = htmlspecialchars($viewBox, ENT_QUOTES, 'UTF-8');

    echo '<svg xmlns="http://www.w3.org/2000/svg" width="' . $widthAttr . '" height="' . $heightAttr . '" viewBox="' . $viewBoxAttr . '" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" preserveAspectRatio="xMidYMid meet" overflow="visible"' . $classAttr . ($extraAttrs !== '' ? ' ' . $extraAttrs : '') . ' aria-hidden="true">' . $inner . '</svg>';
}
