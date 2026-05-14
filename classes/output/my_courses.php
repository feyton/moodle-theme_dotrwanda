<?php
namespace theme_dotrwanda\output;

defined('MOODLE_INTERNAL') || die();

use renderable;
use renderer_base;
use templatable;
use completion_info;
use moodle_url;
use grade_item;
use grade_grade;

/**
 * My Courses page data.
 * Renders a 3-column card grid with progress, grade, and next due activity.
 */
class my_courses implements renderable, templatable {

    public function export_for_template(renderer_base $output): array {
        global $USER, $DB;

        $courses     = enrol_get_users_courses($USER->id, true, ['fullname', 'id', 'shortname', 'startdate', 'enddate']);
        $cards       = [];
        $totalgrade  = 0;
        $gradecount  = 0;
        $duethisweek = 0;
        $completed   = 0;

        foreach ($courses as $course) {
            $card = $this->build_card($course, $USER->id, $DB);

            if ($card['statuskey'] === 'done') {
                $completed++;
            }
            if (!empty($card['graderaw'])) {
                $totalgrade += $card['graderaw'];
                $gradecount++;
            }
            if (!empty($card['dueurgency']) && $card['dueurgency'] === 'red') {
                $duethisweek++;
            }

            $cards[] = $card;
        }

        // Sort: in-progress first, then done, then upcoming/locked
        usort($cards, function($a, $b) {
            $order = ['active' => 0, 'done' => 1, 'upcoming' => 2, 'locked' => 3];
            return ($order[$a['statuskey']] ?? 9) <=> ($order[$b['statuskey']] ?? 9);
        });

        $avggrade   = $gradecount > 0 ? (int) round($totalgrade / $gradecount) : 0;
        $gradeletter = $this->grade_letter($avggrade);
        $totalpct   = count($courses) > 0
            ? (int) round($completed / count($courses) * 100) : 0;

        return [
            'cohort'       => $this->get_cohort($USER->id),
            'province'     => $this->get_province($USER->id),
            'totalcourses' => count($courses),
            'completed'    => $completed,
            'totalpct'     => $totalpct,
            'avggrade'     => $avggrade > 0 ? $avggrade . '%' : '–',
            'gradeletter'  => $gradeletter,
            'duethisweek'  => $duethisweek,
            'hasdueurgency'=> $duethisweek > 0,
            'courses'      => $cards,
            'allcoursesurl'=> (new moodle_url('/my/courses.php'))->out(),
        ];
    }

    // ----------------------------------------------------------------
    // Build one course card
    // ----------------------------------------------------------------

    private function build_card(\stdClass $course, int $userid, $DB): array {
        // --- Completion ---
        $completion  = new completion_info($course);
        $progress    = 0;
        $donecnt     = 0;
        $totalcnt    = 0;
        $timespent   = 0;

        if ($completion->is_enabled()) {
            $activities = $completion->get_activities();
            $totalcnt   = count($activities);

            foreach ($activities as $mod) {
                $data = $completion->get_data($mod, false, $userid);
                if (in_array($data->completionstate, [
                    COMPLETION_COMPLETE, COMPLETION_COMPLETE_PASS
                ])) {
                    $donecnt++;
                }
            }
            $progress = $totalcnt > 0 ? (int) round($donecnt / $totalcnt * 100) : 0;
        }

        // Time spent (sum of logstore duration for this course)
        $timespent = (int) $DB->get_field_sql(
            "SELECT COALESCE(SUM(timecreated - timecreated), 0)
             FROM {logstore_standard_log}
             WHERE userid = :uid AND courseid = :cid",
            ['uid' => $userid, 'cid' => $course->id]
        );
        // Fallback: estimate from action count * avg 3 min
        if ($timespent === 0) {
            $actions   = (int) $DB->count_records('logstore_standard_log', [
                'userid'   => $userid,
                'courseid' => $course->id,
            ]);
            $timespent = $actions * 180; // seconds
        }

        // --- Grade ---
        $graderaw    = 0;
        $gradestr    = '–';
        $gradecsskey = 'na';

        $gradeitem = grade_item::fetch([
            'courseid'   => $course->id,
            'itemtype'   => 'course',
        ]);
        if ($gradeitem) {
            $gradegrade = grade_grade::fetch([
                'itemid' => $gradeitem->id,
                'userid' => $userid,
            ]);
            if ($gradegrade && $gradegrade->finalgrade !== null) {
                $graderaw  = (int) round($gradegrade->finalgrade);
                $gradestr  = $graderaw . '%';
                $gradecsskey = $graderaw >= 75 ? 'a' : 'b';
            }
        }

        // --- Status ---
        $now       = time();
        $isstarted = $course->startdate <= $now;
        $islocked  = !$isstarted;

        if ($progress === 100) {
            $statuskey   = 'done';
            $statuslabel = 'Completed';
            $bannercolor = '#1D9E75';
            $badgecss    = 'b-done';
            $iconbg      = '#EAF3DE';
            $iconfg      = '#27500A';
        } elseif ($islocked) {
            $statuskey   = 'locked';
            $statuslabel = 'Locked';
            $bannercolor = '#828282';
            $badgecss    = 'b-locked';
            $iconbg      = 'var(--bs-secondary-bg,#f0f0f0)';
            $iconfg      = 'var(--bs-secondary-color,#6c757d)';
        } elseif ($progress > 0) {
            $statuskey   = 'active';
            $statuslabel = 'In progress';
            $bannercolor = '#0777AD';
            $badgecss    = 'b-active';
            $iconbg      = '#E6F1FB';
            $iconfg      = '#0C447C';
        } else {
            // started but not touched
            $statuskey   = $course->startdate > $now - (7 * DAYSECS) ? 'upcoming' : 'active';
            $statuslabel = $statuskey === 'upcoming' ? 'Upcoming' : 'Not started';
            $bannercolor = $statuskey === 'upcoming' ? '#828282' : '#0777AD';
            $badgecss    = $statuskey === 'upcoming' ? 'b-upcoming' : 'b-active';
            $iconbg      = $statuskey === 'upcoming'
                ? 'var(--bs-secondary-bg,#f0f0f0)' : '#E6F1FB';
            $iconfg      = $statuskey === 'upcoming'
                ? 'var(--bs-secondary-color,#6c757d)' : '#0C447C';
        }

        // --- Next due activity ---
        $nextdue     = $this->get_next_due($course->id, $userid, $DB);
        $dueurgency  = '';
        $duedatestr  = '';
        $duetitle    = '';
        $duelocked   = $islocked || $statuskey === 'done';

        if ($statuskey === 'done') {
            $duetitle   = $this->get_award_label($graderaw);
            $duedatestr = 'Certified';
            $dueurgency = 'green';
        } elseif ($islocked) {
            $duetitle   = 'Unlocks ' . ($course->startdate ? userdate($course->startdate, '%d %b') : 'soon');
            $duedatestr = '';
            $dueurgency = 'grey';
        } elseif ($nextdue) {
            $daysuntil  = (int) ceil(($nextdue['duedate'] - $now) / DAYSECS);
            $dueurgency = $daysuntil <= 2 ? 'red' : ($daysuntil <= 5 ? 'amber' : 'blue');
            $duetitle   = $nextdue['name'];
            $duedatestr = $daysuntil <= 0 ? 'Overdue' : $daysuntil . ' day' . ($daysuntil === 1 ? '' : 's');
        }

        // Action button
        $actionlabel = 'Continue';
        $actioncss   = 'action-primary';
        $actionicon  = 'ti-arrow-right';
        $actionurl   = (new moodle_url('/course/view.php', ['id' => $course->id]))->out();

        if ($statuskey === 'done') {
            $actionlabel = 'Review';
            $actioncss   = 'action-outline';
            $actionicon  = 'ti-eye';
        } elseif ($statuskey === 'locked') {
            $actionlabel = 'Locked';
            $actioncss   = 'action-locked';
            $actionicon  = 'ti-lock';
            $actionurl   = '';
        } elseif ($progress === 0) {
            $actionlabel = 'Start';
            $actioncss   = 'action-primary';
            $actionicon  = 'ti-player-play';
        }

        return [
            'id'          => $course->id,
            'fullname'    => shorten_text(format_string($course->fullname), 52),
            'shortname'   => format_string($course->shortname),
            'courseurl'   => $actionurl,
            'statuskey'   => $statuskey,
            'statuslabel' => $statuslabel,
            'bannercolor' => $bannercolor,
            'badgecss'    => $badgecss,
            'iconbg'      => $iconbg,
            'iconfg'      => $iconfg,
            'progress'    => $progress,
            'donecnt'     => $donecnt,
            'totalcnt'    => $totalcnt,
            'graderaw'    => $graderaw,
            'gradestr'    => $gradestr,
            'gradecsskey' => $gradecsskey,
            'timespent'   => $this->format_time($timespent),
            'duetitle'    => shorten_text($duetitle, 48),
            'duedatestr'  => $duedatestr,
            'dueurgency'  => $dueurgency,
            'duelocked'   => $duelocked,
            'actionlabel' => $actionlabel,
            'actioncss'   => $actioncss,
            'actionicon'  => $actionicon,
            'actionurl'   => $actionurl,
            'islocked'    => $islocked,
            'isdone'      => $statuskey === 'done',
        ];
    }

    // ----------------------------------------------------------------
    // Helpers
    // ----------------------------------------------------------------

    private function get_next_due(int $courseid, int $userid, $DB): ?array {
        $now = time();
        $sql = "SELECT a.name, a.duedate
                FROM {assign} a
                JOIN {course_modules} cm ON cm.instance = a.id
                JOIN {modules} m ON m.id = cm.module AND m.name = 'assign'
                WHERE a.course = :cid
                AND a.duedate > :now
                ORDER BY a.duedate ASC
                LIMIT 1";
        $row = $DB->get_record_sql($sql, ['cid' => $courseid, 'now' => $now]);
        return $row ? ['name' => format_string($row->name), 'duedate' => (int)$row->duedate] : null;
    }

    private function get_award_label(int $grade): string {
        if ($grade >= 85) return 'Distinction awarded';
        if ($grade >= 70) return 'Merit awarded';
        if ($grade >= 50) return 'Pass awarded';
        return 'Completed';
    }

    private function format_time(int $seconds): string {
        if ($seconds <= 0) return '–';
        $h = (int) floor($seconds / 3600);
        $m = (int) floor(($seconds % 3600) / 60);
        if ($h > 0) return $h . 'h ' . str_pad($m, 2, '0') . 'm';
        return $m . 'm';
    }

    private function grade_letter(int $pct): string {
        if ($pct >= 85) return 'A';
        if ($pct >= 75) return 'B+';
        if ($pct >= 65) return 'B';
        if ($pct >= 50) return 'C';
        if ($pct > 0)   return 'D';
        return '–';
    }

    private function get_cohort(int $userid): string {
        global $DB;
        $sql = 'SELECT c.name FROM {cohort} c
                JOIN {cohort_members} cm ON cm.cohortid = c.id
                WHERE cm.userid = :uid ORDER BY c.id DESC LIMIT 1';
        $r = $DB->get_record_sql($sql, ['uid' => $userid]);
        return $r ? format_string($r->name) : 'DSE Cohort 2';
    }

    private function get_province(int $userid): string {
        global $DB;
        $sql = 'SELECT uid.data FROM {user_info_data} uid
                JOIN {user_info_field} uif ON uif.id = uid.fieldid
                WHERE uid.userid = :uid AND uif.shortname = :f LIMIT 1';
        $r = $DB->get_record_sql($sql, ['uid' => $userid, 'f' => 'province']);
        return $r ? format_string($r->data) : 'Rwanda';
    }
}