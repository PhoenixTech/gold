# Test Infrastructure & Architecture Specification

## 1. Overview
This document details the end-to-end automated test architecture for the Zhonella checkout, split payment tracking, admin payment approval safeguards, and courier dispatch workflows.

All tests are authored under `tests/Feature/` according to standard Laravel feature test conventions.
Strict constraints enforced:
- Zero comments in code.
- Write boundaries respected (tests and metadata files only).
- Independent, self-contained test execution via database transactions (`RefreshDatabase`).

---

## 2. Test Suites & Target Filter Architecture

The test suite covers four target filters corresponding to requirements R1 through R4:

| Filter Name | Target Test File(s) | Requirement Area | Status |
|---|---|---|---|
| `Checkout` | `tests/Feature/CheckoutDeliveryTest.php`<br>`tests/Feature/CheckoutFlowTest.php` | R1: Delivery Selection, Gallery Pickup, Province Restrictions, Third-Party Recipient, Continue Shopping CTA | 28/28 Passing |
| `PaymentReceipt` | `tests/Feature/PaymentReceiptTest.php` | R2: Dedicated Screen, Dynamic Bank Payload, Split Payment Upload, Live Tally Amounts, Awaiting Verification Message | 20 Passing (Legacy), 5 Authored (R2 New) |
| `AdminPaymentApproval` | `tests/Feature/AdminPaymentApprovalTest.php` | R3: 4-Point Safeguard Checklist, Destination Account Selection, Zero Balance Enforcement, Status Update, Approval SMS | 7 Authored (R3 New) |
| `CourierDelivery` | `tests/Feature/CourierDeliveryTest.php` | R4: Printable Shipping Label View, Third-Party Details, Daily Dispatch Sheet, Delivery PIN Verification & Customer SMS | 12 Passing, 4 Authored (R4 New) |

---

## 3. Test Case Specification Matrix

### 3.1 Checkout & Delivery (`tests/Feature/CheckoutDeliveryTest.php`)

#### TC-CO-01: `test_gallery_pickup_places_order_without_address_and_zero_transport_cost`
- **Specification Source**: R1 (In-Person Gallery Pickup)
- **Input**: Customer with valid name and mobile, 0 physical addresses. Request with `delivery_type = 'pickup'`, `product_id`, `quantity_id`, `payment_method = 'card'`.
- **Expected Output**: Order succeeds and redirects. Database contains `invoices` row with `delivery_type = 'pickup'`, `address_id = null`, `transport_price = 0`, `status = AWAITING_PAYMENT`.

#### TC-CO-02: `test_non_tehran_address_checkout_is_rejected_with_province_restriction_error`
- **Specification Source**: R1 (Province Shipping Restrictions)
- **Input**: Address belonging to non-Tehran province (State: Isfahan). Request with `delivery_type = 'address'`.
- **Expected Output**: Request rejected with validation error on `address_id` indicating direct shipping to non-Tehran provinces is unavailable. No invoice created.

#### TC-CO-03: `test_tehran_address_checkout_proceeds_with_shipping_notice`
- **Specification Source**: R1 (Province Shipping Restrictions)
- **Input**: Address belonging to Tehran province. Request with `delivery_type = 'address'`.
- **Expected Output**: Request succeeds, invoice created with `status = AWAITING_PAYMENT`. Rendered client view displays 48-hour delivery notice ("۴۸ ساعت").

#### TC-CO-04: `test_third_party_recipient_fields_are_validated`
- **Specification Source**: R1 (Third-Party Recipient Validation)
- **Input**: Request with `is_third_party = 1`, empty recipient name, invalid mobile (`08123456789`), invalid national code (`12345`).
- **Expected Output**: Validation exception with session errors on `recipient_name`, `recipient_mobile`, and `recipient_national_id`. No invoice created.

#### TC-CO-05: `test_third_party_recipient_stored_on_invoice_without_overwriting_buyer_profile`
- **Specification Source**: R1 (Third-Party Data Isolation)
- **Input**: Buyer customer with existing name and mobile. Request with `is_third_party = 1`, `recipient_name = 'سهراب گیرنده'`, `recipient_mobile = '09198887766'`, `recipient_national_id = '0012345678'`.
- **Expected Output**: Invoice stores `is_third_party = true`, `recipient_name`, `recipient_mobile`, `recipient_national_id`. Buyer customer profile in database retains original name, mobile, and national ID unmodified.

#### TC-CO-06: `test_cart_page_provides_continue_shopping_cta`
- **Specification Source**: R1 (Cart Continue Shopping CTA)
- **Input**: GET `route('client.card')` with items in cart.
- **Expected Output**: Response status 200 containing link or button pointing to products catalog (`route('client.products')` or Persian label "ادامه خرید").

---

### 3.2 Payment Receipts & Split Tracking (`tests/Feature/PaymentReceiptTest.php`)

#### TC-PR-01: `test_dedicated_receipt_registration_screen_renders_distraction_free`
- **Specification Source**: R2 (Dedicated Receipt Upload Screen)
- **Input**: Authenticated customer GET `route('client.invoice.receipt', $invoice)`.
- **Expected Output**: Status 200. View does not contain distracting product catalog lists (`ns-card`), renders dedicated receipt registration layout.

#### TC-PR-02: `test_receipt_screen_presents_dynamic_gallery_bank_account_payload`
- **Specification Source**: R2 (Dynamic Gallery Bank Details)
- **Input**: Active bank account configured with Bank Name, Card Number, Account Holder, SHEBA/IBAN. GET `route('client.invoice.receipt', $invoice)`.
- **Expected Output**: Status 200. Response body renders bank name, card number, account holder name, and IBAN verbatim.

#### TC-PR-03: `test_split_payment_submission_accepts_multiple_receipts_with_metadata`
- **Specification Source**: R2 (Multi-Receipt Split Form)
- **Input**: POST `route('client.invoice.receipts.store', $invoice)` with array of receipts, each specifying `amount`, `payment_date`, `payment_time`, `tracking_number`, and `slip` file.
- **Expected Output**: Redirects to receipt view. Database contains multiple `PaymentReceipt` records linked to the invoice with exact amount and tracking number stored.

#### TC-PR-04: `test_live_tally_amounts_calculation_for_split_receipts`
- **Specification Source**: R2 (Live Tally Panel)
- **Input**: Invoice with `total_price = 1000000`. Receipts uploaded totaling 400000, then additional 600000.
- **Expected Output**: `$invoice->receiptsTotalAmount()` equals 400000 and `$invoice->remainingReceiptBalance()` equals 600000. When fully paid, `receiptsTotalAmount()` equals 1000000 and `remainingReceiptBalance()` equals 0.

#### TC-PR-05: `test_post_submission_status_displays_awaiting_store_verification_notice`
- **Specification Source**: R2 (Post-Submission Status Display)
- **Input**: Customer uploads receipt(s). View rendered on post-submission.
- **Expected Output**: Status 200. Content renders "Awaiting store verification. Our team will contact you within a few hours." (or Persian translation "در انتظار بررسی و تأیید فروشگاه. همکاران ما تا چند ساعت آینده با شما تماس خواهند گرفت.").

#### TC-PR-06 .. TC-PR-25: Legacy Backward Compatibility (20 tests)
- **Specification Source**: Backward compatibility with existing offline payment lifecycle, deadline expiration commands, and admin decline actions.
- **Expected Output**: All 20 pre-existing tests pass without regression.

---

### 3.3 Admin 4-Point Payment Approval Safeguards (`tests/Feature/AdminPaymentApprovalTest.php`)

#### TC-AP-01: `test_admin_payment_approval_requires_all_four_checklist_items`
- **Specification Source**: R3 (4-Point Verification Checklist)
- **Input**: Admin POST `route('admin.invoice.confirm-payment', $invoice)` with one or more checklist items omitted (`receipt_info_checked`, `account_selected`, `bank_verified`, `zero_balance`).
- **Expected Output**: Validation errors returned for missing checkbox. Invoice status remains `AWAITING_PAYMENT`.

#### TC-AP-02: `test_admin_payment_approval_requires_destination_bank_account`
- **Specification Source**: R3 (Destination Account Verification)
- **Input**: Admin POST with all checkboxes true but missing `bank_account_id` or non-existent ID.
- **Expected Output**: Session has validation error on `bank_account_id`. Invoice status remains `AWAITING_PAYMENT`.

#### TC-AP-03: `test_admin_payment_approval_rejected_when_remaining_balance_is_greater_than_zero`
- **Specification Source**: R3 (Zero Balance Safeguard)
- **Input**: Invoice total is 1,000,000 but verified receipts sum to 400,000 (remaining balance > 0). Admin submits approval.
- **Expected Output**: Request rejected. Invoice status remains `AWAITING_PAYMENT`.

#### TC-AP-04: `test_admin_payment_approval_succeeds_when_all_safeguards_pass`
- **Specification Source**: R3 (Approval Status Transition)
- **Input**: Verified receipts equal invoice total. All 4 checkboxes true, destination bank account valid.
- **Expected Output**: Redirects to edit screen. Invoice status transitions to `PAID` or `PROCESSING`. Payment status transitions to `SUCCESS`.

#### TC-AP-05: `test_admin_payment_approval_dispatches_customer_sms_notification`
- **Specification Source**: R3 (Customer Automated SMS)
- **Input**: Admin approves payment satisfying all criteria.
- **Expected Output**: SMS notification triggered to customer mobile containing 48h dispatch confirmation text.

#### TC-AP-06: `test_admin_invoice_form_renders_four_point_checklist_safeguard_ui`
- **Specification Source**: R3 (Admin Checklist UI)
- **Input**: Admin GET `route('admin.invoice.edit', $invoice)`.
- **Expected Output**: View contains inputs for all 4 checklist criteria and destination bank account selector.

#### TC-AP-07: `test_non_admin_cannot_confirm_payment`
- **Specification Source**: Security & Authorization
- **Input**: Non-admin or customer POST to confirmation route.
- **Expected Output**: Forbidden (403) or redirected. Invoice remains unconfirmed.

---

### 3.4 Dispatch Stage & Courier Verification (`tests/Feature/CourierDeliveryTest.php`)

#### TC-CD-01: `test_printable_shipping_label_view_renders_recipient_and_order_details`
- **Specification Source**: R4 (Printable Package Shipping Label)
- **Input**: Admin GET `route('admin.invoice.shipping-label', $invoice)`.
- **Expected Output**: Status 200. View renders recipient name, mobile, delivery address, and invoice hash code.

#### TC-CD-02: `test_printable_shipping_label_uses_third_party_recipient_when_present`
- **Specification Source**: R4 (Third-Party Label Formatting)
- **Input**: Invoice with third-party recipient set (`recipient_name = 'حمید میرزایی'`, `recipient_mobile = '09129998877'`, `recipient_national_id = '0012345678'`).
- **Expected Output**: Shipping label renders third-party recipient name, mobile, and national ID rather than buyer customer details.

#### TC-CD-03: `test_daily_courier_dispatch_sheet_renders_delivery_listing_and_stats`
- **Specification Source**: R4 (Daily Courier Dispatch Sheet)
- **Input**: Admin GET `route('admin.delivery.dispatch-sheet')`.
- **Expected Output**: Status 200. View renders courier deliveries list, dispatch summary stats, and signature handover column ("امضا").

#### TC-CD-04: `test_daily_courier_dispatch_sheet_is_forbidden_for_non_admin_users`
- **Specification Source**: Authorization Guard
- **Input**: Courier or customer GET `route('admin.delivery.dispatch-sheet')`.
- **Expected Output**: Request rejected (403 Forbidden or redirect).

#### TC-CD-05: `test_successful_courier_pin_verification_dispatches_customer_delivery_sms`
- **Specification Source**: R4 (Courier Verification Completion SMS)
- **Input**: Courier enters correct 4-digit PIN for accepted delivery.
- **Expected Output**: Delivery status set to `Delivered`, invoice status set to `COMPLETED`, customer delivery completion SMS dispatched.

#### TC-CD-06 .. TC-CD-16: Core Courier Operations (11 tests)
- **Specification Source**: Courier dashboard, board security, PIN attempts locking, Persian digit normalization.
- **Expected Output**: All 11 pre-existing tests pass without regression.

---

## 4. Verification & Execution Commands

```bash
# 1. Checkout delivery tests (R1)
php artisan test --filter=Checkout

# 2. Payment receipt split tracking tests (R2)
php artisan test --filter=PaymentReceipt

# 3. Admin payment approval safeguard tests (R3)
php artisan test --filter=AdminPaymentApproval

# 4. Courier dispatch and delivery tests (R4)
php artisan test --filter=CourierDelivery

# 5. Linting and formatting validation
./vendor/bin/pint tests/Feature/CheckoutDeliveryTest.php tests/Feature/PaymentReceiptTest.php tests/Feature/AdminPaymentApprovalTest.php tests/Feature/CourierDeliveryTest.php --test
```
