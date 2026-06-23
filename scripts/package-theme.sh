#!/bin/sh

set -eu

ROOT_DIR=$(CDPATH= cd -- "$(dirname -- "$0")/.." && pwd)
THEME_SLUG=$(basename "$ROOT_DIR")
ARTIFACTS_DIR="$ROOT_DIR/artifacts"
STAGING_DIR="$ARTIFACTS_DIR/$THEME_SLUG"
VERSION=$(sed -n 's/^Version:[[:space:]]*//p' "$ROOT_DIR/resources/style.css" | head -n 1 | tr -d '\r')

if [ -z "$VERSION" ]; then
  VERSION=$(date +%Y%m%d%H%M%S)
fi

ARCHIVE_PATH="$ARTIFACTS_DIR/${THEME_SLUG}-${VERSION}.zip"

require_command() {
  if ! command -v "$1" >/dev/null 2>&1; then
    echo "Missing required command: $1" >&2
    exit 1
  fi
}

require_path() {
  if [ ! -e "$ROOT_DIR/$1" ]; then
    echo "Missing required path: $1" >&2
    exit 1
  fi
}

require_command npm
require_command yarn
require_command zip

require_path node_modules
require_path vendor
require_path resources/functions.php
require_path resources/style.css
require_path resources/index.php

echo "Running validation before packaging..."
npm test
npm run -s lint:php
yarn build:production

mkdir -p "$ARTIFACTS_DIR"
rm -rf "$STAGING_DIR" "$ARCHIVE_PATH"
mkdir -p "$STAGING_DIR"

for path in app config dist resources vendor composer.json composer.lock screenshot.png; do
  if [ -e "$ROOT_DIR/$path" ]; then
    cp -R "$ROOT_DIR/$path" "$STAGING_DIR/"
  fi
done

cp "$ROOT_DIR/resources/style.css" "$STAGING_DIR/style.css"
cp "$ROOT_DIR/resources/index.php" "$STAGING_DIR/index.php"
printf '%s\n' '<?php' '' "require_once __DIR__ . '/resources/functions.php';" > "$STAGING_DIR/functions.php"
rm -f "$STAGING_DIR/resources/style.css" "$STAGING_DIR/resources/index.php"

STAGED_FUNCTIONS_FILE="$STAGING_DIR/resources/functions.php"
LC_ALL=C perl -0pi -e 's#\$file = "\.\./app/\{\$file\}\.php";#\$file = "app/\{\$file\}\.php";#' "$STAGED_FUNCTIONS_FILE"
LC_ALL=C perl -0pi -e "s#array_map\\(\\R    'add_filter',\\R    \\['theme_file_path', 'theme_file_uri', 'parent_theme_file_path', 'parent_theme_file_uri'\\],\\R    array_fill\\(0, 4, 'dirname'\\)\\R\\);##s" "$STAGED_FUNCTIONS_FILE"

find "$STAGING_DIR" -name '.DS_Store' -exec rm -f {} \;

(
  cd "$ARTIFACTS_DIR"
  zip -qr "$(basename "$ARCHIVE_PATH")" "$THEME_SLUG"
)

echo "Created $ARCHIVE_PATH"
