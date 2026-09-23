# Project: Checkout and Invoice Lifecycle Audit, Verification & Visual Capture

## Architecture
- **Domain Models**:
  - `App\Models\Invoice`: Central state machine and financial record. Stores 8 persistent database statuses (`PENDING`, `AWAITING_PAYMENT`, `PAID`, `PROCESSING`, `OUT_FOR_DELIVERY`, `COMPLETED`, `CANCELED`, `FAILED`) and calculates 2 virtual display statuses (`WAITING_RECEIPT`, `WAITING_CONFIRMATION`) via `displayStatusKey()`.
  - `App\Models\Quantity` & `App\Enums\QuantityPieceStatus`: Inventory pieces with statuses (`Available`, `Sold`, `Scrapped`). Checkout locks and marks pieces sold; cancellation, expiration, and failure release pieces back to available.
  - `App\Services\ProductPriceCalculator`: Aggregates product inventory quantities and toggles `stock_status` (`IN_STOCK` vs `OUT_STOCK`).
  - `App\Services\DeliveryService`: Handles delivery lifecycle, courier dispatching, PIN verification, and status advancement.
  - `App\Models\Payment` & `App\Models\PaymentReceipt`: Online gateway transactions and offline card-to-card receipt uploads.
- **Workflow Paths**:
  1. *Online Gateway Flow*: Checkout -> `AWAITING_PAYMENT` -> Payment Gateway -> Success (`PAID`) or Failure (`FAILED` with stock release).
  2. *Offline Card-to-Card Flow*: Checkout -> `AWAITING_PAYMENT` (Display: `WAITING_RECEIPT`) -> Receipt Upload -> Display: `WAITING_CONFIRMATION` -> Admin 4-Point Approval (`PAID`) or Decline (`AWAITING_PAYMENT`/`CANCELED`/`FAILED`).
  3. *Fulfillment & Delivery Flow*: `PAID` -> Admin Packaging (`PROCESSING`) -> Courier Assignment (`OUT_FOR_DELIVERY`) -> 4-Digit Customer PIN Verification -> `COMPLETED`.
  4. *In-Person Pickup Flow*: Delivery type `pickup` / `gallery_pickup` bypassing courier delivery.
  5. *Cancellation & Expiration Flow*: Admin Cancellation or Scheduled Expiration (`Schedule::command('offline:expire')`) -> `CANCELED` with complete stock restoration.

## Feature Inventory
| # | Feature | Description | Milestone | Source |
|---|---------|-------------|-----------|--------|
| 1 | Full Status Reachability | All 10 invoice statuses (PENDING, AWAITING_PAYMENT, WAITING_RECEIPT, WAITING_CONFIRMATION, PAID, PROCESSING, OUT_FOR_DELIVERY, COMPLETED, CANCELED, FAILED) reachable without unhandled exceptions | M1 | Survey |
| 2 | Stock Release on FAILED | Ensure `releaseReservedStock()` is called on all payment failures (online & admin) | M1 | Survey |
| 3 | Stock Status Restoral | Ensure `ProductPriceCalculator::syncProductAggregates` restores `stock_status = 'IN_STOCK'` when inventory is released | M1 | Survey |
| 4 | In-Person Pickup Consistency | Normalize `delivery_type` handling for `'pickup'` and `'gallery_pickup'` in shipping labels & dispatch sheets | M1 | Survey |
| 5 | Admin Payment Confirmation Form | Provide required 5-field checklist in admin `invoice-show` modal/form to allow seamless payment approvals | M1 | Survey |
| 6 | Customer View Unreachable Branch | Fix dead `@elseif($invoice->status === 'PAID')` branch in `client.customer.invoice` | M1 | Survey |
| 7 | InvoiceCompleted Event Dispatch | Dispatch `InvoiceCompleted` event upon successful delivery verification and order completion | M1 | Survey |
| 8 | Robust Invoice Factory States | Enhance `InvoiceFactory` with reliable defaults and states for all 10 lifecycle statuses | M2 | Survey |
| 9 | Comprehensive Transition Tests | Feature & unit test coverage for all valid transition paths across online, offline, and courier flows | M2 | Survey |
| 10 | Transition Guardrail Tests | Test coverage asserting invalid state transitions are blocked with appropriate errors | M2 | Survey |
| 11 | Stock Reservation Integrity Tests | Test coverage asserting piece status and product stock_status are strictly synchronized across all state changes | M2 | Survey |
| 12 | Visual Screenshot Generator | Automated simulation and headless Chrome capture of dual-perspective screenshots across all 10 statuses | M3 | Survey |
| 13 | Screenshot Gallery Index | Generate responsive HTML gallery (`index.html`) and Markdown index (`INDEX.md`) in `storage/app/workflow-screenshots/` | M3 | Survey |
| 14 | Forensic Integrity Verification | Binary verification by Forensic Auditor confirming zero cheats, authentic code, and AGENTS.md compliance | M4 | Survey |

## Milestones
| # | Name | Scope | Dependencies | Status |
|---|------|-------|-------------|--------|
| M1 | Lifecycle Transitions & Stock Integrity | Fix stock release on FAILED, fix syncProductAggregates IN_STOCK restoral, fix delivery_type pickup handling, fix admin payment confirmation form, fix customer view paid branch, dispatch InvoiceCompleted event | none | DONE |
| M2 | Automated Lifecycle Test Suite | Enhance InvoiceFactory with all states, write comprehensive transition & stock integrity tests, ensure `php artisan test --filter=Invoice` passes 100% | M1 | DONE |
| M3 | Dual-Perspective Visual Screenshot Capture | Implement workflow simulation & headless Chrome screenshot capture command for 10 statuses (customer & admin), produce HTML gallery & Markdown index | M1 | IN_PROGRESS |
| M4 | Final Forensic Audit & Verification | Run full test suite, verify 20 screenshots and gallery, forensic auditor integrity verification, handoff report | M1, M2, M3 | PLANNED |

## Code Layout
- `app/Models/Invoice.php`: Core invoice state model and helper methods.
- `app/Http/Controllers/ClientController.php`: Online payment handling and fail callback.
- `app/Services/DeliveryService.php`: Admin status transitions and courier delivery verification.
- `app/Services/ProductPriceCalculator.php`: Inventory sync and `stock_status` calculation.
- `resources/views/admin/invoices/shipping-label.blade.php`: Admin shipping label.
- `resources/views/admin/deliveries/dispatch-sheet.blade.php`: Admin courier dispatch sheet.
- `resources/views/admin/invoices/invoice-show.blade.php`: Admin invoice management view.
- `resources/views/client/customer/invoice.blade.php`: Customer invoice detail view.
- `database/factories/InvoiceFactory.php`: Factory states for invoice lifecycle testing.
- `tests/Feature/InvoiceLifecycleTransitionTest.php`: Feature test for valid/invalid transitions.
- `tests/Feature/InvoiceStockReservationTest.php`: Feature test for stock reservation/release integrity.
- `app/Console/Commands/CaptureInvoiceWorkflowScreenshots.php`: Screenshot capture command.
- `storage/app/workflow-screenshots/`: Output directory for customer and admin screenshots + gallery.

## Interface Contracts
### Invoice ↔ Stock / Quantity
- `Invoice::releaseReservedStock()` iterates associated `InvoicePiece` records, calls `$quantity->markAvailable()`, and invokes `ProductPriceCalculator::syncProductAggregates($product)`.
- `ProductPriceCalculator::syncProductAggregates($product)` MUST set `$product->stock_status = 'IN_STOCK'` when `stock_quantity > 0` and price conditions are met, and `'OUT_STOCK'` when `stock_quantity <= 0`.
- All paths leading to `Invoice::FAILED` (online payment gateway rejection, client error callback, or admin status change) MUST call `releaseReservedStock()`.

### DeliveryService ↔ Courier Verification
- `DeliveryService::confirmDelivery($delivery, $code)` validates the 4-digit PIN against `delivery_code_hash`. On success:
  - Updates delivery status to `delivered`.
  - Sets invoice status to `COMPLETED`.
  - Dispatches `InvoiceCompleted($invoice)`.
