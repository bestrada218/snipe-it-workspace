<?php

/*
|--------------------------------------------------------------------------
| Snipe-IT Workspace
|--------------------------------------------------------------------------
|
| Our own version number, kept apart from the Snipe-IT version it is based
| on (config/version.php, which keeps upstream's own numbering). It is read
| from the VERSION file at the repository root; CHANGELOG.md lists what each
| version changed.
|
*/

$versionFile = base_path('VERSION');

return [
    'version' => is_readable($versionFile) ? trim((string) file_get_contents($versionFile)) : 'unknown',
];
