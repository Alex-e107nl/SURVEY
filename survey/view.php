<?php
/*
+---------------------------------------------------------------+
|	e107 website system
|	view.php
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

function np($url, $from, $view, $total, $td, $qs="")
{
	if($total == 0)
	{
		return;
	}
	echo "<div class='d-flex justify-content-between align-items-center mt-3'>";
	if($from >= 1)
	{
		$s = $from-$view;
		if($qs != "")
		{
			echo "<a class='btn btn-outline-secondary' href='".htmlspecialchars($url."?{$qs}.{$s}")."'>".NP_1."</a>";
		}
		else
		{
			echo "<a class='btn btn-outline-secondary' href='".htmlspecialchars($url."?{$s}")."'>".NP_1."</a>";
		}
	}
	else
	{
		echo "<span></span>";
	}

	$start = $from+1;
	$finish = $from+$view;
	if($finish>$total)
	{
		$finish = $total;
	}
	echo "<span class='text-muted small'>".$td." ".$start." - ".$finish." ".LAN_SUR25." ".$total."</span>";

	$s = $from+$view;
	if($s < $total)
	{
		if($qs != "")
		{
			echo "<a class='btn btn-outline-secondary' href='".htmlspecialchars($url."?{$qs}.{$s}")."'>".NP_2."</a>";
		}
		else
		{
			echo "<a class='btn btn-outline-secondary' href='".htmlspecialchars($url."?{$s}")."'>".NP_2."</a>";
		}
	}
	else
	{
		echo "<span></span>";
	}
	echo "</div>";
}

function survey_safe_math($expr)
{
	// Whitelist: only digits, operators, parentheses, spaces, dots
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

	$_res = array();
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
					$ser[] = is_scalar($x) ? $x : '';
				}
				$_res[$fn] = serialize($ser);
				unset($ser);
			} else {
				$_res[$fn] = '';
			}
		}
		else
		{
			$_res[$fn] = is_scalar($v) ? $v : '';
		}
	}
	// Store the clean serialized string via a bound parameter.
	// Never run toDB() on the serialized string: it HTML-encodes the
	// structural quotes into &quot; which breaks unserialize() on read.
	$results = serialize($_res);
	$sql -> db_Update("survey_results", array('results_results' => $results, 'WHERE' => 'results_id='.intval($id)));
}

function delete_record($id)
{
	global $sql, $arg, $ns;
	$id = intval($id);
	$sid = intval($arg[0]);
	if($sql -> db_Select("survey","*","survey_id={$sid}"))
	{
		$row = $sql -> db_Fetch();
		if(is_array($row))
		{
			$text = "<p>".LAN_SUR28.": ".htmlspecialchars($row['survey_name'])."</p>";
			$text .= "
			<form action='".e_SELF."?".e_QUERY."' method='POST'>
			<input type='hidden' name='e-token' value='".defset('e_TOKEN')."' />
			<input type='hidden' name='result_id' value='{$id}'>
			<input type='submit' class='btn btn-danger me-2' name='dconfirm' value='".LAN_SUR29."'>
			<input type='submit' class='btn btn-outline-secondary' name='cancel' value='".LAN_SUR30."'>
			</form>
			";
			$ns -> tablerender(LAN_SUR29, $text);
		}
	}
}

function delete_confirmed($id)
{
	global $sql, $ns;
	$id = intval($id);
	$tokenOk = !e_session::modeUsesToken() || !empty($_POST['e-token']);
	if(!$tokenOk){ return; }
	$deleted = $sql -> db_Delete("survey_results", "results_id={$id}");
	if($deleted)
	{
		$msg = LAN_SUR31;
	}
	else
	{
		$msg = LAN_SUR32;
	}
	$ns -> tablerender("", "<div class='alert alert-success text-center'>{$msg}</div>");
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
		switch($sf['field_type'])
		{
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
			case(12): // emailto
			case(10): // email
			case(11): // number
			case(7):  // date
				$r = @unserialize($resp['results_results']);
				if(is_array($r) && isset($r[$fn]))
				{
					return htmlspecialchars($r[$fn]);
				}
				return '';
				break;
			case(3):  // checkbox
				$r = @unserialize($resp['results_results']);
				if(is_array($r) && isset($r[$fn]))
				{
					$rr = @unserialize($r[$fn]);
					if(is_array($rr)){
						return implode("<br />", array_map('htmlspecialchars', $rr));
					}
				}
				return '';
				break;
			case(4):  // radio
			case(5):  // dropdown
				$r = @unserialize($resp['results_results']);
				if(is_array($r) && isset($r[$fn]))
				{
					return htmlspecialchars($r[$fn]);
				}
				return '';
				break;
			case(9):  // calculation
				return field_calc($sf);
				break;
			case(14): // signature (base64 image)
				$r = @unserialize($resp['results_results']);
				if(is_array($r) && isset($r[$fn]) && $r[$fn])
				{
					return "<img src='".htmlspecialchars($r[$fn])."' alt='' style='max-height:80px;border:1px solid #ddd;' />";
				}
				return '';
				break;
		}
	}
	return '';
}

function get_val($fn)
{
	global $survey_fields, $_res, $selected_rec;
	if(!is_array($survey_fields)){ return 0; }
	foreach($survey_fields as $sf)
	{
		if($sf['field_number'] == $fn)
		{
			if(isset($_res[$selected_rec])){
				return floatval(field_value($_res[$selected_rec], $sf));
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

// ---- Main logic ----
$arg = explode(".", e_QUERY);
$sid = intval($arg[0] ?? 0);

// Fixed SURVEY_PRINT/SURVEY_LIST/SURVEY_EDIT defines (PHP 8: can't define twice)
define("SURVEY_PRINT", !empty($_POST['print']) || (bool)preg_match("/print/", e_QUERY));
define("SURVEY_LIST", !empty($_POST['list']) || (bool)preg_match("/list/", e_QUERY));

if(!(SURVEY_PRINT))
{
	require_once(HEADERF);
}
else
{
	echo "<link rel='stylesheet' href='".THEME."style.css'>";
}

$found_recs = array();
global $survey_fields;
global $_res;
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
		if(!SURVEY_PRINT){ require_once(FOOTERF); }
		exit;
	}
	$survey_viewclass  = $row['survey_viewclass'];
	$survey_save_results = $row['survey_save_results'];
	$survey_editclass  = $row['survey_editclass'];
	$survey_name       = $row['survey_name'];
	$survey_id         = $row['survey_id'];
	$survey_parms      = $row['survey_parms'];
}
else
{
	if(!SURVEY_PRINT){ require_once(FOOTERF); }
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

if($_POST['delete'])
{
	if($canEdit)
	{
		delete_record($_POST['result_id'] ?? 0);
		require_once(FOOTERF);
		exit;
	}
	else
	{
		header("location:".e_BASE."index.php"); exit;
	}
}

if($_POST['dconfirm'])
{
	if($canEdit)
	{
		delete_confirmed($_POST['result_id'] ?? 0);
	}
	else
	{
		header("location:".e_BASE."index.php"); exit;
	}
}

$survey_fields = survey_load_fields($survey_parms);

$_res = array();
$qry = "results_survey_id={$sid} ORDER BY results_datestamp ASC";
$numresults = $sql -> db_Select("survey_results","*",$qry);
$first_response = 9999999999;
$last_response = 0;
while($row = $sql -> db_Fetch())
{
	$first_response = ($row['results_datestamp'] < $first_response) ? $row['results_datestamp'] : $first_response;
	$last_response  = ($row['results_datestamp'] > $last_response) ? $row['results_datestamp'] : $last_response;
	$_res[] = $row;
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
		$_res[$selected_rec] = $found_recs[$selected_rec];
	}
}

// Search form
if(SURVEY_EDIT !== TRUE && SURVEY_PRINT !== TRUE)
{
	$text = "
	<form action='".e_SELF."?{$sid}.' method='POST'>
	<input type='hidden' name='e-token' value='".defset('e_TOKEN')."' />
	<div class='d-flex align-items-center gap-2 mb-3 flex-wrap'>
	<input class='form-control' style='max-width:300px' type='text' name='search_text' value='".htmlspecialchars($search_text)."' >
	<input type='submit' class='btn btn-primary' name='search' value='".LAN_SUR11."'>
	<div class='form-check ms-2'><input type='checkbox' class='form-check-input' name='list'><label class='form-check-label'>".LAN_SUR12."</label></div>
	</div></form>
	";
}

if($numresults && count($_res) > 0)
{
	$cnv = e107::getDate();
	$text .= "<table class='table table-striped align-middle'>";
	if(SURVEY_EDIT !== TRUE && SURVEY_PRINT !== TRUE)
	{
		$text .= "<tr>";
		$text .= "<td class='fw-bold'>".LAN_SUR14.":</td><td>{$numresults}</td>";
		$text .= "</tr>";
		$text .= "<tr>";
		$text .= "<td class='fw-bold'>".LAN_SUR19.":</td><td>".$cnv->convert_date($first_response, "long")."</td>";
		$text .= "</tr>";
		$text .= "<tr>";
		$text .= "<td class='fw-bold'>".LAN_SUR20.":</td><td>".$cnv->convert_date($last_response, "long")."</td>";
		$text .= "</tr>";
		$text .= "<tr>";
		$text .= "<td colspan='2' class='pt-2'>";
		$opts = ".print";
		if(SURVEY_LIST === TRUE)
		{
			$opts .= ".list";
		}
		$text .= "<a href='word.php?{$survey_id}' class='btn btn-outline-secondary me-1' title='".LAN_SUR100."'><span class='fa fa-file-word-o me-1'></span> ".LAN_SUR100."</a> ";
		$text .= "<a href='excel.php?{$survey_id}' class='btn btn-outline-success me-1' title='".LAN_SUR101."'><span class='fa fa-file-excel-o me-1'></span> ".LAN_SUR101."</a> ";
		$text .= "<a href=\"javascript:open_window('view.php?{$survey_id}.{$search_text}.{$selected_rec}{$opts}')\" class='btn btn-outline-secondary' title='".LAN_SUR34."'><span class='fa fa-print me-1'></span> ".LAN_SUR34."</a>";
		$text .= "</td></tr>";
	}
	$i = 0;
	if(SURVEY_LIST === TRUE)
	{
		$text .= "</table><table class='table table-striped align-middle' style='margin-top:10px'><tr>";
		if($canEdit && SURVEY_PRINT !== TRUE)
		{
			$text .= "<td>&nbsp;</td>";
		}
		foreach($survey_fields as $sf)
		{
			$text .= "<td class='fw-bold'>".htmlspecialchars($sf['field_text'])."</td>";
		}
		$text .= "</tr>";
		$reclist = ($search_text) ? $found_recs : $_res;
		$sr = 0;
		foreach($reclist as $r)
		{
			$selected_rec = $sr;
			$sr++;
			$text .= "<tr>";
			if($canEdit && SURVEY_PRINT !== TRUE)
			{
				$text .= "<td>
				<form action='".e_SELF."?{$sid}.{$arg[1]}.".$selected_rec."' method='POST'>
				<input type='hidden' name='e-token' value='".defset('e_TOKEN')."' />
				<input class='btn btn-outline-primary' type='submit' name='edit' value='".LAN_SUR36."'>
				</form></td>";
			}
			foreach($survey_fields as $sf)
			{
				$text .= "<td>";
				$text .= field_value($r, $sf);
				$text .= "</td>";
			}
			$text .= "</tr>";
		}
	}
	else
	{
		if($canEdit && SURVEY_PRINT !== TRUE && SURVEY_EDIT !== TRUE)
		{
			$text .= "
			<form action='".e_SELF."?".e_QUERY."' method='post'>
			<input type='hidden' name='e-token' value='".defset('e_TOKEN')."' />
			<tr>
			<td colspan='2' class='pb-2'><input class='btn btn-outline-primary' type='submit' name='edit' value='".LAN_SUR26."'></td>
			</tr></form>
			";
		}

		$text .= "<tr>";
		$text .= "<td colspan='2'><br /></td>";
		$text .= "</tr>";
		$text .= "<tr>";
		$text .= "<td class='fw-bold'>".LAN_SUR16."</td><td class='fw-bold'>".LAN_SUR17."</td>";
		$text .= "</tr>";

		if(isset($_res[$selected_rec]))
		{
			$_r = @unserialize($_res[$selected_rec]['results_results']);
			if(!is_array($_r)){ $_r = array(); }
			$_res_id = $_res[$selected_rec]['results_id'];

			if(SURVEY_EDIT === TRUE)
			{
				$text .= "<form action='".e_SELF."?".e_QUERY."' method='POST'>";
				$text .= "<input type='hidden' name='e-token' value='".defset('e_TOKEN')."' />";
			}
			$s_f = $survey_fields;
			foreach($s_f as $_sf)
			{
				if($_sf['field_type'] == 6)   // separator
				{
					$text .= "<tr><td colspan='2'>".show_form_field($_sf)."</td></tr>";
				}
				else
				{
					$text .= "<tr><td class='fw-bold'>".htmlspecialchars($_sf['field_text'])."</td><td>";
					if(SURVEY_EDIT === TRUE)
					{
						$_f = $_sf['field_number'];
						$_res[$_f] = $_r[$_f] ?? '';
						$text .= show_form_field($_sf);
					}
					else
					{
						$text .= field_value($_res[$selected_rec], $_sf);
					}
					$text .= "</td></tr>";
				}
			}
			if(SURVEY_EDIT === TRUE)
			{
				$text .= "
			<tr>
			<td colspan='2' class='pt-2'>
			<input type='hidden' name='result_id' value='".intval($_res_id)."'>
			<input class='btn btn-primary me-2' type='submit' name='update' value='".LAN_SUR27."'>
			<input class='btn btn-danger' type='submit' name='delete' value='".LAN_SUR99."'>
			</td>
			</tr></form>
			";
			}
		}
	}
	$text .= "</table>";
}
else
{
	$text .= "<div class='text-center text-muted py-3'>".LAN_SUR15."</div>";
}

$table_title = LAN_SUR18.": ".htmlspecialchars($survey_name);
if(SURVEY_PRINT === TRUE)
{
	$cv = e107::getDate();
	if($search_text)
	{
		$table_title .= " - ".LAN_SUR37.": ".htmlspecialchars($search_text);
	}
	if(isset($arg[2]))
	{
		$table_title .= " - ".LAN_SUR39.": ".($selected_rec+1);
	}
	else
	{
		$table_title .= " - ".LAN_SUR38.": ".($selected_rec+1);
	}
}
$ns -> tablerender($table_title, $text);

if(!$_POST['list'] && SURVEY_PRINT !== TRUE)
{
	np(e_SELF, $selected_rec, 1, $numresults, LAN_SUR10, $sid.".".htmlspecialchars($search_text));
}

if(!(SURVEY_PRINT))
{
	require_once(FOOTERF);
}
else
{
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
}
