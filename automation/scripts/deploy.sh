#!/usr/bin/env bash
set -euo pipefail

HOST="nepaligarage"
REMOTE_BASE="/home/parajuliz/nepaligarage.com/wp-content"
REPO_ROOT="$(cd "$(dirname "$0")/../.." && pwd)"

echo "Deploying theme..."
rsync -az --delete \
  --exclude=".DS_Store" \
  --exclude=".claude-flow/" \
  "$REPO_ROOT/theme/nepaligarage-theme/" \
  "$HOST:$REMOTE_BASE/themes/nepaligarage-theme/"

echo "Deploying plugin..."
rsync -az --delete \
  --exclude=".DS_Store" \
  "$REPO_ROOT/plugin/assets/"    "$HOST:$REMOTE_BASE/plugins/nepaligarage-core/assets/"
rsync -az --delete \
  --exclude=".DS_Store" \
  "$REPO_ROOT/plugin/includes/"  "$HOST:$REMOTE_BASE/plugins/nepaligarage-core/includes/"
rsync -az --delete \
  --exclude=".DS_Store" \
  "$REPO_ROOT/plugin/templates/" "$HOST:$REMOTE_BASE/plugins/nepaligarage-core/templates/"
rsync -az \
  "$REPO_ROOT/plugin/nepaligarage-core.php" \
  "$HOST:$REMOTE_BASE/plugins/nepaligarage-core/nepaligarage-core.php"

echo "Done. Verifying routes..."
bash "$REPO_ROOT/automation/scripts/verify-public-routes.sh" https://nepaligarage.com
