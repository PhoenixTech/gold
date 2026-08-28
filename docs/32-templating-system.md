# Templating Architecture (Plain MVC Migration)

> **Architecture Update**: The front-end has been migrated to a **standard Plain MVC architecture**. All client pages are now rendered with direct Blade templates in `resources/views/client/`, modular SCSS in `resources/sass/client/`, modular JavaScript in `resources/js/client/`, and explicit data passing from controllers.

---

## 1. The big picture

The website front-end is **not** a set of static Blade pages. Every page is assembled at runtime from three layers:

| Layer    | Storage / location                                  | Role                                        |
|----------|-----------------------------------------------------|---------------------------------------------|
| **Area** | `areas` DB table (`App\Models\Area`)                | Named page type (e.g. `index`, `product`, `customer`) |
| **Part** | `parts` DB table (`App\Models\Part`)                | One concrete segment placed in an area, with sort order |
| **Segment** | `resources/views/segments/{segment}/{Part}/...`  | The actual Blade markup + PHP class + SCSS/JS |

A page controller only decides **which area** it is, fetches data, and renders a generic Blade wrapper (`client.default-list` or `client.welcome`). That wrapper then asks the database "which parts belong to this area?" and renders them in `sort` order.

---

## 2. Walk-through: the customer dashboard

Route: `GET /profile` → `CustomerController@profile` (`app/Http/Controllers/CustomerController.php:54`)

```php
public function profile()
{
    $area = 'customer';
    $title = __("Profile");
    $subtitle = 'You information';
    return view('client.default-list', compact('area', 'title', 'subtitle'));
}
```

That's all the controller does — the "dashboard" itself is entirely data-driven:

1. **Area exists in the DB** — seeded by `AreaSeeder.php` with `name = 'customer'`, `valid_segments = ["top","header","footer","menu","parallax","other","customer","ads"]`, `max = 6`, `preview = 'client.profile'`.

2. **A Part row points at the segment** — `PartSeeder.php:275`:

   ```php
   $part->segment = 'customer';
   $part->part = 'AvisaCustomer';
   $part->area_id = Area::where('name', 'customer')->first()->id;
   ```

3. **The wrapper renders it** — `resources/views/client/default-list.blade.php`:

   ```blade
   @extends('website.inc.website-layout')
   @section('content')
       <main>
           @if(findArea($area)->use_default) ... defaultHeader parts ... @endif
           @foreach(getParts($area) as $part)
               @php($p = $part->getBladeWithData($model ?? null))
               @include($p['blade'], ['data' => $p['data']])
           @endforeach
           @if(findArea($area)->use_default) ... defaultFooter parts ... @endif
       </main>
   @endsection
   ```

4. **The segment is a folder** — `resources/views/segments/customer/AvisaCustomer/`:

   ```
   AvisaCustomer.blade.php   ← the dashboard markup (tabs: summary, invoices, profile, credit, tickets, addresses, favorites …)
   AvisaCustomer.php         ← class with static onAdd / onRemove / onMount
   AvisaCustomer.json        ← metadata (name, version, author …)
   AvisaCustomer.js          ← optional, auto-imported into the build
   AvisaCustomer.scss        ← optional, auto-imported into the build
   screenshot.png            ← shown in the admin designer
   ```

The blade file uses `$data` (the `Part` model returned by `onMount`) and queries the auth'd customer directly:

```blade
<section id='AvisaCustomer' class='live-setting' data-live="{{$data->area_name.'_'.$data->part}}">
    ...
    {{auth('customer')->user()->name}}
    {{number_format(auth('customer')->user()->invoices()->count())}}
```

---

## 3. How the pieces connect

### 3.1 `getParts()` — resolve an area's parts

`app/Helpers/Helper.php:765`

```php
function getParts($areaName, $custom = null)
{
    if ($custom != null) { /* parts with custom = $custom win */ }
    return Area::where('name', $areaName)->first()->parts()->orderBy('sort')->get();
}
```

- Parts belong to an area via `area_id`.
- A part with a **`custom`** value (e.g. `Product12`, `Category3`) overrides the default set for a specific record. `AreaController::updateModel` writes per-model overrides and stores them as JSON in the model's `theme` column (`findArea($name, $model)` returns that JSON instead of the DB area).

### 3.2 `Part::getBladeWithData()` — the magic glue

`app/Models/Part.php:21`

```php
public function getBladeWithData($item = null)
{
    $className = ucfirst($this->part);
    $handle = "\\Resources\\Views\\Segments\\$className";
    return [
        'blade' => 'segments.' . $this->segment . '.' . $this->part . '.' . $this->part,
        'data'  => $handle::onMount($this, $item),
    ];
}
```

Three conventions are encoded as **magic strings**:

1. The PHP class for part `AvisaCustomer` is `Resources\Views\Segments\AvisaCustomer` (namespace declared in `AvisaCustomer.php`).
2. The Blade file path is `segments.customer.AvisaCustomer.AvisaCustomer` → `resources/views/segments/customer/AvisaCustomer/AvisaCustomer.blade.php`.
3. `onMount($part, $model)` returns the `$data` variable exposed to that Blade file (usually the `Part` itself).

### 3.3 Segment lifecycle — `onAdd` / `onRemove` / `onMount`

Every segment class implements three static methods (`app/Observers/PartObsever.php` calls them):

| Method      | When                                                                    | Typical job                                      |
|-------------|-------------------------------------------------------------------------|--------------------------------------------------|
| `onAdd`     | Part row created / part swapped in admin designer                       | Create `Setting` rows for this part instance     |
| `onRemove`  | Part row deleted / part swapped out                                     | Delete those `Setting` rows                      |
| `onMount`   | Every page render                                                       | Return the data the Blade template needs         |

Example `AuthorSlider.php` (an `index` segment) registers settings named by prefixing the area + part:

```php
$setting->key = $part->area_name . '_' . $part->part . '_title';   // e.g. index_AuthorSlider_title
$setting->type = 'TEXT';
```

The Blade reads them back with the same magic key:

```blade
{{ getSetting($data->area_name.'_'.$data->part.'_title') }}
{{ getGroupPostsBySetting($data->area_name.'_'.$data->part.'_group', 10) }}
```

So **a segment's configurable fields are just `Setting` rows** keyed `{area}_{part}_{field}`. Setting types (`TEXT`, `COLOR`, `GROUP`, `IMAGE`, …) drive the admin panel form.

### 3.4 Live customization in the panel

- Each rendered segment root element has `class="live-setting"` and `data-live="{area}_{part}"`.
- `resources/js/client-custom/customize.js` listens for clicks on those elements and opens the settings form filtered by that prefix, so the admin can edit a part's settings visually ("live settings").

### 3.5 Asset pipeline

`php artisan client` (`app/Console/Commands/clientAssetGenerator.php`):

1. Reads `gfx()` colors (`Gfx` table) and writes them into `resources/sass/client.scss` as SCSS vars + CSS custom properties.
2. Scans every **distinct used Part** and imports its `.scss` and `.js` from the segment folder.
3. Imports everything under `resources/sass/client-custom/` and `resources/js/client-custom/`.

Then `php artisan build` runs Vite. `AreaController::update`/`build` trigger `php artisan client` automatically after saving a design.

### 3.6 Admin designer

- `Admin/AreaController@design` lists an area's parts and all valid segment folders (scanning the filesystem, filtered by the area's `valid_segments`).
- `designModel` allows per-record overrides for Group / Category / Post / Product (`theme` JSON column, `custom` parts).
- Area/Part editing is restricted to the `developer` role.

---

## 4. The mental model (short version)

```
Route → Controller (chooses $area) → client.default-list wrapper
     → getParts($area)      → [Part rows ordered by sort]
     → Part::getBladeWithData() → blade path + onMount() data
     → @include(segments.{segment}.{part}.{part})
         └─ Blade reads getSetting("{area}_{part}_…")   ← created by onAdd()
         └─ root element data-live="{area}_{part}"       ← panel live editor
```

Everything the page shows is a **database composition of pre-built segment folders**. No Blade changes are needed to re-arrange a page; only `parts` rows (via the admin designer).

---

## 5. Real-world snapshot (behnam_gold DB)

The live database (this project's local import, `behnam_gold`) shows how the system is actually configured. Note: it **differs from the seeders** — the admin designer has rearranged parts since seeding.

### 5.1 Areas (26 rows)

| name | max | use_default | preview | actual parts (segment `part`, sort) |
|------|-----|-------------|---------|--------------------------------------|
| `preloader` | 1 | 1 | – | preloader `PreloaderCircle` (0) |
| `floats` | 2 | 1 | – | *(empty)* |
| `defaultHeader` | 2 | **0** | – | menu `AplMenu` (0), header `ParallaxHeader` (1) |
| `defaultFooter` | 2 | 1 | – | footer `WaveFooter` (2) |
| `index` | 10 | **0** | client.welcome | menu `ZarMenu` (0), index `WTFIndex` (1), index `Natalia2Categories` (2), index `NeginNews` (3), index `BottomBar` (4), footer `WTFFooter` (5) |
| `post` | 6 | 1 | – | post `PostSidebar` (0), attachments `SimpleAttachmentList` (1) |
| `posts-list` | 6 | 1 | client.posts | posts_page `GridPostListSidebar` (1) |
| `clip` | 6 | 1 | – | clip `DorClip` (1) |
| `clips-list` | 6 | 1 | client.clips | clips_page `ClipListGrid` (1) |
| `gallery` | 6 | 1 | – | gallery `GallaryGrid` (1) |
| `galleries-list` | 6 | 1 | client.galleries | galleries_page `GalleriesList` (1) |
| `product` | 6 | 1 | – | product `ProductAria` (0) |
| `products-list` | 6 | 1 | client.products | products_page `ProductGridSidebar` (1) |
| `attachment` | 6 | 1 | – | attachment `AttachmentWithPreview` (1) |
| `attachments-list` | 6 | 1 | client.attachments | attachments_page `DenaAttachList` (1) |
| `category` | 6 | 1 | – | category `SubCategoriesGrid` (0), products_page `ProductGridHiddenSidebar` (1), category `ParallelCategoriesGrid` (2) |
| `group` | 6 | 1 | – | posts_page `GridPostListSidebar` (1) |
| `card` | 6 | 1 | client.card | card `NsCard` (1) |
| `login` | 6 | 1 | client.sign-in | login `LoginPatternBg` (1) |
| `register` | 6 | 1 | client.sign-up | register `SimpleRegister` (1) |
| `customer` | 6 | 1 | client.profile | customer `AvisaCustomer` (1) |
| `invoice` | 3 | 1 | – | invoice `LianaInvoice` (1) |
| `compare` | 4 | 1 | – | compare `CompareProducts` (1) |
| `contact-us` | 4 | 1 | – | contact `MeloContact` (1) |
| `product-grid` | 1 | 1 | – | product_grid `ShivaProductGrid` (0) |
| `under-construction` | 1 | 1 | client.under-construction | *(empty)* |

Notable: `defaultHeader` and `index` have `use_default = 0` — so on the homepage the header parts are rendered **from the `index` area's own parts** (that's why `ZarMenu`/`WTFFooter` are direct members of `index`), not from the shared `defaultHeader`/`defaultFooter`. `use_default = 1` on other areas makes them inherit the shared header/footer parts.

### 5.2 Theme settings in practice

All segment configuration lives in the `settings` table (`section = 'theme'`), keyed `{area}_{part}_{field}`. Real examples from the DB:

```text
index_NeginNews_groups          GROUP_SET   index NeginNews groups
index_NeginNews_title           EDITOR      index NeginNews text 1
index_NeginNews_webp            FILE        index NeginNews image
index_ProductsSlider_query      PRODUCT_QUERY index ProductsSlider query
index_WTFIndex_categories       CATEGORY_SET index WTFIndex categories
index_ZarMenu_menu              MENU        index ZarMenu menu
index_Natalia2Categories_text   EDITOR      index Natalia2Categories modern categories subtitle
index_BottomBar_color1          COLOR       index BottomBar second gradiant color
login_LoginPatternBg_color1     COLOR       login LoginPatternBg second gradiant color 1
login_LoginPatternBg_png        FILE        login LoginPatternBg background pattern image
products-list_ProductGridSidebar_invert CHECKBOX products-list ProductGridSidebar invert sidebar position
invoice_LianaInvoice_desc        EDITOR      invoice LianaInvoice invoice footer description
defaultHeader_AplMenu_menu       MENU        defaultHeader AplMenu menu
defaultHeader_ParallaxHeader_jpg FILE        defaultHeader ParallaxHeader default image
```

**Multilingual values:** `Setting` uses Spatie `HasTranslations` on `value` (`app/Models/Setting.php:9`), so values are stored as JSON per locale, e.g. `{"fa":"…","en":"…"}`. `getSetting()` (`Helper.php:674`) returns:
- `$x->value` (current-locale translation) when xlang is disabled, **or** when the type is `TEXT` / `LONGTEXT` / `EDITOR`;
- `$x->raw` (the raw JSON) for all other types when xlang is enabled.

**Full setting-type taxonomy** (`Setting::$settingTypes`, `app/Models/Setting.php:15`):

```php
TEXT, NUMBER, LONGTEXT, CODE, EDITOR, CATEGORY, GROUP, CHECKBOX, FILE,
COLOR, SELECT, MENU, LOCATION, ICON, DATE, DATETIME, TIME,
PRODUCT_QUERY, POST_QUERY, CATEGORY_SET, GROUP_SET
```

These types drive the admin settings form; a segment's `onAdd()` only needs to create rows with the right `type` and `size`.

**`parts.data`:** every part row in this DB stores `[]` — the JSON `data` column is effectively unused; all configuration goes through `settings`.

### 5.3 Design tokens (`gfxes` table)

`gfx()` (`Helper.php:260`) returns `Gfx::pluck('value','key')` — the DB-driven design tokens:

```text
background  #ffffff      primary  #db9a00      secondary #3d3846
text        #111111      dark     0            border-radius 0px
shadow      2px 2px 4px #feffff   container    container     font Vazir
```

Segments render layout width via `gfx()['container']` (a Bootstrap/`container` class string) and `php artisan client` compiles these into SCSS vars (`$xshop-primary`, …) + CSS custom properties (`--xshop-primary`, …) in `resources/sass/client.scss`.

### 5.4 Theme overrides

No `custom` parts exist in this DB — the per-model override mechanism (`theme` JSON on Group/Category/Post/Product, `custom` on parts) is available but unused.

---

## 6. How to add a new segment (today's process)

1. Create `resources/views/segments/{segment}/{Name}/`:
   - `{Name}.blade.php` — markup, root element `class="live-setting" data-live="{{$data->area_name.'_'.$data->part}}"`.
   - `{Name}.php` — `namespace Resources\Views\Segments; class {Name} { onAdd / onRemove / onMount }`.
   - `{Name}.json` — metadata.
   - `{Name}.scss` / `{Name}.js` — optional; auto-bundled once the part is used.
   - `screenshot.png` — shows in the designer.
2. Make sure the segment's folder name is in some area's `valid_segments`.
3. In the admin designer, add the part to the area (this triggers `onAdd`, creating its settings).
4. Run `php artisan client && php artisan build` (or the panel's build button).

---

## 7. Pain points (why this is hard to change, and hard for AI)

1. **Heavy magic-string conventions.**
   - Class name derived via `ucfirst($part->part)` + namespace string concatenation (`Part.php:23-24`).
   - Blade path built by string concatenation (`Part.php:25`).
   - Setting keys built by prefix concatenation `{area}_{part}_{field}` scattered over every segment's `onAdd` and Blade files.
   A rename, or a part with an unexpected name (e.g. a digit in it), silently breaks rendering.

2. **Segment settings exist only as runtime data.**
   There is no manifest of "which settings this segment needs". The schema lives inside `onAdd()` code, is materialized as `Setting` rows, and is consumed by magic keys in the Blade. An AI (or developer) cannot know a segment's configuration surface without reading `onAdd()` + the Blade + the Settings table.

3. **Values are multilingual JSON, not plain values.**
   With `app.xlang` enabled, setting values are stored as `{"fa":"…","en":"…"}` and `getSetting()` returns either the translated value or the raw JSON depending on the setting type — so any new helper or query must replicate that branching to avoid leaking raw JSON into the front-end.

4. **No explicit contract for `onMount`.**
   The return value can be anything; nothing type-checks it against what the Blade expects. `$data` in the Blade and the `$item` parameter (e.g. `$model`) are ad-hoc.

5. **Duplicate, hand-maintained lists.**
   - `Area::$allSegments` (model), `AreaSeeder`'s `valid_segments`, and the actual filesystem folders must stay in sync by hand.
   - Segment registration isn't discoverable: there is no registry, only the filesystem + `AreaSeeder`.

6. **Side effects at unusual moments.**
   `onAdd`/`onRemove` write DB rows from an Eloquent observer — creating/renaming parts from outside the admin can leave orphan settings.

7. **Per-model theme overrides** (`theme` JSON on Group/Category/Post/Product, `custom` on parts) are a second, undocumented composition mechanism.

8. **No automated tests / lint** around segment structure; breaking a segment's class name or Blade path fails only at render time.

---

## 8. Recommended changes (AI-friendly refactor)

These are ordered: do 1–3 first (low risk, high value for AI agents), 4–8 next.

### 8.1 Add a declarative manifest per segment (biggest win)

Give every segment a machine-readable schema, e.g. extend `{Name}.json` or add `{Name}.manifest.php`:

```json
{
  "name": "AuthorSlider",
  "segment": "index",
  "settings": [
    { "key": "title",   "type": "TEXT",  "default": "Lorem ipsum", "title": "Title" },
    { "key": "group",   "type": "GROUP", "default": null },
    { "key": "color",   "type": "COLOR", "default": "#7a0000" }
  ],
  "blade": "AuthorSlider.blade.php",
  "data": { "title": "string", "items": "Collection<Post>" }
}
```

Then:
- `onAdd`/`onRemove` are **auto-generated from the manifest** (a base class loops `settings` and creates/deletes `Setting` rows) — no per-segment DB code.
- An AI can answer "what does this segment configure?" by reading one JSON file instead of executing code.
- A new command `php artisan segments:list` can validate every folder (class exists, manifest valid, blade exists, settings resolvable).

### 8.2 Replace magic strings with a registry / base class

Introduce `App\Segments\Segment` (abstract base) and `SegmentRegistry`:

- `Part::getBladeWithData()` resolves the class through the registry (`SegmentRegistry::resolve($part)`), which maps part name → class, blade, and segment without string concatenation.
- Base class provides typed, documented contracts:

```php
abstract class Segment
{
    /** @return array<string,mixed> view data passed to the blade */
    abstract public static function onMount(Part $part, ?Model $item = null): array;

    /** @return SettingDefinition[] */
    public static function settings(): array { return static::manifest()['settings']; }
}
```

- Add a `make:segment` artisan command that scaffolds the folder, class, manifest and empty blade — so AI and humans generate valid segments instead of hand-copying.

### 8.3 Centralize setting-key logic

Keep `{area}_{part}_{field}` as the storage format but build it in **one place** (e.g. `SettingKey::for($part, 'title')`), and use it in both `onAdd` and the Blade helpers (`getSetting`, `getGroupPostsBySetting`, …). Consider a helper `segmentSetting($part, $key, $default)`.

### 8.4 Sync valid segments from the filesystem

Replace hand-maintained `valid_segments`/`Area::$allSegments` with an artisan command (`php artisan areas:sync`) that scans `resources/views/segments/*` and updates the allowed segment lists; keep the DB as the source of truth for *composition*, the filesystem as the source of truth for *capabilities*.

### 8.5 Add tests around the render pipeline

- A feature test per area: render `client.default-list` with a fake area/part (a tiny stub segment) and assert it includes the right blade and that `onMount` data is passed.
- A lint test that iterates every segment folder and checks: class + namespace exist, `onAdd/onRemove/onMount` signatures present, blade file exists, manifest parses.

### 8.6 Make the observer side-effect safe

Move setting creation from the Eloquent observer to the segment's `onAdd` via the manifest (7.1), and make it idempotent (update-or-create by key) so re-running `artisan db:seed` or a part swap never duplicates settings.

### 8.7 Document the theme-override format

Write down the `theme` JSON shape (`{parts: Part[], use_default: bool, max: int}`) used by `AreaController::updateModel` and `findArea`, so the second composition mechanism isn't a secret.

---

## 9. File reference

| Path | Role |
|------|------|
| `app/Models/Area.php` | Area model, `$allSegments` list, `parts()` relation |
| `app/Models/Part.php` | Part model — magic glue `getBladeWithData()` |
| `app/Models/Setting.php` | Setting model — `$settingTypes` taxonomy, translatable `value` |
| `app/Models/Gfx.php` | Design tokens (`gfx()` helper, `client` asset generator) |
| `app/Observers/PartObsever.php` | Calls `onAdd`/`onRemove` on create/update/delete |
| `app/Helpers/Helper.php` | `getParts()` (765), `findArea()` (1455), `getSetting()` (674), `gfx()` (260) |
| `app/Http/Controllers/ClientController.php` | Front controllers choosing `$area` |
| `app/Http/Controllers/CustomerController.php:54` | Customer dashboard → area `customer` |
| `app/Http/Controllers/Admin/AreaController.php` | Admin designer, model overrides, build |
| `app/Console/Commands/clientAssetGenerator.php` | `php artisan client` — builds client.scss/js |
| `app/Console/Commands/AssetsBuild.php` | `php artisan build` — Vite |
| `resources/views/client/welcome.blade.php` / `default-list.blade.php` | Area wrapper templates |
| `resources/views/segments/{segment}/{Part}/` | Segment folders (blade, php, json, scss, js) |
| `resources/js/client-custom/customize.js` | Live-setting editor hook (`data-live`) |
| `database/seeders/AreaSeeder.php` / `PartSeeder.php` | Seed data for areas & parts |
