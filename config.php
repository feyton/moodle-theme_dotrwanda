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

// Only override the login layout — everything else inherits Boost's layouts
// Dashboard widgets are injected via classes/output/core_renderer.php
$THEME->layouts['login'] = [
    'file'    => 'login.php',
    'regions' => [],
    'options' => ['nonavbar' => true, 'nocustommenu' => true, 'nocoursefooter' => true],
];

