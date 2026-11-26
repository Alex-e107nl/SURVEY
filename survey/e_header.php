<?php
if (!defined('e107_INIT')) { exit(); }

e107::js('survey', 'survey_cal.js', 'jquery');
e107::js('survey', 'survey.js', 'jquery');
e107::css('survey', 'survey_cal.css');

// Front-end pages (survey.php, view.php, stats.php) use Bootstrap 5 markup.
// When the site theme is the standard bootstrap3 theme, load the
// compatibility layer so the plugin looks native there too.
// NOTE: e_THEME is the themes DIRECTORY, not the folder; the folder name
// lives in e107->site_theme (THEME path constant as fallback). Kept inline
// (no survey.inc.php) so this file stays side-effect-free, since the core
// includes it site-wide via the e_header_list core preference.
$_survey_theme_folder = '';
$_survey_e107 = e107::getInstance();
if (!empty($_survey_e107->site_theme)) {
	$_survey_theme_folder = $_survey_e107->site_theme;
} elseif (defined('THEME')) {
	$_survey_theme_folder = basename(str_replace('\\', '/', rtrim(THEME, '/')));
}
if ($_survey_theme_folder === 'bootstrap3') {
	e107::css('survey', 'survey_bs5_compat.css');
}
