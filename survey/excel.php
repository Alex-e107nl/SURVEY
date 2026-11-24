<?php

require_once(__DIR__.'/../../class2.php');
require_once(e_HANDLER."userclass_class.php");

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
			case(7):  // date
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
			default:
				$r = @unserialize($resp['results_results']);
				if(is_array($r) && isset($r[$fn]))
				{
					$text_to_search = $r[$fn];
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

function field_value($resp, $sf)
{
	if($sf['field_type'] != 6)
	{
		$fn = $sf['field_number'];
		switch($sf['field_type'])
		{
			case(9):  // calculator
				$str = $sf['field_choices'];
				$i = 0;
				while(preg_match("/\{(.*?)\}/", $str, $matches) && $i < 5)
				{
					$val = get_val($matches[1]);
					$str = str_replace("{".$matches[1]."}", $val, $str);
					$i++;
				}
				return survey_safe_math($str);
				break;

			case(3):  // checkbox
				$r = @unserialize($resp['results_results']);
				if(is_array($r) && isset($r[$fn]))
				{
					$rr = @unserialize($r[$fn]);
					if(is_array($rr)){
						return implode(", ", $rr);
					}
				}
				return '';
				break;

			case(6):  // separator
				break;

			default:
				$r = @unserialize($resp['results_results']);
				if(is_array($r) && isset($r[$fn]))
				{
					return $r[$fn];
				}
				return '';
				break;
		}
	}
	return '';
}

$arg = explode(".", e_QUERY);
$sid = intval($arg[0] ?? 0);

$found_recs = array();
global $survey_fields;

if($sid > 0 && $sql->db_Select("survey", "*", "survey_id={$sid}"))
{
	// proceed
}

$selected_rec = intval($arg[2] ?? 0);
$search_text  = $arg[1] ?? '';

if($_POST['search_text'])
{
	$tokenOk = !e_session::modeUsesToken() || !empty($_POST['e-token']);
	if($tokenOk){
		$search_text = $_POST['search_text'];
	}
}

if($row = $sql->db_Fetch())
{
	if(!is_array($row)){
		exit;
	}
	$survey_viewclass    = $row['survey_viewclass'];
	$survey_save_results = $row['survey_save_results'];
	$survey_name         = $row['survey_name'];
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

$survey_fields = (is_string($survey_parms) && $survey_parms !== '' && ($survey_parms[0] === '{' || $survey_parms[0] === '[')) ? json_decode($survey_parms, true) : @unserialize($survey_parms);
if(!is_array($survey_fields)){ $survey_fields = array(); }

$_res = array();
$qry = "results_survey_id={$sid} ORDER BY results_datestamp ASC";
$numresults = $sql->db_Select("survey_results","*",$qry);
$first_response = 9999999999;
$last_response = 0;
while($row = $sql->db_Fetch())
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

// CRLF injection fix: sanitize filename
$filename = preg_replace('/[^a-zA-Z0-9_\-\s]/', '_', $survey_name);
$filename = trim($filename);
if($filename === ''){ $filename = 'survey'; }

header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=\"{$filename}.xls\"");
header("Pragma: no-cache");
header("Expires: 0");

$now_date = date('d-m-Y H:i');
$title = $survey_name." - ".$now_date;

// Define separator (defines columns in excel)
$sep = "\t";
echo($title."\n\n");

foreach($survey_fields as $sf)
{
	echo htmlspecialchars($sf['field_text'])." \t";
}

print "\n";

$reclist = ($search_text) ? $found_recs : $_res;
foreach($reclist as $r)
{
	foreach($survey_fields as $sf)
	{
		$value = field_value($r, $sf);
		$value = str_replace("\r", " ", $value);
		$value = str_replace("\n", " ", $value);
		echo $value;
		echo "\t";
	}
	print "\n";
}

return (true);
