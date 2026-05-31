#!/usr/bin/env bash

set -euo pipefail

if [[ -z "${MAINTENANCE_SECRET:-}" ]]; then
    echo "MAINTENANCE_SECRET is required." >&2
    exit 1
fi

php artisan down --render=maintenance --secret="${MAINTENANCE_SECRET}"
