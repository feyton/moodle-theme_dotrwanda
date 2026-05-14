<?php
namespace theme_dotrwanda\output;

defined('MOODLE_INTERNAL') || die();

use moodle_url;
use context_system;

/**
 * DOT Rwanda core renderer.
 *
 * Overrides main_content() on the dashboard page to prepend our
 * custom widgets. Boost handles all layout, chrome, and CSS compilation
 * — we only touch the content region.
 */
class core_renderer extends \theme_boost\output\core_renderer {

    /**
     * Renders the main content region.
     * On the dashboard (index) we prepend DOT widgets above
     * Moodle's own block/content output.
     */
    public function main_content(): string {
        $isdashboard = $this->page->pagetype === 'my';

        if (!$isdashboard) {
            return parent::main_content();
        }

        $isstaff = has_capability(
            'moodle/course:viewhiddencourses',
            context_system::instance()
        );

        if ($isstaff) {
            $renderable = new dashboard_staff();
        } else {
            $renderable = new dashboard_learner();
        }

        $widgets = $this->render_from_template(
            $isstaff ? 'theme_dotrwanda/dashboard_staff' : 'theme_dotrwanda/dashboard_learner',
            $renderable->export_for_template($this)
        );

        return $widgets . parent::main_content();
    }
}
