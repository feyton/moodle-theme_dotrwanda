<?php
defined('MOODLE_INTERNAL') || die();

/**
 * Injects custom SCSS into the theme.
 */
function theme_dotrwanda_get_extra_scss($theme) {
    // This looks for a file named post.scss in our scss folder
    $scssfile = __DIR__ . '/scss/post.scss';
    
    if (file_exists($scssfile)) {
        return file_get_contents($scssfile);
    }
    return '';
}