# Repository Guidelines

## Localization & Persian Translations

This project operates with Persian (`fa`) as its primary user-facing language (`XLANG_MAIN=fa`).

Whenever adding or modifying user-facing text wrapped in `__('...')` (such as Blade template labels, helper text, tooltips, alert messages, flash notices, or validation error messages):

1. **Exact Key Matching**:
   - The exact English key used in `__('...')` must exist verbatim as a key in `resources/lang/fa.json`.
   - Avoid mismatched wording between the template call and the JSON key.

2. **Persian Translation Required**:
   - Every new `__('...')` key must have an accurate, natural Persian translation added to `resources/lang/fa.json`.
   - Never leave new English UI strings without their corresponding Persian entry.

3. **Verification**:
   - Validate JSON syntax in `resources/lang/fa.json` after editing.
   - Run `php artisan optimize:clear` or verify with tinker/tests so cached translations are refreshed and verified.

## Admin UI & Notification Design Patterns

When building or updating admin dashboard notices, forms, tables, and statistics, follow these established design patterns:

### 1. Dashboard & Summary Notice Banners
- **Container**: `alert alert-{warning|danger} border border-*-subtle shadow-sm d-flex align-items-center justify-content-between flex-wrap gap-2 p-3 mb-4 rounded-3`
- **Icon**: Large filled icon on the start side (`ri-alarm-warning-fill text-warning fs-3` or `ri-error-warning-fill text-danger fs-3`).
- **Text content**:
  - Title: `<strong class="d-block text-dark">...</strong>`
  - Subtext: `<span class="text-muted fs-13">...</span>`
- **Action button**: Compact action button on the end side:
  - `<a href="..." class="btn btn-sm btn-{warning|danger} fw-bold px-3"><i class="ri-eye-line me-1"></i>{{ __('View products') }}</a>`

### 2. Stat Card Sub-Alerts & Links
- Inside dashboard summary cards, append sub-alerts separated by a top border:
  - Container: `mt-2 pt-2 border-top d-flex align-items-center justify-content-between text-{danger|warning-emphasis} fs-12`
  - Label: `<span><i class="ri-*-line me-1"></i>Label: <b>count</b> items</span>`
  - Subtle link pill: `<span class="badge bg-*-subtle text-* border border-*-subtle">{{ __('Filter') }} &larr;</span>`

### 3. Auto-Calculated / Readonly Form Fields
- **Label**: Place an informative lock badge inside the label row:
  - `<label class="fw-semibold d-flex align-items-center justify-content-between"><span>Title</span><span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle fs-11"><i class="ri-lock-line me-1"></i>{{ __('Auto-calculated') }}</span></label>`
- **Input Group**: Use an input group with leading icon and trailing unit, with clear disabled visual cues:
  - Input: `class="form-control bg-light text-dark fw-bold border-start-0 border-end-0" readonly tabindex="-1" style="cursor: not-allowed;"`
  - Unit badge: `<span class="input-group-text bg-light text-muted border-start-0 fs-12">{{ __('pieces') }}</span>`
- **Helper text**: Include an info icon: `<small class="text-muted d-flex align-items-center gap-1 mt-1"><i class="ri-information-line text-primary"></i>...</small>`

### 4. Table & Quick Filter Badges
- **Table inline badges**: Use subtle variants with borders and explanatory tooltip:
  - `<span class="badge bg-danger-subtle text-danger border border-danger-subtle" title="...">`
  - `<span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle" title="...">`
- **Quick filter links**: WordPress-style links with count badge that turns bold/danger when count > 0.

### 5. Dynamic Metadata in Breadcrumbs
- When a form field dynamically updates (e.g. SKU, code), mirror it live in the breadcrumb as a pill badge:
  - `<code class="fw-bold text-primary font-monospace bg-primary-subtle px-2 py-0.5 rounded border border-primary-subtle fs-12" id="breadcrumb-product-sku"></code>`

## Prefer Native Bootstrap UI

Always prioritize native Bootstrap 5 components and utility classes over custom CSS or bespoke markup:

1. **Native Components First**: Use standard Bootstrap components (`alert`, `badge`, `input-group`, `btn`, `table`, `card`, `modal`, `form-control`, `form-select`) and utility classes (flexbox, spacing, sizing, borders, colors) whenever possible.
2. **Avoid Redundant Custom CSS**: Never write custom CSS rules or inline styles when an existing native Bootstrap utility or component can achieve the same result.
3. **Custom Styling as a Last Resort**: Only introduce custom CSS or classes when Bootstrap lacks a native equivalent or when an existing bespoke design system strictly requires it.
