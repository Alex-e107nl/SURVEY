<?php
/*
+---------------------------------------------------------------+
|	e107 website system
|	print.php
|
|	Released under the terms and conditions of the
|	GNU General Public License (http://gnu.org).
+---------------------------------------------------------------+
*/
require_once(__DIR__.'/../../class2.php');
require_once(e_HANDLER."userclass_class.php");
require_once(e_HANDLER."form_handler.php");
require_once(__DIR__.'/survey.inc.php');
e107::includeLan(e_PLUGIN.'survey/languages/'.e_LANGUAGE.'/'.e_LANGUAGE.'_front.php');
e107::coreLan('np');

echo "<link rel='stylesheet' href='".THEME."style.css'>";

function np($url, $from, $view, $total, $td, $qs="")
{
	if($total == 0)
	{
		return;
	}
	echo "<table style=\"width:100%\">
	<tr>";
	if($from >= 1)
	{
		$s = $from-$view;
		echo "<td style=\"width:33%\" class=\"nextprev\">";
		if($qs != "")
		{
			$text = "<div style=\"text-align:left\"><span class=\"smalltext\"><a href=\"".htmlspecialchars($url."?{$qs}.{$s}")."\">".NP_1."</a></span></div>";
		}
		else
		{
			$text = "<div style=\"text-align:left\"><span class=\"smalltext\"><a href=\"".htmlspecialchars($url."?{$s}")."\">".NP_1."</a></span></div>";
		}
		echo $text;
	}
	else
	{
		echo "<td style=\"width:33%\">&nbsp;";
	}

	echo "</td>\n<td style=\"width:34%\" class=\"nextprev\">";
	$start = $from+1;
	$finish = $from+$view;
	if($finish>$total)
	{
		$finish = $total;
	}
	$text = "<div style=\"text-align:center\"><span class=\"smalltext\">$td $start - $finish ".LAN_SUR25." $total</span></div>";
	echo $text;

	$s = $from+$view;
	if($s < $total)
	{
		echo "</td><td style=\"width:33%\" class=\"nextprev\">";
		if($qs != "")
		{
			$text = "<div style=\"text-align:right\"><span class=\"smalltext\"><a href=\"".htmlspecialchars($url."?{$qs}.{$s}")."\">".NP_2."</a></span></div></td>";
		}
		else
		{
			$text = "<div style=\"text-align:right\"><span class=\"smalltext\"><a href=\"".htmlspecialchars($url."?{$s}")."\">".NP_2."</a></span></div></td>";
		}
		echo $text;
	}
	else
	{
		echo "</td><td style=\"width:33%\">&nbsp;</td>";
	}
	echo "</tr>\n</table>";
}

function survey_safe_math($expr)
{
	if(!preg_match('/^[\d+\-*/().\s]+$/', $expr))
	{
		return 0;
	}
	$total = 0;
	eval("\$total = $expr;");
	return (isset($total) && is_numeric($total)) ? $total : 0;
}

function update_record($id)
{
	global $sql, $arg, $tp;
	$id = intval($id);
	$sid = intval($arg[0]);
	$tokenOk = !e_session::modeUsesToken() || !empty($_POST['e-token']);
	if(!$tokenOk){ return; }

	if($sql -> db_Select("survey","*","survey_id={$sid}"))
	{
		$row = $sql -> db_Fetch();
		if(!is_array($row)){ return; }
		$survey_parms = $row['survey_parms'];
	}
	$parms = survey_load_fields($survey_parms);

	$res = array();
	foreach($parms as $parm)
	{
		$fn = $parm['field_number'];
		$v = $_POST['results'][$fn] ?? '';

		if($parm['field_type'] == 3)
		{
			if(is_array($v)){
				$ser = array();
				foreach($v as $x)
				{
					$ser[] = $tp->toDB($x);
				}
				$res[$fn] = serialize($ser);
				unset($ser);
			} else {
				$res[$fn] = '';
			}
		}
		else
		{
			$res[$fn] = $tp->toDB(is_scalar($v) ? $v : '');
		}
	}
	$results = serialize($res);
	$results_escaped = $tp->toDB($results);
	$sql -> db_Update("survey_results", "results_results='{$results_escaped}' WHERE results_id={$id}");
}

function survey_search($resp, $stext)
{
	global $survey_fields;
	global $found_recs;
	$found = 0;
	$stext_safe = preg_quote($stext, '/');
	if(!is_array($survey_fields)){ return; }
	foreach($survey_fields as $sf)
	{
		$fn = $sf['field_number'];
		$text_to_search = '';
		switch($sf['field_type']){
			case(1):  // text
			case(2):  // textarea
			case(4):  // radio
			case(5):  // dropdown
			case(8):  // name
			case(7):  // date
			case(10): // email
			case(11): // number
			case(12): // emailto
				$r = @unserialize($resp['results_results']);
				if(is_array($r) && isset($r[$fn]))
				{
					$text_to_search = $r[$fn];
				}
				break;

			case(3):  // checkbox
				$r = @unserialize($resp['results_results']);
				if(is_array($r) && isset($r[$fn]))
				{
					$rr = @unserialize($r[$fn]);
					if(is_array($rr)){
						$text_to_search = implode(".", $rr);
					}
				}
				break;
		}
		if($text_to_search && preg_match("/{$stext_safe}/", $text_to_search))
		{
			$found = 1;
		}
	}
	if($found)
	{
		$found_recs[] = $resp;
	}
}

function field_value($resp, $sf)
{
	if($sf['field_type'] != 6)
	{
		$fn = $sf['field_number'];
		switch($sf['field_type'])
		{
			case(1):  // text
			case(2):  // textarea
			case(8):  // name
			case(7):  // date
			case(10): // email
			case(11): // number
			case(12): // emailto
				$r = @unserialize($resp['results_results']);
				if(is_array($r) && isset($r[$fn]))
				{
					return $r[$fn];
				}
				return '';
				break;
			case(3):  // checkbox
				$r = @unserialize($resp['results_results']);
				if(is_array($r) && isset($r[$fn]))
				{
					$rr = @unserialize($r[$fn]);
					if(is_array($rr)){
						return implode("<br />", $rr);
					}
				}
				return '';
				break;
			case(4):  // radio
			case(5):  // dropdown
				$r = @unserialize($resp['results_results']);
				if(is_array($r) && isset($r[$fn]))
				{
					return $r[$fn];
				}
				return '';
				break;
			case(9):  // calculation
				return field_calc($sf);
				break;
		}
	}
	return '';
}

function get_val($fn)
{
	global $survey_fields, $res, $selected_rec;
	if(!is_array($survey_fields)){ return 0; }
	foreach($survey_fields as $sf)
	{
		if($sf['field_number'] == $fn)
		{
			if(isset($res[$selected_rec])){
				return floatval(field_value($res[$selected_rec], $sf));
			}
			return 0;
		}
	}
	return 0;
}

function field_calc($sf)
{
	$str = $sf['field_choices'];
	$i = 0;
	while(preg_match("/\{(.*?)\}/", $str, $matches) && $i < 5)
	{
		$val = get_val($matches[1]);
		$str = str_replace("{".$matches[1]."}", $val, $str);
		$i++;
	}
	return survey_safe_math($str);
}

$arg = explode(".", e_QUERY);
$sid = intval($arg[0] ?? 0);

$found_recs = array();
global $survey_fields;
global $res;
global $selected_rec;

if($sid > 0 && $sql -> db_Select("survey","*","survey_id={$sid}"))
{
	// proceed
}

$selected_rec = intval($arg[2] ?? 0);
$search_text  = $arg[1] ?? '';

if($_POST['search'])
{
	$tokenOk = !e_session::modeUsesToken() || !empty($_POST['e-token']);
	if($tokenOk){
		$search_text = $_POST['search_text'] ?? '';
		$arg[1] = $search_text;
	}
}

if($row = $sql -> db_Fetch())
{
	if(!is_array($row)){
		exit;
	}
	$survey_viewclass    = $row['survey_viewclass'];
	$survey_save_results = $row['survey_save_results'];
	$survey_editclass    = $row['survey_editclass'];
	$survey_name         = $row['survey_name'];
	$survey_id           = $row['survey_id'];
	$survey_parms        = $row['survey_parms'];
}
else
{
	exit;
}

if(!check_class($survey_viewclass) || !$survey_save_results)
{
	header("location:".e_BASE."index.php");
	exit;
}

$canEdit = check_class($survey_editclass);
define("SURVEY_EDIT", !empty($_POST['edit']) && $canEdit);

if($_POST['update'])
{
	if($canEdit)
	{
		update_record($_POST['result_id'] ?? 0);
	}
	else
	{
		header("location:".e_BASE."index.php"); exit;
	}
}

// Note: delete_record() and delete_confirmed() are handled by view.php,
// not print.php (print.php is a read-only print view)

$survey_fields = survey_load_fields($survey_parms);

$res = array();
$qry = "results_survey_id={$sid} ORDER BY results_datestamp ASC";
$numresults = $sql -> db_Select("survey_results","*",$qry);
$first_response = 9999999999;
$last_response = 0;
while($row = $sql -> db_Fetch())
{
	$first_response = ($row['results_datestamp'] < $first_response) ? $row['results_datestamp'] : $first_response;
	$last_response  = ($row['results_datestamp'] > $last_response) ? $row['results_datestamp'] : $last_response;
	$res[] = $row;
	if($search_text)
	{
		survey_search($row, $search_text);
	}
}
if($search_text)
{
	$numresults = count($found_recs);
	if(count($found_recs))
	{
		$res[$selected_rec] = $found_recs[$selected_rec];
	}
}

$cv = e107::getDate();
$title = htmlspecialchars($survey_name)." - ".$cv->convert_date(time(), "long");

if($numresults && count($res) > 0)
{
	$cnv = e107::getDate();
	$text = "<table align='center' border='1'>";
	$i = 0;
	if($_POST['list'])
	{
		$text .= "<tr>";
		foreach($survey_fields as $sf){
			$text .= "<td class='fcaption'>".htmlspecialchars($sf['field_text'])."</td>";
		}
		$text .= "</tr>";
		$reclist = ($search_text) ? $found_recs : $res;
		$sr = 0;
		foreach($reclist as $r)
		{
			$selected_rec = $sr;
			$sr++;
			$text .= "<tr>";
			foreach($survey_fields as $sf)
			{
				$text .= "<td class='forumheader3'>";
				$text .= field_value($r, $sf);
				$text .= "</td>";
			}
			$text .= "</tr>";
		}
	}
	else
	{
		$text .= "<tr>";
		$text .= "<td colspan='2'>".$title."</td>";
		$text .= "</tr>";
		$text .= "<tr>";
		$text .= "<td class='fcaption'>".LAN_SUR16."</td><td class='fcaption'>".LAN_SUR17."</td>";
		$text .= "</tr>";
		if(isset($res[$selected_rec]))
		{
			$_r = @unserialize($res[$selected_rec]['results_results']);
			if(!is_array($_r)){ $_r = array(); }
			$_res_id = $res[$selected_rec]['results_id'];
			if(SURVEY_EDIT === TRUE)
			{
				$text .= "<form action='".e_SELF."?".e_QUERY."' method='POST'>";
				$text .= "<input type='hidden' name='e-token' value='".defset('e_TOKEN')."' />";
			}
			$s_f = $survey_fields;
			foreach($s_f as $_sf)
			{
				$text .= "<tr><td class='forumheader3'>".htmlspecialchars($_sf['field_text'])."</td><td class='forumheader3'>";
				$text .= field_value($res[$selected_rec], $_sf);
				$text .= "</td></tr>";
			}
		}
	}
	$text .= "</table>";
}
else
{
	$text .= "<br /><div style='text-align:center;'>".LAN_SUR15.".</div>";
}

echo $text;
echo "
<script>
function printPage() {
if (window.print) {
agree = confirm('".LAN_SUR33."');
if (agree) window.print();
}
}
</script>
<BODY OnLoad='printPage()'>";
