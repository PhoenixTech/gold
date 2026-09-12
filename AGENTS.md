# Repository Guidelines

## Localization & Persian (`fa`)
- **Primary Language**: Persian (`XLANG_MAIN=fa`).
- **Translation Rule**: Every `__('English Key')` must exist verbatim in `resources/lang/fa.json` with a natural Persian translation, no ai smell.
- **Verification**: Ensure valid JSON in `resources/lang/fa.json` and clear cache via `php artisan optimize:clear`.

## Admin UI Patterns (Native Bootstrap 5)
Always use native Bootstrap 5 utilities and components. Avoid custom CSS. Use RemixIcons (`ri-*-fill` / `ri-*-line`).

- **Notice Banners**: `alert alert-{warning|danger} border border-*-subtle shadow-sm d-flex align-items-center justify-content-between flex-wrap gap-2 p-3 mb-4 rounded-3`
  - Start side: Icon `ri-*-fill text-* fs-3`, Title `<strong class="d-block text-dark">`, Subtext `<span class="text-muted fs-13">`
  - End side: Action button `<a class="btn btn-sm btn-{warning|danger} fw-bold px-3">`
- **Stat Card Sub-Alerts**: `mt-2 pt-2 border-top d-flex align-items-center justify-content-between text-{danger|warning-emphasis} fs-12` with filter badge `.badge.bg-*-subtle`.
- **Readonly / Auto-Calculated Fields**:
  - Label: `<span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle fs-11"><i class="ri-lock-line me-1"></i>{{ __('Auto-calculated') }}</span>`
  - Input: `.form-control.bg-light.text-dark.fw-bold[readonly]` in input-group with unit badge.
  - Helper: `<small class="text-muted d-flex align-items-center gap-1 mt-1"><i class="ri-information-line text-primary"></i>...</small>`
- **Badges & Filters**: Use subtle badges (`.badge.bg-*-subtle.text-*.border.border-*-subtle`) with `title="..."`; WordPress-style quick filters with count badges.
- **Dynamic Breadcrumbs**: `<code class="fw-bold text-primary font-monospace bg-primary-subtle px-2 py-0.5 rounded border border-primary-subtle fs-12" id="...">`

## Admin Sidebar Menu (`panel-side-navbar.blade.php`)
When adding items to `resources/views/components/panel-side-navbar.blade.php`:
1. **Permissions**: Wrap item in `@if(auth()->user()->hasAnyAccess('permission_name'))` and update parent group's `hasAnyAccesses([...])`.
2. **Active State**: Use `class="{{ request()->routeIs('admin.<name>.*') ? 'active' : '' }}"`.
3. **Icons & Route**: Use RemixIcons and standard admin route names: `route('admin.<resource>.index')`.
4. **Localization**: Wrap labels in `{{ __('...') }}` and add translations to `resources/lang/fa.json`.
