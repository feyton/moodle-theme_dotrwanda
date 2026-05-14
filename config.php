<?php
defined('MOODLE_INTERNAL') || die();

$THEME->name = 'dotrwanda';
$THEME->sheets = [];
$THEME->editor_sheets = [];
$THEME->parents = ['boost'];
$THEME->enable_dock = false;
$THEME->yuicssmodules = [];
$THEME->rendererfactory = 'theme_overridden_renderer_factory';
$THEME->requiredblocks = '';
$THEME->addblockposition = BLOCK_ADDBLOCK_POSITION_FLATNAV;
$THEME->extrascsscallback = 'theme_dotrwanda_get_extra_scss';
$THEME->prescsscallback   = 'theme_dotrwanda_get_pre_scss';

// ----------------------------------------------------
// Dashboard layout (shared)
// ----------------------------------------------------
$dashboardlayout = [
    'file' => 'mydashboard.php',
    'regions' => ['side-pre'],
    'defaultregion' => 'side-pre',
    'options' => ['langmenu' => true],
];

// ----------------------------------------------------
// Correct Moodle pagelayout mappings
// ----------------------------------------------------
// /my/ dashboard
$THEME->layouts['mydashboard'] = $dashboardlayout;

// /my/courses.php (IMPORTANT FIX)
$THEME->layouts['mycourses'] = $dashboardlayout;

// fallback (some Moodle setups still use this)
$THEME->layouts['my'] = $dashboardlayout;

// ----------------------------------------------------
// Login page only override
// ----------------------------------------------------
$THEME->layouts['login'] = [
    'file'    => 'login.php',
    'regions' => [],
    'options' => [
        'nonavbar' => true,
        'nocustommenu' => true,
        'nocoursefooter' => true
    ],
];