#!/usr/bin/env bash
# Shared helpers for the deployment smoke tests (sourced, not executed).
# Callers set CURL_OPTS (array) before using the HTTP helpers.

WORK="${WORK:-$(mktemp -d)}"
FAILURES=0

pass() { printf '  \033[32mPASS\033[0m %s\n' "$*"; }
fail() { printf '  \033[31mFAIL\033[0m %s\n' "$*"; FAILURES=$((FAILURES + 1)); }
section() { printf '\n== %s\n' "$*"; }

# expect_eq LABEL ACTUAL EXPECTED
expect_eq() {
    if [ "$2" = "$3" ]; then pass "$1 ($2)"; else fail "$1: got '$2', expected '$3'"; fi
}

# expect_in LABEL ACTUAL ALLOWED...
expect_in() {
    local label=$1 actual=$2; shift 2
    for allowed in "$@"; do
        if [ "$actual" = "$allowed" ]; then pass "$label ($actual)"; return; fi
    done
    fail "$label: got '$actual', expected one of: $*"
}

http_code() { curl "${CURL_OPTS[@]}" -s -o /dev/null -w '%{http_code}' "$@" || true; }

# Bodies are saved before searching: with pipefail, "curl | grep -q" can fail on SIGPIPE after a match.
fetch() { curl "${CURL_OPTS[@]}" -s -o "$WORK/body" "$1" || true; }

# body_contains LABEL URL NEEDLE
body_contains() {
    fetch "$2"
    if grep -q -- "$3" "$WORK/body"; then pass "$1"; else fail "$1: '$3' not found at $2"; fi
}

# body_lacks LABEL URL NEEDLE  (e.g. secrets must never be served)
body_lacks() {
    fetch "$2"
    if grep -q -- "$3" "$WORK/body"; then fail "$1: '$3' was served at $2"; else pass "$1"; fi
}

# output_contains LABEL NEEDLE COMMAND...  (runs the command into a file, then searches it)
output_contains() {
    local label=$1 needle=$2; shift 2
    "$@" >"$WORK/out" 2>&1 || true
    if grep -q -- "$needle" "$WORK/out"; then pass "$label"; else fail "$label: '$needle' not in output of: $*"; fi
}

# login BASE JAR EMAIL PASSWORD -> prints the redirect target
login() {
    local base=$1 jar=$2 token
    curl "${CURL_OPTS[@]}" -sS -c "$jar" -b "$jar" -o "$WORK/login.html" "$base/login"
    token=$(grep -o 'name="_token" value="[^"]*"' "$WORK/login.html" | head -1 | sed 's/.*value="//; s/"$//')
    curl "${CURL_OPTS[@]}" -sS -c "$jar" -b "$jar" -o /dev/null -w '%{redirect_url}' \
        --data-urlencode "_token=$token" --data-urlencode "email=$3" --data-urlencode "password=$4" "$base/login"
}

# make_png PATH  (64x64 RGB PNG, no image tools needed)
make_png() {
    python3 - "$1" <<'PY'
import struct, sys, zlib
w = h = 64
raw = b''.join(b'\x00' + b'\xff\xcf\x00' * w for _ in range(h))
def chunk(t, d):
    return struct.pack('>I', len(d)) + t + d + struct.pack('>I', zlib.crc32(t + d) & 0xffffffff)
png = b'\x89PNG\r\n\x1a\n' + chunk(b'IHDR', struct.pack('>IIBBBBB', w, h, 8, 2, 0, 0, 0)) + chunk(b'IDAT', zlib.compress(raw)) + chunk(b'IEND', b'')
open(sys.argv[1], 'wb').write(png)
PY
}

# upload_media BASE JAR FILE -> prints the public URL of the stored image
upload_media() {
    local base=$1 jar=$2 token
    curl "${CURL_OPTS[@]}" -sS -c "$jar" -b "$jar" -o "$WORK/media.html" "$base/dashboard/media"
    token=$(grep -o 'name="csrf-token" content="[^"]*"' "$WORK/media.html" | head -1 | sed 's/.*content="//; s/"$//')
    curl "${CURL_OPTS[@]}" -sS -c "$jar" -b "$jar" -H 'Accept: application/json' -H "X-CSRF-TOKEN: $token" \
        -F "file=@$3;type=image/png" -F purpose=block -o "$WORK/upload.json" "$base/dashboard/media" || true
    python3 -c 'import json,sys; print(json.load(open(sys.argv[1])).get("url",""))' "$WORK/upload.json" 2>/dev/null || true
}

# absolute BASE URL -> URL made absolute against BASE
absolute() {
    case "$2" in http*) echo "$2" ;; *) echo "$1$2" ;; esac
}

finish() {
    echo
    if [ "$FAILURES" -eq 0 ]; then echo "All checks passed."; else echo "$FAILURES check(s) failed."; exit 1; fi
}
