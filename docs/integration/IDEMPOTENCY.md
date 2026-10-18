# Idempotency

## Storage

Table: `integration_idempotency_keys`

| Column | Role |
|--------|------|
| id | ULID PK |
| business_id | Tenant |
| operation_id | Derived from key + operation type |
| operation_type | String |
| status | processing / completed (and possibly others) |
| response | JSON nullable |
| completed_at | nullable |

Unique: `(business_id, operation_id)`.

## Application API

- `IdempotencyStore` (Domain/Application contracts) with `find`, `claim`, `store`.
- `IdempotencyExecutor` (claim processing → run → store completed; replay completed; conflict if processing).
- Module-specific wrappers: e.g. `AdjustStockIdempotently`, `InitializeStockIdempotently`, order create/confirm/cancel services, payment/shipping create paths (from git history and Application/Services).

## HTTP

OpenAPI documents `Idempotency-Key` header. Controllers/services that require it: stock initialize/adjust, order create/confirm/cancel, payments initiate, shipping create/cancel (verified by feature tests and service classes).

## Semantics (from IdempotencyExecutor + module services)

1. Same key + completed → return stored response (replay).
2. Same key + processing → conflict exception / 409-style.
3. claim race → loser sees existing record.
4. Failed operation after claim: **behavior depends on whether status is left processing or cleaned** — module services vary; **risk of stuck processing keys** if exception before store completed.
5. Fingerprint of request body: some services hash key with operation parameters into operation_id (`IdempotencyKey::operationId`); others use raw key — **per-service**.

## Scenarios

| Scenario | Expected from code |
|----------|-------------------|
| Same key same request | Replay response |
| Same key different body | May collide if operation_id ignores body — **verify per endpoint** |
| Concurrent same key | claim uniqueness; one wins |
| Failed transaction after claim | Potential stuck `processing` — **P1 risk** |
| Process restart mid-flight | Same stuck processing risk |

## Not global middleware

Idempotency is **opt-in per application service**, not a global HTTP middleware.
