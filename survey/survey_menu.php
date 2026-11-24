<?php
/*
+---------------------------------------------------------------+
|	e107 website system
|	survey_menu.php - Survey as a placeable menu
+---------------------------------------------------------------+
*/

if (!defined('e107_INIT')) { exit; }

// ---- Load dependencies (guard against double-load) ----
if(!class_exists('myform'))
{
	if(!class_exists('form'))
	{
		require_once(e_HANDLER.'form_handler.php');
	}
	require_once(__DIR__.'/survey.inc.php');
}
if(!defined('LAN_SUR5'))
{
	e107::includeLan(e_PLUGIN.'survey/languages/'.e_LANGUAGE.'/'.e_LANGUAGE.'_front.php');
}

// ---- Read menu params ----
// $parm is an ARRAY when rendered by the Menu Manager (e107 2.x unserializes
// the menu_parms JSON before including this file), or a QUERY STRING for
// {PLUGIN=survey/survey_menu|survey_id=2} shortcodes / legacy menu parms.
// Both must work, or the menu silently renders nothing.
// NOTE: do NOT use varset($parm, '') here - varset() looks up an array key,
// so it returns null when $parm is an array, silently dropping the selection.
$parms = array();
if(is_array($parm))
{
	$parms = $parm;
}
elseif(is_string($parm) && $parm !== '')
{
	parse_str($parm, $parms);
}
$sid   = intval($parms['survey_id'] ?? 0);

$caption = $parms['survey_caption'] ?? '';
if(is_array($caption))
{
	$caption = $caption[e_LANGUAGE] ?? '';
	if(is_array($caption)) $caption = reset($caption);
}
$caption = trim((string)$caption);

// No survey selected → show nothing to visitors.
// For admins, show a small hint so a missing/lost selection is not a mystery.
// ($ns, $tp, $sql are in scope here: this file is included from e_menu::renderMenu())
if($sid <= 0)
{
	if(check_class(e_UC_ADMIN))
	{
		$hint = $caption ? $caption : 'Survey';
		$ns->tablerender($hint,
			"<div class='alert alert-light border small mb-0'>".
			"<strong>Survey menu:</strong> no survey is selected for this menu. ".
			"Choose one in the Menu Manager <em>(Admin &raquo; Menus &raquo; Menu Manager &raquo; ".
			"this menu &raquo; parameters)</em> and check the site again ".
			"(clear the page cache if it is enabled).</div>");
	}
	return;
}

// Load survey
$sql = e107::getDb();
if(!$sql->db_Select("survey", "*", "survey_id = ".intval($sid))) return;
$row = $sql->db_Fetch();
if(!is_array($row)) return;

// Access check
if(!check_class($row['survey_class'])) return;

// Already voted?
if($row['survey_class'] != e_UC_PUBLIC && $row['survey_once'] && already_voted($row['survey_user']))
{
	$ns->tablerender($caption ?: $row['survey_name'],
		'<div class="alert alert-info mb-0">' . LAN_SUR2 . '</div>');
	return;
}

// Load fields
$fdata = survey_load_fields($row['survey_parms']);
if(!is_array($fdata)) $fdata = array();

// Render form
$frm = new myform();
$ret  = $frm->form_open("post", e_PLUGIN.'survey/survey.php?'.intval($sid));
$ret .= "<input type='hidden' name='e-token' value='".defset('e_TOKEN')."' />";
$ret .= $frm->form_hidden("survey_id", $sid);

if(!empty($row['survey_message']))
{
	$ret .= "<div class='mb-3'>".$row['survey_message']."</div>";
}

$ret .= survey_render_fields($fdata);

if(!empty($row['survey_captcha']))
{
	$ret .= survey_render_captcha();
}

if(!empty($row['survey_gdpr']))
{
	$ret .= survey_render_gdpr();
}

$ret .= "<div class='mt-3'>";
$ret .= "<button type='submit' name='submit' value='submit' class='btn btn-primary w-100'>" . LAN_SUR5 . "</button>";
$ret .= "</div>";
$ret .= $frm->form_close();

$ns->tablerender($caption ?: $row['survey_name'], $ret);
