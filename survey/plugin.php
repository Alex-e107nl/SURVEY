<?php
/*
+---------------------------------------------------------------+
|	e107 website system
|	by McFly (mcfly@rocketmail.com)
|
|	Released under the terms and conditions of the
|	GNU General Public License (http://gnu.org).
+---------------------------------------------------------------+
*/

// Plugin info -------------------------------------------------------------------------------------------------------
$eplug_name = "Survey";
$eplug_version = "1.0.0";
$eplug_author = "McFly (maintained by septor)";
$eplug_logo = "/images/survey_icon.png";
$eplug_url = "http://painswitch.com/";
$eplug_email = "patrickweaver@gmail.com";
$eplug_description = "This plugin is designed to allow the site admin to configure surveys for his / her site.";
$eplug_compatible = "e107 v2.0+ (PHP 8.0+)";
$eplug_readme = "README.mkd";

// Name of the plugin's folder -------------------------------------------------------------------------------------
$eplug_folder = "survey";

// Name of menu item for plugin ----------------------------------------------------------------------------------
$eplug_menu_name = "";

// Name of the admin configuration file --------------------------------------------------------------------------
$eplug_conffile = "admin_config.php";

// Icon image and caption text ------------------------------------------------------------------------------------
$eplug_icon = $eplug_folder."/images/survey_icon.png";
$eplug_icon_small = $eplug_folder."/images/survey_icon_16.png";

// List of preferences -----------------------------------------------------------------------------------------------
$eplug_prefs = array(
);

// List of table names -----------------------------------------------------------------------------------------------
$eplug_table_names = array(
"survey",
"survey_results"
);

// List of sql requests to create tables (InnoDB + utf8mb4 for e107 2.4+) -------------------------------------------
$eplug_tables = array(
"CREATE TABLE IF NOT EXISTS ".MPREFIX."survey (
    survey_id int(10) unsigned NOT NULL auto_increment,
    survey_name varchar(128) NOT NULL default '',
    survey_class tinyint(3) unsigned NOT NULL default '0',
    survey_once tinyint(1) unsigned NOT NULL default '0',
    survey_viewclass tinyint(3) unsigned NOT NULL default '0',
    survey_editclass tinyint(3) unsigned NOT NULL default '0',
    survey_mailto varchar(255) NOT NULL default '',
    survey_forum int(10) unsigned NOT NULL default '0',
    survey_save_results tinyint(1) unsigned NOT NULL default '0',
    survey_captcha tinyint(1) unsigned NOT NULL default '0',
    survey_gdpr tinyint(1) unsigned NOT NULL default '0',
    survey_user text NOT NULL,
    survey_parms text NOT NULL,
    survey_message text NOT NULL,
    survey_submit_message text NOT NULL,
    survey_lastfnum int(10) unsigned NOT NULL default '0',
    PRIMARY KEY (survey_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
"CREATE TABLE IF NOT EXISTS ".MPREFIX."survey_results (
    results_id int(10) unsigned NOT NULL auto_increment,
    results_datestamp int(10) unsigned NOT NULL default '0',
    results_survey_id int(10) unsigned NOT NULL default '0',
    results_results text,
    PRIMARY KEY (results_id),
    KEY idx_survey (results_survey_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;"
);


// Create a link in main menu (yes=TRUE, no=FALSE) -------------------------------------------------------------
$eplug_link = FALSE;
$eplug_link_name = "";
$eplug_link_url = "";


// Text to display after plugin successfully installed ------------------------------------------------------------------
$eplug_done = "The survey plugin is now installed.  You can now go to the survey configuration to create your survey(s).";

// upgrading ... //

$upgrade_add_prefs = "";

$upgrade_remove_prefs = "";

if(strpos(e_QUERY, 'upgrade') === 0)
{
	$ssql = new db;
	if($ssql -> db_Select("plugin","*","plugin_name='Survey'"))
	{
		$srow = $ssql -> db_Fetch();
		if(is_array($srow))
		{
			if($srow['plugin_version'] == "0.3")
			{
				$upgrade_alter_tables = array("ALTER TABLE ".MPREFIX."survey ADD survey_editclass TINYINT( 3 ) UNSIGNED NOT NULL AFTER survey_viewclass ;");
			}
			// v1.0.0: add captcha + gdpr columns
			$ssql->db_Query("SHOW COLUMNS FROM ".MPREFIX."survey");
			$_ucols = array();
			while($_uc = $ssql->db_Fetch()){ $_ucols[] = $_uc['Field']; }
			if(!in_array('survey_captcha', $_ucols)){
				$upgrade_alter_tables[] = "ALTER TABLE ".MPREFIX."survey ADD COLUMN survey_captcha tinyint(1) unsigned NOT NULL default '0' AFTER survey_save_results";
			}
			if(!in_array('survey_gdpr', $_ucols)){
				$upgrade_alter_tables[] = "ALTER TABLE ".MPREFIX."survey ADD COLUMN survey_gdpr tinyint(1) unsigned NOT NULL default '0' AFTER survey_captcha";
			}
		}
	}
}
$eplug_upgrade_done = "Your survey plugin has been successfully upgraded, you are now running {$eplug_version}";
