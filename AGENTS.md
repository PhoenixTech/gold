# Repository Guidelines

## 1. Localization & Persian (`fa`)
- **Primary Language**: Persian (`XLANG_MAIN=fa`). RTL-first layout with `Yekan Bakh VF` font.
- **Translation Keys**: Every `__('English Key')` in Blade or PHP must exist verbatim in `resources/lang/fa.json`. Use natural Persian terminology (no machine-translation tone).
- **Date & Numbers**: Use `PersianDate` or Jalali helpers for displaying Persian dates; format currency and weights using Iranian standard separators.
- **Cache**: Run `php artisan optimize:clear` after updating translation files.

## 2. Frontend & UI Standards

### Client Storefront (Design Tokens)
- Use design tokens defined in `resources/sass/client-custom/_tokens.scss`:
  - **Brand Gold**: `--xshop-primary` (`#db9a00`), `--xshop-primary-hover` (`#c48900`), accessible text `--xshop-primary-text` (`#8a5f00`).
  - **Surfaces & Text**: Slate warm grayscale (`--xshop-surface-0` through `--xshop-surface-300`, text: `--xshop-text-main`, `--xshop-text-body`, `--xshop-text-muted`).
  - **Elevation**: Multi-layer shadows (`--xshop-shadow-xs` to `--xshop-shadow-xl`).
- Never introduce hardcoded one-off hex colors when an existing `--xshop-*` token applies.

### Admin Panel (Native Bootstrap 5)
- Use native Bootstrap 5 RTL utility classes. Do not write custom CSS for admin views.
- **Icons**: Use RemixIcons (`ri-*-fill` / `ri-*-line`).
- **Sidebar**: Dark charcoal sidebar (`#3d3846`). Follow permissions wrapping with `@if(auth()->user()->hasAnyAccess(...))` and standard route names `admin.<resource>.index`.
- **Badges**: Use subtle classes (`badge bg-*-subtle text-*-emphasis border border-*-subtle`).
- **Notice Banners**: `alert alert-{warning|danger} border border-*-subtle shadow-sm d-flex align-items-center justify-content-between flex-wrap gap-2 p-3 mb-4 rounded-3`.
- **Readonly Inputs**: `.form-control.bg-light.text-dark.fw-bold[readonly]` with unit badge and helper tooltip.

## 3. Backend & Architecture Rules

### Controllers & Requests
- Controllers must remain thin. Delegate business logic, gold fee calculations, cart quoting, and stock adjustments to dedicated services in `app/Services/`.
- Always validate incoming user input via Form Request classes in `app/Http/Requests/`. Do not write large inline validator arrays in controller actions.

### Domain Logic & Types
- **Enums**: Always use backed Enums from `app/Enums/` (`InvoiceStatus`, `DeliveryStatus`, `MetalType`, `StockStatus`, etc.). Never compare or assign raw integer or string status values.
- **Financial & Stock Integrity**: All multi-step mutations (e.g. invoice payment approval, stock deduction, order cancellation) must run inside `DB::transaction()`.
- **Spatie Packages**: Adhere to Spatie conventions for Translatable model fields, Permission checks, and MediaLibrary collections.

## 4. Testing Standards
- **Feature Tests by Default**: Test full HTTP requests, Form Request validation, middleware, role permissions, and database assertions (`$this->assertDatabaseHas()`).
- **Unit Tests for Math & Services**: Test pricing algorithms (`ProductPriceCalculator`), cart quotas (`CartQuoteService`), and SKU generators in isolation with high-coverage boundary cases.
- **Regression Tests**: Every bug fix must include an automated test that fails before the fix and passes after.
- **No Heavy E2E for Backend Logic**: Reserve browser-driven tests strictly for critical JavaScript-dependent UI journeys (e.g. checkout completion).
