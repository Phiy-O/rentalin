<?php

function icon_path($name)
{
    return dirname(__DIR__) . '/assets/icons/' . basename($name) . '.svg';
}

function render_icon($name, $class = '', $extraAttrs = '')
{
    static $cache = [];

    $filename = basename($name);

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

    $inner = preg_replace('/^\s*<svg[^>]*>\s*|\s*<\/svg>\s*$/s', '', $svg);
    $classAttr = $class !== '' ? ' class="' . htmlspecialchars($class) . '"' : '';

    echo '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="' . htmlspecialchars($viewBox) . '" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"' . $classAttr . ($extraAttrs !== '' ? ' ' . $extraAttrs : '') . ' aria-hidden="true">' . $inner . '</svg>';
}
