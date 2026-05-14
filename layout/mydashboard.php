<?php
defined('MOODLE_INTERNAL') || die();

/**
 * DOT Rwanda — mydashboard layout.
 *
 * Delegates entirely to Boost's columns2.php so all chrome,
 * CSS compilation, and JS remain intact. Our core_renderer.php
 * intercepts main_content() to prepend dashboard widgets.
 */

require($CFG->dirroot . '/theme/boost/layout/columns2.php');