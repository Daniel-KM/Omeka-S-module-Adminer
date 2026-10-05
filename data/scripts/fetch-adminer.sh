#!/usr/bin/env bash
#
# Fetch Adminer for the Omeka S Adminer module.
#
# The upstream release ships the compiled single-file Adminer and Editor, so
# nothing is built here: the files are downloaded as is, and only the plugins
# and the designs are taken from the source archive, because they are not part
# of the compiled files.
#
# Usage:
#   cd modules/Adminer
#   bash data/scripts/fetch-adminer.sh [--archive]
#
# Options:
#   --archive   Create a distributable tar.gz in build/
#
# Requirements: curl, php, tar, unzip
#
# @copyright Daniel Berthereau, 2026

set -euo pipefail

ADMINER_RELEASES="https://github.com/vrana/adminer/releases/download"

# Use a fixed version, or fetch the latest release tag when it is empty.
ADMINER_VERSION="6.1.1"
ADMINER_VERSION=${ADMINER_VERSION:-$(curl -sfL \
    https://api.github.com/repos/vrana/adminer/releases/latest \
    | sed -n 's/.*"tag_name": *"v\([^"]*\)".*/\1/p')}
if [ -z "$ADMINER_VERSION" ]; then
    echo "Error: could not determine the Adminer version." >&2
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

fetch() {
    curl -sfL --retry 3 -o "$2" "$1" \
        || { echo "Error: cannot download $1" >&2; exit 1; }
}

BASE_URL="${ADMINER_RELEASES}/v${ADMINER_VERSION}"

# The files without the "-en" suffix are the multilingual ones: the language is
# set by the module from the locale of the current Omeka user.
echo "==> Downloading Adminer ${ADMINER_VERSION}..."
fetch "${BASE_URL}/adminer-${ADMINER_VERSION}-mysql.php" "${WORK_DIR}/adminer.php"
fetch "${BASE_URL}/editor-${ADMINER_VERSION}.php" "${WORK_DIR}/editor.php"
fetch "${BASE_URL}/adminer-${ADMINER_VERSION}.zip" "${WORK_DIR}/source.zip"

echo "==> Extracting plugins and designs..."
unzip -q "${WORK_DIR}/source.zip" -d "${WORK_DIR}/source"
SOURCE_DIR="${WORK_DIR}/source/adminer-${ADMINER_VERSION}"

echo "==> Assembling output..."
rm -rf "$OUTPUT_DIR"
mkdir -p "${OUTPUT_DIR}/adminer-plugins"

# The compiled files are renamed as templates, so the web server does not run
# them directly: they are included by the Omeka controller.
cp "${WORK_DIR}/adminer.php" "${OUTPUT_DIR}/adminer-mysql.phtml"
cp "${WORK_DIR}/editor.php" "${OUTPUT_DIR}/editor-mysql.phtml"

# Plugin source files, loaded explicitly by adminer-plugins.phtml.
cp "${SOURCE_DIR}/plugins/"*.php "${OUTPUT_DIR}/adminer-plugins/"

# Designs (CSS themes selectable at runtime).
cp -r "${SOURCE_DIR}/designs" "${OUTPUT_DIR}/designs"


# Patches submitted upstream, if any.
for PATCH in "${MODULE_DIR}/data/patches/"*.patch; do
    [ -f "$PATCH" ] || continue
    patch -p1 -s -d "$OUTPUT_DIR" < "$PATCH"
done

# Add the selectors required by the clean urls to all the designs.
php "${MODULE_DIR}/data/scripts/clean-urls-designs.php" \
    "${OUTPUT_DIR}/designs/"*/adminer.css

# Theme CSS: copy hever as the default one.
cp "${OUTPUT_DIR}/designs/hever/adminer.css" "${OUTPUT_DIR}/adminer.css"

# Security: deny direct access except static assets.
cat > "${MODULE_DIR}/asset/vendor/.htaccess" <<'HTACCESS'
Order allow,deny
<FilesMatch "\.(css|js|gif|jpeg|jpg|png|webp)$">
    Order deny,allow
</FilesMatch>
HTACCESS

echo "==> Fetched files:"
ls -lh "${OUTPUT_DIR}/"

if [ "$CREATE_ARCHIVE" = true ]; then
    mkdir -p "${MODULE_DIR}/build"
    ARCHIVE="${MODULE_DIR}/build/adminer-assets-${ADMINER_VERSION}.tar.gz"
    tar -czf "$ARCHIVE" \
        -C "${MODULE_DIR}/asset/vendor" \
        adminer/
    echo "==> Archive created: ${ARCHIVE}"
    echo "    Size: $(du -h "$ARCHIVE" | cut -f1)"
fi

echo "==> Done."
