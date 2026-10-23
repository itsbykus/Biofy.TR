#!/usr/bin/env bash
# Coolify-style deployment test: builds the production image with docker-compose.yml,
# puts an HTTPS proxy in front (Caddy instead of Coolify's Traefik) and checks install,
# HTTPS awareness, health, uploads, file protection, persistence and migrations.
set -euo pipefail
cd "$(dirname "$0")/../.."
source scripts/ci/lib.sh

export COMPOSE_PROJECT_NAME=biofyci
export APP_NAME="Biofy CI" APP_URL="https://biofy.test"
export APP_KEY="base64:$(openssl rand -base64 32)"
export DB_PASSWORD="$(openssl rand -hex 16)" DB_ROOT_PASSWORD="$(openssl rand -hex 16)"
export BIOFY_ADMIN_USERNAME=owner BIOFY_ADMIN_EMAIL=owner@example.com BIOFY_ADMIN_PASSWORD="Ci$(openssl rand -hex 8)9x"

COMPOSE=(docker compose -f docker-compose.yml -f scripts/ci/docker/compose.proxy.yml)
CURL_OPTS=(-k --resolve biofy.test:443:127.0.0.1)
BASE="https://biofy.test"

on_exit() {
    local code=$?
    if [ "$code" -ne 0 ] || [ "$FAILURES" -ne 0 ]; then
        echo "---- app logs ----"; "${COMPOSE[@]}" logs --no-color --tail=200 app || true
    fi
}
trap on_exit EXIT

wait_healthy() {
    local id state
    id=$("${COMPOSE[@]}" ps -q app)
    for _ in $(seq 1 100); do
        state=$(docker inspect -f '{{.State.Health.Status}}' "$id" 2>/dev/null || echo unknown)
        [ "$state" = healthy ] && return 0
        sleep 3
    done
    echo "app did not become healthy (last state: $state)"; return 1
}

section "Build and first start"
"${COMPOSE[@]}" up -d --build --wait --wait-timeout 900
expect_eq "container health" "$(docker inspect -f '{{.State.Health.Status}}' "$("${COMPOSE[@]}" ps -q app)")" healthy
"${COMPOSE[@]}" logs --no-color app | grep -q "Installed. Sign in" && pass "first start installed the application" || fail "install message missing"

section "HTTPS behind the proxy"
expect_eq "GET /up" "$(http_code "$BASE/up")" 200
expect_eq "GET /" "$(http_code "$BASE/")" 200
curl "${CURL_OPTS[@]}" -s -D "$WORK/h" -o "$WORK/login.html" "$BASE/login"
grep -qi '^set-cookie:.*; secure' "$WORK/h" && pass "session cookie has Secure flag" || fail "session cookie is not Secure"
grep -qi '^set-cookie:.*httponly' "$WORK/h" && pass "session cookie is HttpOnly" || fail "session cookie is not HttpOnly"
grep -q 'action="https://biofy.test/login"' "$WORK/login.html" && pass "generated URLs use https" || fail "form action is not https"
grep -qi '^content-security-policy:' "$WORK/h" && pass "CSP header present" || fail "CSP header missing"

section "Sign in, admin, upload"
JAR="$WORK/jar"
expect_eq "login redirect" "$(login "$BASE" "$JAR" "$BIOFY_ADMIN_EMAIL" "$BIOFY_ADMIN_PASSWORD")" "$BASE/admin"
expect_eq "admin dashboard" "$(http_code -b "$JAR" "$BASE/admin")" 200
make_png "$WORK/test.png"
MEDIA_URL=$(absolute "$BASE" "$(upload_media "$BASE" "$JAR" "$WORK/test.png")")
[ "$MEDIA_URL" != "$BASE" ] && pass "image uploaded: $MEDIA_URL" || fail "upload returned no URL"
expect_eq "uploaded image is served" "$(http_code "$MEDIA_URL")" 200

section "Protected files"
for path in /.env /composer.json /artisan /storage/logs/laravel.log /vendor/autoload.php /install/installer.php; do
    expect_in "GET $path" "$(http_code "$BASE$path")" 403 404
done
"${COMPOSE[@]}" exec -T -u root app sh -c 'echo "<?php echo 6*7;" > public/media/probe.php'
body_lacks "PHP in media/ is not executed" "$BASE/media/probe.php" 42
"${COMPOSE[@]}" exec -T -u root app rm -f public/media/probe.php
expect_eq "unknown Host header rejected" "$("${COMPOSE[@]}" exec -T app curl -s -o /dev/null -w '%{http_code}' -H 'Host: evil.test' http://127.0.0.1/)" 404
expect_eq "code is not writable by the web user" "$("${COMPOSE[@]}" exec -T app sh -c 'setpriv --reuid=www-data --regid=www-data --init-groups -- sh -c "touch app/x 2>/dev/null && echo writable || echo readonly"')" readonly

section "Redeploy: rebuild and recreate containers (volumes kept)"
"${COMPOSE[@]}" up -d --build --force-recreate --wait --wait-timeout 900
"${COMPOSE[@]}" logs --no-color app | grep -q "up to date; no pending migrations" && pass "no reinstall, nothing to migrate" || fail "unexpected setup output after redeploy"
rm -f "$JAR"
expect_eq "admin can still sign in (database kept)" "$(login "$BASE" "$JAR" "$BIOFY_ADMIN_EMAIL" "$BIOFY_ADMIN_PASSWORD")" "$BASE/admin"
expect_eq "uploaded image survived (media volume kept)" "$(http_code "$MEDIA_URL")" 200

section "Next release with a new migration: backup first, then migrate"
"${COMPOSE[@]}" exec -T -u root app sh -c 'cat > database/migrations/2099_01_01_000000_ci_probe.php' < scripts/ci/docker/ci_probe_migration.php
"${COMPOSE[@]}" restart app
wait_healthy && pass "healthy after migrating" || fail "not healthy after migrating"
"${COMPOSE[@]}" logs --no-color app | grep -q "backup first" && pass "pre-migration backup announced" || fail "no backup before migrating"
"${COMPOSE[@]}" exec -T app sh -c 'ls storage/app/private/backups' | grep -q pre_update && pass "pre_update backup stored in the storage volume" || fail "backup file missing"
"${COMPOSE[@]}" exec -T mysql sh -c 'mysql -uroot -p"$MYSQL_ROOT_PASSWORD" -N -e "SHOW TABLES LIKE '"'"'ci_probe'"'"'" "$MYSQL_DATABASE"' 2>/dev/null | grep -q ci_probe \
    && pass "migration applied" || fail "migration not applied"

finish
