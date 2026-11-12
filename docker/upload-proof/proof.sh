#!/usr/bin/env bash
#
# Upload proof — live database upload round trip against Turso Cloud.
# Creates a scratch database with `seed.type='database_upload'`, uploads an
# SQLite dump, reads the smoke table back via Hrana, then deletes the scratch.
#
# Requirements (host):
#   - docker
#   - TURSO_PLATFORM_API_TOKEN (live Turso Cloud token)  + optional
#     TURSO_PLATFORM_ORGANIZATION (defaults to softwarefreestylengineer)
#
# Usage:
#   docker/upload-proof/proof.sh             # matrix: 8.3 8.4 8.5 (default)
#   docker/upload-proof/proof.sh 8.4         # single version
#   TURSO_PLATFORM_API_TOKEN=... docker/upload-proof/proof.sh
#
set -euo pipefail
DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$DIR/../.."

DB_URL="${TURSO_PLATFORM_ORGANIZATION:-softwarefreestylengineer}"
API_TOKEN="${TURSO_PLATFORM_API_TOKEN:-}"

if [ -z "$API_TOKEN" ]; then
    echo "TURSO_PLATFORM_API_TOKEN is not set. Skipping live proof (no create/upload)." >&2
    echo "UPLOAD-PROOF: {\"skipped\":\"missing TURSO_PLATFORM_API_TOKEN\"}" >&2
    exit 2
fi

if [ "$#" -gt 0 ]; then
    PHP_VERSIONS=("$@")
else
    PHP_VERSIONS=(8.3 8.4 8.5)
fi

FAILED=()
for V in "${PHP_VERSIONS[@]}"; do
    TAG="turso-upload-proof:${V}"

    echo "==> Building proof image (PHP ${V})"
    docker build --build-arg PHP_VERSION="$V" -f docker/upload-proof/Dockerfile -t "$TAG" .

    echo "==> Running upload proof (PHP ${V})"
    if docker run --rm \
        -e TURSO_PLATFORM_API_TOKEN="$API_TOKEN" \
        -e TURSO_PLATFORM_ORGANIZATION="$DB_URL" \
        "$TAG"; then
        echo "==> PHP ${V}: PASS"
    else
        echo "==> PHP ${V}: FAIL"
        FAILED+=("$V")
    fi
done

if [ "${#FAILED[@]}" -gt 0 ]; then
    echo "UPLOAD-PROOF-MATRIX: FAILED on PHP ${FAILED[*]}" >&2
    exit 1
fi

echo "UPLOAD-PROOF-MATRIX: all green (PHP ${PHP_VERSIONS[*]})"
