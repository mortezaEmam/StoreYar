# Transactional Outbox

## Implementations

### Primary path (module repositories)

- Contract: `StoreYar\Shared\Domain\Contracts\Outbox`
- Impl: `EloquentOutbox` → table **`shared_outbox`**
- Columns: id, aggregate_type, aggregate_id, event_type, payload JSON, occurred_at, created_at, processed_at, attempts, last_error
- **No business_id / correlation_id on shared_outbox**

Repositories calling `pullDomainEvents()` + `outbox->recordMany` on save:
- Catalog EloquentProductRepository
- Inventory EloquentStockItemRepository
- Sales EloquentOrderRepository
- Payments EloquentPaymentRepository
- Shipping EloquentShipmentRepository

### Secondary / parallel model

- Tables: `integration_outbox_messages`, `integration_inbox_messages`
- Application contracts: OutboxStore, InboxStore, richer metadata (business_id, correlation, causation, operation_id)
- **Not** the path used by the five repositories above

## Atomicity

Handlers that wrap `DB::transaction` and save aggregates inside the transaction **can** commit state + outbox rows together **if** outbox insert uses the same connection and happens inside that transaction. EloquentOutbox uses `OutboxModel::create` — atomic **if** called within the open transaction (typical for ConfirmOrder, CompletePayment).

**Gap:** Inventory `reserve` / `release` / `commitReserved` **do not record domain events**. ConfirmOrder mutates stock via those methods then saves StockItem — **outbox may receive zero events for stock reservation/commit**, only later Adjust events if any.

Identity/Organization/Authorization: **no outbox wiring observed** on their repositories.

## Publishing

- Command: `outbox:publish` (`PublishOutboxCommand`)
- Scheduler: every minute, limit 200, withoutOverlapping
- Publishers: `LogOutboxEventPublisher` (default binding in SharedServiceProvider), `QueueOutboxEventPublisher`, job `PublishOutboxEventJob`
- Processor: `OutboxProcessor`

## Consumers

No module-level inbox consumers verified that react to Catalog/Inventory/Sales events to drive other contexts. Cross-context effects are synchronous today.

## Duplicate delivery

processed_at + attempts support at-least-once publish; consumers must be idempotent — **inbox table exists for that purpose but business consumers missing**.
