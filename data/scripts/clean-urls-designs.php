<?php declare(strict_types=1);

/**
 * Add the selectors required by the clean urls to the css of the designs.
 *
 * AdminerCleanUrls strips the connection params (server, username, db) from the
 * href attributes, so the first remaining param starts with "?" instead of "&"
 * and selectors like [href$="&sql="] stop matching.
 *
 * A url has a single "?", so a selector with multiple conditions gets one
 * variant per condition, each switching a single one: "?edit=" and "&where" can
 * coexist, "?edit=" and "?where" cannot.
 *
 * Usage: php clean-urls-designs.php <file.css>…
 */

foreach (array_slice($argv, 1) as $file) {
    if (!is_file($file)) {
        continue;
    }
    $css = file_get_contents($file);
    $css = preg_replace_callback(
        // Match each css rule: selectors { declarations.
        '/^([^\n{]+)\{/m',
        'addCleanUrlSelectors',
        $css
    );
    file_put_contents($file, $css);
}

function addCleanUrlSelectors(array $matches): string
{
    $selectorGroup = $matches[1];
    if (strpos($selectorGroup, '[href') === false
        || strpos($selectorGroup, '"&') === false
    ) {
        return $matches[0];
    }

    $extra = [];
    foreach (preg_split('/\s*,\s*/', rtrim($selectorGroup)) as $selector) {
        if (!preg_match_all('/\[href[$*]="&/', $selector, $found, PREG_OFFSET_CAPTURE)) {
            continue;
        }
        foreach ($found[0] as $match) {
            // Offset of the "&", that is the last character of the match.
            $position = $match[1] + strlen($match[0]) - 1;
            $extra[] = substr_replace($selector, '?', $position, 1);
        }
    }

    return $extra
        ? $selectorGroup . ', ' . implode(', ', $extra) . ' {'
        : $matches[0];
}
