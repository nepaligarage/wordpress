#!/usr/bin/env bash
set -euo pipefail

BASE_URL="${1:-https://nepaligarage.com}"
BASE_URL="${BASE_URL%/}"

routes=(
  "/"
  "/about/"
  "/cars/"
  "/cars/byd/"
  "/contact/"
  "/compare/"
  "/electric-vehicles/"
  "/nepal-car-price-estimator/"
  "/news/"
  "/new-cars/"
  "/privacy-policy/"
)

blocked_routes=(
  "/compare/this-comparison-should-not-exist/"
  "/garages/"
  "/used-cars/"
  "/parts-finder/"
)

bad=0

check_ok() {
  local route="$1"
  local code
  code="$(curl -k -L -sS -o /dev/null -w '%{http_code}' "${BASE_URL}${route}")"
  if [[ "$code" =~ ^[23] ]]; then
    printf 'OK   %s %s\n' "$code" "$route"
  else
    printf 'FAIL %s %s\n' "$code" "$route"
    bad=1
  fi
}

check_not_public() {
  local route="$1"
  local code
  code="$(curl -k -L -sS -o /dev/null -w '%{http_code}' "${BASE_URL}${route}")"
  if [[ "$code" == "404" || "$code" == "410" ]]; then
    printf 'OK   %s %s remains unpublished\n' "$code" "$route"
  else
    printf 'WARN %s %s is public; verify it is intentional and complete\n' "$code" "$route"
  fi
}

for route in "${routes[@]}"; do
  check_ok "$route"
done

for route in "${blocked_routes[@]}"; do
  check_not_public "$route"
done

homepage="$(curl -k -L -sS "${BASE_URL}/")"
for claim in "Official manufacturer data" "122 spec fields per vehicle" "official sources only"; do
  if grep -Fqi "$claim" <<<"$homepage"; then
    printf 'FAIL homepage still contains overstated claim: %s\n' "$claim"
    bad=1
  fi
done

exit "$bad"
