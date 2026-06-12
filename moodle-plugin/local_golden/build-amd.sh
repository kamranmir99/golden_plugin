#!/usr/bin/env bash
# Build the AMD module(s) for local_golden.
#
# Usage:  ./build-amd.sh        (run from anywhere; resolves paths relative to this script)
#
# Requires: terser (npm install -g terser  OR  npx terser ...)
#
# Output:  amd/build/<module>.min.js  +  .min.js.map  for every .js in amd/src/.

set -euo pipefail
HERE="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
SRC_DIR="${HERE}/amd/src"
OUT_DIR="${HERE}/amd/build"
mkdir -p "${OUT_DIR}"

# Resolve terser binary.
if command -v terser >/dev/null 2>&1; then
    TERSER="terser"
elif command -v npx >/dev/null 2>&1; then
    TERSER="npx --yes terser"
else
    echo "terser not found. Install with:  npm install -g terser" >&2
    exit 1
fi

for src in "${SRC_DIR}"/*.js; do
    name="$(basename "${src}" .js)"
    out="${OUT_DIR}/${name}.min.js"
    echo "Building ${name}.min.js"
    ${TERSER} "${src}" \
        --compress 'drop_console=false,passes=2' \
        --mangle 'reserved=["define","init"]' \
        --comments false \
        --source-map "filename='${name}.min.js.map',url='${name}.min.js.map',includeSources" \
        --output "${out}"
done
echo "Done."
