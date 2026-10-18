# ADR-008 Payment Lifecycle

- **Status:** Accepted
- **Context:** Payment aggregate independent; CompletePayment dispatches MarkOrderPaid (VERIFIED). No external PSP.
- **Decision:** Keep payment and order as separate aggregates. Success path updates order only through Sales command with state guards. No provider-specific code until a provider is chosen. Duplicate succeed is idempotent.
- **Alternatives:** Payment fields on Order; automatic capture gateway now.
- **Consequences:** Manual succeed/fail endpoints remain until PSP.
- **Data integrity impact:** Guards prevent illegal order transitions.
- **Migration impact:** Strengthen MarkOrderPaid guards; decide multi-payment uniqueness (UNKNOWN).
