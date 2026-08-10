<?php declare(strict_types=1);

/**
 * Build the Adminer instance with the plugins used by Omeka.
 *
 * This is the upstream extension point: when this function exists, Adminer uses
 * its result instead of scanning the directory "adminer-plugins" relative to
 * the current working directory, so the released files can be used as is.
 *
 * The function must be declared in the global namespace before the Adminer file
 * is included.
 *
 * @see https://www.adminer.org/en/plugins/
 */
function adminer_object()
{
    $plugins = require dirname(__DIR__) . '/view/adminer/admin/index/adminer-plugins.phtml';
    return new Adminer\Plugins($plugins);
}
