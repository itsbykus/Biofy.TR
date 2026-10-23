#!/bin/sh
# Container health: Laravel's /up route must answer 200 through Apache and PHP.
# The Host header must be the platform host (APP_URL); any other host is rejected by the app by design.
set -eu

host="${APP_URL#*://}"
host="${host%%/*}"
host="${host%%:*}"

exec curl --fail --silent --show-error --max-time 5 --output /dev/null \
    --header "Host: ${host:-localhost}" http://127.0.0.1/up
