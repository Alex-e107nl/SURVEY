<?php
/*
+---------------------------------------------------------------+
|	e107 website system
|	survey.inc.php - Shared form rendering functions
|	Bootstrap 5.3 compliant + new field types
+---------------------------------------------------------------+
*/

/**
 * Load survey field data from survey_parms column.
 * Supports both legacy PHP-serialize (a:N:{...) and new JSON ({...) storage.
 * @param string $data  Raw value from the DB column
 * @return array  Field definitions (empty array on failure)
 */
function survey_load_fields($data)
{
	if(!is_string($data) || $data === '') return array();
	$first = $data[0];
	if($first === '{' || $first === '[')
	{
		$dec = json_decode($data, true);
		return is_array($dec) ? $dec : array();
	}
	$u = @unserialize($data);
	return is_array($u) ? $u : array();
}

/**
 * Encode field definitions for storage in survey_parms column.
 * Uses JSON (Phase 5). Old PHP-serialize data is still readable via survey_load_fields().
 * @param array $fields
 * @return string
 */
function survey_save_fields(array $fields)
{
	return json_encode(array_values($fields), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

class myform extends form
{
	/** Bootstrap 5.3: form-select */
	function form_select($form_name, $form_options, $form_value)
	{
		$ret = "\n<select name='".htmlspecialchars($form_name)."' class='form-select'>";
		foreach($form_options as $o)
		{
			$sel = ($o == $form_value) ? " selected" : "";
			$ret .= "\n<option{$sel}>".htmlspecialchars($o)."</option>";
		}
		$ret .= "\n</select>";
		return $ret;
	}

	function form_text($form_name, $form_size, $form_value, $form_maxlength = FALSE, $form_class = 'form-control', $form_readonly = '', $form_tooltip = '', $form_js = '')
	{
		return parent::form_text($form_name, $form_size, $form_value, $form_maxlength, $form_class, $form_readonly, $form_tooltip, $form_js);
	}

	function form_textarea($form_name, $form_columns, $form_rows, $form_value, $form_js = '', $form_style = '', $form_wrap = '', $form_readonly = '', $form_tooltip = '')
	{
		$name = " id='".htmlspecialchars($form_name)."' name='".htmlspecialchars($form_name)."'";
		return "\n<textarea class='form-control' rows='".$form_rows."'".$name.'>'.htmlspecialchars($form_value).'</textarea>';
	}

	function form_checkbox($form_name, $form_value, $form_checked = 0, $form_tooltip = '', $form_js = '')
	{
		$id = htmlspecialchars($form_name.str_replace(['[',']',' '], '', $form_value));
		$checked = $form_checked ? ' checked' : '';
		return "\n<input class='form-check-input' type='checkbox' id='{$id}' name='".htmlspecialchars($form_name)."[]'"
			." value='".htmlspecialchars($form_value)."'{$checked} />";
	}

	function form_radio($form_name, $form_value, $form_checked = 0, $form_tooltip = '', $form_js = '')
	{
		$id = htmlspecialchars($form_name.str_replace(['[',']',' '], '', $form_value));
		$checked = $form_checked ? ' checked' : '';
		return "\n<input class='form-check-input' type='radio' id='{$id}' name='".htmlspecialchars($form_name)."'"
			." value='".htmlspecialchars($form_value)."'{$checked} />";
	}

	function _option_id($form_name, $form_value)
	{
		return $form_name.str_replace(['[',']',' '], '', $form_value);
	}
}

/**
 * Get the Bootstrap col class for a field.
 * @param array $field
 * @return string e.g. "col-12", "col-md-6"
 */
function survey_field_col_class($field)
{
	$col = intval($field['field_col'] ?? 12);
	if($col <= 3)  return 'col-12 col-md-3';
	if($col <= 4)  return 'col-12 col-md-4';
	if($col <= 6)  return 'col-12 col-md-6';
	return 'col-12';
}

function show_form_question($field)
{
	global $tell_required, $_res, $error_text;
	$fn = $field['field_number'];
	$label = '';
	if($field['field_req'])
	{
		$label .= '<span class="text-danger">*</span>&nbsp;';
	}
	$label .= htmlspecialchars($field['field_text']);
	if(!empty($tell_required[$fn])){ $label .= " <small class='text-danger'>".LAN_SUR3."</small>"; }
	if(!empty($error_text[$fn])){ $label .= " <small class='text-danger'>{$error_text[$fn]}</small>"; }
	return "<label class='form-label'>{$label}</label>";
}

/**
 * Get the country list for type 21
 * @return array
 */
function survey_get_countries()
{
	$countries = array(
		'AF'=>'Afghanistan','AL'=>'Albania','DZ'=>'Algeria','AD'=>'Andorra','AO'=>'Angola',
		'AG'=>'Antigua & Barbuda','AR'=>'Argentina','AM'=>'Armenia','AU'=>'Australia',
		'AT'=>'Austria','AZ'=>'Azerbaijan','BS'=>'Bahamas','BH'=>'Bahrain',
		'BD'=>'Bangladesh','BB'=>'Barbados','BY'=>'Belarus','BE'=>'Belgium',
		'BZ'=>'Belize','BJ'=>'Benin','BT'=>'Bhutan','BO'=>'Bolivia',
		'BA'=>'Bosnia & Herzegovina','BW'=>'Botswana','BR'=>'Brazil','BN'=>'Brunei',
		'BG'=>'Bulgaria','BF'=>'Burkina Faso','BI'=>'Burundi','KH'=>'Cambodia',
		'CM'=>'Cameroon','CA'=>'Canada','CF'=>'Central African Rep.','TD'=>'Chad',
		'CL'=>'Chile','CN'=>'China','CO'=>'Colombia','KM'=>'Comoros',
		'CG'=>'Congo','CD'=>'DR Congo','CR'=>'Costa Rica','CI'=>'Cote d\'Ivoire',
		'HR'=>'Croatia','CU'=>'Cuba','CY'=>'Cyprus','CZ'=>'Czechia',
		'DK'=>'Denmark','DJ'=>'Djibouti','DM'=>'Dominica','EC'=>'Ecuador',
		'EG'=>'Egypt','SV'=>'El Salvador','GQ'=>'Equatorial Guinea','ER'=>'Eritrea',
		'EE'=>'Estonia','SZ'=>'Eswatini','ET'=>'Ethiopia','FJ'=>'Fiji',
		'FI'=>'Finland','FR'=>'France','GA'=>'Gabon','GM'=>'Gambia',
		'GE'=>'Georgia','DE'=>'Germany','GH'=>'Ghana','GR'=>'Greece',
		'GD'=>'Grenada','GT'=>'Guatemala','GN'=>'Guinea','GY'=>'Guyana',
		'HT'=>'Haiti','HN'=>'Honduras','HU'=>'Hungary','IS'=>'Iceland',
		'IN'=>'India','ID'=>'Indonesia','IR'=>'Iran','IQ'=>'Iraq',
		'IE'=>'Ireland','IL'=>'Israel','IT'=>'Italy','JM'=>'Jamaica',
		'JP'=>'Japan','JO'=>'Jordan','KZ'=>'Kazakhstan','KE'=>'Kenya',
		'KI'=>'Kiribati','KP'=>'North Korea','KR'=>'South Korea','KW'=>'Kuwait',
		'KG'=>'Kyrgyzstan','LA'=>'Laos','LV'=>'Latvia','LB'=>'Lebanon',
		'LS'=>'Lesotho','LR'=>'Liberia','LY'=>'Libya','LT'=>'Lithuania',
		'LU'=>'Luxembourg','MG'=>'Madagascar','MW'=>'Malawi','MY'=>'Malaysia',
		'MV'=>'Maldives','ML'=>'Mali','MT'=>'Malta','MH'=>'Marshall Islands',
		'MR'=>'Mauritania','MU'=>'Mauritius','MX'=>'Mexico','FM'=>'Micronesia',
		'MD'=>'Moldova','MN'=>'Mongolia','ME'=>'Montenegro','MA'=>'Morocco',
		'MZ'=>'Mozambique','MM'=>'Myanmar','NA'=>'Namibia','NR'=>'Nauru',
		'NP'=>'Nepal','NL'=>'Netherlands','NZ'=>'New Zealand','NI'=>'Nicaragua',
		'NE'=>'Niger','NG'=>'Nigeria','MK'=>'North Macedonia','NO'=>'Norway',
		'OM'=>'Oman','PK'=>'Pakistan','PW'=>'Palau','PA'=>'Panama',
		'PG'=>'Papua New Guinea','PY'=>'Paraguay','PE'=>'Peru','PH'=>'Philippines',
		'PL'=>'Poland','PT'=>'Portugal','QA'=>'Qatar','RO'=>'Romania',
		'RU'=>'Russia','RW'=>'Rwanda','SA'=>'Saudi Arabia','SN'=>'Senegal',
		'RS'=>'Serbia','SC'=>'Seychelles','SL'=>'Sierra Leone','SG'=>'Singapore',
		'SK'=>'Slovakia','SI'=>'Slovenia','SB'=>'Solomon Islands','SO'=>'Somalia',
		'ZA'=>'South Africa','SS'=>'South Sudan','ES'=>'Spain','LK'=>'Sri Lanka',
		'SD'=>'Sudan','SR'=>'Suriname','SE'=>'Sweden','CH'=>'Switzerland',
		'SY'=>'Syria','TW'=>'Taiwan','TJ'=>'Tajikistan','TZ'=>'Tanzania',
		'TH'=>'Thailand','TL'=>'Timor-Leste','TG'=>'Togo','TO'=>'Tonga',
		'TT'=>'Trinidad & Tobago','TN'=>'Tunisia','TR'=>'Turkey',
		'TM'=>'Turkmenistan','TV'=>'Tuvalu','UG'=>'Uganda','UA'=>'Ukraine',
		'AE'=>'United Arab Emirates','GB'=>'United Kingdom','US'=>'United States',
		'UY'=>'Uruguay','UZ'=>'Uzbekistan','VU'=>'Vanuatu','VA'=>'Vatican City',
		'VE'=>'Venezuela','VN'=>'Vietnam','YE'=>'Yemen','ZM'=>'Zambia',
		'ZW'=>'Zimbabwe'
	);
	return $countries;
}

function show_form_field($field)
{
	global $sql, $ns, $tp, $tell_required, $_res, $survey_class, $style;
	$frm = new myform;
	$fn = $field['field_number'];
	$ret = '';
	switch($field['field_type'])
	{
		case 1:  // text
			$parts = explode(",", $field['field_choices']);
			$size = intval($parts[0] ?? 20) ?: 20;
			$maxlength = intval($parts[1] ?? 50) ?: 50;
			$ret .= $frm->form_text("results[".$fn."]", $size, htmlspecialchars($_res[$fn] ?? ''), $maxlength);
			break;

		case 2: // textarea
			$parts = explode(",", $field['field_choices']);
			$cols = intval($parts[0] ?? 45) ?: 45;
			$rows = intval($parts[1] ?? 5) ?: 5;
			$ret .= $frm->form_textarea("results[".$fn."]", $cols, $rows, $_res[$fn] ?? '');
			break;

		case 3:  // checkbox
			$options = array_map('trim', explode(",", $field['field_choices']));
			$checked_vals = @unserialize($_res[$fn] ?? '');
			if(!is_array($checked_vals)){ $checked_vals = array(); }
			foreach($checked_vals as $k => $v){ $checked_vals[$k] = trim($v); }
			foreach($options as $o)
			{
				$ch  = in_array($o, $checked_vals) ? 1 : 0;
				$id  = $frm->_option_id("results[".$fn."]", $o);
				$ret .= "<div class='form-check'>";
				$ret .= "<input class='form-check-input' type='checkbox' id='".htmlspecialchars($id)."' name='results[".$fn."][]' value='".htmlspecialchars($o)."'".($ch ? ' checked' : '')." />";
				$ret .= "<label class='form-check-label' for='".htmlspecialchars($id)."'>".htmlspecialchars($o)."</label>";
				$ret .= "</div>";
			}
			break;

		case 4:  // radio
			$options = array_map('trim', explode(",", $field['field_choices']));
			foreach($options as $o)
			{
				$ch = (($_res[$fn] ?? '') == $o) ? 1 : 0;
				$id = $frm->_option_id("results[".$fn."]", $o);
				$ret .= "<div class='form-check'>";
				$ret .= "<input class='form-check-input' type='radio' id='".htmlspecialchars($id)."' name='results[".$fn."]' value='".htmlspecialchars($o)."'".($ch ? ' checked' : '')." />";
				$ret .= "<label class='form-check-label' for='".htmlspecialchars($id)."'>".htmlspecialchars($o)."</label>";
				$ret .= "</div>";
			}
			break;

		case 5:  // dropdown
			$options = array_map('trim', explode(",", $field['field_choices']));
			$o = array('---');
			foreach($options as $x){ $o[] = $x; }
			$ret .= $frm->form_select("results[".$fn."]", $o, $_res[$fn] ?? '');
			break;

		case 6:  // separator
			$options = explode(",", $field['field_choices']);
			if(!empty($options[0]) && $options[0] == "menu")
			{
				$oldstyle = $style;
				if(!empty($options[1])){ $style = $options[1]; }
				ob_end_flush();
				ob_start();
				$ns->tablerender($tp->toHTML($field['field_text']), "");
				$ret .= ob_get_contents();
				ob_end_clean();
				$style = $oldstyle;
			}
			else
			{
				$ret .= "<hr class='my-3' />".$tp->toHTML($field['field_text']);
			}
			break;

		case 7:  // date
			$calfmt = ($field['field_choices'] == "dmy") ? "dd/mm/y" : "mm/dd/y";
			$calmsg = ($field['field_choices'] == "dmy") ? "dd/mm/yyyy" : "mm/dd/yyyy";
			$xdate  = htmlspecialchars($_res[$fn] ?? '');
			$ret  .= "<input type='date' class='form-control' name='results[".$fn."]' value='".htmlspecialchars($_res[$fn] ?? '')."' />";
			break;

		case 8:  // name
			if($survey_class)
			{
				$ret .= $frm->form_hidden("results[".$fn."]", USERNAME);
				$ret .= "<input class='form-control' type='text' value='".htmlspecialchars($tp->toHTML(USERNAME))."' readonly />";
			}
			else
			{
				$parts = explode(",", $field['field_choices']);
				$size = intval($parts[0] ?? 20) ?: 20;
				$maxlength = intval($parts[1] ?? 50) ?: 50;
				$ret .= $frm->form_text("results[".$fn."]", $size, htmlspecialchars($_res[$fn] ?? ''), $maxlength);
			}
			break;

		case 10:  // email
			$parts = explode(",", $field['field_choices']);
			$size = intval($parts[0] ?? 20) ?: 20;
			$maxlength = intval($parts[1] ?? 100) ?: 100;
			$ret .= "<input type='email' class='form-control' name='results[".$fn."]' value='".htmlspecialchars($_res[$fn] ?? '')."' maxlength='{$maxlength}' placeholder='email@example.com' />";
			break;

		case 11:  // number
			$parts = explode(",", $field['field_choices']);
			$min = $parts[0] ?? '';
			$max = $parts[1] ?? '';
			$minAttr = ($min !== '') ? " min='".intval($min)."' " : '';
			$maxAttr = ($max !== '') ? " max='".intval($max)."' " : '';
			$ret .= "<input type='number' class='form-control' name='results[".$fn."]' value='".htmlspecialchars($_res[$fn] ?? "")."'{$minAttr}{$maxAttr} />";
			break;

		case 12:  // emailto
			$parts = explode(",", $field['field_choices']);
			$size = intval($parts[0] ?? 20) ?: 20;
			$maxlength = intval($parts[1] ?? 100) ?: 100;
			$ret .= "<input type='email' class='form-control' name='results[".$fn."]' value='".htmlspecialchars($_res[$fn] ?? '')."' maxlength='{$maxlength}' placeholder='email@example.com' />";
			break;

		case 13:  // row break (no input)
			$ret = '';
			break;

		case 14:  // signature
			$parts = explode(",", $field['field_choices']);
			$sw = intval($parts[0] ?? 400) ?: 400;
			$sh = intval($parts[1] ?? 150) ?: 150;
			$prev = htmlspecialchars($_res[$fn] ?? '');
			$ret  = "<div class='signature-wrap' data-width='{$sw}' data-height='{$sh}'>";
			$ret .= "<canvas id='sig_{$fn}' class='border rounded bg-white' width='{$sw}' height='{$sh}' style='width:100%;max-width:{$sw}px;touch-action:none;'></canvas>";
			$ret .= "<input type='hidden' name='results[{$fn}]' id='sigval_{$fn}' value='{$prev}' />";
			$ret .= "<div class='mt-1'><button type='button' class='btn btn-sm btn-outline-secondary sig-clear' data-fn='{$fn}'>".LAN_SUR103."</button></div>";
			$ret .= "</div>";
			$ret .= "<script>
			(function(){
				var c = document.getElementById('sig_{$fn}');
				if(!c) return;
				var ctx = c.getContext('2d');
				ctx.lineWidth = 2;
				ctx.lineCap = 'round';
				ctx.lineJoin = 'round';
				ctx.strokeStyle = '#333';
				var drawing = false;
				var hasDrawn = c.dataset.drawn === '1';
				function pos(e){
					var r = c.getBoundingClientRect();
					var t = e.touches ? e.touches[0] : e;
					return { x: (t.clientX - r.left) * (c.width / r.width), y: (t.clientY - r.top) * (c.height / r.height) };
				}
				function start(e){ e.preventDefault(); drawing = true; var p = pos(e); ctx.beginPath(); ctx.moveTo(p.x, p.y); }
				function move(e){ if(!drawing) return; e.preventDefault(); var p = pos(e); ctx.lineTo(p.x, p.y); ctx.stroke(); }
				function end(e){
					if(!drawing) return;
					drawing = false;
					c.dataset.drawn = '1';
					document.getElementById('sigval_{$fn}').value = c.toDataURL('image/png');
				}
				c.addEventListener('mousedown', start);
				c.addEventListener('mousemove', move);
				c.addEventListener('mouseup', end);
				c.addEventListener('mouseleave', end);
				c.addEventListener('touchstart', start, {passive:false});
				c.addEventListener('touchmove', move, {passive:false});
				c.addEventListener('touchend', end);
				var clr = document.querySelector('.sig-clear[data-fn=\"{$fn}\"]');
				if(clr) clr.addEventListener('click', function(){
					ctx.clearRect(0,0,c.width,c.height);
					c.dataset.drawn = '0';
					document.getElementById('sigval_{$fn}').value = '';
				});
			})();
			</script>";
			break;

		case 15:  // file upload
			$parts = explode(",", $field['field_choices']);
			$maxMB = intval($parts[0] ?? 5) ?: 5;
			$exts = trim($parts[1] ?? 'pdf,jpg,jpeg,png,doc,docx');
			$extList = array_map('trim', explode(',', $exts));
			$accept = '.'.implode(',', $extList);
			$ret  = "<input type='file' class='form-control' name='results[{$fn}]' accept='".htmlspecialchars($accept)."'";
			$ret .= " style='max-width:400px;' />";
			$ret .= "<div class='form-text small'>".LAN_SUR104." (max {$maxMB}MB, ".htmlspecialchars($exts).")</div>";
			break;

		case 16:  // phone
			$ret .= "<input type='tel' class='form-control' name='results[".$fn."]' value='".htmlspecialchars($_res[$fn] ?? '')."' placeholder='+31 6 12345678' style='max-width:250px;' />";
			break;

		case 17:  // url
			$ret .= "<input type='url' class='form-control' name='results[".$fn."]' value='".htmlspecialchars($_res[$fn] ?? '')."' placeholder='https://example.com' />";
			break;

		case 18:  // color
			$ret .= "<input type='color' class='form-control form-control-color' name='results[".$fn."]' value='".htmlspecialchars($_res[$fn] ?? '#ffffff')."' style='max-width:80px;height:45px;' />";
			break;

		case 19:  // range
			$parts = explode(",", $field['field_choices']);
			$rmin = intval($parts[0] ?? 0);
			$rmax = intval($parts[1] ?? 100);
			$step = intval($parts[2] ?? 1) ?: 1;
			$rdef = intval($parts[3] ?? 0);
			$val = intval($_res[$fn] ?? $rdef);
			$ret  = "<div class='d-flex align-items-center gap-2' style='max-width:400px;'>";
			$ret .= "<input type='range' class='form-range' name='results[{$fn}]' min='{$rmin}' max='{$rmax}' step='{$step}' value='{$val}' id='range_{$fn}' />";
			$ret .= "<span class='badge text-bg-secondary range-val' id='rangeval_{$fn}' style='min-width:2.5rem;'>{$val}</span>";
			$ret .= "</div>";
			$ret .= "<script>
			(function(){
				var r = document.getElementById('range_{$fn}');
				var v = document.getElementById('rangeval_{$fn}');
				if(r && v) r.addEventListener('input', function(){ v.textContent = r.value; });
			})();
			</script>";
			break;

		case 20:  // time
			$ret .= "<input type='time' class='form-control' name='results[".$fn."]' value='".htmlspecialchars($_res[$fn] ?? '')."' style='max-width:150px;' />";
			break;

		case 21:  // country
			$countries = survey_get_countries();
			$sel = htmlspecialchars($_res[$fn] ?? '');
			$ret  = "<select class='form-select' name='results[{$fn}]' style='max-width:300px;'>";
			$ret .= "<option value=''>--- " . LAN_SUR110 . " ---</option>";
			foreach($countries as $code => $name){
				$s = ($sel === $code) ? ' selected' : '';
				$ret .= "<option value='".htmlspecialchars($code)."'{$s}>".htmlspecialchars($name)."</option>";
			}
			$ret .= "</select>";
			break;

		case 22:  // html block (no input, render sanitized HTML)
			$html = $field['field_text'] ?? '';
			// Strip script tags and inline event handlers (admin is trusted, but sanitize)
			$html = preg_replace('/<script\b[^>]*>.*?<\/script\s*>/si', '', $html);
			$html = preg_replace('/\son\w+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html);
			$ret = $html;
			break;

		case 23:  // password
			$parts = explode(",", $field['field_choices']);
			$showConfirm = (intval($parts[0] ?? 0) == 1);
			$ret  = "<input type='password' class='form-control' name='results[{$fn}]' value='' placeholder='".LAN_SUR111."' style='max-width:300px;' />";
			if($showConfirm){
				$ret .= "<input type='password' class='form-control mt-2' name='results_confirm[{$fn}]' value='' placeholder='".LAN_SUR112."' style='max-width:300px;' />";
			}
			break;

		case 9:  // calculation (hidden, handled in PHP)
			$ret = '';
			break;
	}
	return $ret;
}

/**
 * Check if current user has already voted on this survey.
 */
function already_voted($userlist)
{
	if(USER == TRUE && !empty($userlist)){
		$voted = explode(".", $userlist);
		if(in_array(USERID, $voted)){ return TRUE; }
	}
	return FALSE;
}

/**
 * Render the captcha block using e107's built-in secure_image.
 */
function survey_render_captcha()
{
	$sec_img = e107::getSecureImg();
	$ret = "<div class='mb-3'>";
	$ret .= "<label class='form-label'>" . $sec_img->renderLabel() . "</label>";
	$ret .= "<div class='d-flex align-items-center gap-3'>";
	$ret .= $sec_img->renderImage('survey');
	$ret .= $sec_img->renderInput('survey');
	$ret .= "</div>";
	$ret .= "</div>";
	return $ret;
}

/**
 * Render the GDPR consent block using e107's built-in pref.
 */
function survey_render_gdpr()
{
	$pp = e107::getPref('gdpr_privacypolicy', '');
	if(empty($pp)) return '';
	$pp = e107::getParser()->replaceConstants($pp, 'full');
	$ret = "<div class='mb-3'>";
	$ret .= "<div class='form-check'>";
	$ret .= "<input class='form-check-input' type='checkbox' name='survey_gdpr' value='1' id='survey_gdpr_check' required />";
	$ret .= "<label class='form-check-label' for='survey_gdpr_check'>" . (defined('LAN_SUR118') ? LAN_SUR118 : 'I consent to having this website store my submitted information.') . "</label>";
	$ret .= "<div class='form-text'>" . e107::getParser()->lanVars(defined('LAN_SUR120') ? LAN_SUR120 : 'You can find our privacy policy here: [x]', "<a href='".htmlspecialchars($pp)."' target='_blank'>" . (defined('LAN_SUR119') ? LAN_SUR119 : 'Privacy Policy') . "</a>") . "</div>";
	$ret .= "</div>";
	$ret .= "</div>";
	return $ret;
}

/**
 * Render fields with Bootstrap grid layout.
 * Groups consecutive fields by column width into rows.
 */
function survey_render_fields($fdata)
{
	$ret = '';
	$colCounts = array(3 => 4, 4 => 3, 6 => 2, 12 => 1);
	$currentCol = 0;
	$currentCount = 0;

	foreach($fdata as $field)
	{
		if(!empty($field['field_hidden'])) continue;
		$ftype = intval($field['field_type']);

		if($ftype == 13)
		{
			$ret .= '</div>';
			$currentCol = 0;
			$currentCount = 0;
			continue;
		}

		if($ftype == 6 || $ftype == 22)
		{
			if($currentCol > 0) $ret .= '</div>';
			$currentCol = 0;
			$currentCount = 0;
			$ret .= '<div class="row"><div class="col-12 mb-3">';
			$ret .= show_form_field($field);
			$ret .= '</div></div>';
			continue;
		}

		if($ftype == 9) continue;

		$col = intval($field['field_col'] ?? 12);
		$colClass = survey_field_col_class($field);
		$perRow = isset($colCounts[$col]) ? $colCounts[$col] : 1;

		if($currentCol != $col || $currentCount >= $perRow)
		{
			if($currentCol > 0) $ret .= '</div>';
			$ret .= '<div class="row">';
			$currentCol = $col;
			$currentCount = 0;
		}

		$currentCount++;
		$ret .= "<div class='{$colClass} mb-3'>";
		$ret .= show_form_question($field);
		$ret .= show_form_field($field);
		$ret .= '</div>';
	}

	if($currentCol > 0) $ret .= '</div>';
	return $ret;
}
