<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Privacy provider for local_golden.
 *
 * The plugin reads existing fields of mdl_user (lastip) and standard grade
 * data via Moodle's grading API; it does not store, transmit or share any
 * personal data. The null provider therefore satisfies the privacy API.
 *
 * @package    local_golden
 * @copyright  2026 Kamran Mir <kmir.phd21igis@student.nust.edu.pk>, IGIS, NUST, Islamabad
 * @license    https://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3 or later
 */

namespace local_golden\privacy;

defined('MOODLE_INTERNAL') || die();

/**
 * Null privacy provider.
 */
class provider implements \core_privacy\local\metadata\null_provider {

    /**
     * @return string
     */
    public static function get_reason(): string {
        return 'privacy:metadata';
    }
}
