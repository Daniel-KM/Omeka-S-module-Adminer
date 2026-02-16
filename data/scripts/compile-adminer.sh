#!/usr/bin/env bash
#
# Compile Adminer for the Omeka S Adminer module.
#
# This script downloads Adminer source, patches it for Omeka compatibility,
# compiles it into self-contained PHP files, and packages the result into an
# archive suitable for distribution via sempia/external-assets.
#
# Usage:
#   cd modules/Adminer
#   bash data/scripts/compile-adminer.sh [--archive]
#
# Options:
#   --archive   Create a distributable tar.gz in build/
#
# Requirements: git, php, composer, sed, tar
#
# @copyright Daniel Berthereau, 2026


set -euo pipefail

ADMINER_REPO="https://github.com/vrana/adminer.git"

# Fetch the latest version tag from the repository, or use a fixed version.
# ADMINER_VERSION="5.4.1"
ADMINER_VERSION=$(git ls-remote --tags --sort=-v:refname "$ADMINER_REPO" 'v*' \
    | sed -n '1s|.*refs/tags/v||p')
if [ -z "$ADMINER_VERSION" ]; then
    echo "Error: could not determine latest Adminer version." >&2
    exit 1
fi

# Resolve module root (script is in data/scripts/).
MODULE_DIR="$(cd "$(dirname "$0")/../.." && pwd)"
WORK_DIR="$(mktemp -d)"
OUTPUT_DIR="${MODULE_DIR}/asset/vendor/adminer"
CREATE_ARCHIVE=false

for arg in "$@"; do
    case "$arg" in
        --archive) CREATE_ARCHIVE=true ;;
        *) echo "Unknown option: $arg"; exit 1 ;;
    esac
done

cleanup() {
    rm -rf "$WORK_DIR"
}
trap cleanup EXIT

echo "==> Cloning Adminer ${ADMINER_VERSION} with externals..."
git clone --depth=1 --recurse-submodules --branch="v${ADMINER_VERSION}" \
    "$ADMINER_REPO" "${WORK_DIR}/adminer" --quiet
ADMINER_SRC="${WORK_DIR}/adminer"

echo "==> Installing Adminer composer dependencies..."
composer install --working-dir="$ADMINER_SRC" --quiet --no-interaction

echo "==> Patching source for Omeka compatibility..."

# Suppress error reporting in compiled output.
sed -i -e 's~error_reporting(24575)~error_reporting(0)~' \
    "${ADMINER_SRC}/adminer/include/errors.inc.php"

# Fix plugin/theme paths to use __DIR__ instead of relative paths.
sed -i \
    -e 's~basename = "adminer-plugins"~basename = __DIR__ . "/adminer-plugins"~' \
    -e 's~include = include_once "./$basename.php"~include = include_once "$basename.php"~' \
    "${ADMINER_SRC}/adminer/include/plugins.inc.php"

sed -i \
    -e 's~is_dir("adminer-plugins") || file_exists("adminer-plugins.php")~is_dir(__DIR__ . "/adminer-plugins") || file_exists(__DIR__ . "/adminer-plugins.php")~' \
    "${ADMINER_SRC}/adminer/include/bootstrap.inc.php"

# Fix compile.php issue with translations version (#1085).
sed -i \
    -e 's~crc32($return);~chr(34) . crc32($return) . chr(34);~' \
    "${ADMINER_SRC}/compile.php"

echo "==> Compiling adminer-mysql..."
php -f "${ADMINER_SRC}/compile.php" -- mysql
ADMINER_FILE="adminer-${ADMINER_VERSION}-mysql.php"

echo "==> Compiling editor-mysql..."
php -f "${ADMINER_SRC}/compile.php" -- editor mysql
EDITOR_FILE="editor-${ADMINER_VERSION}-mysql.php"

echo "==> Assembling output..."
rm -rf "$OUTPUT_DIR"
mkdir -p "${OUTPUT_DIR}/adminer-plugins"

mv "$ADMINER_FILE" "${OUTPUT_DIR}/adminer-mysql.phtml"
mv "$EDITOR_FILE" "${OUTPUT_DIR}/editor-mysql.phtml"

# Plugin bridge: delegates to the module's view template.
echo '<?php return require dirname(__DIR__, 3) . "/view/adminer/admin/index/adminer-plugins.phtml";' \
    > "${OUTPUT_DIR}/adminer-plugins.php"

# Copy plugin source files (needed at runtime by adminer-plugins.phtml).
cp "${ADMINER_SRC}/plugins/"*.php "${OUTPUT_DIR}/adminer-plugins/"

# Copy designs (CSS themes selectable at runtime).
cp -r "${ADMINER_SRC}/designs" "${OUTPUT_DIR}/designs"

# Theme CSS with editor fix (also handled in the editor view template).
cp "${ADMINER_SRC}/designs/hever/adminer.css" "${OUTPUT_DIR}/adminer.css"
cat >> "${OUTPUT_DIR}/adminer.css" <<'CSSFIX'
/* fix omeka editor: table links must show text, not icons only. */
body.editor #menu a[href*="&select="],
body.editor #tables a[href*="&select="],
body.editor #tables a.select { overflow: initial !important; width: auto !important; height: auto !important; color: var(--inv-fg, inherit) !important; position: static !important; background-position-x: left !important; }
body.editor #tables a.select::before { display: none !important; }
body.editor #tables { overflow: visible !important; }
CSSFIX

# Security: deny direct access except static assets.
cat > "${MODULE_DIR}/asset/vendor/.htaccess" <<'HTACCESS'
Order allow,deny
<FilesMatch "\.(css|js|gif|jpeg|jpg|png|webp)$">
    Order deny,allow
</FilesMatch>
HTACCESS

echo "==> Compiled files:"
ls -lh "${OUTPUT_DIR}/"

if [ "$CREATE_ARCHIVE" = true ]; then
    mkdir -p "${MODULE_DIR}/build"
    ARCHIVE="${MODULE_DIR}/build/adminer-assets-${ADMINER_VERSION}.tar.gz"
    tar -czf "$ARCHIVE" \
        -C "${MODULE_DIR}/asset/vendor" \
        .htaccess adminer/
    echo "==> Archive created: ${ARCHIVE}"
    echo "    Size: $(du -h "$ARCHIVE" | cut -f1)"
fi

echo "==> Done."
