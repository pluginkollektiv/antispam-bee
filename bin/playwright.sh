#!/bin/bash
set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR/.."

echo "Starting wp-env..."
npm run env:start

cleanup() {
  echo "Stopping wp-env..."
  npm run env:stop
}
trap cleanup EXIT

echo "Running Playwright E2E tests..."
npm run test:e2e
