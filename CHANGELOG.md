# Changelog

## v1.1.1 (2026-09-21)

### Fixed
- Course/quiz filter settings no longer fatal on Moodle 5.0 and 5.3.
  Core's local settings form does not autoload (`namespace core_filters`
  in `filter/classes/form/`), so the subclass now requires that file.

## v1.1.0 (2026-09-19)

### Added
- Quiz-attempt mitigations, applied per question type:
  - Essay / short answer / Cloze: aria-hidden citation honeypot and a
    pixel watermark that carries the short citation
  - Multiple choice, true/false, matching: forensic per-attempt watermark
    and print blocking (a canary cannot come back in a click-to-answer
    submission)
- Citation honeypot: real author + fictional work, configurable per
  category, course, or quiz (blank fields inherit from the parent, then
  the site default)
- Local filter settings form on Course / Quiz → Filters → No Copy / Dev Tools
- Option to disable the honeypot in a context without clearing the parent

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
