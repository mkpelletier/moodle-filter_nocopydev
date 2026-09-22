# No Copy / Dev Tools Filter (filter_nocopydev)

A Moodle filter plugin that prevents copy/paste, right-click context menus, and developer tools access when enabled. Designed as one layer in a multi-layered exam security strategy.

## Features

- Blocks copy, cut, and paste (keyboard shortcuts and browser events)
- Blocks right-click context menu
- Blocks developer tools shortcuts (F12, Ctrl+Shift+I/J/C)
- Blocks view source (Ctrl+U) and save page (Ctrl+S)
- Blocks text selection via CSS
- Works inside TinyMCE editors (hooks into TinyMCE's internal event system)
- Displays a full-page blocking overlay when JavaScript is disabled
- Per-context toggling: can be enabled/disabled at site, category, course, or activity level
- Quiz-attempt layers by question type (citation honeypot on essays; forensic watermark on MCQ, true/false, matching, and short answer)
- Citation honeypot configurable per category, course, or quiz

## Automated tests

PHPUnit tests live in `tests/`. GitHub Actions runs the standard [moodle-plugin-ci](https://github.com/moodlehq/moodle-plugin-ci) suite (phplint, phpcs, phpdoc, validate, savepoints, mustache, grunt, PHPUnit, Behat) against Moodle 5.0–5.2 on PHP 8.2/8.3 with PostgreSQL and MariaDB. See `.github/workflows/ci.yml`.

Locally, sync into a Moodle test site, then from that site’s root (after `php public/admin/tool/phpunit/cli/init.php` if the suite is missing):

```bash
php vendor/bin/phpunit --testsuite filter_nocopydev_testsuite
```

## Requirements

- Moodle 5.0+ (PHP 8.2+)

## Installation

1. Copy the `nocopydev` folder into `/path/to/moodle/filter/`
2. Visit **Site administration > Notifications** to install the plugin
3. Enable the filter at **Site administration > Plugins > Filters > Manage filters**

During development, after copying new JavaScript or language strings (especially when `version.php` has not changed), purge caches or Moodle will keep the old files. Local shortcut: `scripts/sync-local.sh` (rsyncs into the 5.0.7 and 5.3 trees and purges both).

## Configuration

The filter can be set to:

- **On** - Active everywhere on the site
- **Off, but available** - Can be selectively enabled per category, course, or activity

For exam security, a typical setup is to set it to "Off, but available" at the site level and enable it on specific quiz activities.

Copy, paste, and the context menu are blocked only for the roles chosen under **Site administration → Plugins → Filters → No Copy / Dev Tools** (Student by default). Teachers reviewing or reporting on attempts are not locked unless they also have a restricted role in that course. **Preview quiz** (an in-progress attempt page) still applies the lockdown so staff can test the filter. **Switch role to Student** also applies it.

Print / “Save as PDF” from the print dialog is blanked with `@media print`. Safari **File → Export as PDF** does not use print CSS (it snapshots the screen). That path cannot be made blank without hiding the quiz from the student; a graphical Safari PDF will still show the questions and should carry the forensic watermark. Author colours in the stem (for example a red letter in a Hebrew gapfill) are left intact, and answer inputs nested in the stem stay typeable.

### Citation honeypot (per course)

On quiz attempt pages, **essay** questions receive a hidden scholarly instruction: a **real author** paired with a **fictional work**. It is white-on-white at the end of the stem (copy/select is blocked). Tools that scrape visible page text — including Brave Leo — often cite it. A marker who knows the field will spot the fake title immediately. Screen readers will speak it; faculty should not treat that citation as proof of cheating for a known reader user. Short-answer items do not get the canary.

Set this on the **course**, not the site, so the author belongs on that reading list:

1. In the course, go to **More → Filters** (or **Course administration → Filters**)
2. Enable **No Copy / Dev Tools**
3. Click **Settings** next to it
4. Enter an author your markers will recognise and a title that author never published

Leave a field blank to inherit from the parent context (quiz ← course ← category ← site). A quiz can override the course, or disable the honeypot without clearing the parent.

Site administration defaults are only a fallback. Prefer leaving them empty so each course plants its own citation — a single site-wide title will leak across the student body in a week.

When marking, search scripts for the fictional **title**. Author-alone is too common in legitimate essays.

Selected-response items (multiple choice, true/false, matching) do not receive the citation. Their submission is a click, so the canary cannot come back. Those items get a bland forensic watermark instead.

## Important Notes

This filter is a **deterrent, not absolute security**. A technically skilled user can bypass client-side JavaScript protections. It is intended to be used as one component in a layered security approach (e.g., alongside Safe Exam Browser, proctoring, etc.).

## License

This plugin is licensed under the [GNU GPL v3 or later](https://www.gnu.org/copyleft/gpl.html).
