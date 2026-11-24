#
# Table structure for table `survey`
#
CREATE TABLE survey (
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
  PRIMARY KEY  (survey_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
# --------------------------------------------------------
#
# Table structure for table `survey_results`
#
CREATE TABLE survey_results (
  results_id int(10) unsigned NOT NULL auto_increment,
  results_datestamp int(10) unsigned NOT NULL default '0',
  results_survey_id int(10) unsigned NOT NULL default '0',
  results_results text,
  PRIMARY KEY  (results_id),
  KEY idx_survey (results_survey_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
# --------------------------------------------------------
