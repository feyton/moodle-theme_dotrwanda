<?php
namespace theme_dotrwanda\output;

defined('MOODLE_INTERNAL') || die();

use renderable;
use renderer_base;
use templatable;
use context_system;
use moodle_url;

/**
 * Learner (Digital Champion) dashboard data.
 *
 * Pulls real Moodle data where available; all keys map 1-to-1
 * with dashboard_learner.mustache.
 */
class dashboard_learner implements renderable, templatable {

    public function export_for_template(renderer_base $output): array {
        global $USER, $DB;

        $now    = time();
        $hour   = (int) date('G', $now);
        $greeting = $hour < 12 ? 'Good morning'
                  : ($hour < 17 ? 'Good afternoon' : 'Good evening');

        // --- User basics ---
        $data = [
            'greeting'        => $greeting,
            'firstname'       => $USER->firstname,
            'cohort'          => $this->get_user_cohort($USER->id),
            'province'        => $this->get_user_province($USER->id),
            'currentweek'     => 14,   // TODO: derive from cohort start date
            'totalweeks'      => 20,
            'calendarurl'     => (new moodle_url('/calendar/view.php', ['view' => 'month']))->out(),
            'allcoursesurl'   => (new moodle_url('/my/courses.php'))->out(),
            'announcementsurl'=> (new moodle_url('/mod/forum/index.php'))->out(),
        ];

        // --- Enrolment & progress ---
        $courses        = $this->get_user_courses($USER->id, $output);
        $overallpercent = empty($courses) ? 0
            : (int) round(array_sum(array_column($courses, 'progress')) / count($courses));

        $data['courses']        = $courses;
        $data['overallpercent'] = $overallpercent;
        $data['currentmodule']  = $courses[0]['fullname'] ?? 'No active module';
        $data['activitiesleft'] = $courses[0]['activitiesleft'] ?? 0;
        $data['modulesdone']    = count(array_filter($courses, fn($c) => $c['completed']));
        $data['modulestotal']   = count($courses);
        $data['resumeurl']      = $courses[0]['url'] ?? null;

        // --- Upcoming deadlines ---
        $data['deadlines']          = $this->get_upcoming_deadlines($USER->id);
        $data['assignmentsdue']     = count($data['deadlines']);
        $data['assignmentsoverdue'] = count(array_filter($data['deadlines'],
                                            fn($d) => $d['urgency'] === 'red'));

        // --- Badges ---
        $data['badgesearned']    = $DB->count_records('badge_issued', ['userid' => $USER->id]);
        $data['badgesthismonth'] = 1; // TODO: filter by timeissued >= start of month

        // --- Group rank (placeholder — implement via custom gradebook query) ---
        $data['grouprank'] = 3;

        // --- Announcements ---
        $data['announcements'] = $this->get_announcements();

        return $data;
    }

    // ----------------------------------------------------------------
    // Helpers
    // ----------------------------------------------------------------

    private function get_user_cohort(int $userid): string {
        global $DB;
        $sql = 'SELECT c.name FROM {cohort} c
                JOIN {cohort_members} cm ON cm.cohortid = c.id
                WHERE cm.userid = :uid
                ORDER BY c.id DESC LIMIT 1';
        $rec = $DB->get_record_sql($sql, ['uid' => $userid]);
        return $rec ? $rec->name : 'DSE Cohort 2';
    }

    private function get_user_province(int $userid): string {
        global $DB;
        // Stored in user profile field named 'province' — adjust field shortname as needed
        $sql = 'SELECT uid.data FROM {user_info_data} uid
                JOIN {user_info_field} uif ON uif.id = uid.fieldid
                WHERE uid.userid = :uid AND uif.shortname = :field LIMIT 1';
        $rec = $DB->get_record_sql($sql, ['uid' => $userid, 'field' => 'province']);
        return $rec ? $rec->data : 'Kigali Province';
    }

    private function get_user_courses(int $userid, renderer_base $output): array {
        $courses = enrol_get_users_courses($userid, true, ['fullname', 'id']);
        $result  = [];

        foreach (array_slice($courses, 0, 5) as $course) {
            $completion = new \completion_info($course);
            $percent    = 0;

            if ($completion->is_enabled()) {
                $activities = $completion->get_activities();
                $done = 0;
                foreach ($activities as $mod) {
                    $data = $completion->get_data($mod, false, $userid);
                    if ($data->completionstate == COMPLETION_COMPLETE ||
                        $data->completionstate == COMPLETION_COMPLETE_PASS) {
                        $done++;
                    }
                }
                $total   = count($activities);
                $percent = $total > 0 ? (int) round($done / $total * 100) : 0;
                $left    = $total - $done;
            } else {
                $left = 0;
            }

            $completed = $percent === 100;
            $result[]  = [
                'id'             => $course->id,
                'fullname'       => shorten_text(format_string($course->fullname), 50),
                'url'            => (new moodle_url('/course/view.php', ['id' => $course->id]))->out(),
                'progress'       => $percent,
                'completed'      => $completed,
                'activitiesleft' => $left,
                'statuslabel'    => $completed ? 'Completed' : ($percent > 0 ? 'In progress · ' . $left . ' activities left' : 'Not started'),
                'icon'           => 'bulb',
                'colorkey'       => 'blue',
                'badgecolor'     => $completed ? 'green' : ($percent > 0 ? 'blue' : 'amber'),
                'badgelabel'     => $completed ? 'Done' : ($percent > 0 ? 'Active' : 'Upcoming'),
            ];
        }

        return $result;
    }

    private function get_upcoming_deadlines(int $userid): array {
        global $DB;

        $now   = time();
        $limit = $now + (14 * DAYSECS);

        $sql = "SELECT cm.id, cm.course, a.name AS title, a.duedate,
                       c.fullname AS coursename
                FROM {assign} a
                JOIN {course_modules} cm ON cm.instance = a.id
                JOIN {modules} m ON m.id = cm.module AND m.name = 'assign'
                JOIN {course} c ON c.id = a.course
                JOIN {enrol} e ON e.courseid = c.id
                JOIN {user_enrolments} ue ON ue.enrolid = e.id AND ue.userid = :uid
                WHERE a.duedate BETWEEN :now AND :limit
                ORDER BY a.duedate ASC
                LIMIT 5";

        $rows = $DB->get_records_sql($sql, ['uid' => $userid, 'now' => $now, 'limit' => $limit]);

        $result = [];
        foreach ($rows as $row) {
            $daysuntil = (int) ceil(($row->duedate - $now) / DAYSECS);
            $urgency   = $daysuntil <= 2 ? 'red' : ($daysuntil <= 5 ? 'amber' : 'blue');
            $result[]  = [
                'title'        => shorten_text(format_string($row->title), 50),
                'subtitle'     => shorten_text(format_string($row->coursename), 40),
                'day'          => date('d', $row->duedate),
                'month'        => strtoupper(date('M', $row->duedate)),
                'urgency'      => $urgency,
                'urgencylabel' => $daysuntil <= 0 ? 'Overdue' : $daysuntil . ' day' . ($daysuntil === 1 ? '' : 's'),
            ];
        }

        return $result;
    }

    private function get_announcements(): array {
        global $DB;

        // Pull latest 3 posts from site-level news forum
        $sql = "SELECT p.subject, p.message, p.created
                FROM {forum_posts} p
                JOIN {forum_discussions} d ON d.id = p.discussion
                JOIN {forum} f ON f.id = d.forum AND f.type = 'news'
                JOIN {course} c ON c.id = f.course AND c.id = :siteid
                ORDER BY p.created DESC
                LIMIT 3";

        $posts  = $DB->get_records_sql($sql, ['siteid' => SITEID]);
        $result = [];

        foreach ($posts as $post) {
            $result[] = [
                'subject' => format_string($post->subject),
                'summary' => shorten_text(strip_tags(format_text($post->message, FORMAT_HTML)), 120),
                'timeago' => self::timeago($post->created),
            ];
        }

        return $result;
    }

    private static function timeago(int $ts): string {
        $diff = time() - $ts;
        if ($diff < MINSECS * 2)  return 'Just now';
        if ($diff < HOURSECS)     return (int)($diff / MINSECS) . ' min ago';
        if ($diff < DAYSECS)      return (int)($diff / HOURSECS) . 'h ago';
        if ($diff < 2 * DAYSECS)  return 'Yesterday';
        return (int)($diff / DAYSECS) . ' days ago';
    }
}
