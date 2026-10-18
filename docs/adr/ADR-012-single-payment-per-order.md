# ADR-012 Single successful payment per order

- **Status:** Accepted (TASK-010)
- **Decision:** Order is marked paid once. Same payment id is idempotent. Different payment id on paid order is rejected.
