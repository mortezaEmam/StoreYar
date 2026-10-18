# ADR-009 Shipping Lifecycle

- **Status:** Accepted
- **Context:** Shipment unique per org+order; Sales gateway for shippable (VERIFIED).
- **Decision:** Shipping owns shipment state only. Read Sales for eligibility. No Inventory/Payment writes. Partial shipments unsupported until product decision (UNKNOWN → default off).
- **Alternatives:** Embed shipment in Order aggregate.
- **Consequences:** Simple model; one shipment per order for now.
- **Migration impact:** None until partial ship required.
