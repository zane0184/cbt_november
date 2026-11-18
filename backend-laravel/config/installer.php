<?php

return [
    // Set a unique random value of at least 32 characters before exposing /install.
    // Keep it private; never commit the production token to source control.
    'bootstrap_token' => env('CBT_INSTALLER_BOOTSTRAP_TOKEN'),
];
