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
 * Renderable / templatable dashboard view for local_golden.
 *
 * @package    local_golden
 * @copyright  2026 Kamran Mir <kmir.phd21igis@student.nust.edu.pk>, IGIS, NUST, Islamabad
 * @license    https://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3 or later
 */

namespace local_golden\output;

defined('MOODLE_INTERNAL') || die();

use renderable;
use templatable;
use renderer_base;

/**
 * Dashboard renderable.
 */
class dashboard implements renderable, templatable {

    /** @var array Course list (id, code, name). */
    private $courses;

    /**
     * Constructor.
     *
     * @param array $courses
     */
    public function __construct(array $courses) {
        $this->courses = $courses;
    }

    /**
     * Build template data.
     *
     * @param renderer_base $output
     * @return array
     */
    public function export_for_template(renderer_base $output) {
        $coursesout = [];
        foreach ($this->courses as $c) {
            $coursesout[] = [
                'id'   => (int)$c['id'],
                'code' => (string)$c['code'],
                'name' => (string)$c['name'],
            ];
        }
        $credit = get_string('credit', 'local_golden', (object)[
            'name'        => get_string('author_name', 'local_golden'),
            'affiliation' => get_string('author_affiliation', 'local_golden'),
            'email'       => get_string('author_email', 'local_golden'),
        ]);
        return [
            'courses'      => $coursesout,
            'coursecount'  => count($coursesout),
            'creditline'   => $credit,
            'authoremail'  => get_string('author_email', 'local_golden'),
        ];
    }
}
