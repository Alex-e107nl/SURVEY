# Survey Plugin — Modern Admin Builder & New Field Types

## Status

| Phase | Description | Status |
|-------|-------------|--------|
| Phase 1 | Modern Admin Builder UI (Bootstrap 5.3 + Vanilla JS Drag & Drop) | ✅ COMPLETED |
| Phase 2 | New Field Types (13–23) | ✅ COMPLETED |
| Phase 3 | Front-End Layout Engine (Bootstrap Grid) | ✅ COMPLETED |
| Phase 4 | Admin UI Polish (survey list, live preview, stats, import/export) | ✅ COMPLETED |
| Phase 5 | DB Schema Update (JSON field storage with backward compat) | ✅ COMPLETED |

## Current State

**Field types (12):** text, textarea, checkbox, radio, dropdown, separator, date, name, calculation, email, number, emailto

**Admin builder:** Legacy table-based UI (e107 1.x style). Fields listed as table rows with up/down arrows for reordering. Single text input for "parameters" (comma-separated). No drag-drop, no layout options, no visual feedback.

**Available in e107 2.4:**
- Bootstrap 5.3 (CSS grid, cards, collapse, etc.)
- **Built-in CAPTCHA**: `e107::getSecureImg()` → `secure_image` class. Sealed-token image captcha, one-use challenges, IP verification. No external services needed.
- **Built-in GDPR**: `gdpr_privacypolicy` site preference + checkbox pattern (used by contact form, signup form).
- Chart.js (for potential stats dashboards)
- **NO jQuery UI** — e107 is phasing out jQuery. All JS must be vanilla (HTML5 Drag & Drop API, Canvas API, etc.)

---

## Phase 1: Modern Admin Builder UI (Bootstrap 5.3 + Vanilla JS Drag & Drop)

### 1.1 — Card-based field editor
- [ ] Replace the legacy `<table class='fborder'>` with Bootstrap cards
- [ ] Each field becomes a `.card` with a header (field type icon + label) and body (settings)
- [ ] Use `<div class="col-12">` grid layout instead of table rows
- [ ] Add a "field palette" sidebar or top bar with clickable field type buttons to add new fields

### 1.2 — Drag & Drop reordering (Vanilla JS — HTML5 Drag & Drop API)
- [ ] **NO jQuery UI** — e107 is phasing out jQuery. Use pure vanilla JavaScript.
- [ ] Create `survey_admin.js` in the plugin folder, load via `e107::js('survey', 'survey_admin.js', 'jquery')` (the 'jquery' dependency param is fine for load ordering, but the JS itself uses no jQuery)
- [ ] Implement drag-drop with HTML5 Drag & Drop API:
  ```js
  // Each .field-card gets: draggable="true"
  // dragstart: set dataTransfer, add .dragging class (opacity 0.5)
  // dragover: preventDefault(), find insertion point, add .drop-target class
  // drop: reorder DOM, renumber field_number inputs
  // dragend: remove classes, trigger save-order POST
  ```
- [ ] Add a visual drag handle (⠿ or ≡) in card header — only the handle initiates drag
- [ ] On drop: renumber hidden `field_number` inputs sequentially, auto-save via fetch() POST
- [ ] CSS: `.field-card.dragging { opacity: 0.5; }` `.field-card.drop-target { border-top: 3px solid #0d6efd; }`
- [ ] Replace up/down arrow buttons with drag handle (keep arrows as fallback for accessibility)

### 1.3 — Per-field settings panel
- [ ] When a field card is clicked/expanded, show type-specific settings:
  - Text: size, maxlength
  - Textarea: rows, cols
  - Checkbox/Radio/Dropdown: one option per line (textarea), not comma-separated
  - Date: format (dmy/mym)
  - New types: their respective settings
- [ ] Use Bootstrap collapse (`data-bs-toggle="collapse"`) for expand/collapse

### 1.4 — Layout controls per field
- [ ] Add a "Column Width" selector per field: Full (col-12), Half (col-6), Third (col-4), Quarter (col-3)
- [ ] Store as `field_col` in the field array (default: 12)
- [ ] Front-end: wrap fields in `<div class="row"><div class="col-X">...</div></div>`
- [ ] Consecutive fields with same col-6 auto-pair side by side
- [ ] Add "Row Break" field type (type 13) to force a new row

### 1.5 — Field groups / sections
- [ ] Add "Section Header" field type (enhanced separator) with optional:
  - Accordion/collapse behavior
  - Icon selection
  - Background color
- [ ] Front-end renders as `<div class="card mb-3"><div class="card-header">...</div><div class="card-body">fields...</div></div>`

### 1.6 — JSON-based field storage (future-proofing)
- [ ] Migrate from `serialize($fields)` to `json_encode($fields)` for `survey_parms`
- [ ] Keep backward compatibility: detect `a:N:{` prefix → unserialize, else json_decode
- [ ] This allows nested structures (e.g., field groups containing sub-fields)
- [ ] Add `field_col`, `field_group`, `field_settings` (associative array) to each field

---

## Phase 2: New Field Types

### 2.1 — Signature (canvas)
- [ ] **Type 13:** Signature pad
- [ ] Front-end: HTML5 `<canvas>` with mouse/touch drawing (use [Signature Pad](https://github.com/szimek/signature_pad) or vanilla JS)
- [ ] Store as base64 PNG data URI in results
- [ ] Admin: preview thumbnail, "Clear" button
- [ ] Parameters: `width,height` (default: 400,150)
- [ ] Required validation: canvas not empty

### 2.2 — File Upload
- [ ] **Type 14:** File upload
- [ ] Front-end: `<input type="file">` with Bootstrap styling
- [ ] Parameters: `max_size_mb,allowed_extensions,multiple`
- [ ] Store file in `e107_media/uploads/survey/{survey_id}/`
- [ ] Result stores filename + path
- [ ] Admin: list uploaded files, download links
- [ ] Security: validate MIME type, sanitize filename, limit size

### 2.3 — Phone
- [ ] **Type 15:** Phone number
- [ ] Front-end: `<input type="tel">` with Bootstrap `form-control`
- [ ] Validation: regex `^[+\d\s\-()]{7,20}$`
- [ ] Parameters: `country_code_default` (optional)

### 2.4 — URL
- [ ] **Type 16:** URL
- [ ] Front-end: `<input type="url">`
- [ ] Validation: `filter_var($v, FILTER_VALIDATE_URL)`
- [ ] Parameters: none

### 2.5 — Color Picker
- [ ] **Type 17:** Color
- [ ] Front-end: `<input type="color">` (native HTML5)
- [ ] Store as hex `#rrggbb`
- [ ] Parameters: none

### 2.6 — Range / Slider
- [ ] **Type 18:** Range
- [ ] Front-end: `<input type="range" min="X" max="Y" step="Z">`
- [ ] Parameters: `min,max,step,default` (e.g., "0,100,1,50")
- [ ] Show current value beside slider
- [ ] Store as integer

### 2.7 — Time Picker
- [ ] **Type 19:** Time
- [ ] Front-end: `<input type="time">` (native HTML5)
- [ ] Store as `HH:MM`
- [ ] Parameters: none

### 2.8 — Country Selector
- [ ] **Type 20:** Country dropdown
- [ ] Front-end: `<select class="form-select">` with all countries
- [ ] Use e107's built-in country list (from `form_handler.php` → `getCountry()`)
- [ ] Store as ISO 3166-1 alpha-2 code
- [ ] Parameters: `country_code_default` (optional)

### 2.9 — GDPR Consent (e107 built-in)
- [ ] **Use e107's built-in GDPR system** — reads `gdpr_privacypolicy` site preference
- [ ] Admin survey settings: new checkbox "Require GDPR consent" (stored as `survey_gdpr` tinyint in survey table or in `survey_parms` JSON)
- [ ] Front-end rendering (when enabled, uses the same pattern as contact.php):
  ```php
  $pp = e107::getPref('gdpr_privacypolicy', '');
  if($pp) {
      $ret .= "<div class='form-group mb-3'>";
      $ret .= "<label for='survey_gdpr'>" . LAN_CONTACT_24 . "</label>";  // "GDPR Agreement"
      $ret .= "<div class='form-check'>";
      $ret .= "<label class='form-check-label'>";
      $ret .= "<input class='form-check-input' type='checkbox' name='survey_gdpr' value='1' required /> ";
      $ret .= LAN_CONTACT_21;  // "I consent to having this website store my submitted information..."
      $ret .= "</label>";
      $pp = e107::getParser()->replaceConstants($pp, 'full');
      $ret .= "<div class='form-text'>" . e107::getParser()->lanVars(LAN_CONTACT_23, "<a href='{$pp}' target='_blank'>" . LAN_CONTACT_22 . "</a>") . "</div>";
      $ret .= "</div></div>";
  }
  ```
- [ ] Backend validation on submit (when enabled):
  ```php
  if($survey_gdpr_enabled && empty($_POST['survey_gdpr'])) {
      // show error: "You must accept the GDPR consent to submit this form"
  }
  ```
- [ ] Language constants (already in e107 core `lan_contact.php`):
  - `LAN_CONTACT_21` = "I consent to having this website store my submitted information so they can respond to my inquiry"
  - `LAN_CONTACT_22` = "Privacy policy"
  - `LAN_CONTACT_23` = "You can find our privacy policy here: [x]"
  - `LAN_CONTACT_24` = "GDPR Agreement"
- [ ] Load contact language: `e107::lan('core', 'contact')` in survey.php (or define our own survey-specific strings)
- [ ] If `gdpr_privacypolicy` pref is empty (site hasn't configured it), skip the GDPR checkbox entirely
- [ ] Store consent: log timestamp + IP with the result (for legal compliance)

### 2.10 — HTML / Rich Text Block
- [ ] **Type 22:** HTML block (no input, just content)
- [ ] Admin: textarea for HTML content
- [ ] Front-end: renders raw HTML (sanitized)
- [ ] Use: instructions, info boxes, custom styling between fields
- [ ] Parameters: none (content stored in `field_text`)

### 2.11 — Captcha (e107 built-in secure_image)
- [ ] **Use e107's built-in captcha** — `e107::getSecureImg()` returns a `secure_image` instance
- [ ] **No external dependencies** (no reCAPTCHA, no hCaptcha, no Turnstile)
- [ ] Admin survey settings: new checkbox "Enable CAPTCHA" (stored as `survey_captcha` tinyint in survey table or in `survey_parms` JSON)
- [ ] Front-end rendering (when enabled):
  ```php
  $sec_img = e107::getSecureImg();
  // Image:
  $ret .= $sec_img->renderImage('survey');   // returns <img src="e107_images/secimg.php?id=...">
  // Input (hidden token + text field):
  $ret .= $sec_img->renderInput('survey');   // returns <input type="hidden" name="rand_num" ...><input type="text" name="code_verify" ...>
  // Label:
  $ret .= $sec_img->renderLabel();           // returns "Enter code" (LAN_ENTER_CODE)
  ```
- [ ] Backend validation on submit (when enabled):
  ```php
  $sec_img = e107::getSecureImg();
  $err = $sec_img->invalidCode(
      varset($_POST['rand_num'], ''),
      varset($_POST['code_verify'], ''),
      'survey'  // form name — scopes the challenge to survey forms
  );
  if($err) { /* show error, re-render form with FRESH captcha */ }
  ```
- [ ] **IMPORTANT**: Captcha is one-use (sealed token). On failed submission, must call `createCode()` again to get a fresh challenge — never reuse the spent token.
- [ ] Form name: use `'survey'` (string, not a constant) — scopes challenges so a survey captcha can't be spent on the contact form or vice versa.
- [ ] Language: `LAN_ENTER_CODE` = "Enter code", `LAN_INVALID_CODE` = "Incorrect code entered." (already in e107 core English.php)
- [ ] No visible field in the field list (survey-level setting, like a toggle)
- [ ] Parameters: none (it's a survey-level config, not a per-field thing)

### 2.12 — Password Field
- [ ] **Type 24:** Password
- [ ] Front-end: `<input type="password" class="form-control">`
- [ ] Parameters: `show_confirm` (0/1) — if 1, add a "confirm password" field
- [ ] Store hashed (password_hash) if save_results is enabled
- [ ] Never display in results view

---

## Phase 3: Front-End Layout Engine (Bootstrap Grid)

### 3.1 — Row/Column rendering
- [ ] Group fields by implicit rows:
  - Fields with `field_col=12` → own row
  - Consecutive fields with `field_col=6` → pair in one row
  - Consecutive fields with `field_col=4` → triple in one row
  - Consecutive fields with `field_col=3` → quad in one row
  - "Row Break" field (type 13) forces new row
- [ ] Output:
  ```html
  <div class="row">
    <div class="col-6 mb-3">Field A</div>
    <div class="col-6 mb-3">Field B</div>
  </div>
  <div class="row">
    <div class="col-12 mb-3">Field C</div>
  </div>
  ```

### 3.2 — Section rendering
- [ ] "Section Header" fields group subsequent fields until next section
- [ ] Render as Bootstrap card with header
- [ ] Optional: accordion (only one section open at a time)

### 3.3 — Responsive
- [ ] All fields stack on mobile (col-12 on < 576px)
- [ ] Use `col-md-6` etc. instead of `col-6` for responsive behavior

---

## Phase 4: Admin UI Polish

### 4.1 — Survey list page (existing surveys dropdown)
- [ ] Replace dropdown with a proper list/table
- [ ] Show: name, field count, response count, date created
- [ ] Action buttons: Edit, Copy, Delete, View Live
- [ ] Search/filter

### 4.2 — Live preview
- [ ] "Preview" button opens the front-end form in a modal or new tab
- [ ] Shows exactly what the user will see
- [ ] Updates on save

### 4.3 — Response stats dashboard
- [ ] Simple chart (Chart.js is available in e107_web) showing response count over time
- [ ] Per-field completion rate

### 4.4 — Import/Export
- [ ] Export survey definition as JSON
- [ ] Import survey from JSON
- [ ] Share between sites

---

## Phase 5: Database Schema Update

### 5.1 — New columns
- [ ] `survey_captcha` tinyint(1) unsigned NOT NULL default '0' — enable built-in e107 captcha
- [ ] `survey_gdpr` tinyint(1) unsigned NOT NULL default '0' — require GDPR consent checkbox
- [ ] No external keys needed (e107's captcha uses sealed tokens, GDPR reads site pref)
- [ ] OR store all in `survey_parms` JSON as `_settings` key (avoids schema change)

### 5.2 — Field array structure (JSON)
```json
{
  "fields": [
    {
      "number": 1,
      "type": 1,
      "text": "Your name",
      "required": true,
      "hidden": false,
      "col": 6,
      "settings": {"size": 30, "maxlength": 100}
    },
    {
      "number": 2,
      "type": 13,
      "text": "Section: Personal Info",
      "required": false,
      "hidden": false,
      "col": 12,
      "settings": {"accordion": true, "icon": "user"}
    }
  ],
  "settings": {
    "captcha": true,
    "gdpr": true
  }
}
```

---

## Implementation Priority

| Priority | Phase | Effort | Impact |
|----------|-------|--------|--------|
| **P0** | Phase 1 (Vanilla JS Drag-drop + Cards + Col layout) | Medium | High — modern UX |
| **P0** | Phase 3 (Front-end grid rendering) | Small | High — 2-col layouts |
| **P1** | Phase 2.11 (Captcha — e107 built-in) | Small | High — spam protection |
| **P1** | Phase 2.9 (GDPR — e107 built-in) | Small | High — legal compliance |
| **P1** | Phase 2.1 (Signature — vanilla canvas) | Medium | High — requested feature |
| **P2** | Phase 2.2 (File Upload) | Medium | Medium |
| **P2** | Phase 2.3-2.8 (Phone, URL, Color, Range, Time, Country) | Small each | Medium |
| **P2** | Phase 2.10 (HTML block) | Small | Medium |
| **P3** | Phase 1.6 (JSON migration) | Medium | Future-proofing |
| **P3** | Phase 4 (Polish: preview, stats, import/export) | Medium | Nice-to-have |

---

## Technical Notes

- **Drag & Drop**: Pure vanilla JavaScript (HTML5 Drag & Drop API). NO jQuery UI — e107 is phasing out jQuery. The JS file goes in the plugin folder and uses `document.querySelector`, `addEventListener('dragstart/dragover/drop/dragend')`.
- **Captcha**: e107 built-in `secure_image` class. Call `e107::getSecureImg()` to get the instance. Methods: `renderImage($form)`, `renderInput($form)`, `renderLabel()`, `invalidCode($token, $answer, $form)`. Uses sealed encrypted tokens (no DB storage), one-use challenges, IP verification. Form name `'survey'` scopes it to survey forms only.
- **GDPR**: e107 built-in. Site admin sets `gdpr_privacypolicy` preference (URL). We read it with `e107::getPref('gdpr_privacypolicy', '')`. If empty, skip the checkbox. Language strings in `lan_contact.php` (LAN_CONTACT_21 through _24).
- **Signature**: Vanilla JS canvas (~50 lines) — mouse/touch events on `<canvas>`, `toDataURL('image/png')` for storage. No library needed.
- **Backward compat**: Old `serialize()` data must still load. Detect by prefix (`a:` = PHP serialize, `{` = JSON).
- **Field type numbering**: Current 1-12. New types start at 13. Type 9 (calculation) and type 6 (separator) are special (no user input).
- **e107 2.4 pattern**: Use `e107::js('survey', 'filename.js', 'jquery')` to load plugin JS (the 'jquery' param just controls load order, the JS itself is dependency-free).
- **e107 2.4 captcha pattern** (from contact.php):
  ```php
  // Render:
  $sec_img = e107::getSecureImg();
  echo $sec_img->renderImage('survey');    // <img>
  echo $sec_img->renderInput('survey');    // <input hidden rand_num> + <input text code_verify>
  
  // Verify:
  if($sec_img->invalidCode($_POST['rand_num'], $_POST['code_verify'], 'survey')) {
      // error — show message, re-render form (new captcha auto-issued on render)
  }
  ```
- **e107 2.4 GDPR pattern** (from contact.php + contact_shortcodes.php):
  ```php
  // Render:
  $pp = e107::getPref('gdpr_privacypolicy', '');
  if($pp) {
      echo "<div class='form-check'>";
      echo "<input class='form-check-input' type='checkbox' name='survey_gdpr' value='1' required />";
      echo "<label class='form-check-label'>" . LAN_CONTACT_21 . "</label>";
      echo "<div class='form-text'>" . e107::getParser()->lanVars(LAN_CONTACT_23, "<a href='{$pp}'>" . LAN_CONTACT_22 . "</a>") . "</div>";
      echo "</div>";
  }
  
  // Verify:
  if(empty($_POST['survey_gdpr'])) { /* error */ }
  ```
