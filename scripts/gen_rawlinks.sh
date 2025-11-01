#!/usr/bin/env bash
set -euo pipefail

# Lấy owner/repo từ remote origin
REMOTE_URL="$(git config --get remote.origin.url || true)"
if [[ -z "$REMOTE_URL" ]]; then
  echo "[ERR] Không tìm thấy remote.origin.url" >&2
  exit 1
fi

# Parse owner/repo từ dạng git@github.com:owner/repo.git hoặc https://github.com/owner/repo.git
if [[ "$REMOTE_URL" =~ github\.com[:/](.+)/(.+)\.git$ ]]; then
  OWNER="${BASH_REMATCH[1]}"
  REPO="${BASH_REMATCH[2]}"
else
  echo "[ERR] Không parse được owner/repo từ: $REMOTE_URL" >&2
  exit 1
fi

# Lấy branch hiện tại (fallback 'main')
BRANCH="$(git rev-parse --abbrev-ref HEAD 2>/dev/null || echo main)"
[[ "$BRANCH" == "HEAD" ]] && BRANCH=main

# Nếu có dir massagenow.vn thì output vào đó, ngược lại dùng rawlink.md ở root
OUT="rawlink.md"
[[ -d "massagenow.vn" ]] && OUT="massagenow.vn/rawlink.md"

# Chạy PHP script (quét root: không đặt base=…)
php scripts/gen_rawlinks.php owner="$OWNER" repo="$REPO" ref="$BRANCH" output="$OUT"
echo "[OK] Wrote $OUT"
