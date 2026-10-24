#!/usr/bin/env bash
# Smoke StoreYar API happy path (no UI).
# Usage:
#   export API_BASE=http://localhost:8000/api
#   bash scripts/smoke-api.sh
set -euo pipefail

API_BASE="${API_BASE:-http://localhost:8000/api}"
EMAIL="${SMOKE_EMAIL:-smoke-$(date +%s)@example.com}"
PASS="${SMOKE_PASSWORD:-password12}"
NAME="${SMOKE_NAME:-Smoke User}"

json() { python3 -c "import sys,json; print(json.load(sys.stdin)$1)"; }

echo "==> API_BASE=$API_BASE"
echo "==> Register $EMAIL"
REG=$(curl -sS -X POST "$API_BASE/auth/register" \
  -H 'Content-Type: application/json' \
  -H 'Accept: application/json' \
  -d "{\"email\":\"$EMAIL\",\"password\":\"$PASS\",\"name\":\"$NAME\"}")
TOKEN=$(printf '%s' "$REG" | python3 -c "import sys,json; d=json.load(sys.stdin); print(d.get('data',d).get('token') or d.get('token') or '')")
if [ -z "$TOKEN" ]; then
  echo "Register response: $REG"
  echo "Trying login..."
  REG=$(curl -sS -X POST "$API_BASE/auth/login" \
    -H 'Content-Type: application/json' -H 'Accept: application/json' \
    -d "{\"email\":\"$EMAIL\",\"password\":\"$PASS\"}")
  TOKEN=$(printf '%s' "$REG" | python3 -c "import sys,json; d=json.load(sys.stdin); print(d.get('data',d).get('token') or d.get('token') or '')")
fi
test -n "$TOKEN" || { echo "No token"; echo "$REG"; exit 1; }
echo "    token ok"

AUTH=( -H "Authorization: Bearer $TOKEN" -H 'Accept: application/json' -H 'Content-Type: application/json' )

echo "==> Create organization"
ORG=$(curl -sS -X POST "$API_BASE/organizations" "${AUTH[@]}" \
  -d '{"name":"Smoke Org"}')
ORG_ID=$(printf '%s' "$ORG" | python3 -c "import sys,json; d=json.load(sys.stdin); print(d.get('data',d).get('id') or d.get('id') or '')")
test -n "$ORG_ID" || { echo "$ORG"; exit 1; }
echo "    org=$ORG_ID"

BIZ=( "${AUTH[@]}" -H "X-Business-Id: $ORG_ID" )

echo "==> Create product"
SKU="SKU-$(date +%s)"
PROD=$(curl -sS -X POST "$API_BASE/products" "${BIZ[@]}" \
  -H "Idempotency-Key: smoke-prod-$SKU" \
  -d "{\"name\":\"Smoke Product\",\"sku\":\"$SKU\",\"unit_price_amount\":1500,\"currency\":\"IRR\"}")
PROD_ID=$(printf '%s' "$PROD" | python3 -c "import sys,json; d=json.load(sys.stdin); print(d.get('data',d).get('id') or d.get('id') or '')")
test -n "$PROD_ID" || { echo "$PROD"; exit 1; }
echo "    product=$PROD_ID"

echo "==> Initialize stock"
curl -sS -X POST "$API_BASE/stock" "${BIZ[@]}" \
  -H "Idempotency-Key: smoke-stock-$SKU" \
  -d "{\"product_id\":\"$PROD_ID\",\"quantity\":20}" | head -c 200
echo

echo "==> Create order + line + confirm"
ORDER=$(curl -sS -X POST "$API_BASE/orders" "${BIZ[@]}" \
  -H "Idempotency-Key: smoke-order-$SKU")
ORDER_ID=$(printf '%s' "$ORDER" | python3 -c "import sys,json; d=json.load(sys.stdin); print(d.get('data',d).get('id') or d.get('id') or '')")
test -n "$ORDER_ID" || { echo "$ORDER"; exit 1; }

curl -sS -o /dev/null -w "    line HTTP %{http_code}\n" -X POST "$API_BASE/orders/$ORDER_ID/lines" "${BIZ[@]}" \
  -H "Idempotency-Key: smoke-line-$SKU" \
  -d "{\"product_id\":\"$PROD_ID\",\"quantity\":2,\"unit_price_amount\":1500}"

curl -sS -o /dev/null -w "    confirm HTTP %{http_code}\n" -X POST "$API_BASE/orders/$ORDER_ID/confirm" "${BIZ[@]}" \
  -H "Idempotency-Key: smoke-confirm-$SKU" \
  -d '{}'

echo "==> Request-Id header check"
HDR=$(curl -sS -D - -o /dev/null -X POST "$API_BASE/auth/login" \
  -H 'Content-Type: application/json' -H 'Accept: application/json' \
  -d "{\"email\":\"$EMAIL\",\"password\":\"$PASS\"}" | tr -d '\r' | grep -i '^x-request-id:' || true)
echo "    $HDR"

echo "==> SMOKE OK  order=$ORDER_ID"
