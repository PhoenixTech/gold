# Invoice Lifecycle Workflow Screenshots Index

This directory contains visual documentation for all 10 invoice statuses in the Zhonella gold ecommerce application. Each status captures the customer-facing view (`invoice/{hash}`), the primary admin edit view (`dashboard/invoices/edit/{invoice_hash}`), and the admin show view (`dashboard/invoices/show/{item}`) at 1400x1000 resolution via Google Chrome headless CLI.

## Order Board Overview
- **Route**: `dashboard/order-board`
- **File**: [admin_order_board.png](admin_order_board.png) (167,908 bytes)
- **Description**: Live administrative order pipeline showing active and completed orders with live delivery stages.

## Interactive Gallery
Open [index.html](index.html) in any modern browser for side-by-side comparison, filtering, and high-resolution lightbox inspection.

## Screenshots Catalog

| # | Status Key | Status Name | View / Route | Screenshot File | File Size | Stock State |
|---|---|---|---|---|---|---|
| 01 | `PENDING` | در انتظار پرداخت آنلاین | Customer (`invoice/{hash}`) | [customer_01_pending.png](customer_01_pending.png) | 73,319 bytes | رزرو شده (Piece Reserved) |
| 01 | `PENDING` | در انتظار پرداخت آنلاین | Admin Edit (`dashboard/invoices/edit/{hash}`) | [admin_edit_01_pending.png](admin_edit_01_pending.png) | 136,221 bytes | رزرو شده (Piece Reserved) |
| 01 | `PENDING` | در انتظار پرداخت آنلاین | Admin Show (`dashboard/invoices/show/{id}`) | [admin_show_01_pending.png](admin_show_01_pending.png) | 159,581 bytes | رزرو شده (Piece Reserved) |
| 02 | `AWAITING_PAYMENT` | در انتظار پرداخت | Customer (`invoice/{hash}`) | [customer_02_awaiting_payment.png](customer_02_awaiting_payment.png) | 86,208 bytes | رزرو شده (Piece Reserved) |
| 02 | `AWAITING_PAYMENT` | در انتظار پرداخت | Admin Edit (`dashboard/invoices/edit/{hash}`) | [admin_edit_02_awaiting_payment.png](admin_edit_02_awaiting_payment.png) | 139,631 bytes | رزرو شده (Piece Reserved) |
| 02 | `AWAITING_PAYMENT` | در انتظار پرداخت | Admin Show (`dashboard/invoices/show/{id}`) | [admin_show_02_awaiting_payment.png](admin_show_02_awaiting_payment.png) | 160,933 bytes | رزرو شده (Piece Reserved) |
| 03 | `WAITING_RECEIPT` | در انتظار ثبت فیش | Customer (`invoice/{hash}`) | [customer_03_waiting_receipt.png](customer_03_waiting_receipt.png) | 86,244 bytes | رزرو شده (Piece Reserved) |
| 03 | `WAITING_RECEIPT` | در انتظار ثبت فیش | Admin Edit (`dashboard/invoices/edit/{hash}`) | [admin_edit_03_waiting_receipt.png](admin_edit_03_waiting_receipt.png) | 140,468 bytes | رزرو شده (Piece Reserved) |
| 03 | `WAITING_RECEIPT` | در انتظار ثبت فیش | Admin Show (`dashboard/invoices/show/{id}`) | [admin_show_03_waiting_receipt.png](admin_show_03_waiting_receipt.png) | 160,824 bytes | رزرو شده (Piece Reserved) |
| 04 | `WAITING_CONFIRMATION` | در انتظار تایید فیش توسط مدیر | Customer (`invoice/{hash}`) | [customer_04_waiting_confirmation.png](customer_04_waiting_confirmation.png) | 83,010 bytes | رزرو شده (Piece Reserved) |
| 04 | `WAITING_CONFIRMATION` | در انتظار تایید فیش توسط مدیر | Admin Edit (`dashboard/invoices/edit/{hash}`) | [admin_edit_04_waiting_confirmation.png](admin_edit_04_waiting_confirmation.png) | 171,465 bytes | رزرو شده (Piece Reserved) |
| 04 | `WAITING_CONFIRMATION` | در انتظار تایید فیش توسط مدیر | Admin Show (`dashboard/invoices/show/{id}`) | [admin_show_04_waiting_confirmation.png](admin_show_04_waiting_confirmation.png) | 175,078 bytes | رزرو شده (Piece Reserved) |
| 05 | `PAID` | پرداخت شده و تایید شده | Customer (`invoice/{hash}`) | [customer_05_paid.png](customer_05_paid.png) | 76,079 bytes | فروخته شده (Piece Sold) |
| 05 | `PAID` | پرداخت شده و تایید شده | Admin Edit (`dashboard/invoices/edit/{hash}`) | [admin_edit_05_paid.png](admin_edit_05_paid.png) | 147,541 bytes | فروخته شده (Piece Sold) |
| 05 | `PAID` | پرداخت شده و تایید شده | Admin Show (`dashboard/invoices/show/{id}`) | [admin_show_05_paid.png](admin_show_05_paid.png) | 156,228 bytes | فروخته شده (Piece Sold) |
| 06 | `PROCESSING` | در حال بسته‌بندی در انبار | Customer (`invoice/{hash}`) | [customer_06_processing.png](customer_06_processing.png) | 73,225 bytes | فروخته شده (Piece Sold) |
| 06 | `PROCESSING` | در حال بسته‌بندی در انبار | Admin Edit (`dashboard/invoices/edit/{hash}`) | [admin_edit_06_processing.png](admin_edit_06_processing.png) | 144,889 bytes | فروخته شده (Piece Sold) |
| 06 | `PROCESSING` | در حال بسته‌بندی در انبار | Admin Show (`dashboard/invoices/show/{id}`) | [admin_show_06_processing.png](admin_show_06_processing.png) | 155,007 bytes | فروخته شده (Piece Sold) |
| 07 | `OUT_FOR_DELIVERY` | تحویل به پیک موتوری | Customer (`invoice/{hash}`) | [customer_07_out_for_delivery.png](customer_07_out_for_delivery.png) | 81,234 bytes | فروخته شده (Piece Sold) |
| 07 | `OUT_FOR_DELIVERY` | تحویل به پیک موتوری | Admin Edit (`dashboard/invoices/edit/{hash}`) | [admin_edit_07_out_for_delivery.png](admin_edit_07_out_for_delivery.png) | 149,988 bytes | فروخته شده (Piece Sold) |
| 07 | `OUT_FOR_DELIVERY` | تحویل به پیک موتوری | Admin Show (`dashboard/invoices/show/{id}`) | [admin_show_07_out_for_delivery.png](admin_show_07_out_for_delivery.png) | 164,353 bytes | فروخته شده (Piece Sold) |
| 08 | `COMPLETED` | سفارش تکمیل شده و تحویل داده شده | Customer (`invoice/{hash}`) | [customer_08_completed.png](customer_08_completed.png) | 73,375 bytes | فروخته شده نهایی (Piece Sold) |
| 08 | `COMPLETED` | سفارش تکمیل شده و تحویل داده شده | Admin Edit (`dashboard/invoices/edit/{hash}`) | [admin_edit_08_completed.png](admin_edit_08_completed.png) | 124,656 bytes | فروخته شده نهایی (Piece Sold) |
| 08 | `COMPLETED` | سفارش تکمیل شده و تحویل داده شده | Admin Show (`dashboard/invoices/show/{id}`) | [admin_show_08_completed.png](admin_show_08_completed.png) | 152,076 bytes | فروخته شده نهایی (Piece Sold) |
| 09 | `CANCELED` | لغو شده توسط مدیر یا خریدار | Customer (`invoice/{hash}`) | [customer_09_canceled.png](customer_09_canceled.png) | 83,145 bytes | آزاد شده به قفسه فروش (Piece Restored) |
| 09 | `CANCELED` | لغو شده توسط مدیر یا خریدار | Admin Edit (`dashboard/invoices/edit/{hash}`) | [admin_edit_09_canceled.png](admin_edit_09_canceled.png) | 115,705 bytes | آزاد شده به قفسه فروش (Piece Restored) |
| 09 | `CANCELED` | لغو شده توسط مدیر یا خریدار | Admin Show (`dashboard/invoices/show/{id}`) | [admin_show_09_canceled.png](admin_show_09_canceled.png) | 153,210 bytes | آزاد شده به قفسه فروش (Piece Restored) |
| 10 | `FAILED` | ناموفق / انقضای مهلت پرداخت | Customer (`invoice/{hash}`) | [customer_10_failed.png](customer_10_failed.png) | 85,754 bytes | آزاد شده به قفسه فروش (Piece Restored) |
| 10 | `FAILED` | ناموفق / انقضای مهلت پرداخت | Admin Edit (`dashboard/invoices/edit/{hash}`) | [admin_edit_10_failed.png](admin_edit_10_failed.png) | 117,091 bytes | آزاد شده به قفسه فروش (Piece Restored) |
| 10 | `FAILED` | ناموفق / انقضای مهلت پرداخت | Admin Show (`dashboard/invoices/show/{id}`) | [admin_show_10_failed.png](admin_show_10_failed.png) | 153,961 bytes | آزاد شده به قفسه فروش (Piece Restored) |


## Lifecycle Transition Matrix & Branching Paths

1. **Online Gateway Flow**:
   - `PENDING` -> Payment Gateway -> `PAID` (Piece Marked Sold) or `FAILED` (Stock Restored).
2. **Offline Card-to-Card Flow**:
   - `AWAITING_PAYMENT` / `WAITING_RECEIPT` -> Customer Receipt Upload -> `WAITING_CONFIRMATION`.
   - Admin 4-Point Checklist Approval -> `PAID` / `PROCESSING`.
   - Admin Rejection or Deadline Expiration -> `CANCELED` or `FAILED` (Stock Restored).
3. **Fulfillment & Delivery Flow**:
   - `PAID` -> Admin Packaging -> `PROCESSING` -> Courier Assignment -> `OUT_FOR_DELIVERY` (4-Digit PIN SMS Dispatched).
   - Courier Delivery Code Verification -> `COMPLETED` (`InvoiceCompleted` event dispatched).
4. **Cancellation & Expiration Flow**:
   - Customer or Admin Cancellation -> `CANCELED` (Stock Released to Available).
   - Overdue Offline Payment (`offline:expire`) -> `FAILED` (Stock Released to Available).