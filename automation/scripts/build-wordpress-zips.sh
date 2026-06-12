#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
BUILD_DIR="${BUILD_DIR:-/tmp/ng_build}"

rm -rf "${BUILD_DIR}"
mkdir -p "${BUILD_DIR}/nepaligarage-core"

(
  cd "${ROOT_DIR}/theme"
  zip -qr "${BUILD_DIR}/nepaligarage-theme.zip" nepaligarage-theme/ -x "*.DS_Store"
)

cp -R \
  "${ROOT_DIR}/plugin/assets" \
  "${ROOT_DIR}/plugin/includes" \
  "${ROOT_DIR}/plugin/templates" \
  "${ROOT_DIR}/plugin/nepaligarage-core.php" \
  "${BUILD_DIR}/nepaligarage-core/"

(
  cd "${BUILD_DIR}"
  zip -qr nepaligarage-core.zip nepaligarage-core/ -x "*.DS_Store"
)

printf 'Built WordPress ZIPs:\n'
printf '  %s\n' "${BUILD_DIR}/nepaligarage-theme.zip"
printf '  %s\n' "${BUILD_DIR}/nepaligarage-core.zip"
