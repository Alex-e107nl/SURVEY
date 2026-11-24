<?php
/*
+---------------------------------------------------------------+
|	e107 website system
|	survey admin_config.php - Modern Bootstrap 5.3 Builder
|
|	Released under the terms and conditions of the
|	GNU General Public License (http://gnu.org).
+---------------------------------------------------------------+
*/
require_once(__DIR__.'/../../class2.php');

if(!getperms("P"))
{
	e107::redirect('admin');
	exit;
}

require_once(e_ADMIN."auth.php");
require_once(e_HANDLER."form_handler.php");
require_once(e_HANDLER."userclass_class.php");
require_once(__DIR__.'/survey.inc.php');

$e_sub_cat = 'survey';
e107::includeLan(e_PLUGIN.'survey/languages/'.e_LANGUAGE.'/'.e_LANGUAGE.'_admin.php');

// ---- Admin left sidebar menu ----
// Auto-detected by the {ADMIN_MENU} shortcode in the admin template.
// The function name must match: basename of SCRIPT_FILENAME without .php + '_adminmenu'.
function admin_config_adminmenu()
{
	$var = array();
	$var['main']['text'] = 'Survey List';
	$var['main']['link'] = e_SELF;

	$icon = '<img src="'.e_PLUGIN.'survey/images/survey_icon.png" alt="Survey" class="icon S32" />';
	$caption = '<span>Survey</span>';
	$var['_extras_']['icon'] = $icon;

	e107::getNav()->admin($caption, 'main', $var);
}

// ---- Auto-migration: add new columns if missing ----
$_migration_sql = new db;
$_migration_sql->db_Query("SHOW COLUMNS FROM ".MPREFIX."survey");
$_colNames = array();
while($_c = $_migration_sql->db_Fetch()){
	$_colNames[] = $_c['Field'];
}
if(!in_array('survey_captcha', $_colNames)){
	$_migration_sql->db_Query("ALTER TABLE ".MPREFIX."survey ADD COLUMN survey_captcha tinyint(1) unsigned NOT NULL default '0' AFTER survey_save_results");
}
if(!in_array('survey_gdpr', $_colNames)){
	$_migration_sql->db_Query("ALTER TABLE ".MPREFIX."survey ADD COLUMN survey_gdpr tinyint(1) unsigned NOT NULL default '0' AFTER survey_captcha");
}

$mes = e107::getMessage();

// CSRF token check
$tokenOk = !e_session::modeUsesToken() || !empty($_POST['e-token']);

// Build field type lookup
$fieldtypes = array();
for($i = 1; $i <= 23; $i++){
	$x = "ADLAN_SURTYPE_".$i;
	if(defined($x)){
		$fieldtypes[$i] = constant($x);
	}
}

// ---- Helper: field type icons (inline SVG, single color via currentColor) ----
function survey_svg($inner, $size = 16)
{
	return '<svg xmlns="http://www.w3.org/2000/svg" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">'.$inner.'</svg>';
}

function survey_svg_grip()
{
	$dots = '';
	foreach(array(6, 12, 18) as $cy)
	{
		$dots .= '<circle cx="9" cy="'.$cy.'" r="1.4" fill="currentColor" stroke="none"/>'
			.'<circle cx="15" cy="'.$cy.'" r="1.4" fill="currentColor" stroke="none"/>';
	}
	return survey_svg($dots, 14);
}

function survey_svg_x()
{
	return survey_svg('<line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>', 12);
}

function survey_type_icon($type, $size = 16)
{
	$icons = array(
		// 1 Text field
		1  => '<polyline points="4 7 4 4 20 4 20 7"/><line x1="9" y1="20" x2="15" y2="20"/><line x1="12" y1="4" x2="12" y2="20"/>',
		// 2 Text area
		2  => '<line x1="21" y1="6" x2="3" y2="6"/><line x1="17" y1="10" x2="3" y2="10"/><line x1="21" y1="14" x2="3" y2="14"/><line x1="17" y1="18" x2="3" y2="18"/>',
		// 3 Checkboxes
		3  => '<polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>',
		// 4 Radio buttons
		4  => '<circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="3.5" fill="currentColor" stroke="none"/>',
		// 5 Dropdown
		5  => '<rect x="3" y="5" width="18" height="14" rx="2"/><polyline points="9 10.5 12 13.5 15 10.5"/>',
		// 6 Separator
		6  => '<line x1="4" y1="12" x2="20" y2="12"/>',
		// 7 Date picker
		7  => '<rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>',
		// 8 Name
		8  => '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
		// 9 Calculation
		9  => '<rect x="4" y="2" width="16" height="20" rx="2"/><line x1="8" y1="6" x2="16" y2="6"/><line x1="8" y1="10" x2="8" y2="10.01"/><line x1="12" y1="10" x2="12" y2="10.01"/><line x1="16" y1="10" x2="16" y2="10.01"/><line x1="8" y1="14" x2="8" y2="14.01"/><line x1="12" y1="14" x2="12" y2="14.01"/><line x1="16" y1="14" x2="16" y2="14.01"/><line x1="8" y1="18" x2="12" y2="18"/>',
		// 10 Email
		10 => '<rect x="2" y="4" width="20" height="16" rx="2"/><polyline points="22,6 12,13 2,6"/>',
		// 11 Number
		11 => '<line x1="4" y1="9" x2="20" y2="9"/><line x1="4" y1="15" x2="20" y2="15"/><line x1="10" y1="3" x2="8" y2="21"/><line x1="16" y1="3" x2="14" y2="21"/>',
		// 12 Email-to
		12 => '<line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/>',
		// 13 Row break
		13 => '<path d="M4 4v7a4 4 0 0 0 4 4h12"/><polyline points="15 10 20 15 15 20"/>',
		// 14 Signature
		14 => '<path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/>',
		// 15 File upload
		15 => '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/>',
		// 16 Phone
		16 => '<path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>',
		// 17 URL
		17 => '<path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/>',
		// 18 Color picker
		18 => '<path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"/>',
		// 19 Range slider
		19 => '<line x1="4" y1="21" x2="4" y2="14"/><line x1="4" y1="10" x2="4" y2="3"/><line x1="12" y1="21" x2="12" y2="12"/><line x1="12" y1="8" x2="12" y2="3"/><line x1="20" y1="21" x2="20" y2="16"/><line x1="20" y1="12" x2="20" y2="3"/><line x1="1" y1="14" x2="7" y2="14"/><line x1="9" y1="8" x2="15" y2="8"/><line x1="17" y1="16" x2="23" y2="16"/>',
		// 20 Time picker
		20 => '<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>',
		// 21 Country
		21 => '<circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>',
		// 22 HTML block
		22 => '<polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/>',
		// 23 Password
		23 => '<rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>'
	);
	$inner = isset($icons[$type])
		? $icons[$type]
		: '<path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><rect x="8" y="2" width="8" height="4" rx="1"/>';
	return survey_svg($inner, $size);
}

function survey_erase($survey_num) {
	global $sql;
	$survey_num = intval($survey_num);
	if($survey_num > 0) {
		$sql->db_Delete("survey_results", "results_survey_id={$survey_num}");
	}
}

// ---- Delete survey ----
if(!empty($_POST['delete']) && $tokenOk){
	$sid = intval($_POST['existing'] ?? 0);
	if(!empty($_POST['tick']) && $sid > 0){
		$sql->db_Delete("survey", "survey_id={$sid}");
		$sql->db_Delete("survey_results", "results_survey_id={$sid}");
		$mes->addSuccess(ADLAN_SUR2);
	} else {
		$mes->addError(ADLAN_SUR3);
	}
}

// ---- Create copy ----
if(!empty($_POST['createcopy']) && $tokenOk){
	$sid = intval($_POST['existing'] ?? 0);
	if($sid > 0 && $sql->db_Select("survey", "*", "survey_id={$sid}")){
		$row = $sql->db_Fetch();
		if(is_array($row)){
			$copyname = $tp->toDB("Copy of: " . $row['survey_name']);
			$sql->db_Insert("survey", array(
				'survey_name'         => $copyname,
				'survey_class'        => intval($row['survey_class']),
				'survey_once'         => intval($row['survey_once']),
				'survey_viewclass'    => intval($row['survey_viewclass']),
				'survey_editclass'    => intval($row['survey_editclass']),
				'survey_mailto'       => $row['survey_mailto'],
				'survey_forum'        => intval($row['survey_forum']),
				'survey_save_results' => intval($row['survey_save_results']),
				'survey_captcha'      => intval($row['survey_captcha'] ?? 0),
				'survey_gdpr'         => intval($row['survey_gdpr'] ?? 0),
				'survey_user'         => '',
				'survey_parms'        => $row['survey_parms'],
				'survey_message'      => $row['survey_message'],
				'survey_submit_message' => $row['survey_submit_message'],
				'survey_lastfnum'     => intval($row['survey_lastfnum'])
			));
			$mes->addSuccess(ADLAN_SUR72." [{$copyname}]");
		}
	}
}

// ---- Export survey as JSON ----
if(!empty($_POST['export']) && $tokenOk){
	$sid = intval($_POST['existing'] ?? 0);
	if($sid > 0 && $sql->db_Select("survey", "*", "survey_id={$sid}")){
		$row = $sql->db_Fetch();
		if(is_array($row)){
			$fields = survey_load_fields($row['survey_parms'] ?? '');
			if(!is_array($fields)) $fields = array();
			$export = array(
				'version'  => '1.0',
				'name'     => $row['survey_name'],
				'settings' => array(
					'class'          => intval($row['survey_class']),
					'once'           => intval($row['survey_once']),
					'viewclass'      => intval($row['survey_viewclass']),
					'editclass'      => intval($row['survey_editclass']),
					'mailto'         => $row['survey_mailto'],
					'forum'          => intval($row['survey_forum']),
					'save_results'   => intval($row['survey_save_results']),
					'captcha'        => intval($row['survey_captcha'] ?? 0),
					'gdpr'           => intval($row['survey_gdpr'] ?? 0),
					'message'        => $row['survey_message'] ?? '',
					'submit_message' => $row['survey_submit_message'] ?? '',
				),
				'fields'   => array_values($fields),
			);
			$json = json_encode($export, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
			$fname = 'survey_' . intval($sid) . '_' . preg_replace('/[^a-z0-9]/i', '_', $row['survey_name']) . '.json';
			header('Content-Type: application/json; charset=utf-8');
			header('Content-Disposition: attachment; filename="' . $fname . '"');
			echo $json;
			exit;
		}
	}
}

// ---- Import survey from JSON ----
if(!empty($_POST['import']) && $tokenOk && !empty($_FILES['import_file']['tmp_name'])){
	$raw  = file_get_contents($_FILES['import_file']['tmp_name']);
	$data = json_decode((string)$raw, true);
	if(!is_array($data) || empty($data['name'])){
		$mes->addError(ADLAN_SUR211);
	} else {
		$s = $data['settings'] ?? array();
		$f = $data['fields']   ?? array();
		$insResult = $sql->db_Insert("survey", array(
			'survey_name'           => $tp->toDB($data['name']),
			'survey_class'          => intval($s['class'] ?? 0),
			'survey_once'           => intval($s['once'] ?? 0),
			'survey_viewclass'      => intval($s['viewclass'] ?? 0),
			'survey_editclass'      => intval($s['editclass'] ?? 0),
			'survey_mailto'         => $tp->toDB($s['mailto'] ?? ''),
			'survey_forum'          => intval($s['forum'] ?? 0),
			'survey_save_results'   => intval($s['save_results'] ?? 0),
			'survey_captcha'        => intval($s['captcha'] ?? 0),
			'survey_gdpr'           => intval($s['gdpr'] ?? 0),
			'survey_user'           => '',
			'survey_parms'          => json_encode(array_values($f), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
			'survey_message'        => $tp->toDB($s['message'] ?? ''),
			'survey_submit_message' => $tp->toDB($s['submit_message'] ?? ''),
			'survey_lastfnum'       => count($f),
		));
		if($insResult !== false){
			$mes->addSuccess(ADLAN_SUR210 . ' [' . $data['name'] . ']');
		} else {
			$mes->addError('Import failed: ' . $sql->getLastErrorText());
		}
	}
}

// ---- Read survey data from POST ----
// Both the create flow (add=1) and the update flow (update=1) submit these fields.
// (Before 1.0.1 this only ran for 'update', so creating a NEW survey always failed
//  with ADLAN_SUR4 "Velden leeg gelaten" because $survey_name was never read.)
if(!empty($_POST['update']) || !empty($_POST['add'])){
	$survey_name        = $tp->toDB($_POST['survey_name'] ?? '');
	$survey_class       = intval($_POST['survey_class'] ?? 0);
	$survey_once        = intval($_POST['survey_once'] ?? 0);
	$survey_viewclass   = intval($_POST['survey_viewclass'] ?? 0);
	$survey_editclass   = intval($_POST['survey_editclass'] ?? 0);
	$survey_mailto      = $tp->toDB($_POST['survey_mailto'] ?? '');
	$survey_forum       = intval($_POST['survey_forum'] ?? 0);
	$survey_save_results= intval($_POST['survey_save_results'] ?? 0);
	$survey_captcha     = intval($_POST['survey_captcha'] ?? 0);
	$survey_gdpr        = intval($_POST['survey_gdpr'] ?? 0);
	$survey_message     = $tp->toDB($_POST['survey_message'] ?? '');
	$survey_submit_message = $tp->toDB($_POST['survey_submit_message'] ?? '');
	if($survey_once && $survey_class != e_UC_PUBLIC){
		$survey_save_results = 1;
	}
}

// ---- Add new survey ----
if(!empty($_POST['add']) && $tokenOk){
	if(!$survey_name){
		$mes->addError(ADLAN_SUR4);
		$_POST['create'] = 1;
	} else {
		$insResult = $sql->db_Insert("survey", array(
			'survey_name'         => $survey_name,
			'survey_class'        => $survey_class,
			'survey_once'         => $survey_once,
			'survey_viewclass'    => $survey_viewclass,
			'survey_editclass'    => $survey_editclass,
			'survey_mailto'       => $survey_mailto,
			'survey_forum'        => $survey_forum,
			'survey_save_results' => $survey_save_results,
			'survey_captcha'      => $survey_captcha,
			'survey_gdpr'         => $survey_gdpr,
			'survey_user'         => '',
			'survey_parms'        => '',
			'survey_message'      => $survey_message,
			'survey_submit_message' => $survey_submit_message,
			'survey_lastfnum'     => 0
		));
		if($insResult === false){
			$mes->addError("Insert failed: " . $sql->getLastErrorText());
			$_POST['create'] = 1;
		} else {
			$_POST['existing'] = $insResult;
			$_POST['edit'] = 1;
		}
	}
}

// ---- Update survey / fields ----
if($tokenOk && !empty($_POST['update'])){
	$sid = intval($_POST['existing'] ?? 0);
	if($sid <= 0){
		$mes->addError("No survey selected for update.");
	} else {
		// Build fields array from POST
		$fields = array();
		$fi = 0;
		if(is_array($_POST['field_type'] ?? null)){
			// Get order from the hidden field-order input
			$orderStr = $_POST['field_order'] ?? '';
			$orderArr = array_map('intval', explode(',', $orderStr));
			
			// Collect all field data by index
			$allFields = array();
			foreach($_POST['field_type'] as $key => $val){
				$allFields[$key] = array(
					'field_number'  => intval($_POST['field_number'][$key] ?? 0),
					'field_text'    => $_POST['field_text'][$key] ?? '',
					'field_req'     => intval($_POST['field_req'][$key] ?? 0),
					'field_hidden'  => intval($_POST['field_hidden'][$key] ?? 0),
					'field_type'    => intval($val),
					'field_choices' => $_POST['field_choices'][$key] ?? '',
					'field_col'     => intval($_POST['field_col'][$key] ?? 12),
				);
			}
			
			// Order by field_order if provided, otherwise by key
			if(!empty($orderArr)){
				$orderedKeys = array();
				foreach($orderArr as $ok){
					if(isset($allFields[$ok])){
						$orderedKeys[] = $ok;
					}
				}
				// Append any missing keys
				foreach(array_keys($allFields) as $fk){
					if(!in_array($fk, $orderedKeys)){
						$orderedKeys[] = $fk;
					}
				}
			} else {
				$orderedKeys = array_keys($allFields);
			}
			
			foreach($orderedKeys as $key){
				if(!empty($allFields[$key]['field_text'])){
					$allFields[$key]['field_number'] = $fi + 1;
					$fields[$fi] = $allFields[$key];
					$fi++;
				}
			}
		}

		$ser = json_encode(array_values($fields), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		$maxNum = $fi;

		// Use the query builder (bound parameters) instead of embedding the raw
		// serialized data in a SQL string — field labels containing quotes
		// (e.g. "Wat's...") broke the UPDATE before.
		$updResult = $sql->createQueryBuilder()
			->update('survey')
			->set('survey_name',           $survey_name)
			->set('survey_class',          (int) $survey_class)
			->set('survey_once',           (int) $survey_once)
			->set('survey_viewclass',      (int) $survey_viewclass)
			->set('survey_editclass',      (int) $survey_editclass)
			->set('survey_mailto',         $survey_mailto)
			->set('survey_forum',          (int) $survey_forum)
			->set('survey_save_results',   (int) $survey_save_results)
			->set('survey_captcha',        (int) $survey_captcha)
			->set('survey_gdpr',           (int) $survey_gdpr)
			->set('survey_parms',          $ser)
			->set('survey_message',        $survey_message)
			->set('survey_submit_message', $survey_submit_message)
			->set('survey_lastfnum',       (int) $maxNum)
			->where('survey_id', (int) $sid)
			->execute();
		if($updResult === false || $updResult === -1){
			$mes->addError("Update failed: " . $sql->getLastErrorText());
		} else {
			$mes->addSuccess(ADLAN_SUR33);
		}
		$_POST['edit'] = $sid;
	}
}

// Render messages
if($mes->hasMessage()){
	echo "<div class='alert alert-info text-center mb-3'>{$mes->render()}</div>";
}

// ---- Survey dropdown helper ----
function survey_existing_dropdown($name, $cur_survey){
	$sql2 = new db;
	$ret = "";
	if($sql2->db_Select("survey", "survey_id, survey_name") > 0){
		$ret .= "<select name='".htmlspecialchars($name)."' class='form-select' style='max-width:300px;'>";
		while($r = $sql2->db_Fetch()){
			$sel = ($cur_survey == $r['survey_id']) ? ' selected' : '';
			$ret .= "<option value='".intval($r['survey_id'])."'{$sel}>".htmlspecialchars($r['survey_name'])."</option>";
		}
		$ret .= "</select>";
	}
	return $ret;
}

// ---- Main config UI ----
$text = "<div class='container-fluid'>";

$isBuilderMode = !empty($_POST['create']) || !empty($_POST['edit']);

if(!$isBuilderMode){
	// ---- Survey List View ----
	$surveyList = array();
	if($sql->db_Select("survey", "survey_id, survey_name, survey_lastfnum") > 0){
		while($sr = $sql->db_Fetch()){
			$sr['resp_count'] = 0;
			$sql2 = new db;
			if($sql2->db_Select("survey_results", "COUNT(*) as cnt", "results_survey_id = ".intval($sr['survey_id']))){
				$cr = $sql2->db_Fetch();
				$sr['resp_count'] = intval($cr['cnt'] ?? 0);
			}
			$surveyList[] = $sr;
		}
	}

	$text .= "<div class='card mb-3'>";
	$text .= "<div class='card-header d-flex flex-wrap justify-content-between align-items-center gap-2'>";
	$text .= "<strong>".ADLAN_SUR200."</strong>";
	$text .= "<div class='d-flex flex-wrap gap-2'>";
	$text .= "<form method='post' action='".e_SELF."' class='d-inline'>";
	$text .= "<input type='hidden' name='e-token' value='".defset('e_TOKEN')."' />";
	$text .= "<button type='submit' name='create' value='1' class='btn btn-sm btn-success'>+ ".ADLAN_SUR7."</button>";
	$text .= "</form>";
	$text .= "<form method='post' action='".e_SELF."' enctype='multipart/form-data' class='d-inline'>";
	$text .= "<input type='hidden' name='e-token' value='".defset('e_TOKEN')."' />";
	$text .= "<input type='file' name='import_file' accept='.json,application/json' class='form-control form-control-sm' style='display:inline-block;max-width:180px;' />";
	$text .= "<button type='submit' name='import' value='1' class='btn btn-sm btn-outline-secondary'>".ADLAN_SUR213."</button>";
	$text .= "</form>";
	$text .= "</div></div>"; // card-header

	if(empty($surveyList)){
		$text .= "<div class='card-body text-center text-muted py-4'>".ADLAN_SUR208."</div>";
	} else {
		$text .= "<div class='table-responsive'>";
		$text .= "<table class='table table-hover align-middle mb-0'>";
		$text .= "<thead class='table-light'><tr>";
		$text .= "<th style='width:40px'>#</th>";
		$text .= "<th> Name </th>";
		$text .= "<th>".ADLAN_SUR201."</th>";
		$text .= "<th>".ADLAN_SUR202."</th>";
		$text .= "<th style='width:320px'>".ADLAN_SUR203."</th>";
		$text .= "</tr></thead><tbody>";
		foreach($surveyList as $sv){
			$sid = intval($sv['survey_id']);
			$tok = defset('e_TOKEN');
			$text .= "<tr>";
			$text .= "<td class='text-muted'>{$sid}</td>";
			$text .= "<td><strong>".htmlspecialchars($sv['survey_name'])."</strong></td>";
			$text .= "<td>{$sv['resp_count']}</td>";
			$text .= "<td>".intval($sv['survey_lastfnum'])."</td>";
			$text .= "<td class='text-nowrap'>";
			// Edit
			$text .= "<form method='post' action='".e_SELF."' class='d-inline'>";
			$text .= "<input type='hidden' name='e-token' value='{$tok}' />";
			$text .= "<input type='hidden' name='existing' value='{$sid}' />";
			$text .= "<button type='submit' name='edit' value='1' class='btn btn-sm btn-primary me-1'>".ADLAN_SUR6."</button>";
			$text .= "</form>";
			// Preview
			$text .= "<a href='".e_PLUGIN."survey/survey.php?{$sid}' target='_blank' class='btn btn-sm btn-outline-secondary me-1'>".ADLAN_SUR204."</a>";
			// Results
			$text .= "<a href='".e_PLUGIN."survey/view.php?{$sid}' target='_blank' class='btn btn-sm btn-outline-success me-1'>".ADLAN_SUR216."</a>";

			// Stats
			$text .= "<a href='".e_PLUGIN."survey/stats.php?{$sid}' target='_blank' class='btn btn-sm btn-outline-info me-1'>".ADLAN_SUR205."</a>";
			// Export
			$text .= "<form method='post' action='".e_SELF."' class='d-inline'>";
			$text .= "<input type='hidden' name='e-token' value='{$tok}' />";
			$text .= "<input type='hidden' name='existing' value='{$sid}' />";
			$text .= "<button type='submit' name='export' value='1' class='btn btn-sm btn-outline-primary me-1'>".ADLAN_SUR206."</button>";
			$text .= "</form>";
			// Copy
			$text .= "<form method='post' action='".e_SELF."' class='d-inline'>";
			$text .= "<input type='hidden' name='e-token' value='{$tok}' />";
			$text .= "<input type='hidden' name='existing' value='{$sid}' />";
			$text .= "<button type='submit' name='createcopy' value='1' class='btn btn-sm btn-outline-secondary me-1'>Copy</button>";
			$text .= "</form>";
			// Delete
			$text .= "<form method='post' action='".e_SELF."' class='d-inline' onsubmit=\"return confirm('".ADLAN_SUR29."');\">";
			$text .= "<input type='hidden' name='e-token' value='{$tok}' />";
			$text .= "<input type='hidden' name='existing' value='{$sid}' />";
			$text .= "<input type='hidden' name='tick' value='1' />";
			$text .= "<button type='submit' name='delete' value='1' class='btn btn-sm btn-outline-danger d-inline-flex align-items-center gap-1'>".survey_svg_x()." ".ADLAN_SUR8."</button>";
			$text .= "</form>";
			$text .= "</td></tr>";
		}
		$text .= "</tbody></table></div>";
	}
	$text .= "</div>"; // card
} else {
	// Builder mode: show a back link
	$text .= "<div class='mb-2'>";
	$text .= "<a href='".e_SELF."' class='btn btn-sm btn-outline-secondary'>&larr; ".ADLAN_SUR212."</a>";
	$text .= "</div>";
}

// ---- Edit / Create form ----
if(!empty($_POST['create']) || !empty($_POST['edit'])){
	// Load forum list
	$forumList = array();
	if(e107::isInstalled('forum')){
		if($sql->db_Select("forum","forum_id, forum_name","forum_parent != 0")){
			while($row = $sql->db_Fetch()){
				$forumList[$row['forum_id']] = $row['forum_name'];
			}
		}
	}

	if(empty($_POST['add'])){
		// Defaults for the create flow - and a safety net for the edit flow
		// when the requested survey row does not exist (invalid `existing` id).
		$survey_name = "";
		$survey_class = 0;
		$survey_mailto = "";
		$survey_save_results = 0;
		$survey_captcha = 0;
		$survey_gdpr = 0;
		$survey_once = 0;
		$survey_viewclass = 0;
		$survey_editclass = 0;
		$survey_forum = 0;
		$survey_message = '';
		$survey_submit_message = '';
		$survey_parms = '';
		$survey_lastfnum = 0;
	}

	if(!empty($_POST['edit'])){
		$sid = intval($_POST['existing']);
		if($sql->db_Select("survey","*","survey_id = {$sid}")){
			$row = $sql->db_Fetch();
			if(is_array($row)){
				$survey_name           = $row['survey_name'];
				$survey_class          = $row['survey_class'];
				$survey_mailto         = $row['survey_mailto'];
				$survey_save_results   = $row['survey_save_results'];
				$survey_once           = $row['survey_once'];
				$survey_viewclass      = $row['survey_viewclass'];
				$survey_editclass      = $row['survey_editclass'];
				$survey_forum          = $row['survey_forum'];
				$survey_captcha        = intval($row['survey_captcha'] ?? 0);
				$survey_gdpr           = intval($row['survey_gdpr'] ?? 0);
				$survey_message        = $row['survey_message'];
				$survey_submit_message = $row['survey_submit_message'];
				$survey_parms          = $row['survey_parms'];
				$survey_lastfnum       = $row['survey_lastfnum'];
			}
		}
	}

	// Load fields
	$fields = survey_load_fields($survey_parms ?? '');

	$submit_name = empty($_POST['edit']) ? "add" : "update";
	$submit_value = empty($_POST['edit']) ? ADLAN_SUR19 : ADLAN_SUR27;

	$text .= "<form method='post' action='".e_SELF."' id='survey-config-form'>";
	$text .= "<input type='hidden' name='e-token' value='".defset('e_TOKEN')."' />";
	if(!empty($_POST['edit'])){
		$text .= "<input type='hidden' name='existing' value='".intval($_POST['existing'])."' />";
		$text .= "<input type='hidden' name='edit' value='1' />";
	}

	// ===== Survey Settings Card (accordion — open on create, closed on edit) =====
	$detailsOpen = empty($_POST['edit']) ? ' open' : '';
	$text .= "<div class='card mb-3'>";
	$text .= "<details class='survey-settings-details'{$detailsOpen}>";
	$text .= "<summary class='card-header d-flex justify-content-between align-items-center p-2'><strong>".ADLAN_SUR100."</strong><span class='text-muted small'>".(empty($_POST['edit']) ? '▼' : '▶')."</span></summary>";
	$text .= "<div class='card-body'>";
	$text .= "<div class='row g-3'>";

	// Name
	$text .= "<div class='col-12 col-md-6'>";
	$text .= "<label class='form-label'>".ADLAN_SUR10."</label>";
	$text .= "<input type='text' name='survey_name' class='form-control' value='".htmlspecialchars($tp->toHTML($survey_name ?? ''))."' maxlength='128' required />";
	$text .= "</div>";

	// Access Class
	$text .= "<div class='col-12 col-md-6'>";
	$text .= "<label class='form-label'>".ADLAN_SUR11."</label>";
	$text .= r_userclass("survey_class", $survey_class);
	$text .= "</div>";

	// Mail to
	$text .= "<div class='col-12 col-md-6'>";
	$text .= "<label class='form-label'>".ADLAN_SUR15."</label>";
	$text .= "<input type='email' name='survey_mailto' class='form-control' value='".htmlspecialchars($tp->toHTML($survey_mailto ?? ''))."' maxlength='255' />";
	$text .= "</div>";

	// Allow multiple
	$text .= "<div class='col-6 col-md-3'>";
	$text .= "<label class='form-label'>".ADLAN_SUR12."</label>";
	$text .= "<select name='survey_once' class='form-select'>";
	$text .= "<option value='1'".($survey_once == 1 ? ' selected' : "").">".ADLAN_SUR13."</option>";
	$text .= "<option value='0'".($survey_once == 0 || !isset($survey_once) ? ' selected' : "").">".ADLAN_SUR14."</option>";
	$text .= "</select>";
	$text .= "</div>";

	// Save Results
	$text .= "<div class='col-6 col-md-3'>";
	$text .= "<label class='form-label'>".ADLAN_SUR17."</label>";
	$text .= "<select name='survey_save_results' class='form-select'>";
	$text .= "<option value='1'".($survey_save_results == 1 ? ' selected' : "").">".ADLAN_SUR13."</option>";
	$text .= "<option value='0'".($survey_save_results == 0 ? ' selected' : "").">".ADLAN_SUR14."</option>";
	$text .= "</select>";
	$text .= "</div>";

	// Forum
	$text .= "<div class='col-12 col-md-6'>";
	$text .= "<label class='form-label'>".ADLAN_SUR16."</label>";
	if(!empty($forumList)){
		$text .= "<select name='survey_forum' class='form-select'>";
		$text .= "<option value='0'>".ADLAN_SUR30."</option>";
		foreach($forumList as $fid => $fname){
			$sel = ($survey_forum == $fid) ? ' selected' : '';
			$text .= "<option value='".intval($fid)."'{$sel}>".htmlspecialchars($fname)."</option>";
		}
		$text .= "</select>";
	} else {
		$text .= "<input type='text' class='form-control' disabled value='".ADLAN_SUR30."' />";
		$text .= "<input type='hidden' name='survey_forum' value='0' />";
	}
	$text .= "</div>";

	// View Class
	$text .= "<div class='col-6 col-md-3'>";
	$text .= "<label class='form-label'>".ADLAN_SUR18."</label>";
	$text .= r_userclass("survey_viewclass", $survey_viewclass);
	$text .= "</div>";

	// Edit Class
	$text .= "<div class='col-6 col-md-3'>";
	$text .= "<label class='form-label'>".ADLAN_SUR68."</label>";
	$text .= r_userclass("survey_editclass", $survey_editclass);
	$text .= "</div>";

	// Captcha toggle
	$text .= "<div class='col-6 col-md-3'>";
	$text .= "<div class='form-check mt-4'>";
	$text .= "<input class='form-check-input' type='checkbox' name='survey_captcha' value='1' id='survey_captcha_chk'".($survey_captcha ? ' checked' : '')." />";
	$text .= "<label class='form-check-label' for='survey_captcha_chk'>".ADLAN_SUR108."</label>";
	$text .= "</div>";
	$text .= "</div>";

	// GDPR toggle
	$text .= "<div class='col-6 col-md-3'>";
	$text .= "<div class='form-check mt-4'>";
	$text .= "<input class='form-check-input' type='checkbox' name='survey_gdpr' value='1' id='survey_gdpr_chk'".($survey_gdpr ? ' checked' : '')." />";
	$text .= "<label class='form-check-label' for='survey_gdpr_chk'>".ADLAN_SUR109."</label>";
	$text .= "</div>";
	$text .= "</div>";

	$text .= "</div>"; // row

	// Messages (collapsed)
	$text .= "<div class='mt-3'>";
	$text .= "<details>";
	$text .= "<summary class='text-muted small'>".ADLAN_SUR119."</summary>";
	$text .= "<div class='row g-3 mt-2'>";
	$text .= "<div class='col-12 col-md-6'>";
	$text .= "<label class='form-label small'>".ADLAN_SUR20."</label>";
	$text .= "<textarea name='survey_message' class='form-control form-control-sm' rows='3'>".htmlspecialchars($tp->toHTML($survey_message ?? ''))."</textarea>";
	$text .= "</div>";
	$text .= "<div class='col-12 col-md-6'>";
	$text .= "<label class='form-label small'>".ADLAN_SUR21."</label>";
	$text .= "<textarea name='survey_submit_message' class='form-control form-control-sm' rows='3'>".htmlspecialchars($tp->toHTML($survey_submit_message ?? ''))."</textarea>";
	$text .= "</div>";
	$text .= "</div>";
	$text .= "</details>";
	$text .= "</div>";
	$text .= "</div></details></div>"; // card-body, details (settings), card

	// ===== Field Builder Card =====
	$isNewSurvey = !empty($_POST['create']) && empty($_POST['edit']);

	if($isNewSurvey)
	{
		// New survey not saved yet - the builder unlocks after the settings are saved.
		$text .= "<div class='card mb-3'>";
		$text .= "<div class='card-header'><strong>".ADLAN_SUR101."</strong></div>";
		$text .= "<div class='card-body'>";
		$text .= "<div class='alert alert-light border d-flex align-items-center gap-3 mb-0 text-secondary'>";
		$text .= "<span>".survey_type_icon(23, 22)."</span>";
		$text .= "<span>".ADLAN_SUR215."</span>";
		$text .= "</div>";
		$text .= "</div></div>"; // card-body, card
	}
	else
	{
		$text .= "<div class='card mb-3'>";
		$text .= "<div class='card-header d-flex justify-content-between align-items-center'>";
		$text .= "<strong>".ADLAN_SUR101."</strong>";
		if(!empty($_POST['edit'])){
			$survey_url = preg_replace("/_config/", "", SITEURLBASE.e_PLUGIN_ABS."survey/")."survey.php?".intval($_POST['existing']);
			$text .= "<a href='".htmlspecialchars($survey_url)."' class='btn btn-sm btn-outline-primary' target='_blank'>".ADLAN_SUR28."</a>";
		}
		$text .= "</div>";
		$text .= "<div class='card-body'>";

		// Field palette
		$text .= "<div class='survey-field-palette mb-3' id='survey-field-palette'>";
		$paletteTypes = array(1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23);
		foreach($paletteTypes as $pt){
			$icon = survey_type_icon($pt);
			$label = $fieldtypes[$pt] ?? "?";
			$text .= "<button type='button' class='btn btn-sm btn-outline-primary' data-field-type='{$pt}' title='".htmlspecialchars($label)."'>";
			$text .= "<span class='field-icon'>{$icon}</span>".htmlspecialchars($label);
			$text .= "</button>";
		}
		$text .= "</div>";

		// Hidden order input
		$text .= "<input type='hidden' name='field_order' id='field-order-input' value='' />";

		// Field list (draggable cards)
		$text .= "<div id='survey-field-list' class='survey-field-list' data-next-index='".count($fields)."'>";

		if(empty($fields)){
			$text .= "<div class='text-center text-muted py-4'>".ADLAN_SUR116."</div>";
		}

		$fi = 0;
		$orderIndices = array();
		foreach($fields as $f){
			$idx = $fi;
			$orderIndices[] = $idx;
			$ftype = intval($f['field_type'] ?? 1);
			$ftype_label = $fieldtypes[$ftype] ?? "?";
			$ftype_icon = survey_type_icon($ftype);
			$fcol = intval($f['field_col'] ?? 12);
			$flabel = $tp->toHTML($f['field_text'] ?? '');
			$fchoices = $tp->toHTML($f['field_choices'] ?? '');

			// Type options
			$typeOptions = '';
			foreach($fieldtypes as $ftnum => $ftval){
				$sel = ($ftnum == $ftype) ? ' selected' : '';
				$typeOptions .= "<option value='".intval($ftnum)."'{$sel}>".htmlspecialchars($ftval)."</option>";
			}

			// Col options
			$colOptions = '<option value="12"'.($fcol == 12 ? ' selected' : '').'>'.ADLAN_SUR104.'</option>'
				.'<option value="6"'.($fcol == 6 ? ' selected' : '').'>'.ADLAN_SUR105.'</option>'
				.'<option value="4"'.($fcol == 4 ? ' selected' : '').'>'.ADLAN_SUR106.'</option>'
				.'<option value="3"'.($fcol == 3 ? ' selected' : '').'>'.ADLAN_SUR107.'</option>';

			$text .= "<div class='survey-field-card card' draggable='true' data-index='{$idx}'>";
			$text .= "<div class='card-header'>";
			$text .= "<span class='drag-handle' title='".ADLAN_SUR110."'>".survey_svg_grip()."</span>";
			$text .= "<span class='field-num'>".($fi + 1)."</span>";
			$text .= "<span class='badge field-type-badge'><span class='field-icon'>{$ftype_icon}</span> ".htmlspecialchars($ftype_label)."</span>";
			$text .= "<span class='field-label-preview'>".htmlspecialchars($f['field_text'] ?? '')."</span>";
			$text .= "<div class='field-actions'>";
			$text .= "<button type='button' class='btn btn-sm btn-outline-danger btn-delete-field d-inline-flex align-items-center' title='".ADLAN_SUR117."'>".survey_svg_x()."</button>";
			$text .= "</div>";
			$text .= "</div>";
			$text .= "<div class='card-body d-none'>";
			$text .= "<div class='row g-2'>";

			if($ftype == 22)
			{
				// HTML Block: full-width textarea, no label/params/required
				$text .= "<div class='col-12'>";
				$text .= "<label class='form-label small mb-1'>".ADLAN_SUR214."</label>";
				$text .= "<textarea class='form-control form-control-sm field-text-input' name='field_text[{$idx}]' rows='8' spellcheck='false' style='font-family:monospace;'>".htmlspecialchars($f['field_text'] ?? '')."</textarea>";
				$text .= "</div>";
				$text .= "<div class='col-6 col-md-4'>";
				$text .= "<label class='form-label small mb-1'>".ADLAN_SUR112."</label>";
				$text .= "<select class='form-select form-select-sm field-type-input' name='field_type[{$idx}]'>";
				$text .= $typeOptions;
				$text .= "</select>";
				$text .= "</div>";
				$text .= "<div class='col-6 col-md-2 text-center'>";
				$text .= "<div class='form-check'>";
				$text .= "<input class='form-check-input field-hidden-input' type='checkbox' name='field_hidden[{$idx}]' value='1'".(!empty($f['field_hidden']) ? ' checked' : '')." />";
				$text .= "<label class='form-check-label small'>".ADLAN_SUR114."</label>";
				$text .= "</div>";
				$text .= "</div>";
			}
			else
			{
				$text .= "<div class='col-12 col-md-5'>";
				$text .= "<label class='form-label small mb-1'>".ADLAN_SUR111."</label>";
				$text .= "<input type='text' class='form-control form-control-sm field-text-input' name='field_text[{$idx}]' value='".htmlspecialchars($f['field_text'] ?? '')."' />";
				$text .= "</div>";
				$text .= "<div class='col-6 col-md-3'>";
				$text .= "<label class='form-label small mb-1'>".ADLAN_SUR112."</label>";
				$text .= "<select class='form-select form-select-sm field-type-input' name='field_type[{$idx}]'>";
				$text .= $typeOptions;
				$text .= "</select>";
				$text .= "</div>";
				$text .= "<div class='col-6 col-md-2'>";
				$text .= "<label class='form-label small mb-1'>".ADLAN_SUR103."</label>";
				$text .= "<select class='form-select form-select-sm field-col-input' name='field_col[{$idx}]'>";
				$text .= $colOptions;
				$text .= "</select>";
				$text .= "</div>";
				$text .= "<div class='col-4 col-md-1 text-center'>";
				$text .= "<div class='form-check'>";
				$text .= "<input class='form-check-input field-req-input' type='checkbox' name='field_req[{$idx}]' value='1'".(!empty($f['field_req']) ? ' checked' : '')." />";
				$text .= "<label class='form-check-label small'>".ADLAN_SUR113."</label>";
				$text .= "</div>";
				$text .= "</div>";
				$text .= "<div class='col-4 col-md-1 text-center'>";
				$text .= "<div class='form-check'>";
				$text .= "<input class='form-check-input field-hidden-input' type='checkbox' name='field_hidden[{$idx}]' value='1'".(!empty($f['field_hidden']) ? ' checked' : '')." />";
				$text .= "<label class='form-check-label small'>".ADLAN_SUR114."</label>";
				$text .= "</div>";
				$text .= "</div>";
				$text .= "<div class='col-12 col-md-4'>";
				$text .= "<label class='form-label small mb-1'>".ADLAN_SUR115."</label>";
				$text .= "<input type='text' class='form-control form-control-sm field-choices-input' name='field_choices[{$idx}]' value='".htmlspecialchars($f['field_choices'] ?? '')."' placeholder='e.g. 30,100 or opt1,opt2,opt3' />";
				$text .= "</div>";
			}

			$text .= "</div>"; // row
			$text .= "</div>"; // card-body

			// Hidden field_number
			$text .= "<input type='hidden' name='field_number[{$idx}]' value='".intval($f['field_number'] ?? ($fi+1))."' />";
			$text .= "</div>"; // survey-field-card

			$fi++;
		}

		$text .= "</div>"; // survey-field-list

		// Add field button
		$text .= "<div class='mt-3'>";
		$text .= "<button type='button' class='btn btn-outline-primary' id='survey-add-field'>+ ".ADLAN_SUR102."</button>";
		$text .= "</div>";

		$text .= "</div></div>"; // card-body, card
	}

	// Submit
	$text .= "<div class='text-center mb-4'>";
	$text .= "<button type='submit' name='{$submit_name}' value='1' class='btn btn-primary'>{$submit_value}</button>";
	$text .= "</div>";

	$text .= "</form>"; // survey-config-form
}

$text .= "</div>"; // container-fluid

// Admin CSS + JS
$text .= "<style>";
$text .= file_get_contents(__DIR__.'/survey_admin.css');
$text .= "</style>";

// Type template (hidden select for JS to read)
$text .= "<select id='survey-type-template' style='display:none;'>";
foreach($fieldtypes as $ftnum => $ftval){
	$text .= "<option value='".intval($ftnum)."'>".htmlspecialchars($ftval)."</option>";
}
$text .= "</select>";

$text .= "<script>";
$text .= file_get_contents(__DIR__.'/survey_admin.js');
$text .= "</script>";

$ns->tablerender(ADLAN_SUR9, $text);
require_once(e_ADMIN."footer.php");
exit;
