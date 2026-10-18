# Tenancy and Authorization — Current + Target

## Current (VERIFIED)

- `BusinessContext` / `CurrentBusinessContext`: businessId, optional branchId
- Header `X-Business-Id` set by `business.context` middleware
- `auth.session`: Bearer opaque token → Identity session
- `org.member` / `org.member:role`: membership via GetMembershipQuery; prefers X-Business-Id over route id
- Handlers compare `organizationId` on aggregates
- No DB RLS; branch not on commerce tables
- Jobs: context must be supplied explicitly (**UNKNOWN** if all jobs do)

## Target rules (PROPOSED)

### Resolution order
1. Authenticate user (session).
2. Read client `X-Business-Id` as **requested** business.
3. Authorize membership (and role if required).
4. Bind `BusinessContext` only after step 3 succeeds.
5. Commands use organizationId from context (or validated route under org.member), never trust body.organization_id alone without match to context.

### Deny by default
- Missing membership → 403
- Resource org ≠ context org → 404/403 (prefer 404 to avoid leakage)
- Unauthenticated → 401

### Lists and gets
Every query handler **must** filter by organization_id from context. Architecture/feature tests (TASK-001) enforce cross-tenant denial.

### Background work
Integration/outbox consumers set context from event `organization_id` / `business_id` in payload. No ambient tenant.

### Branch
Do **not** enforce branch-level commerce isolation until product requires it. Branch remains Organization sub-resource.

### Authorization granularity
Roles: owner, admin, member (VERIFIED enum usage). Fine-grained Permission objects in Shared remain optional; middleware roles sufficient for v1.

### Client header is not proof
Changing `X-Business-Id` without membership cannot grant access (**PROPOSED** test matrix proves this).

## Security impact of roadmap tasks
- TASK-001: detects tenant escape regressions
- Port fixes do not weaken checks if organizationId still threaded from context
