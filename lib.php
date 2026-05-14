<?php
defined('MOODLE_INTERNAL') || die();

/**
 * Injects pre-SCSS variables (runs before Boost's own SCSS).
 */
function theme_dotrwanda_get_pre_scss($theme) {
    $pre = '';
    $pre .= '$primary:            #0777AD;' . "\n";
    $pre .= '$secondary:          #828282;' . "\n";
    $pre .= '$dot-blue:           #0777AD;' . "\n";
    $pre .= '$dot-blue-dark:      #055a82;' . "\n";
    $pre .= '$dot-blue-light:     #e6f4fb;' . "\n";
    $pre .= '$dot-grey:           #828282;' . "\n";
    $pre .= '$dot-grey-light:     #f4f5f7;' . "\n";
    $pre .= '$dot-white:          #ffffff;' . "\n";
    $pre .= '$dot-dark:           #0f1117;' . "\n";
    $pre .= '$dot-surface:        #161b22;' . "\n";
    $pre .= '$dot-surface-2:      #1c2330;' . "\n";
    $pre .= '$dot-border:         #2a3441;' . "\n";
    $pre .= '$dot-text-muted:     #8b949e;' . "\n";
    $pre .= '$font-family-sans-serif: "Poppins", "Segoe UI", system-ui, -apple-system, sans-serif;' . "\n";
    return $pre;
}

/**
 * Injects post-SCSS (appended after Boost compiles).
 */
function theme_dotrwanda_get_extra_scss($theme) {
    $scssfile = __DIR__ . '/scss/post.scss';
    if (file_exists($scssfile)) {
        return file_get_contents($scssfile);
    }
    return '';
}

/**
 * Returns the Google OAuth login URL configured in plugin settings.
 * Falls back gracefully if auth_googleoauth2 is not installed.
 */
function theme_dotrwanda_get_google_login_url() {
    if (!is_enabled_auth('googleoauth2')) {
        return null;
    }
    return new moodle_url('/auth/googleoauth2/redirect.php');
}
