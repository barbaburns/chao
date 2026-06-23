#!/bin/sh

set -eu

ROOT_DIR=$(CDPATH= cd -- "$(dirname -- "$0")/.." && pwd)
TMP_FILE=$(mktemp)

cleanup() {
  rm -f "$TMP_FILE"
}

trap cleanup EXIT HUP INT TERM

find "$ROOT_DIR/app" "$ROOT_DIR/config" -type f -name '*.php' | sort > "$TMP_FILE"

if [ -f "$ROOT_DIR/resources/functions.php" ]; then
  printf '%s\n' "$ROOT_DIR/resources/functions.php" >> "$TMP_FILE"
fi

if [ -f "$ROOT_DIR/resources/index.php" ]; then
  printf '%s\n' "$ROOT_DIR/resources/index.php" >> "$TMP_FILE"
fi

FAILED=0

while IFS= read -r file; do
  if ! php -l "$file" >/dev/null; then
    FAILED=1
  fi
done < "$TMP_FILE"

if [ "$FAILED" -ne 0 ]; then
  echo "PHP syntax check failed."
  exit 1
fi

echo "PHP syntax check passed."
