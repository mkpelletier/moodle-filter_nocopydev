#!/bin/sh
# Copy this plugin into the local Moodle 5.0.7 and 5.3 trees and purge caches.
# Required during development because version.php is not bumped on every change,
# so Moodle will otherwise keep stale JS, language strings, and class maps.
set -e

SRC="$(CDPATH= cd -- "$(dirname "$0")/.." && pwd)"
PHP="${PHP:-/Applications/ServBay/package/php/8.3/8.3.33/bin/php}"

if [ ! -x "$PHP" ]; then
    PHP="$(command -v php || true)"
fi
if [ -z "$PHP" ] || [ ! -x "$PHP" ]; then
    echo "php not found; set PHP=/path/to/php" >&2
    exit 1
fi

sync_tree() {
    dest="$1"
    mkdir -p "$dest"
    rsync -a --delete \
        --exclude '.git/' \
        --exclude '.gitignore' \
        --exclude 'scripts/' \
        "$SRC/" "$dest/"
    echo "Synced $dest"
}

purge_50() {
    root="$1"
    "$PHP" "$root/admin/cli/purge_caches.php"
    echo "Purged caches: $root"
}

purge_53() {
    root="$1"
    "$PHP" "$root/bin/moodle" admin:purge-caches
    echo "Purged caches: $root"
}

sync_tree "/Users/mathieu/Sites/moodle/prod507/filter/nocopydev"
purge_50 "/Users/mathieu/Sites/moodle/prod507"

sync_tree "/Users/mathieu/Sites/moodle/dev53/public/filter/nocopydev"
purge_53 "/Users/mathieu/Sites/moodle/dev53"
