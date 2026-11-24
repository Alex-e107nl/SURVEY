# Survey Plugin for e107 CMS

A modern, security-hardened survey and form builder plugin for e107 CMS 2.4 — visual drag-and-drop field builder, results management, statistics, and multi-format export.

---

## Key Features

- **Visual Field Builder** — drag-and-drop interface with 23 field types (text, textarea, checkboxes, radio, dropdown, date, email, number, file upload, signature, color picker, range slider, time picker, country selector, HTML block, password, and more)
- **Results Management** — view, search, filter, edit, and delete individual responses; paginated list view
- **Statistics & Charts** — per-field response distribution with visual breakdowns
- **Multi-Format Export** — download results as **Excel (.xls)**, **Word (.doc)**, **JSON**, or **print-friendly HTML**
- **Survey Import/Export (JSON)** — migrate surveys between sites with a single JSON file
- **Email Notification** — optionally email results to one or more addresses on submission
- **Forum Integration** — post results as a new forum thread (requires e107 Forum plugin)
- **GDPR Consent** — optional consent checkbox per survey
- **CAPTCHA** — optional bot protection per survey
- **Access Control** — per-survey visibility, viewing, and editing user classes; one-time vote enforcement
- **Menu Manager Integration** — embed any survey as a placeable menu anywhere on the site
- **Multi-Language** — ships with English, Dutch, French, Italian, Norwegian, and Spanish translations
- **Security Hardened** — CSRF tokens on all forms, SQL parameterized queries, XSS output escaping, SSRF protection, `eval()` elimination, CRLF header injection prevention
- **Modern UI** — Bootstrap 5.3, inline SVG icons (no icon font), responsive layout

---

## Tech Stack

| Layer | Technology |
|-------|-----------|
| CMS Platform | [e107 CMS](https://e107.org) 2.4+ |
| Server Language | PHP 8.0+ (tested on 8.3) |
| Database | MySQL 5.7+ / MariaDB 10.3+ (InnoDB, utf8mb4) |
| Front-end | Bootstrap 5.3, vanilla JavaScript |
| Icons | Inline SVG (no external icon font) |
| Calendar | Dynarch Calendar (bundled, backward-compatible) |

---

## Prerequisites

- **e107 CMS** version 2.4 or later (2.0+ minimum, 2.4 recommended)
- **PHP** 8.0 or later
- **MySQL** 5.7+ or **MariaDB** 10.3+
- **Web server** with PHP-FPM or mod_php (Apache, Nginx, etc.)
- For forum posting: e107 **Forum** plugin installed and active

---

## Installation

### 1. Upload the Plugin

Copy the `survey` folder into your e107 plugins directory:

```
your-site/
├── e107_core/
├── e107_admin/
├── e107_handlers/
├── e107_plugins/
│   └── survey/          ← place the entire folder here
│       ├── admin_config.php
│       ├── plugin.php
│       ├── plugin.xml
│       ├── survey.php
│       ├── survey.inc.php
│       ├── survey_sql.php
│       ├── images/
│       ├── languages/
│       └── ...
```

### 2. Install via e107 Admin

1. Log in to the e107 admin panel
2. Navigate to **Admin → Plugins → Install Plugin**
3. Select **Survey** from the available plugins list
4. Click **Install**

The installer will:
- Create the `survey` and `survey_results` database tables (InnoDB / utf8mb4)
- Register the plugin and its admin link
- Enable the plugin

### 3. Upgrade from 0.4.x

Upgrading from version 0.4.10 or earlier:

1. **Back up your existing survey folder** (recommended)
2. Replace the plugin files with the new version
3. Go to **Admin → Plugins → Manage Plugins**
4. Click **Upgrade** next to Survey

The upgrade process will:
- Add new columns (`survey_captcha`, `survey_gdpr`) automatically
- Preserve all existing surveys and results
- Update the plugin version record

> **Note:** No database data is lost during upgrade. Existing surveys, fields, and results remain intact.

### 4. Configure (Optional)

After installation:

1. Go to **Admin → Survey → Survey List**
2. Click **+ Create** to build your first survey
3. Add fields using the drag-and-drop palette
4. Configure access classes, email, forum, GDPR, and CAPTCHA settings
5. Click **Save**

---

## Configuration Reference

Each survey is configured individually in the admin builder. There are no global environment variables — all settings are per-survey and stored in the database.

| Setting | Type | Default | Description |
|---------|------|---------|-------------|
| **Survey Name** | Text (128) | — | Display name of the survey |
| **Access Class** | User class | Public | Who can view and submit the survey |
| **Allow Multiple** | Yes/No | No | If "No", each user can only submit once |
| **Save Results** | Yes/No | No | Store responses in the database |
| **Mail To** | Email | — | Comma-separated addresses for result notifications |
| **Forum** | Dropdown | None | Post results as a forum thread (requires Forum plugin) |
| **View Class** | User class | Public | Who can view the results page |
| **Edit Class** | User class | Public | Who can edit/delete individual results |
| **CAPTCHA** | Toggle | Off | Require a CAPTCHA challenge before submission |
| **GDPR Consent** | Toggle | Off | Require a GDPR consent checkbox |
| **Survey Message** | HTML | — | Custom intro HTML shown above the form |
| **Submit Message** | HTML | — | Thank-you message shown after submission |

### Field Types

| # | Type | Description |
|---|------|-------------|
| 1 | Text Field | Single-line text input |
| 2 | Text Area | Multi-line text input |
| 3 | Checkboxes | Multiple-select options |
| 4 | Radio Buttons | Single-select options |
| 5 | Dropdown | Select dropdown with options |
| 6 | Separator | Visual divider line |
| 7 | Date Picker | Calendar date input |
| 8 | Name | Full name (first + last) |
| 9 | Calculation | Formula field (safe math evaluation) |
| 10 | Email | Email address input |
| 11 | Number | Numeric input |
| 12 | Email-to | Additional notification email field |
| 13 | Row Break | Full-width row separator |
| 14 | Signature | Freehand signature pad |
| 15 | File Upload | File attachment |
| 16 | Phone | Telephone number input |
| 17 | URL | Website URL input |
| 18 | Color Picker | Color selection |
| 19 | Range Slider | Numeric range slider |
| 20 | Time Picker | Time input |
| 21 | Country | Country dropdown selector |
| 22 | HTML Block | Raw HTML content (no label/params) |
| 23 | Password | Masked password input |

Each field supports: **label**, **required** toggle, **hidden** toggle, **column width** (full / half / third / quarter), and **choices** (for option-based fields).

---

## Usage

### Creating a Survey (Admin)

1. Navigate to **Admin → Survey → Survey List**
2. Click **+ Create**
3. Fill in the survey settings (name, access class, etc.)
4. Click **Save** — the field builder unlocks
5. Click field type buttons in the palette to add fields
6. Drag the grip handle to reorder fields
7. Click a field card to expand its settings (label, type, choices, required, column width)
8. Click the **×** button on a field to remove it
9. Click **Save** when done

### Embedding a Survey on the Front-End

**Option A — Direct URL:**

Each survey is accessible at:
```
https://yoursite.com/e107_plugins/survey/survey.php?{survey_id}
```

**Option B — Menu Manager (recommended):**

1. Go to **Admin → Menus → Menu Manager**
2. Create a new menu (or edit an existing one)
3. Set the menu type to **Survey**
4. Select the desired survey from the dropdown
5. Optionally set a custom caption
6. Save and place the menu in your theme layout

**Option C — PLUGIN Shortcode:**

In any text area that supports shortcodes (news items, pages, etc.):
```
{PLUGIN=survey/survey_menu|survey_id=2}
```

### Viewing Results

| Action | URL / Path |
|--------|-----------|
| Survey form | `e107_plugins/survey/survey.php?{id}` |
| Results (list) | `e107_plugins/survey/view.php?{id}` |
| Statistics | `e107_plugins/survey/stats.php?{id}` |
| Print view | `e107_plugins/survey/print.php?{id}` |
| Excel export | `e107_plugins/survey/excel.php?{id}` |
| Word export | `e107_plugins/survey/word.php?{id}` |
| Admin builder | `e107_plugins/survey/admin_config.php` |

### Importing / Exporting Surveys (JSON)

**Export:** On the Survey List page, click the **Export** button next to a survey. This downloads a `.json` file containing the survey settings and all field definitions.

**Import:** On the Survey List page, select a `.json` file and click **Import**. The survey is created with all settings and fields intact.

JSON structure:
```json
{
  "version": "1.0",
  "name": "Customer Feedback",
  "settings": {
    "class": 0,
    "once": 1,
    "viewclass": 0,
    "editclass": 0,
    "mailto": "admin@example.com",
    "forum": 0,
    "save_results": 1,
    "captcha": 0,
    "gdpr": 0,
    "message": "<p>Please share your feedback.</p>",
    "submit_message": "<p>Thank you!</p>"
  },
  "fields": [
    {
      "field_number": 1,
      "field_text": "Your Name",
      "field_req": 1,
      "field_hidden": 0,
      "field_type": 8,
      "field_choices": "",
      "field_col": 12
    },
    {
      "field_number": 2,
      "field_text": "How would you rate us?",
      "field_req": 0,
      "field_hidden": 0,
      "field_type": 4,
      "field_choices": "1,2,3,4,5",
      "field_col": 12
    }
  ]
}
```

---

## Database Schema

Two tables are created on install (prefix `{p}` = `MPREFIX`):

### `{p}survey`

| Column | Type | Description |
|--------|------|-------------|
| `survey_id` | INT UNSIGNED AUTO_INCREMENT | Primary key |
| `survey_name` | VARCHAR(128) | Display name |
| `survey_class` | TINYINT UNSIGNED | Access user class |
| `survey_once` | TINYINT(1) | One-time vote enforcement |
| `survey_viewclass` | TINYINT UNSIGNED | Results view class |
| `survey_editclass` | TINYINT UNSIGNED | Results edit class |
| `survey_mailto` | VARCHAR(255) | Notification email(s) |
| `survey_forum` | INT UNSIGNED | Forum ID for posting |
| `survey_save_results` | TINYINT(1) | Store results flag |
| `survey_captcha` | TINYINT(1) | CAPTCHA enabled |
| `survey_gdpr` | TINYINT(1) | GDPR consent enabled |
| `survey_user` | TEXT | Serialized user tracking (one-time votes) |
| `survey_parms` | TEXT | JSON-encoded field definitions |
| `survey_message` | TEXT | Intro HTML |
| `survey_submit_message` | TEXT | Thank-you HTML |
| `survey_lastfnum` | INT UNSIGNED | Last field number |

### `{p}survey_results`

| Column | Type | Description |
|--------|------|-------------|
| `results_id` | INT UNSIGNED AUTO_INCREMENT | Primary key |
| `results_datestamp` | INT UNSIGNED | Unix timestamp |
| `results_survey_id` | INT UNSIGNED | FK → survey (indexed) |
| `results_results` | TEXT | JSON-encoded response data |

Both tables use **InnoDB** engine with **utf8mb4_unicode_ci** collation.

---

## File Structure

```
survey/
├── admin_config.php      # Admin builder (survey list + field builder)
├── plugin.php            # Plugin metadata + install/upgrade logic
├── plugin.xml            # Modern plugin descriptor (e107 2.x)
├── survey.php            # Front-end survey form + submission handler
├── survey.inc.php        # Shared functions (form rendering, field loading, CAPTCHA, GDPR)
├── survey_menu.php       # Menu Manager renderer (placeable menu)
├── e_menu.php            # Menu Manager configuration (dropdown + caption fields)
├── view.php              # Results viewer (list, search, edit, delete)
├── stats.php             # Statistics & response distribution
├── print.php             # Print-friendly results view
├── excel.php             # Excel (.xls) export
├── word.php              # Word (.doc) export
├── help.php              # Admin help page
├── e_header.php          # e107 header hook
├── survey_sql.php        # Table DDL (InnoDB / utf8mb4)
├── survey_admin.css      # Admin builder styles
├── survey_admin.js       # Admin builder JS (drag-and-drop, field CRUD)
├── survey_cal.css        # Dynarch Calendar styles
├── survey_cal.js         # Dynarch Calendar (bundled)
├── survey.js             # Front-end JS
├── images/               # Plugin icons
└── languages/            # Translations
    ├── English/
    ├── Dutch/
    ├── French/
    ├── Italian/
    ├── Norwegian/
    └── Spanish/
```

---

## Security

The plugin is hardened against common web vulnerabilities:

| Threat | Mitigation |
|--------|-----------|
| **SQL Injection** | All user input cast with `intval()` before SQL; query builder with bound parameters for updates |
| **XSS** | `htmlspecialchars()` / `$tp->toHTML()` on all user data in HTML output; `$tp->toDB()` before DB writes |
| **CSRF** | `e-token` hidden field in every form; `$tokenOk` check gates all POST handlers |
| **Code Injection** | `eval()` eliminated — replaced with `survey_safe_math()` whitelist regex |
| **CRLF Header Injection** | Export filenames sanitized with `preg_replace('/[^a-zA-Z0-9_\-]/', '_')` |
| **SSRF** | `isImage()` checks URL scheme (http/https only), resolves DNS, blocks private IP ranges, 5-second timeout |
| **Regex Injection** | `preg_quote()` on all user-supplied search strings |
| **Deserialization** | All `unserialize()` calls prefixed with `@` and guarded with `is_array()` |

---

## Translations

The plugin ships with 6 language files. Each language has two files:

| File | Scope |
|------|-------|
| `{Lang}_admin.php` | Admin panel strings |
| `{Lang}_front.php` | Front-end / visitor strings |

Available: **English**, **Dutch**, **French**, **Italian**, **Norwegian**, **Spanish**

To add a new language:
1. Create a folder under `languages/` (e.g., `languages/German/`)
2. Copy the English files as a starting point
3. Translate the string values (keep the constant names unchanged)
4. Add an `index.html` file (empty) to prevent directory listing

---

## Contributing

Contributions are welcome! Please follow these guidelines:

### Reporting Issues

- Open an issue on the [e107.org forum](https://e107.org) or the plugin's GitHub repository
- Include: e107 version, PHP version, browser, steps to reproduce, and error messages (check browser console + e107 debug mode)

### Submitting Pull Requests

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/my-improvement`)
3. Ensure your code passes `php -l` (no syntax errors)
4. Follow the existing code style (tabs for indentation, single quotes for strings)
5. Update language files if you add new UI strings
6. Test on e107 2.4 + PHP 8.0 minimum
7. Submit a pull request with a clear description of the change

### Code Conventions

- **PHP**: No `extract()`, no `eval()`, no bare `$_POST` access without `?? ''`
- **Security**: All DB queries use `intval()` or query builder bound parameters
- **Output**: All user data escaped with `$tp->toHTML()` or `htmlspecialchars()`
- **Forms**: Every form includes an `e-token` CSRF hidden field
- **Language**: All user-facing strings use language constants (`LAN_*`, `ADLAN_*`)
- **File paths**: Use `__DIR__` or `e_PLUGIN` constants — no relative `../../` paths

### Testing Checklist

Before submitting, verify:
- [ ] `php -l` passes on all modified files
- [ ] Fresh install works (no existing survey data)
- [ ] Upgrade from previous version works (existing data preserved)
- [ ] Admin: create, edit, copy, delete survey
- [ ] Admin: add, reorder, edit, delete fields of all 23 types
- [ ] Front-end: fill and submit survey (all field types)
- [ ] Results: view, search, edit, delete records
- [ ] Stats page renders correctly
- [ ] Excel, Word, JSON exports produce valid files
- [ ] CSRF: direct POST without token is rejected
- [ ] No PHP warnings or notices in error log

---

## Upgrade Notes

### From 0.4.x to 1.0.0

This is a major update. Key changes:

- **PHP 8.0+ required** (0.4.x supported PHP 5.x)
- **e107 2.0+ required** (2.4 recommended)
- **InnoDB + utf8mb4** tables (was MyISAM + latin1)
- **`plugin.xml`** added for modern e107 2.x plugin registration
- **Bootstrap 5.3** admin UI (was legacy table-based layout)
- **JSON field storage** (was PHP serialized arrays)
- **CSRF tokens** on all forms (new `e-token` requirement)
- **Security hardening** (see [Security](#security) section)
- **New columns**: `survey_captcha`, `survey_gdpr` (auto-added on upgrade)

> **Your existing data is safe.** The upgrade process preserves all surveys, fields, and results. The admin builder auto-migrates missing columns on first load.

---

## License

Released under the terms and conditions of the [GNU General Public License v2 or later](https://www.gnu.org/licenses/old-licenses/gpl-2.0.html).

```
Copyright (c) 2004-2026 e107 Inc (e107.org)
Original author: McFly (patrickweaver@gmail.com)
Maintained by: septor

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
```

---

## Credits

- **Original author**: [McFly](http://painswitch.com/) — initial plugin development
- **Security & maintenance**: [septor](https://github.com/septor) — PHP 8.x compatibility, security hardening, modernization
- **e107 CMS**: [e107 Inc](https://e107.org) — platform and framework
- **Bootstrap**: [Bootstrap 5.3](https://getbootstrap.com/) — front-end framework
- **Dynarch Calendar**: [Dynarch.com](https://dynarch.com/) — bundled date picker (MIT license)
