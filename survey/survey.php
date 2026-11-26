<?php
/*
+---------------------------------------------------------------+
|	e107 website system
|	survey.php - Front-end form display + submission
|	Bootstrap 5.3 grid layout + e107 captcha + GDPR
+---------------------------------------------------------------+
*/
require_once(__DIR__.'/../../class2.php');
require_once(e_HANDLER."userclass_class.php");
require_once(e_HANDLER."form_handler.php");
require_once(__DIR__.'/survey.inc.php');
e107::includeLan(e_PLUGIN.'survey/languages/'.e_LANGUAGE.'/'.e_LANGUAGE.'_front.php');
e107::coreLan('np');
e107::lan('core', 'contact'); // For GDPR strings (LAN_CONTACT_21-24)

function np($url, $from, $view, $total, $td, $qs=""){
	if($total == 0){ return; }
	echo "<table style='width:100%'><tr>";
	if($from > 1){
		$s = $from-$view;
		echo "<td style='width:33%' class='pagination'>";
		$text = ($qs != "")
			? "<div style='text-align:left'><span class='smalltext'><a href='".htmlspecialchars($url."?{$qs}.{$s}")."' >".NP_1."</a></span></div>"
			: "<div style='text-align:left'><span class='smalltext'><a href='".htmlspecialchars($url."?{$s}")."' >".NP_1."</a></span></div>";
		echo $text;
	}else{
		echo "<td style='width:33%'>&nbsp;</td>";
	}
	echo "<td style='width:34%' class='pagination'>";
	$start = $from+1;
	$finish = min($from+$view, $total);
	echo "<div style='text-align:center'><span class='smalltext'>$td $start - $finish of $total</span></div>";
	$s = $from+$view;
	if($s < $total){
		echo "</td><td style='width:33%' class='pagination'>";
		$text = ($qs != "")
			? "<div style='text-align:right'><span class='smalltext'><a href='".htmlspecialchars($url."?{$qs}.{$s}")."' >".NP_2."</a></span></div>"
			: "<div style='text-align:right'><span class='smalltext'><a href='".htmlspecialchars($url."?{$s}")."' >".NP_2."</a></span></div>";
		echo $text."</td>";
	}else{
		echo "</td><td style='width:33%'>&nbsp;</td>";
	}
	echo "</tr></table>";
}

require_once(HEADERF);
$arg = explode(".", e_QUERY);

function getfield($f){
	global $fdata;
	if(!is_array($fdata)){ return null; }
	foreach($fdata as $field){
		if($f == $field['field_number']){
			return $field;
		}
	}
	return null;
}

function parse_survey_message($message){
	$tmp = explode("\n", $message);
	for($c=0; $c < count($tmp); $c++){
		if(preg_match("/\{(.*?)=(.*?)\}/", $tmp[$c], $m)){
			$field = getfield($m[2]);
			if($field !== null){
				if($m[1] == "Q"){ $ret .= show_form_question($field); }
				if($m[1] == "F"){ $ret .= show_form_field($field); }
			}
		}else{
			$ret .= $tmp[$c];
		}
	}
	return $ret;
}

function isImage($url)
{
	$parsed = parse_url($url);
	if(!$parsed || !isset($parsed['scheme']) || !in_array($parsed['scheme'], array('http', 'https'))) return false;
	if(!isset($parsed['host'])) return false;
	$ip = gethostbyname($parsed['host']);
	if($ip === $parsed['host']) return false;
	if(filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) return false;
	$params = array('http' => array('method' => 'HEAD', 'timeout' => 5, 'ignore_errors' => true));
	$ctx = stream_context_create($params);
	$fp = @fopen($url, 'rb', false, $ctx);
	if(!$fp) return false;
	$meta = stream_get_meta_data($fp);
	if($meta === false){ fclose($fp); return false; }
	$wrapper_data = $meta["wrapper_data"];
	if(is_array($wrapper_data)){
		foreach(array_keys($wrapper_data) as $hh){
			if(isset($wrapper_data[$hh]) && substr($wrapper_data[$hh], 0, 19) == "Content-Type: image"){
				fclose($fp);
				return true;
			}
		}
	}
	fclose($fp);
	return false;
}

function show_survey($snum)
{
	global $sql, $ns, $tell_required, $_res, $fdata, $survey_class, $tp;
	$snum = intval($snum);
	if($sql->db_Select("survey","*","survey_id={$snum}"))
	{
		if($row = $sql->db_Fetch())
		{
			if(!is_array($row)){ return; }
			if(!check_class($row['survey_class']))
			{
				$ns->tablerender("Error - ".htmlspecialchars($row['survey_name']), LAN_SUR6);
				return;
			}
			if($row['survey_class'] != e_UC_PUBLIC && $row['survey_once'])
			{
				if(already_voted($row['survey_user']))
				{
					$ns->tablerender("Error - ".htmlspecialchars($row['survey_name']), LAN_SUR2);
					return;
				}
			}
			$fdata = survey_load_fields($row['survey_parms']);
			$frm = new myform;
			$ret .= $frm->form_open("post", e_SELF);
			$ret .= "<input type='hidden' name='e-token' value='".defset('e_TOKEN')."' />";
			$ret .= $frm->form_hidden("survey_id", $row['survey_id']);

			// View/Stats buttons
			if(check_class($row['survey_viewclass']) && $row['survey_save_results'])
			{
				$ret .= "<div class='text-center mb-3'>";
				$ret .= "<a class='btn btn-outline-primary me-2' href='".e_PLUGIN."survey/view.php?{$row['survey_id']}' >".LAN_SUR1."</a>";
				$ret .= "<a class='btn btn-outline-primary' href='".e_PLUGIN."survey/stats.php?{$row['survey_id']}' >".LAN_SUR13."</a>";
				$ret .= "</div>";
			}

			// Survey message
			if(!empty($row['survey_message']))
			{
				if(preg_match("/\{Q=(\d+?)\}/", $row['survey_message']))
				{
					$ret .= "<div class='mb-3'>".parse_survey_message($row['survey_message'])."</div>";
				}
				else
				{
					$ret .= "<div class='mb-3'>{$row['survey_message']}</div>";
				}
			}

			// Fields with grid layout
			$ret .= survey_render_fields($fdata);

			// Captcha (e107 built-in)
			if(!empty($row['survey_captcha']))
			{
				$ret .= survey_render_captcha();
			}

			// GDPR consent (e107 built-in)
			if(!empty($row['survey_gdpr']))
			{
				$ret .= survey_render_gdpr();
			}

			// Submit
			$ret .= "<div class='mb-3 text-muted'>".LAN_SUR4."</div>";
			$ret .= "<div class='d-grid'>";
			$ret .= "<button type='submit' name='submit' value='submit' class='btn btn-primary'>".LAN_SUR5."</button>";
			$ret .= "</div>";
			$ret .= $frm->form_close();
		}
		if(!empty($row))
		{
			$ns->tablerender($row['survey_name'], survey_bs3_wrap($ret));
		}
	}
}

// ---- Submit handler ----
if(!empty($_POST['submit']))
{
	$tokenOk = !e_session::modeUsesToken() || !empty($_POST['e-token']);
	if(!$tokenOk)
	{
		$ns->tablerender(LAN_SUR9, "<div class='alert alert-danger'>Invalid security token. Please go back and try again.</div>");
		require_once(FOOTERF);
		exit;
	}

	$cnv = e107::getDate();
	$mailto_addresses = "";
	$submit_time = time();
	$mailtext = LAN_SUR42.": ".$cnv->convert_date($submit_time, "long")."\n\n";

	$sid = intval($_POST['survey_id'] ?? 0);
	if($sid > 0 && $sql->db_Select("survey","*","survey_id={$sid}"))
	{
		$row = $sql->db_Fetch();
		if(!is_array($row)){ require_once(FOOTERF); exit; }
	}
	if(isset($row) && is_array($row)){
		$survey_class          = $row['survey_class'];
		$survey_once           = $row['survey_once'];
		$survey_user           = $row['survey_user'];
		$survey_parms          = $row['survey_parms'];
		$survey_forum          = $row['survey_forum'];
		$survey_mailto         = $row['survey_mailto'];
		$survey_save_results   = $row['survey_save_results'];
		$survey_name           = $row['survey_name'];
		$survey_submit_message = $row['survey_submit_message'];
		$survey_viewclass      = $row['survey_viewclass'];
		$survey_captcha        = intval($row['survey_captcha'] ?? 0);
		$survey_gdpr           = intval($row['survey_gdpr'] ?? 0);
	}

	// ---- CAPTCHA verification (e107 built-in) ----
	if($survey_captcha)
	{
		$sec_img = e107::getSecureImg();
		$captchaErr = $sec_img->invalidCode(
			$_POST['rand_num'] ?? '',
			$_POST['code_verify'] ?? '',
			'survey'
		);
		if($captchaErr)
		{
			$reshow = 1;
			$ns->tablerender("Error - ".htmlspecialchars($survey_name), "<div class='alert alert-danger'>".LAN_SUR115."</div>");
			// Re-render form with fresh captcha
			show_survey($sid);
			require_once(FOOTERF);
			exit;
		}
	}

	// ---- GDPR verification ----
	if($survey_gdpr)
	{
		$pp = e107::getPref('gdpr_privacypolicy', '');
		if(!empty($pp) && empty($_POST['survey_gdpr']))
		{
			$reshow = 1;
			$ns->tablerender("Error - ".htmlspecialchars($survey_name), "<div class='alert alert-danger'>".LAN_SUR116."</div>");
			show_survey($sid);
			require_once(FOOTERF);
			exit;
		}
	}

	if($survey_class != e_UC_PUBLIC && $survey_once)
	{
		if(already_voted($survey_user))
		{
			$ns->tablerender("Error - ".htmlspecialchars($survey_name), LAN_SUR2);
			require_once(FOOTERF);
			exit;
		}
	}

	if(!check_class($survey_class))
	{
		$ns->tablerender("Error - ".htmlspecialchars($survey_name), LAN_SUR6);
		require_once(FOOTERF);
		exit;
	}

	$parms = survey_load_fields($survey_parms);

	$reshow = 0;
	$_res = array();
	foreach($parms as $parm)
	{
		$fn = $parm['field_number'];
		$v = $_POST['results'][$fn] ?? '';
		$ptype = intval($parm['field_type']);

		// ---- Required field checks ----
		if($parm['field_req'])
		{
			switch($ptype)
			{
				case 1: case 2: case 7: case 8: case 10: case 11: case 12:
				case 14: case 16: case 17: case 19: case 20: case 21: case 23:
					if(is_string($v) && ltrim(rtrim($v)) == "")
					{
						$tell_required[$fn] = 1;
						$reshow = 1;
					}
					break;
				case 3:
					if(empty($v) || !is_array($v))
					{
						$tell_required[$fn] = 1;
						$reshow = 1;
					}
					break;
				case 4: case 5:
					if(empty($v) || $v === '---')
					{
						$tell_required[$fn] = 1;
						$reshow = 1;
					}
					break;
				case 18: // color
					if(is_string($v) && ltrim(rtrim($v)) == "")
					{
						$tell_required[$fn] = 1;
						$reshow = 1;
					}
					break;
			}
		}

		// ---- Field content validation ----
		switch($ptype)
		{
			case 12: // emailto
				if($v && is_string($v) && !filter_var($v, FILTER_VALIDATE_EMAIL))
				{
					$error_text[$fn] = LAN_SUR40;
					$reshow = 1;
				}
				else
				{
					if(!empty($v)){ $mailto_addresses .= $v.","; }
				}
				break;
			case 10: // email
				if($v && is_string($v) && !filter_var($v, FILTER_VALIDATE_EMAIL))
				{
					$error_text[$fn] = LAN_SUR40;
					$reshow = 1;
				}
				break;
			case 11: // number
				if(!empty($v) && !is_numeric($v))
				{
					$error_text[$fn] = LAN_SUR41;
					$reshow = 1;
				}
				break;
			case 16: // phone
				if(!empty($v) && !preg_match('/^[+\d\s\-()]{7,20}$/', $v))
				{
					$error_text[$fn] = LAN_SUR105;
					$reshow = 1;
				}
				break;
			case 17: // url
				if(!empty($v) && !filter_var($v, FILTER_VALIDATE_URL))
				{
					$error_text[$fn] = LAN_SUR106;
					$reshow = 1;
				}
				break;
			case 23: // password with confirm
				$parts = explode(",", $parm['field_choices']);
				if(intval($parts[0] ?? 0) == 1)
				{
					$confirm = $_POST['results_confirm'][$fn] ?? '';
					if($v !== $confirm)
					{
						$error_text[$fn] = LAN_SUR113;
						$reshow = 1;
					}
				}
				break;
		}

		// ---- Store result ----
		if($ptype == 3) // checkbox
		{
			if(is_array($v)){
				$ser = array();
				foreach($v as $x){ $ser[] = is_scalar($x) ? $x : ''; }
				$_res[$fn] = serialize($ser);
			} else {
				$_res[$fn] = '';
			}
		}
		elseif($ptype == 14) // signature (base64 image)
		{
			// Store as-is (data URI)
			$_res[$fn] = is_string($v) ? $v : '';
		}
		elseif($ptype == 15) // file upload (handled separately)
		{
			$_res[$fn] = '';
		}
		else
		{
			$_res[$fn] = is_scalar($v) ? $v : '';
		}

		// ---- Mail text ----
		switch($ptype)
		{
			case 3:
				if(is_array($v)){ $mailtext .= "{$parm['field_text']}: ".implode(", ", $v)." \n"; }
				break;
			case 6:
				$mailtext .= "{$parm['field_text']} \n";
				break;
			case 12:
				break;
			case 14: // signature
				$mailtext .= "{$parm['field_text']}: [signature provided] \n";
				break;
			case 15: // file upload
				$mailtext .= "{$parm['field_text']}: [file uploaded] \n";
				break;
			case 22: // html block
				break;
			default:
				if(is_scalar($v) && $v !== ''){
					$mailtext .= "{$parm['field_text']}: {$v} \n";
				}
				break;
		}
	}

	// ---- Handle file uploads ----
	if(!empty($_FILES['results']) && is_array($_FILES['results']))
	{
		$uploadDir = e_MEDIA."uploads/survey/".intval($sid)."/";
		if(!is_dir($uploadDir)){ @mkdir($uploadDir, 0755, true); }
		foreach($_FILES['results'] as $fileFn => $fileData)
		{
			if($fileData['error'] === UPLOAD_ERR_OK && $fileData['size'] > 0)
			{
				// Sanitize filename
				$ext = strtolower(pathinfo($fileData['name'], PATHINFO_EXTENSION));
				$safeName = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $fileData['name']);
				$destName = time()."_".mt_rand(1000,9999)."_".$safeName;
				$dest = $uploadDir.$destName;
				if(move_uploaded_file($fileData['tmp_name'], $dest))
				{
					$_res[$fileFn] = 'uploads/survey/'.intval($sid)."/".$destName;
				}
			}
		}
	}

	if($reshow)
	{
		$ns->tablerender("Error - ".htmlspecialchars($survey_name), "<div class='alert alert-warning'>".LAN_SUR4."</div>");
		show_survey($sid);
		require_once(FOOTERF);
		exit;
	}

	// Forum posting
	if($survey_forum && e107::isInstalled('forum'))
	{
		require_once(e_PLUGIN."forum/forum_class.php");
		$survey_forum_obj = new e107forum();
		$postInfo = array();
		$threadInfo = array();
		if(USER)
		{
			$postInfo['post_user'] = USERID;
			$threadInfo['thread_lastuser'] = USERID;
			$threadInfo['thread_user'] = USERID;
			$threadInfo['thread_lastuser_anon'] = '';
		}
		else
		{
			$anonname = htmlspecialchars($_POST['anonname'] ?? '');
			$postInfo['post_user_anon'] = $anonname;
			$threadInfo['thread_lastuser_anon'] = $anonname;
			$threadInfo['thread_user_anon'] = $anonname;
		}
		$postInfo['post_ip'] = e107::getIPHandler()->getIP(FALSE);
		$time = time();
		$newMailText = array();
		$checkLinks = explode(" ", $mailtext);
		foreach($checkLinks as $convertLink)
		{
			if(preg_match("/\bhttp\b/i", $convertLink))
			{
				if(isImage($convertLink))
				{
					$convertLink = "<img src='".htmlspecialchars($convertLink)."' />";
				}
			}
			$newMailText[] = $convertLink;
		}
		$mailtext = implode(" ", $newMailText);
		$postInfo['post_entry'] = $tp->toDB($mailtext);
		$postInfo['post_forum'] = $survey_forum;
		$postInfo['post_datestamp'] = $time;
		$threadInfo['thread_lastpost'] = $time;
		$threadInfo['thread_sticky'] = 0;
		$threadInfo['thread_name'] = $survey_name;
		$threadInfo['thread_forum_id'] = $survey_forum;
		$threadInfo['thread_active'] = 1;
		$threadInfo['thread_datestamp'] = $time;
		$threadInfo['thread_options'] = '';
		$survey_forum_obj->threadAdd($threadInfo, $postInfo);
		$survey_forum_obj->forumUpdateCounts($survey_forum);
	}

	// Mail to admin
	if($survey_mailto)
	{
		require_once(e_HANDLER."mail.php");
		sendemail($survey_mailto, LAN_SUR7." {$survey_name}", strip_tags($mailtext));
	}

	// Mail to emailto fields
	if($mailto_addresses)
	{
		require_once(e_HANDLER."mail.php");
		sendemail($mailto_addresses, LAN_SUR7." {$survey_name}", strip_tags($mailtext));
	}

	// Track voted users
	if(USER == TRUE && !already_voted($survey_user))
	{
		$survey_user .= ".".USERID;
		$sql->db_Update("survey", "survey_user='{$survey_user}' WHERE survey_id={$sid}");
	}

	// Save results - clean serialized string, db_Insert binds it safely.
	$results = serialize($_res);
	$sql->db_Insert("survey_results", array(
		'results_datestamp' => $submit_time,
		'results_survey_id' => $sid,
		'results_results'   => $results
	));

	$text = LAN_SUR8;
	if($survey_submit_message)
	{
		$text = $tp->toHTML($survey_submit_message, true);
	}

	if(check_class($survey_viewclass) && $survey_save_results)
	{
		$text .= "<br /><br /><div class='text-center'>";
		$text .= "[<a href='".e_PLUGIN."survey/view.php?{$sid}' >".LAN_SUR1."</a>]";
		$text .= "</div>";
	}

	$ns->tablerender(LAN_SUR9, survey_bs3_wrap("<div class='alert alert-success'>".$text."</div>"));
	require_once(FOOTERF);
	exit;
}

show_survey($arg[0] ?? '');
require_once(FOOTERF);
