#!/bin/sh
set -e

if [ ! -f package.json ]; then
  echo ""
  echo "React app is not installed in ./frontend yet."
  echo "From the repo root, run:"
  echo "  sh scripts/bootstrap.sh"
  echo ""
  exec tail -f /dev/null
fi

if [ ! -d node_modules ] || [ ! -f node_modules/.package-lock.json ]; then
  if [ -f package-lock.json ]; then
    npm ci
  else
    npm install
  fi
fi

exec npm run dev -- --host 0.0.0.0 --port 5173
