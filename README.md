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

## Requirements

- Moodle 4.5+

## Installation

1. Copy the `nocopydev` folder into `/path/to/moodle/filter/`
2. Visit **Site administration > Notifications** to install the plugin
3. Enable the filter at **Site administration > Plugins > Filters > Manage filters**

## Configuration

The filter can be set to:

- **On** - Active everywhere on the site
- **Off, but available** - Can be selectively enabled per category, course, or activity

For exam security, a typical setup is to set it to "Off, but available" at the site level and enable it on specific quiz activities.

## Important Notes

This filter is a **deterrent, not absolute security**. A technically skilled user can bypass client-side JavaScript protections. It is intended to be used as one component in a layered security approach (e.g., alongside Safe Exam Browser, proctoring, etc.).

## License

This plugin is licensed under the [GNU GPL v3 or later](https://www.gnu.org/copyleft/gpl.html).
