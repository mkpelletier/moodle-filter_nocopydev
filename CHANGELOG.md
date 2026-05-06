# Changelog

## v1.0.1 (2026-05-06)

### Fixed
- Lockdown JS no longer activates on pages where the filter is "Off" but a child
  context (e.g. an embedded module) has it locally overridden to "On". Activation
  now checks the page's primary context, not the per-text context. Surfaced by
  format_simple, which renders inline module content on the course view and so
  triggered filter setup() with module contexts on every page render.

## v1.0.0 (2026-04-07)

### Added
- Initial release
- Block copy, cut, paste events and keyboard shortcuts globally
- Block right-click context menu
- Block developer tools shortcuts (F12, Ctrl+Shift+I/J/C)
- Block view source (Ctrl+U), save page (Ctrl+S), select all (Ctrl+A)
- Disable text selection via CSS (with exceptions for editor formatting)
- TinyMCE lockdown via TinyMCE event API with MutationObserver for async loading
- Noscript overlay to block access when JavaScript is disabled
- Per-context filter toggling (site, category, course, activity level)
