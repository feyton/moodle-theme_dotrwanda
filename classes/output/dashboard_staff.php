<?php
namespace theme_dotrwanda\output;

defined('MOODLE_INTERNAL') || die();

use renderable;
use renderer_base;
use templatable;
use moodle_url;

/**
 * Staff / Programme Officer dashboard data.
 * Zones, reach numbers, action items, and announcements.
 */
class dashboard_staff implements renderable, templatable {

    public function export_for_template(renderer_base $output): array {
        global $USER, $DB;

        $hour     = (int) date('G', time());
        $greeting = $hour < 12 ? 'Good morning'
                  : ($hour < 17 ? 'Good afternoon' : 'Good evening');

        $data = [
            'greeting'           => $greeting,
            'firstname'          => $USER->firstname,
            'jobtitle'           => $this->get_user_jobtitle($USER->id),
            'zonecontext'        => $this->get_zone_context($USER->id),
            'cohort'             => '2',
            'newannouncementurl' => (new moodle_url('/mod/forum/post.php'))->out(),
            'exporturl'          => (new moodle_url('/report/outline/index.php'))->out(),
            'scheduleurl'        => (new moodle_url('/calendar/view.php', ['view' => 'month']))->out(),
            'zonereporturl'      => (new moodle_url('/report/outline/index.php'))->out(),
            'newtaskurl'         => (new moodle_url('/calendar/event.php', ['action' => 'new']))->out(),
        ];

        // --- Learner metrics ---
        $totallearners = (int) $DB->count_records_sql(
            "SELECT COUNT(DISTINCT ue.userid)
             FROM {user_enrolments} ue
             JOIN {enrol} e ON e.id = ue.enrolid
             JOIN {course} c ON c.id = e.courseid AND c.id <> :siteid",
            ['siteid' => SITEID]
        );

        $data['activelearners']     = number_format($totallearners);
        $data['newlearnersmonth']   = 312; // TODO: filter by timestart >= start of month
        $data['avgcompletion']      = 67;
        $data['completionchange']   = '+4%';
        $data['activepartners']     = 5;
        $data['totalpartners']      = 5;

        // --- Tasks / action items ---
        $tasks                    = $this->get_staff_tasks($USER->id);
        $data['tasks']            = $tasks;
        $data['pendingtasks']     = count($tasks);
        $data['overduecount']     = count(array_filter($tasks, fn($t) => $t['overdue']));
        $data['hasoverdue']       = $data['overduecount'] > 0;

        // --- Zone reach ---
        $data['zones'] = $this->get_zone_reach();

        // --- Announcements ---
        $data['announcements'] = $this->get_staff_announcements($USER->id);

        return $data;
    }

    // ----------------------------------------------------------------
    // Helpers
    // ----------------------------------------------------------------

    private function get_user_jobtitle(int $userid): string {
        global $DB;
        $sql = 'SELECT uid.data FROM {user_info_data} uid
                JOIN {user_info_field} uif ON uif.id = uid.fieldid
                WHERE uid.userid = :uid AND uif.shortname = :field LIMIT 1';
        $rec = $DB->get_record_sql($sql, ['uid' => $userid, 'field' => 'jobtitle']);
        return $rec ? $rec->data : 'Programme Officer';
    }

    private function get_zone_context(int $userid): string {
        // Programme Directors see "All zones"; officers see their assigned zone
        if (has_capability('moodle/site:config', \context_system::instance(), $userid)) {
            return 'All zones';
        }
        global $DB;
        $sql = 'SELECT uid.data FROM {user_info_data} uid
                JOIN {user_info_field} uif ON uif.id = uid.fieldid
                WHERE uid.userid = :uid AND uif.shortname = :field LIMIT 1';
        $rec = $DB->get_record_sql($sql, ['uid' => $userid, 'field' => 'province']);
        return $rec ? $rec->data : 'All zones';
    }

    private function get_zone_reach(): array {
        // In production: query cohort-based enrolment grouped by user profile field 'province'
        // Returning representative static data for initial release
        return [
            ['name' => 'Kigali Province',   'count' => '1,420', 'pct' => 88, 'color' => '#0777AD'],
            ['name' => 'Northern Province', 'count' => '1,160', 'pct' => 72, 'color' => '#1D9E75'],
            ['name' => 'Southern Province', 'count' => '1,048', 'pct' => 65, 'color' => '#BA7517'],
            ['name' => 'Eastern Province',  'count' => '854',   'pct' => 53, 'color' => '#7F77DD'],
            ['name' => 'Western Province',  'count' => '694',   'pct' => 43, 'color' => '#D85A30'],
        ];
    }

    private function get_staff_tasks(int $userid): array {
        global $DB;

        $now   = time();
        $limit = $now + (14 * DAYSECS);

        // Pull calendar events created by this user + site-wide events
        $sql = "SELECT id, name, description, timestart, userid
                FROM {event}
                WHERE (userid = :uid OR userid = 0)
                AND timestart BETWEEN :start AND :limit
                AND eventtype IN ('user','site')
                ORDER BY timestart ASC
                LIMIT 6";

        $events = $DB->get_records_sql($sql, ['uid' => $userid, 'start' => $now - DAYSECS, 'limit' => $limit]);
        $result = [];

        foreach ($events as $ev) {
            $diff     = $ev->timestart - $now;
            $overdue  = $diff < 0;
            $daysaway = abs((int) ceil($diff / DAYSECS));
            $urgency  = $overdue ? 'red' : ($daysaway <= 2 ? 'red' : ($daysaway <= 5 ? 'amber' : 'blue'));

            $result[] = [
                'title'        => shorten_text(format_string($ev->name), 55),
                'subtitle'     => shorten_text(strip_tags(format_text($ev->description, FORMAT_HTML)), 60),
                'day'          => date('d', $ev->timestart),
                'month'        => strtoupper(date('M', $ev->timestart)),
                'overdue'      => $overdue,
                'urgency'      => $urgency,
                'urgencylabel' => $overdue ? 'Overdue' : ($daysaway . ' day' . ($daysaway === 1 ? '' : 's')),
            ];
        }

        return $result;
    }

    private function get_staff_announcements(int $userid): array {
        global $DB;

        $sql = "SELECT p.subject, p.message, p.created, p.userid,
                       u.firstname, u.lastname
                FROM {forum_posts} p
                JOIN {forum_discussions} d ON d.id = p.discussion
                JOIN {forum} f ON f.id = d.forum AND f.type = 'news'
                JOIN {course} c ON c.id = f.course AND c.id = :siteid
                JOIN {user} u ON u.id = p.userid
                ORDER BY p.created DESC
                LIMIT 3";

        $posts  = $DB->get_records_sql($sql, ['siteid' => SITEID]);
        $result = [];

        foreach ($posts as $post) {
            $byself   = (int)$post->userid === (int)$userid;
            $result[] = [
                'subject' => format_string($post->subject),
                'summary' => shorten_text(strip_tags(format_text($post->message, FORMAT_HTML)), 130),
                'timeago' => $this->timeago($post->created),
                'author'  => $byself ? 'you' : fullname($post),
            ];
        }

        return $result;
    }

    private function timeago(int $ts): string {
        $diff = time() - $ts;
        if ($diff < MINSECS * 2)  return 'Just now';
        if ($diff < HOURSECS)     return (int)($diff / MINSECS) . ' min ago';
        if ($diff < DAYSECS)      return (int)($diff / HOURSECS) . 'h ago';
        if ($diff < 2 * DAYSECS)  return 'Yesterday';
        return (int)($diff / DAYSECS) . ' days ago';
    }
}
