<?php
namespace theme_dotrwanda\output;

defined('MOODLE_INTERNAL') || die();

use context_system;
use html_writer;

/**
 * DOT Rwanda core renderer.
 *
 * - /my/            -> role-aware dashboard widgets
 * - /my/courses.php -> custom course grid
 */
class core_renderer extends \theme_boost\output\core_renderer {

    /**
     * Determine whether current user is staff.
     */
    protected function is_staff_user(): bool {
        global $USER, $DB;

        // Site admins.
        if (is_siteadmin($USER->id)) {
            return true;
        }

        // System capability shortcut.
        if (has_capability(
            'moodle/site:viewreports',
            context_system::instance(),
            $USER
        )) {
            return true;
        }

        // Check role assignments.
        $roleids = [];

        foreach (['manager', 'editingteacher', 'teacher'] as $shortname) {

            $role = $DB->get_record(
                'role',
                ['shortname' => $shortname],
                'id'
            );

            if ($role) {
                $roleids[] = $role->id;
            }
        }

        if (empty($roleids)) {
            return false;
        }

        list($insql, $params) = $DB->get_in_or_equal(
            $roleids,
            SQL_PARAMS_NAMED
        );

        $params['userid'] = $USER->id;

        return $DB->record_exists_sql(
            "SELECT 1
               FROM {role_assignments}
              WHERE userid = :userid
                AND roleid $insql",
            $params
        );
    }

    /**
     * Override main content rendering.
     */
    public function main_content(): string {

        $pagetype = $this->page->pagetype;

        // Optional debugging.
        error_log('DOT PAGETYPE: ' . $pagetype);

        // -------------------------------------------------
        // Dashboard (/my/)
        // -------------------------------------------------

        if (
            strpos($pagetype, 'my-index') === 0 ||
            $pagetype === 'my'
        ) {

            return $this->render_dashboard()
                 . parent::main_content();
        }

        // -------------------------------------------------
        // My Courses (/my/courses.php)
        // -------------------------------------------------

        if ($pagetype === 'my-courses') {
            return $this->render_my_courses() . parent::main_content();
        }

        // Default Moodle rendering.
        return parent::main_content();
    }

    /**
     * Render dashboard widgets.
     */
    protected function render_dashboard(): string {

        try {

            $isstaff = $this->is_staff_user();

            if ($isstaff) {
                $renderable = new dashboard_staff();
                $template   = 'theme_dotrwanda/dashboard_staff';
            } else {
                $renderable = new dashboard_learner();
                $template   = 'theme_dotrwanda/dashboard_learner';
            }

            return $this->render_from_template(
                $template,
                $renderable->export_for_template($this)
            );

        } catch (\Throwable $e) {

            return $this->dot_error($e)
                 . parent::main_content();
        }
    }

    /**
     * Render custom My Courses page.
     */
    protected function render_my_courses(): string {

        try {

            $renderable = new my_courses();

            return $this->render_from_template(
                'theme_dotrwanda/my_courses',
                $renderable->export_for_template($this)
            );

        } catch (\Throwable $e) {

            return $this->dot_error($e)
                 . parent::main_content();
        }
    }

    /**
     * Safe developer-only error rendering.
     */
    protected function dot_error(\Throwable $e): string {

        if (debugging('', DEBUG_DEVELOPER)) {

            return html_writer::div(
                'DOT theme error: ' . s($e->getMessage()),
                'alert alert-danger m-3'
            );
        }

        return '';
    }
}