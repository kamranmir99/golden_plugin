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
 * Data service for local_golden – courses, users, grades.
 *
 * @package    local_golden
 * @copyright  2026 Kamran Mir <kmir.phd21igis@student.nust.edu.pk>, IGIS, NUST, Islamabad
 * @license    https://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3 or later
 */

namespace local_golden;

defined('MOODLE_INTERNAL') || die();

/**
 * Read-only helper that aggregates Moodle data for the GOLDEN dashboard.
 */
class data_service {

    /**
     * Ensure Moodle's grading + enrolment libs are loaded.
     */
    private static function require_libs() {
        global $CFG;
        require_once($CFG->libdir . '/gradelib.php');
        require_once($CFG->libdir . '/grade/grade_item.php');
        require_once($CFG->libdir . '/enrollib.php');
    }

    /**
     * Return all visible courses (id, code, name, category).
     *
     * @return array
     */
    public static function list_courses(): array {
        global $DB;
        $records = $DB->get_records_sql(
            "SELECT id, shortname, fullname, category
               FROM {course}
              WHERE id <> :siteid AND visible = 1
           ORDER BY shortname ASC",
            ['siteid' => SITEID]
        );
        $out = [];
        foreach ($records as $r) {
            $out[] = [
                'id'       => (int)$r->id,
                'code'     => $r->shortname,
                'name'     => $r->fullname,
                'category' => (int)$r->category,
            ];
        }
        return $out;
    }

    /**
     * Return active users, optionally restricted to one course's enrolees.
     *
     * @param int|null $courseid
     * @param int      $max
     * @return array
     */
    public static function list_users($courseid, int $max = 5000): array {
        global $DB;
        self::require_libs();

        if (!empty($courseid)) {
            $context = \context_course::instance((int)$courseid);
            list($esql, $params) = \get_enrolled_sql($context, '', 0, true);
            $sql = "SELECT u.id, u.firstname, u.lastname, u.email, u.lastip
                      FROM {user} u
                      JOIN ($esql) je ON je.id = u.id
                     WHERE u.deleted = 0 AND u.suspended = 0 AND u.lastip <> ''
                  ORDER BY u.lastaccess DESC";
        } else {
            $sql = "SELECT id, firstname, lastname, email, lastip
                      FROM {user}
                     WHERE deleted = 0 AND suspended = 0 AND lastip <> '' AND id > 2
                  ORDER BY lastaccess DESC";
            $params = [];
        }
        $rs   = $DB->get_recordset_sql($sql, $params, 0, $max);
        $rows = [];
        foreach ($rs as $r) {
            $rows[] = [
                'id'        => (int)$r->id,
                'firstname' => $r->firstname,
                'lastname'  => $r->lastname,
                'email'     => $r->email,
                'lastip'    => trim((string)$r->lastip),
            ];
        }
        $rs->close();
        return $rows;
    }

    /**
     * Final percentage grade for a user in a course (0-100, or null).
     *
     * @param int $userid
     * @param int $courseid
     * @return float|null
     */
    public static function course_grade(int $userid, int $courseid) {
        self::require_libs();
        if (!function_exists('grade_get_course_grade')) {
            return null;
        }

        $grade = \grade_get_course_grade($userid, $courseid);
        if (empty($grade) || !isset($grade->grade) || $grade->grade === null || $grade->grade === false) {
            return null;
        }
        $item = \grade_item::fetch_course_item($courseid);
        if ($item && $item->grademax > 0) {
            return round(((float)$grade->grade / (float)$item->grademax) * 100, 2);
        }
        return round((float)$grade->grade, 2);
    }

    /**
     * Mean of a user's final course grades (or null).
     *
     * @param int $userid
     * @return float|null
     */
    public static function overall_grade(int $userid) {
        global $DB;
        $courseids = $DB->get_fieldset_sql(
            "SELECT DISTINCT c.id
               FROM {course} c
               JOIN {enrol} e ON e.courseid = c.id
               JOIN {user_enrolments} ue ON ue.enrolid = e.id
              WHERE ue.userid = :uid AND c.id <> :siteid",
            ['uid' => $userid, 'siteid' => SITEID]
        );
        $grades = [];
        foreach ($courseids as $cid) {
            $g = self::course_grade($userid, (int)$cid);
            if ($g !== null) {
                $grades[] = $g;
            }
        }
        if (!$grades) {
            return null;
        }
        return round(array_sum($grades) / count($grades), 2);
    }
}
