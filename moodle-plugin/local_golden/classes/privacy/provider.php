<?php
// Privacy provider for local_golden – the plugin reads but does not store personal data.

namespace local_golden\privacy;

defined('MOODLE_INTERNAL') || die();

class provider implements
        \core_privacy\local\metadata\null_provider {

    public static function get_reason(): string {
        return 'privacy:metadata';
    }
}
