<?php

$installerRuntimePath = dirname(__DIR__) . '/storage/app/private/installer-runtime.php';
$installerRuntimeOverrides = [];
if (is_file($installerRuntimePath)) {
    $loadedInstallerRuntime = require $installerRuntimePath;
    if (is_array($loadedInstallerRuntime)) {
        $installerRuntimeOverrides = $loadedInstallerRuntime;
    }
}

return [
    // Set a unique random value of at least 32 characters before exposing /install.
    // Keep it private; never commit the production token to source control.
    'bootstrap_token' => array_key_exists('bootstrap_token', $installerRuntimeOverrides)
        ? $installerRuntimeOverrides['bootstrap_token']
        : env('CBT_INSTALLER_BOOTSTRAP_TOKEN'),
];
