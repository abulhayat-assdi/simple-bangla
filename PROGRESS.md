# Simple Bangla — Progress

Last updated: 2026-08-06

## Ordering now works end to end (2026-08-06)

The store owner reported that pressing Order did nothing. Two causes, both real:

1. **No payment method was enabled.** A fresh WooCommerce has every gateway off, so the
   checkout rendered but no order could ever complete.
2. **Cart and Checkout were WooCommerce 11's block pages**, which render entirely client-side
   and cannot be templated by a classic theme — so the theme's checkout design never appeared.

Fixed, and verified by placing real orders through the running site:

- **Card "Order Now" now adds the product and goes straight to checkout**, where it sits
  alongside anything already in the basket. Variable, grouped, external and out-of-stock
  products still go to their own page, with the label changed to "Choose Options".
- The product page's **Buy Now** already worked (it was the missing gateway that made it look
  broken) and is unchanged.
- **Cash on Delivery enabled**; **two flat rates** offered as a choice — ঢাকার ভেতরে ৳70 and
  ঢাকার বাইরে ৳120 — rather than matching a zone from a state field, which is what lets the
  form stay short.
- **Cart and Checkout switched to the classic shortcodes**, so `woocommerce/` overrides apply.
- **Checkout rebuilt in Bangla**: navy banner, three-step progress bar (কার্ট → অর্ডার →
  সম্পন্ন), and four fields only — name, mobile, full address, optional email. Company,
  second address line, city, state, postcode, order notes and the separate shipping-address
  form are all removed. Country is fixed to Bangladesh and hidden.
- **Thank-you page rebuilt in Bangla** with the last step marked done, a green tick, the order
  number, date, mobile, total and payment method, and a line telling the customer a
  representative will ring to confirm.

Verified: `Order Now → checkout → Place Order → order #180 created → thank-you page`, total
৳1,360 (৳1,290 + ৳70 delivery), zero PHP errors on every page.

All store configuration is done by the demo importer, never by the theme itself.

## Reference-matching pass (2026-08-06)

The user supplied screenshots of demarkt.com.bd showing the footer, header, hero and product
rows. Rather than eyeball them, the reference was fetched directly — `WebFetch` 403s, but
**plain `curl` with a desktop User-Agent returns 200** — and its Elementor stylesheets were
read for exact values. Everything in this pass is measured. The table of values lives in
`CLAUDE.md` under "Reference measurements".

**A correction.** An earlier pass told the user the reference homepage has no hero, and they
agreed on that basis. That was wrong: the page opens with a narrow one-card Hot Deals slider
beside a wide banner carousel. The first HTML scan found only the left column and missed the
hero images in Elementor slide backgrounds. Rebuilt as `template-parts/home/hero.php`.

Changed in this pass:

- **Page background** is now warm cream `#FBF4E2`, not white. `--sb-page` is a separate token
  from `--sb-bg` so cards, dropdowns and the search field keep a true white to sit on.
- **Footer rebuilt**: one grid of brand · three link columns · map. Brand block carries logo,
  phone, email, address and five contact circles. Headings are Raleway 900/20px, underlined.
  The copyright is a full-bleed black band with `#808080` text.
- **Header**: hamburger reproduced as an "All Categories" button that opens the same drawer at
  every width, with the mega menu kept beside it (the user chose this hybrid over the
  reference's burger-only nav). Search is a white pill, account is icon-only, cart is an
  outlined box carrying the running total. The nav hotline was dropped — the reference's nav
  strip carries nothing but the menu.
- **Section headings**: Baskervville 32px uppercase behind a thick black rule, closed by a
  black underline, with a black uppercase VIEW ALL button linking to that category archive.
- **Sale ribbon**: orange `#F85606` with the reference's `10px 0 10px 0` diagonal radius.
  This settles the "sale ribbon shape unknown" item that was open since Phase 0.
- **Product rows are now one-line carousels**: exactly 3 cards on desktop, 2 on a phone, with
  five-second autoplay, page dots, desktop arrows and touch swipe. Autoplay pauses on hover,
  focus, touch, when the row is off-screen, when the tab is hidden, and under
  `prefers-reduced-motion`.
- **Banner pairs** render as equal columns, as the reference shows them.
- **Fonts** are now four: Oswald, Lato, Raleway (footer headings), Baskervville (section
  headings). Baskervville has no Bengali glyphs, but fallback is per character, so Bangla text
  drops through to the system serif — the Bangla-ready requirement still holds.
- **`blueprint.json` moved into the repo** and now sets BDT, turns WooCommerce's "Coming soon"
  mode off, and runs the demo import automatically, so a restarted Playground comes up
  complete without manual admin steps.

### Card fixes (2026-08-06, from a user screenshot)

- **Every product card rendered its image twice**, with a stray "Sale!" label between the two
  copies, which roughly doubled the height of every card on the site.
  `woocommerce/content-product.php` draws its own image and ribbon and then fires
  `woocommerce_before_shop_loop_item_title` so badge plugins keep their extension point — but
  WooCommerce hangs `woocommerce_template_loop_product_thumbnail` and
  `woocommerce_show_product_loop_sale_flash` on that same hook. Both are now unhooked in
  `inc/woocommerce.php`; the hook itself still fires.
- **Card images are 4:3, not square**, matching the reference, and use `object-fit: contain`
  so a cable or a tripod is not cropped at the ends.
- **Placeholder art rewritten.** The first generator drew an abstract disc-and-rectangle with
  the product's initials stamped on it, which read as a coloured blob rather than a product.
  It now draws a recognisable silhouette per category — headphones, a watch, a ring light, a
  tripod, a keyboard and so on, twenty-odd shapes in all — in white-panelled artwork whose hue
  is derived from the product name, so neighbouring cards in a row differ. Still pure GD, still
  generated locally, still nothing copied from the reference.

## Status

| Phase | State |
|---|---|
| 0 · Reference research | ✅ Done |
| 0 · `CLAUDE.md` | ✅ Done |
| 1 · Foundation | ✅ Done |
| 2 · Header | ✅ Done |
| 3 · Product card + homepage | ✅ Done |
| 4 · Shop page | ✅ Done |
| 5 · Single product | ✅ Done |
| 6 · Footer + mobile bar | ✅ Done |
| — · Demo content importer | ✅ Done |
| — · Translation template | ✅ Done |

All six phases were built in one pass at the user's request, rather than stopping for review
after each. Everything below was verified against a real WordPress + WooCommerce 11 install
running under WordPress Playground.

---

## Decisions taken

| Question | Decision |
|---|---|
| Palette | **Black + warm cream**, matching the measured reference. `--sb-brand: #000000`, `--sb-bg-alt: #FFFCF7`, white footer, `#BA5E5E` for the struck-through price |
| Fonts | **Oswald** (headings + nav) + **Lato** (body, product titles, buttons) — two families, not the reference's five |
| Container | **1200px** per the project spec, not the reference's 1600px |
| EverCompare menu item | Skipped — product comparison is out of scope |
| Menu icons | Optional per item; the walker renders without one |
| Sticky header | Single header + `position: sticky` + `.is-stuck`, never duplicated markup |
| Build cadence | Phases 2–6 in one pass, reviewed at the end |
| Demo content | Shipped as a one-click importer under Appearance → Demo Content |
| UI language | English strings, every one wrapped for translation, with a generated `.pot` |
| Contact details | Customizer fields with visible placeholders; no contact detail is hard-coded |
| Product gallery | **Own vanilla gallery**, not WooCommerce's. See "Deliberate divergences" below |
| Shop filtering | GET parameters + `pre_get_posts`, re-fetched by JS. One query builder, not two |

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

Theme supports, menus, image sizes, widget areas, default pages, design tokens wired to nine
Customizer colour controls, mobile-first base stylesheet, ৳ currency formatting, accessibility
scaffolding, and a WooCommerce-missing admin notice.

### Phase 2 — Header (2026-08-05)

- `inc/nav-walker.php` — three-level walker. A top-level branch that has grandchildren renders
  as a multi-column **mega panel**; a shallow branch stays a plain dropdown, and a third level
  inside one flies out sideways. The decision is made in `display_element()`, the only point in
  the walk where grandchildren are visible.
- **Per-item icon field** on Appearance → Menus, backed by core's `wp.media` picker. Stored as
  an attachment ID in menu-item meta. Optional per item, as the live reference proves it must be.
- Every parent gets a real `<button aria-expanded>`. Hover is layered on top for pointer users
  rather than being the mechanism, so the menu is usable by keyboard and on touch.
- **Sticky header** via `position: sticky` plus an `.is-stuck` class set from an
  IntersectionObserver sentinel — no scroll handler on the main thread.
- **Live product search** (`inc/ajax-search.php`) — nonce-verified, debounced, arrow-key
  navigable. The form underneath is a plain GET search that works without JavaScript.
- **Cart widget** with WooCommerce fragment refresh, so count and subtotal update after an
  AJAX add-to-cart without redrawing the link.
- **Mobile drawer** with focus trap, Escape to close, and a resize guard so it can never be
  left open and focus-trapped on desktop.
- Menu fallback: with no menu assigned, the bar lists the store's own top product categories
  instead of rendering empty.

### Phase 3 — Product card + homepage (2026-08-05)

- `woocommerce/content-product.php` — the card, exactly as measured: sale ribbon, 1:1 image,
  two-line clamped title, `<del>`/`<ins>` price with the current price first, and a black
  **Order Now** pill that goes to the product page, not to add-to-cart.
- `front-page.php` renders the verified section order: Hot Deals → circles → 2 rows →
  banner pair → 2 rows → banner pair → 2 rows.
- Sliders are **CSS scroll-snap tracks**, not a carousel library. Arrows are injected by
  `slider.js` so they never appear as dead controls without JavaScript.
- Homepage heading text and target category are **separate Customizer settings** — the
  reference's broken wiring is structurally impossible to reproduce here.
- Category circles fall back to the category's initial when it has no thumbnail.

### Phase 4 — Shop (2026-08-05)

- `woocommerce/archive-product.php` with breadcrumb, result count, ordering, a collapsible
  filter panel and the product grid.
- **Filters are GET parameters** applied in `pre_get_posts`. That makes a filtered view
  linkable, bookmarkable and functional without JavaScript. `shop.js` re-fetches the same URL
  with `sb_ajax=1`, which returns only the results fragment — so there is exactly one query
  builder and the two paths cannot drift apart.
- Load-more, back-button support via `popstate`, price bounds cached in a transient that is
  invalidated whenever a product is saved or deleted.
- Recently viewed, stored in a first-party cookie holding nothing but product IDs.

### Phase 5 — Single product (2026-08-05)

- `woocommerce/single-product.php` — sticky gallery beside the summary, with WooCommerce's own
  summary hooks left intact so variations, reviews and plugins keep working.
- **Buy Now** is a second submit button inside WooCommerce's own form, so quantity, variations
  and validation all apply to it; a `woocommerce_add_to_cart_redirect` filter sends it to
  checkout. AJAX add-to-cart is disabled on product pages only, or the redirect would be
  silently swallowed.
- **Order on WhatsApp** button, pre-filled with the product name and URL.
- Delivery / warranty / returns assurance row.
- Related products overridden to use the theme's own card strip.

### Phase 6 — Footer + mobile (2026-08-05)

- White footer: brand row with address, phone, email and social icons; three link columns with
  page fallbacks; a Google Maps panel; payment-methods strip; copyright.
- Mobile sticky bottom bar — Shop · Call · Home · Chat · Cart, with Home icon-only in a raised
  circle, hidden from 768px.
- Floating WhatsApp button and a back-to-top button that only appears once there is something
  to scroll back from.
- `inc/customizer-store.php` — every contact route and social profile as a Customizer field.

### Demo content importer (2026-08-05)

Appearance → **Demo Content**. One nonce-protected button. Verified run on a clean install:

```
34 categories · 47 products · 55 images
```

- Three-level category tree, products priced in whole Taka with realistic sale spreads.
- **Every image is generated locally with GD** from the theme's palette — no network fetch, and
  nothing copied from the reference site. Falls back to creating products without images when
  GD is unavailable, and says so on screen.
- Builds the primary menu from the category tree and three footer menus from the theme's pages.
- Sets a static front page, generates four banners, and switches the store to BDT — but only
  while the currency is still WooCommerce's untouched default.
- **Idempotent**: anything already present under the same slug is left alone.

---

## Verification

Run against WordPress (latest) + WooCommerce 11.0 on PHP 8.3, under WordPress Playground.

| Check | Result |
|---|---|
| PHP syntax / runtime errors | ✅ **Zero** fatals, warnings, notices or deprecations across home, shop, category, sub-category, product, cart, checkout, my-account, search, page and 404 |
| Filters | ✅ `?sb_sale=1&sb_min=1000&sb_max=3000` narrows 16 → 15 cards |
| Pagination | ✅ `/shop/page/2/` renders a full second page |
| AJAX fragment | ✅ `?sb_ajax=1` returns the grid only — no header markup |
| AJAX search | ✅ Returns products; **bad nonce → HTTP 403** |
| Mega menu | ✅ 16 top-level, 40 second-level, 12 third-level items; 2 branches render as mega panels |
| Gallery weight | ✅ No flexslider, photoswipe or zoom scripts. jQuery loads only because WooCommerce's own frontend scripts require it |
| CSS budget | ✅ **59.9 KB** of 60 KB across all seven sheets — and no single page loads all of them |
| JS budget | ✅ **24.9 KB** of 30 KB front-end |
| i18n | ✅ 204 strings extracted to `languages/simple-bangla.pot` |

---

## Deliberate divergences from the earlier plan

1. **The three `wc-product-gallery-*` theme supports were removed.** Declaring them makes
   WooCommerce load jQuery, flexslider, photoswipe and zoom on every product page — roughly
   90 KB of script to swap an image, against a Lighthouse ≥ 90 target. The theme ships its own
   gallery instead: `woocommerce/single-product/product-image.php` plus ~1.6 KB of vanilla JS.
   **Trade-off:** there is no pinch-zoom lightbox. Thumbnails switch the main image, and
   without JavaScript they remain plain links to the full-size files.

2. **The shop fragment endpoint (`?sb_ajax=1`) is not nonce-verified.** `CLAUDE.md` asks for a
   nonce on every AJAX endpoint. This one is a read-only GET view of content the same URL
   already serves publicly; a nonce there is session-bound, breaks for logged-out visitors
   behind a page cache, and protects nothing, because there is no state change to forge.
   Every endpoint that *does* change state or read on a user's behalf — the live search — is
   nonce-verified, and rejects a bad nonce with a 403.

---

## Open

### Needed from the user

The theme works with placeholders in place; these are Customizer fields, not code changes:

- [ ] Phone number, WhatsApp number, email — currently `+880 1XXX-XXXXXX` / `hello@simplebangla.com`
- [ ] Facebook / Instagram / YouTube URLs — currently `.../simplebangla` guesses that should be
      confirmed or replaced, since those handles may belong to someone else
- [ ] Messenger username
- [ ] Google Maps embed URL — the footer map panel is empty until one is set
- [ ] Payment-methods strip image
- [ ] A real logo — the header currently renders the site name as text

### Known gaps

- **No pinch-zoom lightbox on product images** — see divergence 1 above.
- **Demo product images are generated placeholders**, not photography. They are meant to be
  deleted once a real catalogue is in.
- **WooCommerce ships with "Coming soon" mode on** in version 11. A fresh install hides the
  storefront until it is switched off under WooCommerce → Settings → Site visibility. This is
  WooCommerce's own default, not something the theme sets, so the theme does not override it.
- Not yet load-tested with a large catalogue; the price-bounds query is cached for an hour but
  has not been measured against thousands of products.

### Fixed

- ✅ **`/privacy-policy/` 404'd on a fresh install.** WordPress creates its own Privacy Policy
  page as a draft, `get_page_by_path()` found it, and the theme skipped creating one — leaving
  the footer link dead. `inc/setup.php` now publishes that specific page (matched by
  `wp_page_for_privacy_policy`, so a page the owner deliberately drafted is never touched).

---

## Local preview

Real WordPress runs under WordPress Playground — WASM PHP inside Node, no XAMPP or MySQL:

```
npx @wp-playground/cli@latest server --port=8882 \
  --mount-dir <abs path>/simple-bangla /wordpress/wp-content/themes/simple-bangla \
  --blueprint <path>/blueprint.json
```

Three Windows gotchas, all worked around above:

- `--mount host:vfs` cannot be used, because `C:\…` contains a colon. Use `--mount-dir`.
- `@wp-now/wp-now` crashes here: it resolves its own install path against the current drive,
  producing `D:\C:\Users\…`. Use `@wp-playground/cli`.
- **Start it from PowerShell, not Git Bash.** Git Bash rewrites the second `--mount-dir`
  argument from `/wordpress/wp-content/...` to `C:/Program Files/Git/wordpress/...` and the
  mount fails.

Playground keeps its database in a temp directory, so **restarting loses all content**. That
is why the blueprint now runs the demo import itself — a restart takes about a minute and
comes back with the full catalogue, BDT pricing and the storefront visible.

`preview/` holds static HTML mirrors of the **Phase 1** templates only. They are superseded by
the real install above and are not kept in sync.
