<?php
/*
+---------------------------------------------------------------+
|	e107 website system
|	survey e_menu.php - Menu Manager configuration
|
|	Defines per-instance settings for the survey menu.
|	Follows the same pattern as news/e_menu.php.
+---------------------------------------------------------------+
*/

if (!defined('e107_INIT')) { exit; }

class survey_menu
{
	function __construct()
	{
	}

	/**
	 * Configuration fields shown in Menu Manager.
	 * @param string $menu
	 * @return array
	 */
	public function config($menu = '')
	{
		$fields = array();

		// Build survey dropdown options (same pattern as news/e_menu.php).
		// String keys: menu parms come back from the DB as JSON strings, so
		// '2' (string) is the form of the value the dropdown has to match.
		$surveys = array('0' => '-none-');
		$rows = e107::getDb()->createQueryBuilder()
			->select('survey_id', 'survey_name')
			->from('survey')
			->orderBy('survey_name')
			->fetchAll();
		if(is_array($rows))
		{
			foreach($rows as $r)
			{
				$surveys[(string) $r['survey_id']] = $r['survey_name'];
			}
		}

		$fields['survey_id'] = array(
			'title'      => 'Select Survey',
			'type'       => 'dropdown',
			'writeParms' => array('optArray' => $surveys),
		);

		$fields['survey_caption'] = array(
			'title'      => 'Caption (optional)',
			'type'       => 'text',
			'multilan'   => true,
			'writeParms' => array('size' => 'medium'),
		);

		return $fields;
	}
}
