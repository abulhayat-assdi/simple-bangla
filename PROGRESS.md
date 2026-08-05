# Simple Bangla — Progress

Last updated: 2026-08-04

## Status

| Phase | State |
|---|---|
| 0 · Reference research | ✅ Done |
| 0 · `CLAUDE.md` | ✅ Done |
| 1 · Foundation | ✅ Done — awaiting review |
| 2 · Header | ⬜ Not started |
| 3 · Product card + homepage | ⬜ Not started |
| 4 · Shop page | ⬜ Not started |
| 5 · Single product | ⬜ Not started |
| 6 · Footer + mobile bar | ⬜ Not started |

---

## Decisions taken

| Question | Decision |
|---|---|
| Palette | **Black + warm cream**, matching the measured reference. `--sb-brand: #000000`, `--sb-bg-alt: #FFFCF7`, white footer, `#BA5E5E` for the struck-through price |
| Fonts | **Oswald** (headings + nav) + **Lato** (body, product titles, buttons) — two families, not the reference's five |
| Container | **1200px** per the project spec, not the reference's 1600px |
| EverCompare menu item | Skipped — product comparison is out of scope |
| Menu icons | Optional per item; the walker must render without one |
| Sticky header | Single header + `position: sticky`, never duplicated markup |

---

## Done

### Reference research (2026-08-04)

- WebFetch returns **403** on `demarkt.com.bd` — switched to the browser MCP, which renders
  the page and lets computed styles be read directly.
- Extracted and verified: full 3-level menu tree with per-item icons, homepage section order,
  product-card anatomy, header search form and cart widget, footer columns and links, mobile
  bottom bar, sticky-header mechanism, and the real colour/type values.
- Findings and every divergence from the written spec are recorded in `CLAUDE.md`.

Key divergences found (live site wins):

1. No full-width promo banner between Hot Deals and the category circles.
2. Banners appear as narrow+wide **pairs**, twice — not four separate banner sections.
3. Hot Deals slider carries **8** cards, not 6.
4. Not every top-level menu item has an icon → icon field is optional.
5. Extra menu item `EverCompare` — **out of scope, skipped**.
6. Palette is **black + warm cream**, not the red `#e63946` in the spec. Footer is **white**.
7. Reference container is 1600px; we use the spec's 1200px.
8. Their heading↔category wiring is broken on 5 of 6 rows — **not reproduced**.

### Phase 1 — Foundation (2026-08-04)

Files created (19):

```
simple-bangla/
├── style.css                       theme header only
├── functions.php                   constants + module includes
├── header.php  footer.php  sidebar.php
├── index.php  page.php  single.php  archive.php  search.php  404.php
├── inc/setup.php                   supports, menus, image sizes, widgets, default pages
├── inc/enqueue.php                 fonts, base CSS, palette inline, preconnect, defer
├── inc/customizer.php              9 palette colour controls
├── inc/template-tags.php           branding, search form, SVG icons, pagination, meta
├── inc/woocommerce.php             ৳ currency format, content wrappers
├── template-parts/content.php
├── template-parts/content-none.php
└── assets/css/base.css             tokens + reset + typography + layout + components
```

Delivered:

- All required theme supports: `woocommerce`, `wc-product-gallery-zoom`,
  `wc-product-gallery-lightbox`, `wc-product-gallery-slider`, `title-tag`, `post-thumbnails`,
  `custom-logo`, `html5`, `responsive-embeds`, plus feed links and selective refresh.
- Menus registered: `primary`, `footer-1`, `footer-2`, `footer-3`.
- Image sizes registered and exposed in the media picker: `150×150`, `600×600`, `1024×1024`.
- Widget areas: Shop Sidebar, Blog Sidebar.
- All 13 custom pages auto-created on theme activation; existing pages never overwritten.
- Design tokens in one `:root` block, all 9 colours wired to Customizer controls. Only
  changed colours are emitted inline, so a default install ships **zero** extra bytes.
- Mobile-first CSS at 480 / 768 / 1024 / 1200, container 1200px.
- Currency: `৳ 1,999` — comma thousands, zero decimals, via WooCommerce display filters
  (no option overwriting, no core edits).
- Accessibility: skip link, visible focus rings, `screen-reader-text`, `prefers-reduced-motion`.
- Admin notice when WooCommerce is inactive; the theme still renders without it.

**CSS budget: 13.9 KB of 60 KB. JS: 0 KB of 30 KB** (no theme JS needed yet).

#### Self-check

| Check | Result |
|---|---|
| `php -l` | ✅ **Now verified (2026-08-05).** No PHP binary is installed, so the theme was booted on real WordPress + WooCommerce 11.0 under WordPress Playground (PHP 8.3 as WebAssembly, via `npx @wp-playground/cli`). Home, single, search, 404, shop and cart all render with **zero** fatals, warnings, notices or deprecations |
| Undefined functions | ✅ 31 defined, 31 referenced, exact match |
| Unescaped output | ✅ No raw `echo $var`, no `<?=` short tags |
| Untranslated strings | ✅ Every user-facing string carries the `simple-bangla` domain |
| Inline styles | ✅ None. The only inline CSS is the Customizer palette via `wp_add_inline_style` |
| `!important` | ✅ Two, both justified in comments: `screen-reader-text` (WP core convention) and the reduced-motion override |

---

## Open

### Bug found while running the theme (2026-08-05)

- [ ] **`/privacy-policy/` 404s on a fresh install.** WordPress core auto-creates a Privacy
      Policy page as a **draft**. `simple_bangla_create_default_pages()` sees it via
      `get_page_by_path()` and skips it, so the theme never publishes one and the footer link
      is dead. Fix: when the existing page is a draft the theme created no content for,
      publish it instead of skipping — check `post_status`, not just existence.
      See `inc/setup.php:175`.

### Local preview

- `preview/` holds static HTML mirrors of the Phase 1 templates for eyeballing the design
  without a PHP stack. Not shipped. See `preview/README.md`.
- Real WordPress runs under WordPress Playground (WASM PHP under Node) — no XAMPP or MySQL
  needed. Command and the two Windows gotchas are in `preview/README.md`.

### Needed from the user (`// TODO:` placeholders until supplied)

Not blocking Phase 2, but needed by Phase 6:

- [ ] Phone number
- [ ] WhatsApp number
- [ ] Email
- [ ] Facebook URL
- [ ] Instagram URL
- [ ] YouTube URL
- [ ] Messenger username

### Screenshots wanted

- [ ] **Sale ribbon** — its shape and fill are not readable from computed styles (white text on
      a transparent container, so the fill comes from somewhere the DOM does not expose)
- [ ] Header bar and one product card — to confirm spacing, gutters and hover states

### Not started

Phases 2–6 per the build order in `CLAUDE.md`.

### Deferred, unassigned to a phase

- Seeding the 32 product categories listed in the spec — needs WooCommerce installed;
  planned as an importer alongside the Phase 4 shop work.
- Demo products and placeholder images.
