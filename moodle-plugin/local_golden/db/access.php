<?php
// Capabilities for local_golden.

defined('MOODLE_INTERNAL') || die();

$capabilities = [
    'local/golden:view' => [
        'captype'      => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes'   => [
            'manager' => CAP_ALLOW,
        ],
    ],
];
