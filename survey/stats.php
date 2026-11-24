<?php

require_once(__DIR__.'/../../class2.php');
require_once(e_HANDLER."userclass_class.php");
e107::includeLan(e_PLUGIN.'survey/languages/'.e_LANGUAGE.'/'.e_LANGUAGE.'_front.php');

require_once(HEADERF);
$arg = explode(".", e_QUERY);
$sid = intval($arg[0] ?? 0);

$found_recs = array();
global $survey_fields;

if($sid > 0 && $sql -> db_Select("survey","*","survey_id={$sid}"))
{
	// proceed
}

if($row = $sql -> db_Fetch())
{
	if(!is_array($row)){
		require_once(FOOTERF);
		exit;
	}
	$survey_viewclass    = $row['survey_viewclass'];
	$survey_save_results = $row['survey_save_results'];
	$survey_name         = $row['survey_name'];
	$survey_parms        = $row['survey_parms'];
}
else
{
	require_once(FOOTERF);
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
$numresults = $sql -> db_Select("survey_results","*",$qry);
$first_response = 9999999999;
$last_response = 0;
while($row = $sql -> db_Fetch())
{
	$first_response = ($row['results_datestamp'] < $first_response) ? $row['results_datestamp'] : $first_response;
	$last_response  = ($row['results_datestamp'] > $last_response) ? $row['results_datestamp'] : $last_response;
	$_res[] = $row;
}

// Build daily response counts for Chart.js
$dailyCounts = array();
foreach($_res as $_rs){
	$day = date('Y-m-d', $_rs['results_datestamp']);
	if(!isset($dailyCounts[$day])){ $dailyCounts[$day] = 0; }
	$dailyCounts[$day]++;
}
ksort($dailyCounts);

if($numresults && count($_res) > 0)
{
	$cnv = e107::getDate();

	// ---- Chart.js: responses over time ----
	$text .= "<div class='mb-4'><canvas id='chart-responses' height='80'></canvas></div>";
	$text .= "<script src='https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js'></script>";
	$text .= "<script>
(function(){
	var labels = " . json_encode(array_keys($dailyCounts)) . ";
	var data   = " . json_encode(array_values($dailyCounts)) . ";
	new Chart(document.getElementById('chart-responses'), {
		type: 'line',
		data: {
			labels: labels,
			datasets: [{
				label: '" . htmlspecialchars($survey_name) . "',
				data: data,
				fill: true,
				tension: 0.3,
				borderColor: '#0d6efd',
				backgroundColor: 'rgba(13,110,253,0.1)',
				pointRadius: 3
			}]
		},
		options: {
			responsive: true,
			plugins: { legend: { display: false } },
			scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
		}
	});
})();
</script>";

	$text .= "<table align='center'>";
	$text .= "<tr>";
	$text .= "<td class='forumheader2'><div class='defaulttext'>".LAN_SUR14.":</div></td><td class='forumheader2'><div class='defaulttext'>{$numresults}</div></td>";
	$text .= "</tr>";
	$text .= "<tr>";
	$text .= "<td class='forumheader2'><div class='defaulttext'>".LAN_SUR19.":</div></td><td class='forumheader2'><div class='defaulttext'>".$cnv->convert_date($first_response, "long")."</div></td>";
	$text .= "</tr>";
	$text .= "<tr>";
	$text .= "<td class='forumheader2'><div class='defaulttext'>".LAN_SUR20.":</div></td><td class='forumheader2'><div class='defaulttext'>".$cnv->convert_date($last_response, "long")."</div></td>";
	$text .= "</tr>";
	$text .= "<tr>";
	$text .= "<td colspan='2'><br /></td>";
	$text .= "</tr>";
	$text .= "<tr>";
	$text .= "<td class='fcaption'>".LAN_SUR16."</td><td class='fcaption'>".LAN_SUR17."</td>";
	$text .= "</tr>";
	$i = 0;
	foreach($survey_fields as $sf)
	{
		if($sf['field_type'] != 6)
		{
			$fn = $sf['field_number'];
			$text .= "<tr><td class='forumheader3'>".htmlspecialchars($sf['field_text'])."</td><td class='forumheader3'>";
			switch($sf['field_type'])
			{
				case(1):  // text
					$text .= "[".LAN_SUR21."]</td></tr>";
					break;
				case(2):  // textarea
					$text .= "[".LAN_SUR22."]</td></tr>";
					break;
				case(8):  // name
					$text .= "[".LAN_SUR23."]</td></tr>";
					break;
				case(7):  // date
					$text .= "[".LAN_SUR24."]</td></tr>";
					break;
				case(3):  // checkbox
					$choices = explode(",", $sf['field_choices']);
					$text .= "<table style='width:100%'>";
					$count = array();
					foreach($_res as $_rs)
					{
						$r = @unserialize($_rs['results_results']);
						if(!is_array($r) || !isset($r[$fn])){ continue; }
						$rr = @unserialize($r[$fn]);
						if(!is_array($rr)){ continue; }
						foreach($choices as $choice)
						{
							foreach($rr as $_r)
							{
								if(trim($choice) == trim($_r))
								{
									if(!isset($count[$choice])){ $count[$choice] = 0; }
									$count[$choice]++;
								}
							}
						}
					}
					foreach($choices as $choice)
					{
						if(!isset($count[$choice]) || $count[$choice] == ""){ $count[$choice] = 0; }
						$per = sprintf("%.1f", $count[$choice] / $numresults * 100);
						$text .= "<tr><td style='width:40%'>".htmlspecialchars($choice)."</td><td style='width:35%; text-align:right;'>{$count[$choice]}</td><td style='width:25%; text-align:right;'>{$per}%</td></tr>";
					}
					$text .= "</table>";
					unset($count);
					$text .= "</td></tr>";
					break;
				case(4):  // radio
				case(5):  // dropdown
					$choices = explode(",", $sf['field_choices']);
					$text .= "<table style='width:100%'>";
					$chartLabels = array();
					$chartData = array();
					foreach($choices as $choice){
						$cnt = 0;
						foreach($_res as $_r){
							$r = @unserialize($_r['results_results']);
							if(is_array($r) && isset($r[$fn]) && trim($r[$fn]) == trim($choice)){
								$cnt++;
							}
						}
						$chartLabels[] = trim($choice);
						$chartData[] = $cnt;
						$per = sprintf("%.1f", $cnt / $numresults * 100);
						$text .= "<tr><td style='width:40%'>".htmlspecialchars($choice)."</td><td style='width:35%; text-align:right;'>{$cnt}</td><td style='width:25%; text-align:right;'>{$per}%</td></tr>";
					}
					$text .= "</table>";
					$text .= "<div class='mt-2'><canvas id='chart-field-{$fn}' height='60'></canvas></div>";
					$text .= "<script>
(function(){
	new Chart(document.getElementById('chart-field-{$fn}'), {
		type: 'bar',
		data: {
			labels: " . json_encode($chartLabels) . ",
			datasets: [{
				label: '" . addslashes($sf['field_text']) . "',
				data: " . json_encode($chartData) . ",
				backgroundColor: 'rgba(13,110,253,0.5)',
				borderColor: '#0d6efd',
				borderWidth: 1
			}]
		},
		options: {
			responsive: true,
			plugins: { legend: { display: false } },
			scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
		}
	});
})();
</script>";
					$text .= "</td></tr>";
					break;
			}
		}
		$i++;
	}
	$text .= "</table>";
}
else
{
	$text .= "<br /><div style='text-align:center;'>".LAN_SUR15.".</div>";
}

$ns -> tablerender("results of survey: ".htmlspecialchars($survey_name), $text);
require_once(FOOTERF);
