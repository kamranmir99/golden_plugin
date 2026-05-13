<?php
// Data service for local_golden – reads users, grades, courses.
//
// @package    local_golden
// @copyright  2026 Kamran Mir <kmir.phd21igis@student.nust.edu.pk>
// @license    https://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3 or later

namespace local_golden;

defined('MOODLE_INTERNAL') || die();

class data_service {

    /**
     * Ensure Moodle's gradelib.php is loaded.
     * Calling require_once is cheap; doing it lazily inside each method
     * removes any ordering issues caused by Moodle's class autoloader.
     */
    private static function require_gradelib() {
        global $CFG;
        require_once($CFG->libdir . '/gradelib.php');
        require_once($CFG->libdir . '/grade/grade_item.php');
    }

    /**
     * Return all courses (id, shortname, fullname).
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
     * Return users, optionally filtered by enrolment in a course.
     */
    public static function list_users($courseid, int $max = 5000): array {
        global $DB, $CFG;
        require_once($CFG->libdir . '/enrollib.php');

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
        $rs = $DB->get_recordset_sql($sql, $params, 0, $max);
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
     * Get final grade for a user in a course (0-100, or null).
     */
    public static function course_grade(int $userid, int $courseid) {
        self::require_gradelib();

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
     * Compute a user's overall grade as the mean of their final course grades.
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
