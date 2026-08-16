# Simple Bangla — Custom WooCommerce Classic Theme

Custom classic WordPress theme for a Bangladeshi gadget e-commerce store.

- **Theme slug / text domain:** `simple-bangla`
- **Theme directory:** `simple-bangla/`
- **Currency:** BDT (৳), comma thousands separator, **0 decimals**
- **UI language:** English, fully translatable (Bangla-ready)

---

## Hard constraints

| Rule | Detail |
|---|---|
| No page builder | No Elementor, no Gutenberg-block dependency, no premium plugins |
| Only required plugin | **WooCommerce** — nothing third-party. `simple-bangla-cms/` is our own first-party plugin and is optional: the storefront runs identically without it (revised 2026-08-08) |
| WooCommerce integration | Template overrides in `simple-bangla/woocommerce/` + hooks only. **Never** edit WooCommerce core |
| Stock install | Must run on stock WordPress + WooCommerce with zero extra setup |
| Demo content | Own placeholder images and own copy. Nothing copied from the reference site |
| JS | Vanilla only. No jQuery unless WooCommerce forces it. No Swiper / carousel libraries |
| Budget | CSS < 60 KB, JS < 30 KB (unminified). Lighthouse perf ≥ 90 on home with ~20 products |
| Robots | Do **not** replicate the reference site's sitewide `noindex,nofollow` |

---

## Reference site

`https://demarkt.com.bd` — structure reference only.

**WebFetch returns HTTP 403 on this host** (bot protection). All structural research is done
through the in-app browser MCP (`mcp__Claude_Browser__*`), which renders the page for real.
That has a side benefit: **computed styles are readable**, so colours, font sizes, radii and
spacing are measured, not guessed. Screenshots are unavailable in this session (the browser
pane does not composite frames), so anything that depends on *visual composition* — the shape
of the sale ribbon, shadow depth, hover states — still needs a screenshot from the user.

**Re-read the relevant page at the start of each phase** using the browser MCP, not from notes.

| Page | URL |
|---|---|
| Home | https://demarkt.com.bd/ |
| Shop | https://demarkt.com.bd/shop/ |
| Product | https://demarkt.com.bd/product/ulanzi-mt44b-selfie-stick-tripod-stand/ |
| Category | https://demarkt.com.bd/product-category/microphone/ |
| Cart | https://demarkt.com.bd/cart/ |

**Copy structure, not content.** Logo, brand name, product photography and written copy belong
to them.

---

## File structure

As built (59 files):

```
simple-bangla/
├── style.css                     theme header only
├── functions.php                 constants, includes
├── index.php  header.php  footer.php  sidebar.php
├── front-page.php                homepage section builder
├── page.php  single.php  archive.php  search.php  404.php
├── inc/
│   ├── setup.php                 theme supports, menus, image sizes, default pages
│   ├── enqueue.php               per-view css/js registration
│   ├── customizer.php            colour palette
│   ├── customizer-store.php      contact routes, socials, map, payment strip
│   ├── customizer-home.php       homepage rows, banners, circles
│   ├── nav-walker.php            3-level mega menu walker + per-item icon field
│   ├── template-tags.php         reusable render helpers, icon set, product loops
│   ├── ajax-search.php           live product search endpoint
│   ├── ajax-filter.php           shop filtering + the sb_ajax fragment
│   ├── site-icon.php             browser-tab icon, falling back to the logo
│   ├── recently-viewed.php       cookie-backed recently viewed strip
│   ├── woocommerce.php           currency, Buy Now, WhatsApp, cart fragments
│   └── demo-content.php          one-click demo importer + GD image generator
├── woocommerce/
│   ├── archive-product.php
│   ├── single-product.php
│   ├── content-product.php       ← the product card
│   └── single-product/           product-image.php · related.php
├── template-parts/
│   ├── header/                   bar.php · nav.php · drawer.php
│   ├── home/                     hot-deals · circles · product-row · banner-pair
│   ├── shop/                     filters.php · results.php
│   ├── product/                  assurances.php
│   ├── footer/                   brand · columns · mobile-bar · floats
│   └── content.php  content-none.php
├── languages/simple-bangla.pot   275 strings
└── assets/
    ├── css/   base · header · footer · card · home · shop · product   (53.8 KB)
    └── js/    header · slider · shop · product · ui   (20.6 KB) + admin-menu-icon
```

The custom management interface lives outside the theme, as an optional first-party plugin —
see "Custom CMS" below:

```
simple-bangla-cms/
├── simple-bangla-cms.php         constants, includes, WooCommerce/theme guards, HPOS declaration
├── inc/
│   ├── auth.php                  ability → capability map, REST permission callbacks
│   ├── schema.php                theme_mod bridge, generated from the theme's own registries
│   ├── stats.php                 dashboard figures via wc_order_stats / wc_product_meta_lookup
│   ├── router.php                /manage matching, sign-in + throttle, sign-out
│   ├── app.php                   login page, no-access page, app shell + boot payload + import map
│   ├── rest-session.php          /session — user, abilities, store, environment
│   ├── rest-settings.php         /settings — GET schema+values, POST batch write
│   ├── rest-dashboard.php        /dashboard — one request, every figure
│   ├── menu-icon.php             menu-item icon meta for REST + /menu-locations
│   ├── rest-pages.php            the theme's footer-link tick, registered for REST
│   ├── rest-blocklist.php        /blocklist — editor for the theme's list
│   ├── rest-staff.php            /staff — staff accounts, with the lockout guards
│   └── courier.php               /courier + /orders/{id}/courier + /record; dispatch and the
│                                 delivery-record lookup for Steadfast, Pathao and RedX
├── languages/simple-bangla-cms.pot   131 strings
└── assets/
    ├── css/cms.css               ~46 KB
    ├── js/    api · ui · text · nav · router · media · editor · order-utils · settings-form · app
    │           screens/  overview · products · product-edit · categories
    │                     orders · order-detail · invoice
    │                     hero · hot-deals · circles · rows · banners
    │                     menu · footer · reviews · settings · coupons · courier
    │                     content · content-edit · blocked · admins
    └── vendor/preact-htm.module.js   13 KB, vendored
```

The theme gained one file for the CMS's sake — and only because the CMS could not be allowed to own
it: `simple-bangla/inc/blocklist.php`, which stores the order block list and enforces it at the
checkout. See phase 7 below. A second followed on 2026-08-09 for the same reason —
`simple-bangla/inc/order-status.php`, which registers the two order stages WooCommerce lacks; a
status is customer-visible, so it cannot belong to a plugin that might be switched off. A third the
same day — `simple-bangla/inc/pages.php`, which owns the "show this page in the footer" tick and the
query that turns it into links; the footer is the theme's, so its links cannot come from a plugin.
See phase 9. A fourth on 2026-08-12 — `simple-bangla/inc/order-number.php`, the unique alphanumeric
order number; it is printed in the customer's confirmation email and in My Account, so an order must
still answer to it with the plugin off. See "Order numbers" below.

---

## Verified reference structure

Everything below was read off the live DOM, not assumed. Where it disagrees with the original
written spec, **the live site wins** and the difference is called out.

### Primary menu tree (verified)

```
A-Z All Product      /product-category/a-z-all-product/     [icon 24×24]
Best Selling         /product-category/best-selling/        [icon 24×24]
Microphone           /product-category/microphone/          [icon 24×24]
Gadgets              /product-category/gadgets/             [icon 24×24]  ▼
  Airpod's           /product-category/airpods/             [icon]        ▼
    Airpod's         /product-category/airpods/             [icon]
    Airpod's Case    /product-category/airpods-case/        [icon 36×36]
  Power Bank         /product-category/power-bank/          [icon]
  Smart Watch        /product-category/smart-watch/         [icon]        ▼
    Smart Watch      /product-category/smart-watch/         [icon]
    Watch Strap      /product-category/watch-strap/         [icon]
  Headphone          /product-category/headphone/           [icon]        ▼
    Bluetooth Headphone's  /product-category/bluetooth-headphones/  [icon]
    TWS                    /product-category/tws/                   [icon]
    Neckband               /product-category/neckband/              [icon]
  Mobile Charger     /product-category/mobile-charger/       [icon]       ▼
    Mobile Charger   /product-category/mobile-charger/       [icon]
    Cable            /product-category/cable/                [icon]
  Bluetooth Speaker  /product-category/bluetooth-speaker/    [icon]
  Rechargeable Fan   /product-category/rechargeable-fan/     [icon]
  EverCompare        /evercompare/                           (no icon)
Tutorials            /tutorials/                             (no icon)
User                 /user/                                  (no icon)   ▼
  Login / Register / Password Reset / Logout / Warranty Policy
Special Deals        /special-deals/                         (no icon)
```

Differences from the original spec:

1. **`EverCompare → /evercompare/`** exists under Gadgets. Not in the spec. It is a plugin
   page on their site; **we skip it** — no comparison feature is in scope.
2. **Not every top-level item has an icon.** Tutorials, User and Special Deals have none.
   The icon field is therefore **optional per item**, and the walker must render cleanly
   without one.
3. **`Headphone` is itself a link** to `/product-category/headphone/`, not a label-only
   dropdown parent. Same for Airpod's, Smart Watch and Mobile Charger — each parent links to
   its own category and repeats itself as the first child.

### Header

- Search form: `GET /` with `s`, `post_type=product`, plus a plugin flag we drop.
  → we reproduce **`s` + `post_type=product`** and add our own nonce-verified AJAX endpoint.
- Cart widget renders literally `৳ 0` · `0` · `Cart`.
- Logo bar row background is **solid black**.
- **Sticky:** they use Elementor's sticky (`sticky:top`, all breakpoints), **one** header in
  the DOM. The spec's claim that they output the header markup twice does **not** hold on the
  current site. We use `position: sticky` + an `.is-stuck` class — same outcome, no duplication.

### Homepage section order (verified — this replaces the 13-row table in the spec)

| # | Section | Detail |
|---|---|---|
| 1 | **Hot Deals slider** | 8 on-sale product cards (spec said 6) |
| 2 | **Category circles** | 6 × 150×150 + label below |
| 3 | Product row | `Best Selling` → View All `/product-category/best-selling/` · 24 items · slider |
| 4 | Product row | `Lighting` → View All `/product-category/trending-now/` · 19 items · slider |
| 5 | **Banner pair** | small 595×280 + wide 1024×512 → `/product-category/microphone/` |
| 6 | Product row | `Tripod's` → View All `/product-category/airpods/` · 14 items · slider |
| 7 | Product row | `microphone` → View All `/product-category/airpods/` · 11 items · slider |
| 8 | **Banner pair** | small 595×280 + wide 820×312 → `/product-category/microphone/` |
| 9 | Product row | `Powerbank` → View All `/product-category/airpods/` · 7 items · slider |
| 10 | Product row | `Powerbank` (repeated) → View All `/product-category/airpods/` · 12 items · slider |
| 11 | Footer | |

Differences from the spec:

1. **There is no full-width promo banner between Hot Deals and the category circles.** The
   circles come immediately after the slider. Spec section 2 does not exist on the live site.
2. **Banners always appear as a pair** (one narrow + one wide in a single row), twice total —
   not as the four separate banner sections the spec lists.
3. **6 product rows** — this matches the spec's "6 configurable Customizer rows" exactly.
4. **Their heading↔category wiring is broken.** Four of six rows point at `/airpods/`
   regardless of heading, and `Lighting` points at `/trending-now/`. Two rows are both titled
   `Powerbank` while the second one lists earbuds. **We keep heading text and target category
   as separate Customizer fields and ship them correctly wired.** Do not inherit this bug.

### Product card (verified)

```
[ Hot Deals ]        ← white text, only when on sale
   1:1 image         ← 1024×1024 source, linked to product
Product Title        ← Lato 600 / 16px / #333, links to product
৳ 2,599  ৳ 1,999     ← <del> muted red, <ins> black bold
[  Order Now  ]      ← black pill → product page (NOT add-to-cart)
```

Card container: background `#FFFCF7`, `border-radius: 15px`, **no border, no shadow**.

### Footer (verified)

Background is **white**, not dark. Headings are 20px `#2D3134`.

```
Row 1 (mobile only): sticky bar — Shop · Call · Home · Chat · Cart
Row 2: logo (563×188) · phone · email · social icons
Row 3 (4 col): De Markt | Helps | Customer Service | Google Map iframe
Row 4: payment methods image strip
Row 5: © {year} … All rights reserved
```

**We do not ship the map cell.** The owner asked for it removed on 2026-08-07, so row 3 is the
brand block plus three link columns and there is no Google Maps Customizer field.

Link targets: About Us `/about-us/`, Privacy Policy `/privacy-policy/`,
Delivery & Return `/refund_returns/`, Shop Now `/shop/`, Checkout `/checkout/`,
How to Order `/shop/`, Contact Us `/contact-us/`, Membership `/register/`,
Terms & Conditions `/terms-and-condition/`.

Their "How to Order" points at `/shop/` — we point it at a real page.

### Mobile bottom bar (verified)

5 items, `Home` is icon-only (no label): `Shop` `/shop/` · `Call` `tel:` · `Home` `/` ·
`Chat` Messenger · `Cart` `/cart/`. Hidden ≥768px.

---

## Measured design values

Read from computed styles on the live site. **These are measurements, not guesses**, except
where marked.

| What | Measured |
|---|---|
| Body text | `#364151`, 16px, line-height 1.65 |
| Card title | Lato 600, 16px, `#333333` |
| Card background | `#FFFCF7`, radius 15px, no border, no shadow |
| Current price `<ins>` | `#000000`, 18px, 700 |
| MRP `<del>` | `#BA5E5E`, 15px, line-through |
| `Order Now` button | bg `#000000`, text `#FFFFFF`, radius 15px, Roboto 500 16px |
| `View All` button | bg `#000000`, text `#FFFFFF`, 2px white border, radius 8px, 12px |
| Section heading / circle label | `#7E7E7E`, 18px, Chelsea Market |
| Nav link | `#000000`, 15px, Oswald 400 |
| Logo bar background | `#000000` |
| Footer background | `#FFFFFF`, headings `#2D3134` 20px Raleway |
| Container max-width | **1600px** |

**Their palette is black + warm cream, not red.** The `--sb-brand: #e63946` starting token in
the spec does not match anything on the live site. The only red present is the struck-through
price at `#BA5E5E`.

**Fonts in use on the reference site:** Oswald, Lato, Roboto, Raleway, Chelsea Market — five
families. That is a performance problem we are not copying.

**Still unknown (needs a screenshot):** the sale-ribbon shape and its background — the badge
renders white text on a transparent container, so the ribbon fill comes from something not
readable in computed styles. Also unverified: hover states, shadow depth, exact gutters.

---

## Design tokens

One `:root` block, every token wired to a Customizer colour control.

```css
:root {
  --sb-brand:      /* pending — see palette decision */
  --sb-brand-dark:
  --sb-ink:        #333333;
  --sb-muted:      #7e7e7e;
  --sb-sale:       #ba5e5e;  /* struck-through MRP */
  --sb-line:       #e5e7eb;
  --sb-bg:         #ffffff;
  --sb-bg-alt:     #fffcf7;  /* card / section background */
  --sb-footer-bg:  #ffffff;
}
```

- Mobile-first. Breakpoints **480 / 768 / 1024 / 1200**.
- Container max-width **1200px** (project spec) — the reference uses 1600px. Project spec wins
  unless told otherwise.
- Registered image sizes: `150×150` category icon, `600×600` gallery, `1024×1024` card.

---

## Code standards

- **Escape every output**: `esc_html`, `esc_url`, `esc_attr`, `wp_kses_post`.
- **Sanitize every input**; **nonce-verify every** AJAX endpoint and form.
- **i18n**: every user-facing string in `__()` / `_e()` with `simple-bangla`.
- WordPress Coding Standards. All functions prefixed `simple_bangla_`.
- No inline styles. No `!important` unless a WooCommerce default leaves no other option —
  and comment why when it happens.
- `wp_enqueue_script` with real dependencies, JS in footer, `defer` where safe.
- Lazy-load below-the-fold images. `srcset` everywhere.
- Comment the **why**, not the what.

### Per-phase self-check

1. `php -l` on every changed file
2. No undefined function calls
3. No unescaped output
4. No untranslated user-facing string

---

## Build order

All six phases are **complete** as of 2026-08-05. See `PROGRESS.md` for what each one shipped
and how it was verified.

1. ~~**Foundation**~~ — scaffold, theme supports, menus, image sizes, tokens, base CSS
2. ~~**Header**~~ — mega menu walker + icon field, sticky, AJAX search, cart widget, hamburger
3. ~~**Product card + homepage**~~ — card, `front-page.php`, Hot Deals slider, circles, banners
4. ~~**Shop**~~ — filters, load more, recently viewed
5. ~~**Single product**~~ — gallery, Buy Now handler, WhatsApp button, tabs, related
6. ~~**Footer + mobile**~~ — 4-column footer, payment strip, scroll-to-top, bottom bar, WhatsApp float

The "stop for feedback after each phase" rule was **lifted by the user on 2026-08-05** in
favour of a single pass. Reinstate it for any future work unless told otherwise.

## Decisions log

- **Browser MCP over WebFetch** for all reference research — WebFetch is 403-blocked on this host.
- **Skip EverCompare** — product-comparison feature is out of scope.
- **Menu icon is optional per item** — the live site proves not every item has one.
- **Single sticky header**, not duplicated markup.
- **Heading text and target category stay separate Customizer fields** — the reference site's
  wiring is broken and we are not reproducing the bug.
- **Six product rows**, matching both the spec and the live site.
- **The store is managed from a custom CMS, not wp-admin** (2026-08-08) — a separate first-party
  plugin, same-origin cookie auth, `wc/v3` reused rather than re-implemented, settings written as
  `theme_mod`s. CartFlows dropped; Combo Offers out of scope. See "Custom CMS" below for the full
  reasoning, the permission model and the build order.

## The CSS budget is per view, not per repo (revised 2026-08-06)

The original "CSS < 60 KB" was written before the theme had a checkout. Summed across all eight
sheets it now reads 70 KB, but **no page loads more than about 51 KB** — base, header and footer
are the only unconditional sheets, and card/home/shop/product/checkout are enqueued per view.
Judge the budget by what a visitor downloads:

| View | CSS |
|---|---|
| Homepage | ~47 KB |
| Shop / category | ~47 KB |
| Single product | ~51 KB |
| Cart / checkout / thank-you | ~60 KB |

Front-end JS is ~25 KB across five files, likewise split by view.

The checkout sheet grew from ~50 KB to ~60 KB with the thank-you rebuild (2026-08-07) and is now
the heaviest view. It is one sheet shared by three pages, so the cart pays for styles only the
thank-you page uses; splitting it is the obvious move if that view ever needs trimming.

## Ordering (2026-08-06)

- **"Order Now" on a card adds the product and goes straight to checkout** —
  `/checkout/?add-to-cart=ID&sb_buy_now=1`. This replaces the earlier rule that the card CTA
  should link to the product page; the store owner asked for one-click ordering. A variable,
  grouped, external or out-of-stock product still goes to its own page and the label changes to
  "Choose Options", because one-click ordering it is not actually possible.
- **Checkout and Cart are the classic shortcodes, not the block versions.** WooCommerce 11
  ships them as blocks that render client-side and cannot be templated by a classic theme, so
  the importer swaps the page content — but only while it still holds WooCommerce's own block
  markup, never a page the owner has written.
- **Checkout copy is Bangla**, unlike the rest of the site. It is the one page a customer types
  into. Strings still go through `__()`, so it stays translatable.
- Store configuration — Cash on Delivery, the two flat rates, the page swap, selling to
  Bangladesh only — lives in the demo importer, never in the theme. A theme that rewrote a live
  store's payment settings on activation would be a menace.

## Checkout layout (2026-08-06)

Built to a screenshot the owner supplied. Two columns: the address on the left, the order on the
right.

- **The delivery-charge radios sit under the address, not in the totals table.** Choosing
  "inside Dhaka" or "outside Dhaka" is part of saying where the parcel goes.
  `template-parts/checkout/shipping.php` rebuilds WooCommerce's rate list keeping its input
  names and `shipping_method` class, so the delegated change handler still recalculates the
  total. It sits outside `#order_review` — the fragment WooCommerce replaces on every
  recalculation — so the choice is never re-rendered under a finger mid-tap. The trade: a rate
  list that changed with the address would go stale. These two never do, because they are a
  choice rather than a zone matched from a state field.
- **No delivery row in the order summary**, matching the screenshot. Subtotal, then Total. The
  charge is visible beside the address instead. One row in `checkout/review-order.php` brings it
  back if the owner wants it itemised.
- **Labels are visually hidden and the placeholder asks the question**, asterisk included. On a
  four-field form a label above each box asks it twice. The labels stay in the markup.
- **The coupon form stays above the checkout form**, where WooCommerce puts it, styled down to a
  single line. It cannot move into the order column: it is a `<form>`, the order column is
  inside the checkout `<form>`, and a browser drops a nested form tag — which would leave
  "Apply coupon" submitting the checkout itself.
- **The country field is shown, not asked.** With one allowed country WooCommerce renders the
  name plus a hidden input instead of a 250-option select — the right thing to show, and ~15 KB
  less markup. Set by the importer; if it has not run, the select renders and still works.

## Thank-you page (2026-08-07)

Rebuilt to a second screenshot the owner supplied — a confirmation banner, then one card per
question a customer actually has after ordering: where the parcel is going, what is in it, what
will be paid.

- **The reference is green; the page is black and cream.** The owner chose the site palette over
  the reference's colour. Green is spent on exactly two things — the success tick and the total —
  so it still reads as "it worked" without the page becoming a different site.
- **Copy is Bangla**, matching the checkout. Same rule as before: the pages a customer transacts
  on speak Bangla, everything else is English, and all of it goes through `__()`.
- **The step bar stays** (the owner asked for it) at the top of the banner, above the tick. A
  **failed** payment stops the bar at the order step rather than ticking "সম্পন্ন" green over a
  page that says the payment did not go through.
- **WooCommerce's own order-details and address tables are unhooked** —
  `remove_action( 'woocommerce_thankyou', 'woocommerce_order_details_table', 10 )` in
  `inc/woocommerce.php`. The theme's cards print every line those tables would, in Bangla; with
  both on, every fact appeared twice, the second time in an unstyled English table.
- **`woocommerce_thankyou` still fires**, so gateways and plugins keep their slot. Its output is
  buffered and the wrapper is only printed when something hooked in — an empty div would still
  claim a row of the grid. It is printed raw, not through `wp_kses_post`, which would strip the
  forms and scripts a payment plugin legitimately renders there.
- **Cash on delivery's `instructions` field is now blank**, set by the importer. WooCommerce
  prints it on this page, where the theme's own payment card already says the same thing in
  Bangla with the amount filled in. The COD title and description are Bangla for the same reason.
  All three are store configuration, so they live in the importer and are only written when COD
  is not already enabled — a live store's payment settings are never overwritten.
- Two icons added to `simple_bangla_icons()`: `box` and `card`.

**Sized down for phones (same day, after the owner saw it on a device).** The first build used one
scale at every width and the banner alone filled a phone screen. The whole page is now mobile-first
and steps up at 600px:

- Banner: 52px medallion, `--sb-text-xl` title, `--sb-text-sm` subtitle, `space-5/space-4` padding —
  roughly half its former height. At 600px it returns to 68px / `--sb-text-2xl`.
- **The step bar is hidden below 600px.** It reports a journey already finished, so it was the
  least useful thing in the banner and the most expensive in vertical space.
- The banner is **rounded** now. It sits inside the page container rather than bleeding to the
  edges, and square corners inside a margin read as a mistake.
- Cards, facts, item rows and totals all drop one step on phones and come back at 600px.
- **The WordPress entry title is suppressed on checkout and order-received** —
  `simple_bangla_hide_entry_title()` in `inc/template-tags.php`, checked by
  `template-parts/content.php`. WooCommerce relabels the page title on that endpoint, so an English
  "Order received" h1 was printing directly above the Bangla banner. Deliberately narrow: the cart
  and the empty-cart checkout have no banner, so suppressing the title there would leave those
  pages with no heading at all.

Verified with Chrome DevTools device emulation at 390 / 600 / 1280 px: `scrollWidth` equals the
viewport at every width, so nothing overflows. Note that `chrome --headless --window-size` does
**not** set the layout viewport — it produced convincing but wrong "content is cut off"
screenshots. Use `Emulation.setDeviceMetricsOverride` over CDP.

## Reference measurements (2026-08-06)

`WebFetch` gets a 403 from demarkt.com.bd, but **plain `curl` with a desktop User-Agent gets
200**. Its Elementor stylesheets (`/wp-content/uploads/elementor/css/post-{15,202,286,308}.css`,
plus inline blocks for the loop template) are readable, so these are measured, not guessed:

| Thing | Value |
|---|---|
| Page background | `rgba(222,166,11,0.12)` over white → **`#FBF4E2`** warm cream |
| Section heading | **Baskervville** 32px / 600 / uppercase / 1px tracking / `#000` |
| "VIEW ALL" button | `#000` bg, 16px/500, uppercase, 2px border, 8px radius, 10px 12px padding; hover inverts |
| Sale ribbon | `#F85606`, white **Lato** 16px/500, `border-radius: 10px 0 10px 0`, `left: 20px; top: 7px` |
| Footer headings | **Raleway** 900 / 20px / capitalize / **underlined** / `#2D3134` |
| Footer social circles | `#3C3A3A`, hover `#F5735A`; five of them — Facebook, Instagram, YouTube, Email, WhatsApp |
| Copyright bar | full-bleed `#000` band, text `#808080`, centred |
| Primary nav | `elementor-nav-menu--burger` at every width — no horizontal nav row at all |

**Correction (2026-08-06):** an earlier pass concluded the homepage has no hero. That was
wrong. The page opens with a two-column row — a narrow one-card Hot Deals slider on the left
beside a wide banner carousel on the right. The single "carousel" found in the HTML was the
left column; the hero images sit in Elementor slide backgrounds that the first scan missed.
Confirmed from screenshots and rebuilt as `template-parts/home/hero.php`.
- **Own vanilla product gallery** instead of WooCommerce's. The three `wc-product-gallery-*`
  theme supports are deliberately not declared, because they pull in jQuery, flexslider,
  photoswipe and zoom — about 90 KB of script to swap an image. Cost: no pinch-zoom lightbox.
- **Shop filters are GET parameters applied in `pre_get_posts`**, and the AJAX path re-fetches
  the same URL rather than calling a second query builder. One source of truth; filtered views
  stay linkable and work without JavaScript.
- **The `?sb_ajax=1` shop fragment carries no nonce.** It is a read-only GET view of already
  public content, so there is nothing to forge; a nonce there would only break page caching for
  logged-out shoppers. Every state-changing or user-scoped endpoint is still nonce-verified.
- **Demo images are generated with GD at import time by default** — see the revision below
  (2026-08-06, "Real product photos for demo content") for the opt-in Unsplash path.

## Real product photos for demo content (2026-08-06)

The owner asked for a realistic client preview instead of the GD-drawn silhouettes, so the
importer can now pull real photos from the **Unsplash API**. This revises the earlier "nothing
is fetched over the network" decision — deliberately, and only for this one feature.

- **Opt-in via a free Access Key**, entered on Appearance → Demo Content
  (`simple_bangla_unsplash_key` option). No key set → behaviour is unchanged, drawn
  placeholders, zero setup, no network call. This keeps the "stock install runs with zero
  extra setup" hard constraint true; Unsplash is a convenience for showing a client a preview,
  not a runtime dependency.
- **Search phrases are per-category**, not per-product — `simple_bangla_demo_photo_queries()`
  in `inc/demo-content.php` maps each category slug (`microphone`, `power-bank`, `smart-watch`,
  …) to a query. Unsplash's search returns up to 10 photos per query, and the importer rotates
  through them, so four products in the same row get four different photos instead of one
  photo repeated.
- **Cropped by Unsplash's own CDN**, not locally. The raw photo URL gets `w`, `h`, `fit=crop`
  query params appended before download, so every product/category image arrives already at
  the exact square (or banner) size needed — no local `WP_Image_Editor` crop step.
- **Falls back to the GD silhouette on any failure** — no key, a failed search, a failed
  download, an empty result set. The importer never partially fails a product over a photo.
- **Fires Unsplash's download-tracking ping** (`links.download_location`) per the API
  guidelines, non-blocking, whenever a photo is actually used.
- Applies to product images, category-circle icons, the hero slider, and the two homepage
  banner pairs — every place the GD placeholders showed up.
- Saving the key only affects images generated *after* it is saved; the importer never
  regenerates an image for a product, category or banner that already has one (same rule as
  the rest of the importer — nothing already present is touched).

## Custom CMS (2026-08-08)

The store owner wants to set the site up once and then never open wp-admin again, managing
everything from a purpose-built interface. `simple-bangla-cms/` is that project. It is a
**separate plugin, not part of the theme** — if it is deactivated the storefront is unchanged,
and if the theme is ever swapped the management interface does not die with it.

### The realistic goal

**wp-admin cannot be eliminated, and the plugin never tries to block it.** Core/plugin updates,
WooCommerce database migrations, payment-gateway settings (bKash/SSLCommerz/Nagad plugin screens
are custom PHP with no REST API), courier integrations and permalink flushes have no API to drive
them. The target is "every daily operation happens in the CMS; wp-admin is the maintenance door,
opened a few times a year." Never install anything that locks an administrator out of wp-admin —
that removes the escape hatch, not the need for it.

### Architecture

- **Same origin, not a separate domain.** The interface will be served from `/manage` on the
  store's own domain. That makes authentication WordPress's own login cookie plus the `wp_rest`
  nonce: no JWT, no token in localStorage for an XSS to steal, no refresh-token rotation, no
  CORS. A separate `cms.` subdomain would have forced all four.
- **`wc/v3` is not re-implemented.** Products, orders, customers, coupons and reviews already
  have a maintained REST API; the CMS calls it directly. The `sb-cms/v1` namespace only covers
  what WooCommerce has no concept of — theme settings, an aggregated dashboard, and later the
  menu and block-list modules. This roughly halves the surface that a WooCommerce upgrade can
  break.
- **Settings are written as `theme_mod`s — the same storage the Customizer writes.** The theme
  reads exactly what it read before, no template changed, no migration ran, and the Customizer
  stays fully usable as a fallback if the CMS is ever broken or unavailable.

### The schema is generated, never hand-written

`inc/schema.php` builds its 64-field registry from the theme's own registry functions —
`simple_bangla_color_tokens()`, `simple_bangla_contact_fields()`, `simple_bangla_home_row_defaults()`
and the `SIMPLE_BANGLA_HERO_SLIDES` / `HOME_ROWS` / `HOME_BANNERS` constants. Add a colour token
to the theme and a colour picker appears in the CMS with no change to the plugin. Duplicating the
field list would have guaranteed the two drift apart.

It is built at **request time, not plugin load**, because plugins load before themes and none of
those functions exist yet when the file is included.

### Permissions are ability-based from day one

The CMS never asks "is this an administrator?". It asks whether the user holds a named ability
(`orders.manage`, `appearance.manage`, `staff.manage`, …) and each maps to a real WordPress
capability that WooCommerce already registers. With one owner account the indirection buys
nothing today — which is exactly why it had to be written now. The owner confirmed staff accounts
are coming later; when they do, granting an order-desk user `edit_shop_orders` produces a
correctly restricted CMS with **no endpoint changes**. Verified: a `shop_manager` sees orders and
revenue but not staff management, a `subscriber` gets the dashboard with the revenue block
stripped out.

`/session` reports abilities so the interface can hide what a user cannot use, but every endpoint
re-checks server-side. A hidden button is a courtesy, not a control.

401 and 403 are kept distinct — the interface needs to know whether to show a login screen or an
"you do not have access" message, and a bare capability string collapses both into one response.

### Performance

A custom CMS does not slow the storefront down; admin and front-end are separate requests and a
visitor never downloads a byte of it. The risks are elsewhere, and each is handled:

- **One request per screen.** A dashboard firing six REST calls pays to boot WordPress six times.
  `/dashboard` returns every figure together.
- **Nothing is counted by loading orders.** Revenue reads WooCommerce's own `wc_order_stats`
  summary table and stock reads `wc_product_meta_lookup` — both indexed, both maintained by
  WooCommerce for exactly this. Summing `wc_get_orders()` works at 500 orders and collapses at
  50,000.
- **Five-minute transient**, busted on `woocommerce_new_order` / `order_status_changed` /
  `delete_order` so a new order appears immediately rather than up to five minutes late.
- **HPOS is required in practice.** The plugin declares compatibility and shows an admin notice
  while it is off.

### Settings writes are all-or-nothing

The whole batch is validated before anything is written. A form failing halfway would leave the
homepage in a state the owner never chose and cannot identify. Unknown keys are rejected rather
than stored, so a buggy or hostile client cannot invent theme mods. Invalid colours are caught
explicitly because `sanitize_hex_color()` answers "unusable" with null, and storing that would
silently drop the theme back to its compiled default — which reads as "the save didn't work".

### Decisions

- **CartFlows is dropped** (owner's decision, 2026-08-08). Its funnel builder is Elementor-based
  with no REST API, so funnels could never be managed from the CMS; it overrides the custom
  checkout that was built to the owner's own screenshot; and order bumps/upsells are Pro-only.
  If those are wanted later they get coded into the theme, where the CMS can control them.
- **Combo Offers are out of scope** — the module exists in the reference dashboard but has no
  meaning for this store. This removed the only module needing a real pricing engine.
- **Nahian Fashion is a visual reference only.** The screenshot the owner supplied is a different
  site; its sidebar was remapped onto Simple Bangla's actual features. Its Combo Offers,
  Testimonials and Support modules are not built.
- **Interface language is English** (owner's decision), unlike checkout and thank-you which stay
  Bangla. Strings still go through `__()`.
- **Custom branded login at `/manage`**, using WordPress's own authentication functions with a
  different face. Bouncing to `wp-login.php` would show the WordPress-blue page the whole project
  exists to avoid.
- **Light theme in the site's own palette** (black + `#FBF4E2` cream), not the reference's dark
  gold dashboard.
- **`/manage` sidebar** (17 items in four groups): **Store** — Overview · Orders ·
  Products · Categories · Coupons. **Homepage** — Hero Slider · Hot Deals · Category Circles ·
  Product Rows · Banners. **Site** — Content Pages · Menu · Footer · Reviews · Settings.
  **People** — Blocked List · Manage Admins. (Customers was removed 2026-08-09; Content Pages was
  added the same day — see round two and phase 9.) Every item is visible from day one, filtered
  only by ability and marked "soon" until its phase lands; a sidebar that grew week by week would
  hide what is still owed.

### Phase 2 — the interface (2026-08-08)

- **Preact + htm, no build step** (owner's decision after weighing the alternatives). Vendored as
  a single 13 KB ES module in `assets/vendor/`, not loaded from a CDN. Deployment is a folder
  copy; there is no `npm run build`, no committed bundle, and no toolchain a future maintainer
  needs in order to change a screen. Plain vanilla JS was the other no-build option and was
  rejected once the screen list was clear — the product editor and order list are too stateful to
  hand-write against the DOM.
- **`/manage` is matched on the request path, not a rewrite rule.** A rewrite needs a flush, and a
  flush that silently did not happen — files copied without reactivating, a migration, a cache —
  would 404 the only URL the owner uses.
- **The shell renders its own HTML document**, not a theme template. `wp_head()` would pull in the
  storefront's stylesheets and every plugin's opinion about `<head>`; the signed-out page is
  1.8 KB and the shell 1.7 KB.
- **The session payload is embedded in the shell as `application/json`**, so the sidebar and user
  chip paint on first byte and only the dashboard figures are waited on. Printed as JSON in a
  script tag rather than a JS assignment: nothing in a store name can then terminate the script.
- **Sign-in is throttled** — five failures per address, then a 15-minute wait. Keyed on
  `REMOTE_ADDR` only; forwarded-for headers are ignored because they are spoofable, which would
  turn the control into decoration. One error message for a bad username and a bad password, so
  the form does not report which half was right.
- **Sign-out is nonce-checked**, or an `<img src>` on any other site could sign the owner out
  mid-task.
- **`wp_nonce_url()` must not be used for a URL that travels in JSON.** It HTML-escapes its
  ampersand, so `&amp;` survives into the DOM and the parameter becomes `amp;_wpnonce` — a
  sign-out link that silently does nothing. Caught in verification; `simple_bangla_cms_logout_url()`
  builds the URL with `add_query_arg()` and callers escape at output.
- **System font stack, no webfont.** The storefront spends its font budget on brand typography
  because customers see it; this is read all day by one or two people.
- **Two columns of stat tiles below 600px.** Five full-width tiles turned the first screen into
  five screens of scrolling. The revenue tile spans both because it carries a second line.

Verified in a real browser (headless Chrome over the Playground server): 33 assertions covering
sign-in, the rendered sidebar, live dashboard figures in BDT at zero decimals, client-side
routing with working back button, the mobile drawer, no horizontal overflow at 390/768/1280, a
working sign-out, and zero console errors. Plus the HTTP-level checks: `noindex` and no-store
headers on every CMS response, REST 401 without a nonce, sign-out ignored without one, and the
throttle firing on the sixth attempt.

### Phase 3 — catalogue (2026-08-08)

**`wc/v3` accepts cookie + nonce authentication for reads *and* writes.** Verified before a line
of the phase was written, because the whole plan depended on it: a `POST /wc/v3/products` with
only the login cookie and `X-WP-Nonce` returns `201`, and `X-WP-Total` / `X-WP-TotalPages` come
back on list calls. So phase 3 added **no PHP at all** — products, variations and categories are
WooCommerce's own endpoints called directly, and only the plugin version was bumped (to 0.3.0)
so browsers fetch the new assets.

- **`apiList()` exists because WordPress reports totals in headers, not the body.** A plain
  `api()` call returns the rows and silently loses the count the pager needs.
- **Media is a small custom picker over `wp/v2/media`.** WordPress's own media modal is jQuery,
  Backbone and a dependency chain that only assembles inside wp-admin. Uploads go straight to the
  library — an image added while editing a product is then available everywhere, not trapped on
  that product. Files are sent as a raw body with `Content-Disposition` rather than multipart.
- ~~**Descriptions are textareas holding raw HTML, not a rich-text editor.**~~ **Superseded
  2026-08-09** — the owner asked for an editor and got `editor.js`, the plugin's own. The textarea
  survives as its "HTML" toggle, and the stored field is unchanged. See round two.
- **Deleting a product trashes it; deleting a category is permanent.** WooCommerce's `DELETE`
  default for products is the trash, which is what an owner mis-tapping on a phone needs. Terms
  have no trash in WordPress at all — `force=true` is required — so that confirmation says so
  explicitly.
- **Variations can be priced and counted, not created.** Attribute and variation generation is a
  genuinely large interface and this catalogue is mostly simple products; price and stock are the
  parts that change weekly.
- **A variable product's `regular_price` is never sent.** Its price lives on its variations, and
  posting an empty string blanks the parent so the storefront shows no price range at all.
- **Unsaved work is guarded with `beforeunload`.** Note for future browser tests: headless Chrome
  will sit on that dialog until the navigation times out unless the run handles `dialog`.
- **Routing moved to `assets/js/router.js`** once screens needed to navigate themselves — a row
  opens an editor, saving a new product replaces the URL with the real one (`replaceState`, so
  the back button does not return to a blank form). Passing a navigate function through every
  component would have coupled each screen to its parent for nothing.
- **Save sits in a fixed bottom bar below 600px.** In the page header it was above a form long
  enough that committing a change meant scrolling back to the top every time.

**A real layout bug worth remembering:** a visually-hidden `.sb-sr` label in the *last* column of
a wide table widened the whole document by 7px on a 390px screen. `position: absolute` with no
positioned ancestor resolves against the initial containing block, so the 1px box sat past the
right edge and escaped the table's `overflow-x` container — `overflow` alone does not create a
containing block. Fixed by giving `.sb-table-wrap` `position: relative`, plus `margin: -1px` on
the utility class as a second line of defence.

Verified with 47 browser assertions against real WordPress + WooCommerce: create a product,
refuse a nameless one, search, open from the list, edit price and SKU, reload and confirm the
values persisted *server-side*, toggle stock tracking, upload an image through the picker, save,
reload and confirm the image and quantity stuck, the list reflecting the new stock and thumbnail,
the out-of-stock filter, category create → rename → delete, no page overflow across three screens
at 390/768/1360, and zero console errors.

Two lessons about the checks themselves, both of which produced false failures first:

- **Fixtures must be created by the run, with names unique to it.** Asserting against records
  left by a previous run reported two "bugs" that were only stale state.
- **Wait for the committed state, not for a toast.** An earlier version waited for any `.sb-toast`
  and matched the *upload* toast, then navigated while the save was still in flight. Waiting for
  the Save button to read "Saved" is the honest signal. (WooCommerce rejecting a duplicate SKU
  surfaced correctly through the error toast while this was being chased — the error path works.)

### Phase 4 — orders (2026-08-08)

Orders, notes, refunds and batch updates all answer to cookie + nonce as well, so phase 4 added
**no PHP either**; only the plugin version moved (0.4.0). After this phase the store is genuinely
runnable without wp-admin.

- **Bulk status change is the point of the list screen.** A cash-on-delivery store takes a pile of
  new orders, confirms them by phone, and moves a batch to the next status together. Clicking into
  forty orders one at a time is the difference between the screen being used and being abandoned.
- **A batch response can fail silently.** WooCommerce returns `200` and reports per-item failures
  *inside* `update[]`, so the result is inspected for `error` entries rather than trusted.
- **Refunds are recorded, not processed** — `api_refund: false`. Cash on delivery has no gateway
  to return money through, and asking WooCommerce to call one would fail on the only payment
  method this store uses. The dialog says so.
- **Notes are refetched after a status change**, because WooCommerce writes its own note when the
  status moves and the trail would otherwise be one entry behind.
- **`checkout-draft` is absent from the status list.** It is WooCommerce's internal placeholder
  for a checkout in progress; showing it would put half-abandoned baskets in the owner's list as
  if they were real orders.
- **Order dates are parsed exactly as sent.** WooCommerce's `date_created` is store-local with no
  zone marker; appending "Z" would claim UTC and shift every order by six hours for Dhaka.
- **The invoice is the one Bangla screen in the CMS**, following the rule already set for the
  checkout and thank-you page: what a customer reads is Bangla, what only the store sees is
  English. The shop's own phone, email and address come from the same `theme_mod` values the
  storefront footer prints, via `/settings?group=store` — not a second copy kept in the CMS. The
  country line is dropped when it is Bangladesh, because the store ships nowhere else and "BD" on
  every parcel is noise.
- **Printing resets the layout, not just the colours.** The rail is `position: fixed` and the main
  column is offset by its width, so `@media print` has to zero that margin or every sheet prints
  with a 240px empty gutter.
- **Coupons moved from phase 4 to phase 6.** Marking phase 4 as built would otherwise have removed
  the "soon" badge from a screen that is still a placeholder.

**A real bug the checks caught:** a refund typed as `460` was recorded as `46`. `Modal`'s mount
effect depended on `[onClose]`, and callers recreate that arrow function on every render — so
every keystroke in a dialog re-ran the effect and called `focus()` on the panel, pulling focus out
of the field being typed into and dropping characters. Fixed by holding the handler in a ref and
giving the effect an empty dependency array. It only showed up here because this dialog's input
state lives in the parent; phase 3's category form keeps its state internally, so `Modal`'s props
were stable and the same code behaved.

Verified with 56 browser assertions — seeding a real order through the API, then searching by
phone, bulk-changing status, reading items, delivery line and totals, changing status from the
detail screen and confirming it after a reload, adding a note and finding it plus WooCommerce's
own status note after a reload, recording a partial refund and checking the Refunded and Net rows
survive a reload, the Bangla invoice, the print stylesheet under emulated print media, no overflow
across three screens at 390/768/1360, and no console errors. Phase 3's suite was re-run afterwards
as a regression check on the shared `Modal`: 46 of 47, the one failure being its now-obsolete
assertion that Orders is still marked "soon".

More lessons about the checks: **scope assertions to the element under test.** `'Refunded'` also
appears in the status `<select>` and `'460'` is a substring of `'2,460'`, so two checks passed
against text that had nothing to do with the totals block. And fixtures need every searched field
unique per run, phone numbers included.

### Phase 5 — homepage modules (2026-08-08)

Five screens — Hero Slider, Hot Deals, Category Circles, Product Rows, Banners — and, like phases
3 and 4, **no new PHP**. `/settings` was built in phase 1 to hand back schema and values together
for any group, so every one of these screens is a form the plugin was never hard-coded for. Only
the version moved (0.5.0) so browsers fetch the new assets.

- **`settings-form.js` holds the shape all five share.** `useSettings( group )` loads a group,
  tracks what changed against a server-confirmed baseline, and posts back **only the changed
  keys**. Five hand-written copies of that loop would have meant five slightly different answers to
  "what counts as unsaved", and the endpoint rejects an empty batch with a 400 — which is what a
  save button that does not know it has nothing to send produces.
- **The form trusts the server's echo, not the local value.** A sanitiser may clamp a count or tidy
  a URL, and the field should show what was actually stored.
- **Counts are bounded in the input at 2–24**, because `simple_bangla_sanitize_count()` silently
  clamps to that range. Combined with trusting the echo, an unbounded input would let the owner
  type 40 and watch the field jump to 24 on save with no explanation.
- **Slide, row and banner-pair counts are read off the field names**, not hard-coded — the schema
  is already generated from `SIMPLE_BANGLA_HERO_SLIDES` / `HOME_ROWS` / `HOME_BANNERS`, so raising
  one of those constants puts a sixth slide on the screen with no front-end change.
- **Hot Deals shows what the row will actually contain**, queried from WooCommerce with the same
  `on_sale` filter the theme uses. The row has no product picker — it fills itself — so "how many"
  is unanswerable without knowing how many products are on sale, and an empty preview is the honest
  early warning that the homepage row will be empty too.
- **Category Circles edits two different stores at once.** The count is a `theme_mod`; each
  category's picture and position are WooCommerce's. They are on one screen because the owner is
  answering one question, and the save is **not atomic across the two APIs** — categories are
  written first, one at a time, and a failure stops the run with the successful ones already
  committed and shown as saved. Claiming "nothing was saved" after four of six went through would
  be the more comfortable lie.
- **Row heading and row category stay two independent fields**, and the interface never derives one
  from the other. That is the whole point of the screen: the reference site couples them and gets
  it wrong on five of its six rows.
- **`menu_order` is not in WooCommerce's `orderby` enum for terms**, so the circles list is sorted
  client-side — by menu order, then name, which is the order the theme's `get_terms()` call
  produces.

**A bug worth remembering, caught before the browser run:** WooCommerce sends a category's `image`
as an empty **array** when there is no thumbnail, and `[]` is truthy in JavaScript. The obvious
`category.image ? category.image.id : 0` therefore yields `undefined` for exactly the categories
that have no picture — so every one of them would have read as permanently unsaved, and the save
button would never have gone back to "Saved". Hence `imageIdOf()`.

**Not built, deliberately:** `simple_bangla_home_hotdeals_heading` exists in the Customizer and in
the generated schema but **the theme reads it nowhere** — the Hot Deals shelf became the hero's
left column when the hero was rebuilt on 2026-08-06 and the heading was left behind. The CMS does
not render a control for it, because a field that visibly does nothing is worse than an absent one.
It wants either wiring into `hero.php` or removing from `customizer-home.php`; until then it is
dead in both interfaces.

Verified with 47 browser assertions against real WordPress + WooCommerce with the demo catalogue
imported: the five sidebar items losing their "soon" badge while phase 6/7 keep theirs, Save
starting disabled and enabling on the first edit, a save on each of the four writing screens, and
every value re-read after a reload so the check is server-side rather than self-confirming. Then
the part that matters most — **the storefront**: the row heading typed into the CMS printing on the
homepage, the hero and banner links present in the markup, exactly the 4 circles the count was set
to, and 5 product rows after one was switched off. Plus category `menu_order` reaching WooCommerce
and reordering the list, no horizontal overflow across three screens at 390/768/1360, and no
console errors.

Two lessons about the checks, both of which produced false failures first:

- **Set state, do not toggle it.** The suite clicked row 6's switch and then asserted the row was
  off. A previous run had already left it off, so the click turned it back on. A run has to
  establish the state it asserts, not assume where it started.
- **Type into a cleared field.** `page.type()` appends. The hero link field already held a URL from
  the demo importer, so the new value landed as
  `http://…/shop//shop/?hero=…` and the "did it persist" check failed against a value that had
  in fact persisted perfectly.

And one about this machine: **Playground here answers a single request in about 25 seconds** (three
WASM workers on four CPUs). A 20-second wait for a save that writes a WooCommerce term and then a
theme mod reported a working save as broken. Timeouts in these suites are set in minutes, not
seconds.

### Phase 6 — menu, footer, settings, reviews, coupons (2026-08-09)

Five screens, and the first new PHP since phase 2 — 90 lines of it, all in `inc/menu-icon.php`.

- **The theme's per-item menu icon had to be registered for REST.** `wp/v2/menu-items` has existed
  since WP 5.9, so the Menu screen needs no endpoint of its own, but the icon lives in
  `_simple_bangla_menu_icon`, a protected post meta key nothing had exposed. Without registering
  it, the CMS could not see the icon and — worse — saving a menu item from the CMS would leave it
  carrying an icon the owner could neither see nor remove. The meta stays the theme's; only its
  REST exposure lives in the plugin, so deactivating the plugin still changes nothing.
  `auth_callback` gates it on `edit_theme_options`, the same capability Appearance → Menus uses.
- **`/menu-locations` is the one genuinely new endpoint.** `wp/v2/menus` says which locations a
  menu is assigned to, which cannot answer "is the primary location empty?" — and an empty primary
  location is exactly the state where the shop renders no navigation at all and the owner needs
  telling.
- **`reviews.moderate` was added to the ability map.** WooCommerce gates its review endpoints on
  `moderate_comments`, not on any product capability, so the sidebar's original `products.view`
  would have shown the screen to a catalogue editor who then got a 403 from every request on it.
  This is the ability table doing the job it was built for.
- **The schema now carries each field's description**, pulled from the theme's own registries.
  `simple_bangla_contact_fields()` already explains that the phone number is also the mobile Call
  button; the CMS prints that sentence rather than keeping a second copy of it.
- **`SchemaField` picks its control from `spec.type`, never from a list of keys.** That is what
  keeps the generated-schema promise alive one layer up: a colour token added to the theme gets a
  colour picker here with no change to the plugin. `layoutFields()` names which settings each card
  holds and sweeps anything unnamed into a trailing "Other" card, so a new field can never silently
  vanish because nobody listed it.
- **Settings holds two stores of truth and says so.** Colours are theme mods through `/settings`;
  the address and currency are WooCommerce's through `wc/v3/settings/general`, written as one
  batch. Same non-atomic caveat as the circles screen, and the same honesty about it.
- **The sidebar's Settings item asks for either `appearance.manage` or `store.manage`** — hence
  `canAny()`. Gating it on one would have hidden a working half of the screen from someone entitled
  to it; each card re-checks for itself.
- **Payment gateways and shipping are deliberately absent from Settings.** bKash, SSLCommerz and
  Nagad are plugin screens with no REST API, and a payments card that silently omitted the gateway
  the shop actually uses would be worse than sending the owner to wp-admin. This is the
  "maintenance door" line drawn concretely.
- **Review bodies are rendered as text, parsed with `DOMParser`.** It is the one field in the CMS
  written by a stranger, and a moderation queue is precisely where an unreviewed review should not
  be executing anything. `DOMParser` rather than assigning `innerHTML` to a detached div: a parsed
  document is inert, while a detached div still kicks off `<img src>` loads in some browsers.
- **Reviews open on the pending queue, not on "all".** The only reason to open the screen is that
  something is waiting; a list opening on three hundred approved reviews buries the two that need
  an answer.
- **Menu reordering is buttons, not drag-and-drop**, and depth is changed by choosing a parent. A
  three-level tree that has to work under a thumb is where drag-and-drop stops being obvious, and
  "move up" is unambiguous in a way that dropping between two nested rows is not. The parent list
  excludes the item itself, its own descendants, and anything already at the deepest level the
  theme renders.
- **Deleting a menu item takes its children with it, and the confirmation counts them.** WordPress
  does not re-parent them; they would keep pointing at an id that no longer exists and vanish from
  the menu with no way to find them again.
- **Deleting a coupon is permanent, unlike a product.** WooCommerce would trash it, but the CMS has
  no trash view to retrieve it from, so a trashed coupon would simply disappear.

**Known weight on the Settings screen:** `woocommerce_default_country` is a country *and state*
list — measured at **2,221 options, out of 2,388 on the whole page**. One field is therefore about
ninety per cent of that screen's DOM. It renders fine and is left as it is, because it is the one
control that says where the shop physically is and a Bangladeshi store sets it once; but if that
screen ever needs trimming, this is the only thing on it worth trimming.

**The bug the browser run caught**, and the second one in two phases to come from reading a value
before checking its shape: `layoutFields()` guarded `Object.keys( fields || {} )` but then read
`fields[ key ]` directly in the same function. `fields` is null until the fetch lands, and both
callers run it during the very render in which `SettingsPage` is showing its spinner — so the
first paint of Footer and Settings threw a TypeError and took the whole app down with it. The
screen showed nothing at all, which the suite could only report as "timed out waiting for a
selector."

Two things followed from that. The guard belongs at the top of the function, not on one line of
it. And the suite now prints the page's console errors and its URL when it throws, so a render
that crashed names itself instead of arriving as an anonymous selector timeout — the failure and
its cause were three minutes apart in wall-clock time and would have been one line apart in the
log.

**A second one, subtler:** the Menu screen has to resolve *which* menu to show before it can list
anything, and it was setting `items` to `[]` while that was still in flight. An empty array is the
empty state, so every visit flashed "This menu is empty" over a menu that was about to load. The
two conditions are genuinely different — "we do not know yet" and "there is nothing" — and only
the menu list can tell them apart, so `items` now stays null until it arrives. The theme registers
four locations (`primary`, `footer-1..3`), so there is always more than one menu and the window
was always there; it just took a browser reading the first paint to notice it.

The matching test lesson: **wait for the thing you are asserting about, not for whichever state
paints first.** `waitForSelector( '.sb-menutree, .sb-empty' )` was satisfied by the empty state
during exactly that window and then counted zero rows.

Verified with 47 browser assertions against real WordPress + WooCommerce with the demo catalogue:
the five sidebar items losing their "soon" badge while phase 7 keeps its own, the footer hints
arriving from the theme's own registry, a save on Footer, Settings, Coupons and Menu with every
value re-read after a reload, a malformed hex flagged in the field and cleared when fixed, the
colour reaching a theme mod while the store city reaches WooCommerce **in the same save**, a
coupon created and reopened, the reviews queue defaulting to pending and its "all" filter loading,
the imported menu rendering with its nesting, a new menu item added and reopened with its icon
field — then the storefront showing the phone, the address, the brand colour and the new menu item
that were typed into the CMS. Plus no horizontal overflow across four screens at 390/768/1360 and
no console errors.

One false failure worth naming: the coupon amount was asserted as the string `'15'` and WooCommerce
returns `'15.00'`. Same discount, different string — **compare money numerically.**

### Phase 7 — customers, block list, staff (2026-08-09)

The last three screens, and the phase where a design rule had to be defended rather than followed.

**The block list is enforced by the theme, not by the plugin** (owner's decision, 2026-08-09, after
being asked). A block list is a safety control, and the project's standing rule is that
deactivating the CMS plugin changes nothing about the storefront. Enforcing the list from the
plugin would have broken that rule in the worst possible direction: deactivating the *management
interface* would quietly let blocked customers order again. Checkout belongs to the theme, so the
blocking belongs to the theme. `simple-bangla/inc/blocklist.php` owns the data and hooks
`woocommerce_after_checkout_validation`; the plugin's `/blocklist` endpoint is only an editor for
it and calls the theme's own `simple_bangla_save_blocklist()` rather than repeating the rules.

- **Phone numbers are stored as typed and matched on their last ten digits.** The same Bangladeshi
  mobile arrives as `01712345678`, `+8801712345678`, `8801712345678` and `01712-345678` depending
  on the customer and the keyboard. Comparing raw strings would block one spelling and wave the
  next one through, which is the same as not blocking at all. Stored as typed so the owner still
  recognises the number in the list.
- **Stored as an option, not a `theme_mod`.** It is operational data rather than appearance, and
  the settings bridge carries scalars while this is a list of records.
- **The second entry type is an IP address, not an email** (revised 2026-08-09 — see round two).
- **Validation, not order creation.** The customer is refused before anything is written, so there
  is no half-order and no stock movement to undo.
- **The refusal does not say "you are blocked."** It says the order cannot be placed and gives the
  shop's phone number. The shop may want to talk to the person, and someone whose number was
  mistyped onto the list deserves a way back rather than an accusation.
- **The whole list is sent on every save.** It is short and edited rarely, so add, edit and remove
  are one request with no half-applied state. The endpoint reports how many entries the theme
  rejected, and the screen says so — claiming "saved" over a silently discarded row would only be
  discovered when the blocked customer ordered again.

**Staff is the one place core is re-implemented, and the reason is the guards, not the CRUD.**
`wp/v2/users` will happily let an owner demote their only administrator from a phone. `/staff`
enforces: you cannot change your own role, you cannot delete yourself, the last administrator
cannot be removed or demoted, and only an administrator can hand out the administrator role.
Enforcing those with filters instead would have applied them to wp-admin too — the escape hatch
this whole project depends on staying unchanged. The screen mirrors the rules in what it offers,
because a disabled control explains itself better than an error, but the endpoint is the control.

- ~~**No password is set or sent.**~~ **Superseded 2026-08-09** — the owner sets the password on
  the form and hands it over; a "check your email" step fails whenever the site cannot send mail,
  which on a shared Bangladeshi host is most of the time. The welcome mail still goes out with a
  reset link as a second route in. The password is never echoed back. See round two.
- **Three roles offered, not eight.** WordPress ships a set that mostly describes a magazine;
  `author` on a shop's staff screen invites a choice that means nothing here. A user holding some
  other role is shown but marked unmanaged rather than silently converted.

**Customers is read-only, deliberately.** Editing an address here would change it for future orders
and not for the one being delivered, which is the opposite of what anyone clicking "edit" would
expect. The row links to that customer's orders instead — and the screen says plainly that only
registered accounts appear, because on a cash-on-delivery store most orders are placed as a guest
and an empty result would otherwise read as "no such customer". That link seeds the Orders screen
from `?search=`, which meant teaching Orders to read the parameter; before that it was a link that
looked like it did something and did not.

**The test lesson of this phase: do not drive a slow server through the UI to observe a server
verdict.** The first suite filled the checkout form, clicked "Place order" and waited for either an
error notice or the order-received page. On an instance answering in ~25 seconds that is a race with
nothing useful at stake, and it timed out at three minutes having proved nothing — while a probe
showed the block was working perfectly all along. The suite now posts the serialised form to
`/?wc-ajax=checkout`, which is exactly what WooCommerce's own checkout script does, and reads
`result` and `messages` out of the JSON. Deterministic, seconds instead of minutes, and it asserts
the thing that actually matters.

Verified with 26 browser assertions: every screen out of "soon", Customers loading and saying where
guest orders live, the staff list marking the signed-in account and offering it neither a role
select nor a Remove button, the sole-administrator warning, and — asked directly over REST, past the
interface — **the server refusing a self-demotion with `sb_cms_not_self`**. Then the block list end
to end: a number added from the CMS, surviving a reload, and the theme answering `result: failure`
with the Bangla refusal when that number tries to check out; **the same number written `+880 …`
refused too**, which is the normalisation doing its job; and a control order from an unrelated
number succeeding, without which the two refusals would only prove the checkout was broken. Then
unblocking, no horizontal overflow across three screens at 390/768/1360, and no console errors.

One more test bug of the same family as the earlier ones: `attemptCheckout()` read the CMS boot
payload for a product id, but after its first call the browser is on the storefront, where that
element does not exist. Resolve shared fixtures once, before the run leaves the page that has them.

### Round two — the owner's ten changes (2026-08-09, plugin 1.1.0)

One message, ten requests. What changed and why, in the order it matters:

- **Orders are filtered by stage, not by status.** Five tabs — New Orders, Courier-এ আছে, Completed
  Orders, Returned, Failed / Cancelled — plus All, opening on New. `ORDER_VIEWS` in
  `order-utils.js` **is** that mapping and nothing else may decide it: New = `pending` +
  `processing` + `on-hold`, Courier = `sb-courier`, Completed = `completed`, Returned =
  `sb-returned` + `refunded`, Failed = `failed` + `cancelled`. Every status appears in exactly one
  tab; a status missing from all of them would be an order the owner could never find. Tab counts
  come from `wc/v3/reports/orders/totals` — one request, not five list calls made only to read their
  totals. The dashboard tiles sum the *same* table, so the two screens cannot disagree.
- **The theme registers two order statuses** — `sb-courier` and `sb-returned`, in
  `simple-bangla/inc/order-status.php`. **This corrects the same day's earlier decision to map the
  stages onto existing statuses**, which the owner's first real test order disproved: WooCommerce's
  Cash on Delivery gateway sets an order to `processing` the moment it is placed, so with
  `processing` standing in for "with the courier" every new order appeared as already dispatched.
  There was no spare status to borrow — `on-hold` means "waiting for payment" throughout WooCommerce
  and in its emails. In the **theme**, not the plugin, for the block list's reason: an order's status
  is customer-visible in My Account and in every WooCommerce email, so with the plugin off real
  orders must still have a name. `sb-courier` is added to `woocommerce_order_is_paid_statuses` or
  stock would be reduced twice; `sb-returned` restocks, guarded by WooCommerce's own
  `_order_stock_reduced` flag so toggling twice cannot restock twice.
- **A stage is only entered by someone deciding it was.** Send to Courier moves an order to
  `sb-courier` from the three un-dispatched statuses only, so re-sending a delivered parcel cannot
  drag it backwards. At the courier stage the screen — and the phone card — offer exactly two
  buttons: *Delivered — mark completed* and *Returned / not received*. "Returned" rather than
  "Cancelled" for the second: a parcel that came back is a different number from an order killed
  before it shipped, and the returns are what the courier fees were spent on. Cancelling before
  dispatch is still in the status list.
- **Two order layouts, one data source.** Cards below 900px to the owner's screenshot, the table
  above it. Both are in the markup and CSS picks; a resize listener deciding what the owner can see
  would also decide what a printed page shows.
- **The reference screenshots are dark; the CMS stays black-and-cream** (owner's choice). Layout
  borrowed, palette not, so all twenty screens still look like one product.

**Couriers** — `simple-bangla-cms/inc/courier.php`, entirely in the plugin. Unlike the block list,
nothing on the storefront reads it and a shop with the plugin off simply has no button, so the
theme knows nothing about couriers. Three are supported and the active one is chosen in Settings.

Each courier needs **two unrelated sets of credentials**, and this is the thing to remember:

| | Dispatch — documented API | Delivery record — portal session |
|---|---|---|
| Steadfast | `portal.packzy.com/api/v1/create_order`, Api-Key + Secret-Key | `steadfast.com.bd/login` (CSRF + cookie) → `/user/frauds/check/{phone}` |
| Pathao | `api-hermes.pathao.com/aladdin/api/v1/orders`, OAuth | `merchant.pathao.com/api/v1/login` → `/api/v1/user/success` |
| RedX | `openapi.redx.com.bd/v1.0.0-beta/parcel`, access token | `api.redx.com.bd/v4/auth/login` → `customer-success-return-rate` |

**No courier publishes an API for the delivery record.** Every Bangladeshi fraud-check site signs
in to the merchant portal and calls what that portal's dashboard calls, and so does this. It is
therefore treated as something that will break: cached six hours, never in the way of a dispatch,
and a failure is reported per courier as "could not be read" rather than as a number. The local
half — this shop's own orders for that number — needs nobody and is shown first. Pathao and RedX
address parcels by **numeric city/zone/area ID**, not by address text, so each carries a default in
Settings; that is a real limitation and the field says so.

Other decisions from the same round:

- **`register_rest_field( 'shop_order', 'sb_courier' )`** rather than reading `_sb_courier` out of
  `meta_data`. Which meta WooCommerce exposes has changed more than once and the order list would
  have quietly lost its courier column the next time it did.
- **A dispatch is refused twice** unless `force` is sent; the interface turns that 409 into "send it
  again?" rather than a red toast. Two consignments for one parcel is a bill paid twice.
- **Deleting an order erases it**, unlike a product. There is no trash view here to retrieve it
  from, and a "deleted" order that kept appearing in search would be worse.
- **A staff password is set on the form and shown, not masked.** It is typed once to be read out to
  someone in the same room; masking adds only the typo a confirm field then exists to catch. The
  username is derived from the email and never shown. Sign-in already accepted an email — core
  registers `wp_authenticate_email_password` — so that half needed labels, not code.
- **Rich text is the plugin's own `editor.js`**, ~9 KB, contenteditable plus a short toolbar, with
  an HTML view as the escape hatch. WordPress's TinyMCE was the alternative and would have brought
  jQuery, ~300 KB and wp-admin's stylesheets into the one page built to avoid them. `execCommand`
  is deprecated with no replacement; if it ever stops working the textarea is already there and the
  stored data does not change.
- **A category's slug is generated and Parent is folded under "Advanced".** Parent stays because
  several of the shop's categories are already nested and removing the control would make that
  unreachable — but it is answered once and never again, so it is not in the way. An empty slug is
  *omitted* from the request rather than sent blank: on an update, blank asks WooCommerce to
  regenerate, which silently breaks every link to a category that was only being renamed.
- **The block list is phone + IP; email is gone.** Entries stored as `type: email` are ignored and
  dropped by the next save. IP matching uses `WC_Geolocation::get_ip_address()` — deliberately *not*
  `REMOTE_ADDR` as the sign-in throttle does — so the address blocked is the same one WooCommerce
  writes on the order and the CMS shows, which is where the owner copies it from. Checkout only, and
  the file says plainly that a shared mobile-carrier address makes this a nuisance control rather
  than a security one.
- **Each social link has a `*_show` switch**, default on. Blanking the URL already hid the icon but
  threw the address away; a shop pausing its Instagram for a month should not have to find the link
  again.
- **One back button, in the topbar.** It is then in the same place on every screen including the
  ones drawn by shared components, and a new screen cannot forget it. It prefers real history —
  which brings scroll position and screen state back — and walks up the path only for a deep link.
- **`/manage` with no trailing slash resolves to the dashboard.** `SB.base` carries the slash, so
  the path anyone actually types did not start with it and fell through as the literal route
  `/manage`, which matches no screen — a "Not found" page at the CMS's own front door.
  `currentPath()` strips both spellings.
- **There is no Customers screen** (removed 2026-08-09, owner's decision, and its `customers.view`
  ability with it). It could only ever list registered accounts, and on a cash-on-delivery shop
  almost nobody registers — so it showed an empty table beside a sentence explaining that the real
  answer was on the Orders screen. Searching Orders by phone number *is* that answer, and the
  `?search=` seeding that fed it stays for exactly that.

**The import map is a real fix, not tidying.** Versioning the entry script never versioned the
modules it imports, so a release changing `ui.js` under a changed `app.js` would ship a new entry
point beside cached copies of its own dependencies. `simple_bangla_cms_import_map()` reads
`assets/` and rewrites every import to a versioned URL. Two things about it:
`GLOB_BRACE` **silently matches nothing** on some libc builds including Playground's, which printed
an empty map that looked like it was working — hence the directory iterator; and the map covers
`assets/vendor/` too, or the Preact bundle every screen depends on would be the one unversioned
import.

Not verified against a live courier account — nobody has real credentials here, so dispatch and the
record lookup are written to their documented and observed request shapes and exercised only for
their failure paths. Send the first real parcel with the courier's own panel open beside the screen.

### The browser-tab icon (2026-08-09, plugin 1.3.1)

The owner reported the WordPress mark showing in the tab. With `site_icon` unset, WordPress 6.1+
serves a default favicon of its own, so a shop that never opened Settings wears somebody else's
logo everywhere a favicon appears.

- **Fixed by filtering `get_site_icon_url()`, not by printing link tags.** `has_site_icon()` is
  only a call to that function, so answering the one filter turns on the whole of core's icon
  handling — the 32px and 192px icons, the apple-touch-icon, the Windows tile, the
  `/favicon.ico` redirect — consistently and without a second `<link rel="icon">` to go stale the
  moment a real icon is set. A real site icon always wins; the filter only speaks when core had
  nothing to say. On multisite it declines an explicit `$blog_id`, because core fires the filter
  *after* restoring the current blog and this site's logo is not that site's answer.
- **In the theme** (`inc/site-icon.php`), for the block list's reason: a shop with the plugin
  switched off must not go back to wearing the WordPress mark.
- **The fallback is the site logo** — the picture the shop has already uploaded, the same
  `custom_logo` mod the header, the footer and the CMS sign-in page read. A wide wordmark at 16px
  is legible only as a smudge of the right colour, which still beats someone else's logo; the
  real answer is a square icon, which is what the new field is for.
- **`site_icon` is an option, not a `theme_mod`** — the first field in the schema that is. Rather
  than special-case it, a spec may now say `store => 'option'`, and `simple_bangla_cms_read_setting()`
  / `_write_setting()` route every read and write. Hard-coding `get_theme_mod()` at the call sites
  would not have failed loudly: it would have read a mod nobody ever wrote and returned the
  default, which on screen is indistinguishable from "never set".
- **It is WordPress's own `site_icon`**, the same setting Customizer → Site Identity writes, for
  the same reason `custom_logo` was: two "shop icon" settings would mean two answers to one
  question.

Verified with 23 assertions in Playground: no icon links at all when neither is set, core's four
tags printed once the logo is set, the 32px request served a scaled size rather than the 800px
original, a real site icon overriding the fallback, the endpoint writing the **option** and
leaving no stray theme mod, a missing attachment refused, and clearing the field returning to the
logo.

### The tidy-up pass (2026-08-09, plugin 1.4.0)

The owner opened the CMS on a laptop and reported it as "এলোমেলো" — everything slightly out of
line, empty space around the edges, the profile chip wrong. Read against a real install rather than
from the screenshot, most of it turned out to be four faults repeating themselves across twenty
screens, plus one number that was quietly lying.

- **`text.js` is the one place an API's HTML becomes text.** `wp/v2/menu-items` returns a title
  entity-encoded, so half the shop's own menu read `Airpod&#8217;s` on screen; `wc/v3/settings`
  labels the currency `Bangladeshi taka (&#2547;&nbsp;)` and the select printed exactly that.
  Nothing in this interface assigns `innerHTML`, which is the right rule and the reason these
  arrived raw. There were already three private half-copies of the fix — `decodeEntities` in the
  Content Pages screen and a `stripTags` in each of Products and Settings, two of them building a
  detached `<div>` — so they became one module using `DOMParser` throughout: a parsed document is
  inert, a detached div still starts `<img src>` loads. Menu titles were the worst case, because the
  edit dialog loaded the encoded string as the value to save and would have re-encoded it on every
  pass.
- **Revenue is summed from the orders, not from WooCommerce Analytics** — and this is the one that
  mattered most. `wc_order_stats` is filled by a *scheduled* action, so on a shop whose cron is late
  or throttled the dashboard reported ৳0 beside an Orders screen listing paid orders. This reverses
  the phase-1 decision. The warning that decision carried was about `wc_get_orders()`, which
  instantiates every order; an indexed `SUM()` over the same table the order counts already group
  is not that, and using one source means the two figures on the same screen cannot disagree — the
  rule the order stages were rebuilt around. HPOS reads `wc_orders.total_amount`; legacy storage
  joins `_order_total`.
- **Sign out moved under the avatar.** The chip only displayed, while Sign out and a sentence about
  wp-admin sat in 12px grey at the foot of the rail, the last of them wrapping. Both are about the
  person rather than the shop, so both are in the profile menu now — with the account's email in
  full, because a shop's account is usually set up under its own address and that is the line the
  owner recognises. The rail's foot keeps "View the store" alone.
- **Four layout faults, each fixed where it was caused, not where it showed:**
  `.sb-row` wrapping inside a `width: 1%` actions cell made every Categories and Content Pages row
  two buttons tall; the Category Circles list was ragged because its picker column was as wide as
  whichever buttons a category happened to need (two with an image, one without), so a grid replaced
  the flex row; a settings field was a full 1,100px wide with nothing beside it, so `Fields` lays
  them two abreast at `minmax(min(100%, 420px), 1fr)` with pickers and switches spanning; and a card
  `lead` that was the first `.sb-hint` in the body sat directly above the first field's label in the
  same grey, reading as help for the wrong thing — it is part of `Card`'s header now.
- **The three couriers fold.** Eleven fields between them made that one card longer than the rest of
  Settings put together, on a screen where a shop uses one courier. `<details>` bound to the active
  choice, so changing the select opens the fields that choice just made relevant.
- **A grid column sized `auto` is never narrower than its widest item's min-content**, which is how
  one long product name in the Hot Deals preview pushed a 390px phone 17px sideways. `.sb-form` and
  `.sb-grid-cards` now say `minmax(0, 1fr)` and `minmax(min(100%, 280px), 1fr)`. The ellipsis that
  should have prevented it never had the chance — a row can only truncate once something upstream
  says the column stops at the container.
- **The rail fits a laptop.** Sixteen items at the old row height overflowed a 900px window and the
  owner's screenshot showed it cut off mid-list, which reads as broken rather than as long.
- **`:has()` earns its place once.** The phone's fixed action bar had clearance only on `.sb-editor`
  and `.sb-form`, so on the dashboard it covered "Figures updated at…" and on a list it covered the
  pager — precisely the screens that are not forms. `.sb-page:has(> .sb-page__header
  .sb-page__actions)` asks the only question that matters.

Verified with **31 browser assertions** against real WordPress + WooCommerce with the demo catalogue
and five seeded orders: the revenue tile matching a total summed independently from `wc/v3/orders`,
the whole sidebar visible without scrolling at 900px, the profile menu opening with its email and
Sign out and closing on Escape, no `&#…;` left anywhere in the menu tree or on Settings, the menu
edit dialog holding decoded text, address fields sitting two abreast with the lead in the card head,
three couriers folded shut, row actions on one line in both tables, every circle name and order box
on the same vertical line, no horizontal overflow at 1440/1024/390 across all seventeen screens, the
phone action bar clearing the last line of the page, and zero console errors.

Three lessons about the checks, each of which produced a false failure first: **wait for the loading
state the screen actually uses** — the dashboard shows skeleton figures, not a spinner, so a suite
waiting on `.sb-spinner` read "0000" and reported a working revenue figure as a false zero;
**scope a selector to the markup, not to a guess at it** — a menu row's edit trigger is a
`.sb-menurow__title` button, and a query for anchors silently skipped the assertion rather than
failing it; and **run the browser with its cache off**. The import map versions every module by the
plugin version, which does not move while a file is being edited during a run, so the browser paired
a freshly-fetched module with a cached copy of one it imports and reported an export that plainly
exists as missing. `page.setCacheEnabled( false )`. Between releases the version bump is the whole
point of the map; within one editing session it cannot help, and the failure it produces reads
exactly like a real broken import.

### Phase 9 — Content Pages (2026-08-09)

The owner asked where About Us, Privacy Policy and Delivery & Return are written, and the honest
answer was "wp-admin" — the one answer this project exists to stop giving. One screen and one
editor close it: pick an existing page, write it in the rich-text editor, or add a new one with a
name, an address and a tick that puts it in the footer.

- **Built on core's `wp/v2/pages`, which has covered pages since WordPress 4.7.** Listing, search,
  statuses, slugs, revisions and the trash all already work and are all already capability-checked.
  Re-implementing them would have meant re-implementing `kses` on the way in and getting it wrong.
  The plugin adds exactly one thing: `inc/rest-pages.php`, which registers the theme's footer tick
  for REST. Same split as the menu-item icon, for the same reason.
- **The tick's data and its enforcement live in the theme** — `simple-bangla/inc/pages.php`. This is
  the block list's rule and the order statuses' rule applied a third time: the footer is rendered by
  the theme, so deactivating the management interface must not empty it. The plugin only exposes the
  key so the CMS can write it.
- **A tick beats the menu assigned to `footer-1`.** That ordering is the only surprising thing in
  the file and it is deliberate. The demo importer assigns a menu there, so if the menu won, the
  tick box would silently do nothing on exactly the installs that had been set up properly. Tick
  nothing and the column behaves exactly as it always has; tick anything and the ticks are the
  column. Columns two and three stay menu-driven and are untouched.
- **Registered on `rest_api_init`, not `init`.** Plugins load before themes, so
  `SIMPLE_BANGLA_FOOTER_LINK_META` does not exist when this plugin is included, and the meta is
  genuinely absent on a plain page load. That is correct, and it is worth knowing before reading a
  test that checks for it too early — the first version of this suite did exactly that and reported
  a working feature as broken.
- **`auth_callback` gates the tick on `edit_pages`, not `edit_theme_options`.** Linking a page in
  the footer is arguably a navigation decision, but the person who writes the Delivery & Return page
  is the person who needs it linked — and splitting the two would have produced a form that saved
  everything on it except the tick.
- **An empty slug is omitted from the request, never sent blank.** On create, WordPress makes one
  from the title; on update, blank asks it to *regenerate*, which silently changes the address of a
  page that was only being renamed and breaks every link to it. Same rule already learned on
  categories.
- **Titles are decoded on the way in and stored decoded.** The theme's own default page is stored as
  `Delivery &amp; Return Policy`, and an editor that showed that to the owner would be asking them
  to understand HTML entities to fix a typo. Decoded via `DOMParser` — inert — not `innerHTML`.
- **The homepage and the store pages are marked, not blocked.** The front page's body is never
  printed (the theme builds it from the Homepage screens) so the row says so; Cart, Checkout and My
  Account are one WooCommerce shortcode each, so they open in the HTML view with a warning. Sniffed
  from the body rather than asked of WooCommerce, which would cost a second request per visit
  needing `manage_woocommerce` — a capability this screen otherwise does not want.
- **Deleting trashes rather than erases**, unlike a coupon or an order. A page is often the only
  copy of writing nobody wants to do twice.
- **Only published pages reach the footer**, and the tick's hint says so while the status is Draft —
  otherwise ticking a draft is a control that appears to work and does not.

**The gap that made this a two-session job:** `PageEdit` was imported into `app.js` and `resolve()`
had no pattern for `/content/:id`, so both "Add page" and "Edit" fell through to "Not found" — a
complete, correct editor that nothing could reach. Nothing warns about an imported-but-unused
component, which is the argument for a route check in the suite rather than only a screen check.

Verified with 60 assertions in Playground against real WordPress + WooCommerce: the meta registered
with its auth callback once `rest_api_init` has fired, a page created through `wp/v2/pages` with the
tick coming back true *and* reaching the database as `'1'`, the list request returning drafts and
`title.raw`/`content.raw`, then the storefront — **the theme's own footer template rendering the
link**, with the unticked page and the ticked *draft* both absent from the markup and column two
still menu-driven. Then a subscriber refused the tick with 403 and the stored value untouched, a
rename leaving the slug alone, unticking putting the column back to its menu, a delete landing in
the trash, and the route, the sidebar entry and the import map all covering the two new modules.

### The order card, and Content Pages narrowed (2026-08-10, theme 1.3.0 / plugin 1.5.0)

Three requests from the owner after using the CMS on the live shop.

**Content Pages lists the footer's pages and nothing else.** It listed all fourteen pages WordPress
contained, of which four were writing and the rest were Cart, Checkout, My account, Sample Page and
the homepage — bodies that are a WooCommerce shortcode or are never printed. Offering those for
editing was an invitation to break the checkout while looking for About Us.

- **The theme derives the list, not the plugin.** `simple_bangla_footer_pages()` in
  `simple-bangla/inc/pages.php` walks the same tick → assigned menu → theme defaults order the
  footer renders with, and `template-parts/footer/columns.php` now reads its column definitions and
  its source-priority from the same two functions. A second copy of "which pages are in the footer"
  living in the plugin would have drifted the first time a column changed, and the CMS would then
  have offered the wrong pages to edit. `/footer-pages` is a thin exposure of it, returning IDs only
  — the screen still asks `wp/v2/pages` for the records, so core keeps owning the page controller.
- **Add page, delete and the footer tick are gone from the screen.** All three were traps once the
  list became derived: a new page could not appear in it, a deleted page would take a footer link
  with it, and unticking a page would remove it from the only screen that could tick it back. Which
  pages are in the footer is now unambiguously the Menu screen's question; this screen answers what
  they say. The theme still honours a tick set before the change, and the meta stays registered.
- Menu items that are not pages — category links, custom URLs — are skipped, and only top-level
  items are read, matching the `depth => 1` the footer renders with.
- **A page reached through a menu is listed whatever its status.** The first version filtered to
  published pages, which is right for the tick and the theme-defaults branches — both build their
  list from a page query, and the footer prints only published pages there — but wrong for a menu,
  because `wp_nav_menu()` prints an item whether or not the page behind it is published. The
  theme's own Refund and Returns Policy ships as a draft in a footer menu, so the filter hid the one
  page most obviously in need of writing from the screen for writing pages. Caught by asserting the
  CMS list against the links actually rendered in the storefront's footer rather than against an
  expectation written by hand.

**Reviews opens on All** (owner's decision, reversing phase 6). Opening on Pending was reasoned
from "the only reason to visit is that something is waiting"; in practice this shop approves reviews
as they arrive, so the screen opened on an empty box reading "Nothing waiting" and gave no sign the
store had any reviews at all.

**An order opens as a card over the list**, to a reference the owner supplied — the shape borrowed,
the palette not: it is the shop's black and cream, because the CMS is one product and this would
otherwise be the one screen that looked like a different one.

- **The whole row opens it**, not just the order number. Clicking a row and having nothing happen
  was the single most confusing thing about that screen; the row plainly *is* one order. The
  checkbox is the exception, and the number stays a real link so middle-click still reaches the full
  page.
- **The card is not a second order screen.** It carries what the morning's work needs — the items,
  the delivery details, the money, the one action the order is waiting for — plus Edit info and a
  permanent delete. Notes, refunds and the printable invoice stay on `/orders/123`, one click away
  in the card's footer and still what a deep link opens.
- **`order-parts.js` is why there are not two of everything.** Items, totals, delivery details,
  payment and the stage buttons are one component each, drawn by both the card and the full page.
  The alternative was two copies of "what is in the parcel" that would disagree the first time
  either changed — and it is the card the owner works from all day.
- **Editing an address writes billing *and* shipping.** The checkout asks for one address on a
  single-country cash-on-delivery shop, so writing only billing would put the corrected number on
  the invoice and the old one on the courier's label.
- **The card's open state is component state, not a route.** Closing it must not cost a navigation,
  and the list keeps its scroll position, its tab and its selection while an order is worked on.
  Anything the card changes refetches the rows and the tab counts behind it.

### Build order

| Phase | Scope | State |
|---|---|---|
| 0 | HPOS, plugin scaffold, guards | ✅ done 2026-08-08 |
| 1 | REST layer: `/session`, `/settings`, `/dashboard` | ✅ done 2026-08-08 |
| 2 | `/manage` route, branded login, sidebar shell, dashboard screen | ✅ done 2026-08-08 |
| 3 | Products + Categories + media | ✅ done 2026-08-08 |
| 4 | Orders, invoice, status, refunds | ✅ done 2026-08-08 |
| 5 | Homepage modules (hero, rows, circles, banners) | ✅ done 2026-08-08 |
| 6 | Menu, Footer, Settings, Reviews, Coupons | ✅ done 2026-08-09 |
| 7 | Customers, Blocked List, staff accounts | ✅ done 2026-08-09 |
| 8 | Round two: back button, socials, staff passwords, slugs, rich text, order stages, couriers, IP blocking | ✅ done 2026-08-09 |
| 9 | Content Pages, the browser-tab icon | ✅ done 2026-08-09 |
| 10 | The tidy-up pass: entities, revenue, profile menu, form and table layout | ✅ done 2026-08-09 |

All seventeen screens are built. The audit log listed against phase 7 in the original plan was
never specified and is not built; it is the obvious next thing if the owner wants one.

After phase 4 the store is genuinely runnable without wp-admin. The rest is control and polish.

### Verification

`php -l` plus a 70-assertion end-to-end suite executed inside WordPress Playground against real
WordPress + WooCommerce — it dispatches actual REST requests through `rest_do_request()`, writes
settings, and reads them back through *the theme's own* `simple_bangla_get_color()` and
`simple_bangla_get_contact()` to prove the bridge is real rather than self-consistent. 70/70 pass.

Two traps worth remembering:

- `rest_do_request()` does **not** parse a query string appended to the route. Use
  `$request->set_param()`, or the parameter silently never arrives and the endpoint looks broken.
- Running the Playground CLI from **Git Bash mangles the VFS mount path** — `/wordpress/...`
  becomes `C:/Program Files/Git/wordpress/...`. Run it from PowerShell, or prefix the command
  with `MSYS_NO_PATHCONV=1`.
- **`playground server` accepts connections before its blueprint has finished applying.** An
  early request returns a half-configured site — default theme, plugin not yet active — which
  looks exactly like a broken route. Poll something the blueprint sets (`/wp-json/` reports the
  site name and registered namespaces) rather than trusting the first 200.
- `runPHP` output is not printed by the CLI. Have the script write its results to a mounted
  directory and read the file.
- **A long-running `playground server` sometimes dies mid-session** (six WASM workers). It leaves
  its site directory behind in `%TEMP%\node.exe-playground-cli-site-*`, and those do not always
  delete. Restart on a fresh port and carry on; the mounts make the state disposable.

## Local development

**PHP 8.5 is installed at `C:\php\php` (corrected 2026-08-08).** The earlier note that this
machine had no PHP is out of date — `php -l` works directly and should be run on every changed
file.

Mount the CMS plugin alongside the theme; `blueprint.json` activates it only if the path exists,
so the blueprint still works when it is not mounted:

```
--mount-dir <abs>/simple-bangla-cms /wordpress/wp-content/plugins/simple-bangla-cms
```

No MySQL, XAMPP or Docker is installed. For anything needing a running WordPress, use
WordPress Playground — WASM PHP inside Node:

```
npx @wp-playground/cli@latest server --port=8882 \
  --mount-dir <abs>/simple-bangla /wordpress/wp-content/themes/simple-bangla \
  --blueprint <path>/blueprint.json
```

- Use `--mount-dir`, not `--mount host:vfs` — a Windows path contains a colon.
- Do **not** use `@wp-now/wp-now`; it resolves its install path against the current drive and
  crashes with `D:\C:\Users\…`.
- WooCommerce 11 ships with **Coming soon mode on**. Turn it off under
  WooCommerce → Settings → Site visibility or the storefront renders as a placeholder page.

## White cards and the phone bottom bar (2026-08-11, theme 1.4.0)

The owner sent a screenshot of the reference site's bottom bar and asked for two things: cards
white, and the bar like the marked buttons.

- **Cards are white; the page stays cream** (owner's choice, asked before building — the first
  reading of "the whole background white" would have flattened `--sb-page` too). `--sb-bg-alt`
  goes `#fffcf7` → `#ffffff`, which is also what the reference does: a white product panel on a
  warm page. Cards carry no border or shadow, so the page colour is the only thing separating
  them from the background — which is why the page must **not** follow them to white.
- **`--sb-hover` is new, and it is the reason the change is safe.** Two hover states borrowed
  `--sb-bg-alt` — the live-search results and the mega-menu's sub-links, both sitting on white
  panels — so making cards white would have made both hovers invisible with nothing failing
  loudly. It is `rgb(0 0 0 / 5%)`, a translucent tint rather than a colour, so it stays visible
  whatever surface ends up under it. Deliberately *not* a Customizer token: it is a derived
  effect, not a palette entry.
- **`--sb-home-button` is a Customizer token** (`#e8262d`), the tenth, and the only colour in
  the palette that is not black, cream or grey. A token rather than a value in `footer.css`
  because it is the loudest thing on a phone screen and therefore the most likely to be
  argued about — and the palette rule is that no colour is hard-coded. It appears in the CMS
  Settings screen automatically, since the schema is generated from this registry.
- **The bar's icons and labels are `--sb-ink` at 24px/13px**, up from `--sb-muted` at 22px/11px,
  with `stroke-width: 2` — at 24px on a phone a 1.8px stroke reads as grey however black it is.
- **The Home circle is filled, not outlined.** The `home` icon's path is already a closed house
  silhouette with the doorway cut out, so `fill: currentColor; stroke: none` gives the
  reference's solid mark with no second icon added.
- **The `chat` icon was replaced.** It had been an arc with a gap plus a detached dash, which at
  the bar's new size reads as a reload symbol rather than as Chat — the one icon there a
  customer has to recognise instantly. It is a speech bubble now, and the thank-you page's
  delivery-note row gets it too.
- **The reserved space under the bar is one number, read twice.** `--sb-bottom-bar-height` is
  declared on `:root` inside the phone query and used both for `body`'s padding and by the
  floating buttons; it used to be `68px` written out in two places. Declaring it only inside
  the query preserves the floats' `var( …, 0px )` fallback on desktop, where there is no bar.

**A live site will not pick the card colour up if it was ever saved.** `simple_bangla_palette_css()`
emits only the deltas from each token's default, so a shop where someone opened the colour picker
and saved `#fffcf7` keeps writing it. Clearing the field under CMS → Settings → Colours (or
Customizer → Colours) returns it to the new default.

**Verified with 30 browser assertions** against real WordPress + WooCommerce with the demo
catalogue: the card computing to white while the body stays `rgb(251, 244, 226)`, the bar's
colour, item ink, label size, stroke weight, the circle's red, size, lift, and its solid white
icon, the reserved height covering the measured bar, the copyright clearing it at the foot of
the page, the bar gone at 1280px, and the rescued hover tint not resolving to white.

**Three test bugs on the way, all the same family — never compare a rect to `clientWidth`.**
Under Chrome's mobile emulation (`isMobile: true`) `getBoundingClientRect()` reports in a space
**four times** the layout viewport: 1560 and 3376 for a 390×844 window. So an overflow check
written as `scrollWidth <= window.innerWidth` passed a page that scrolls sideways and failed one
that does not, and a visible copyright line was reported as off-screen. Compare rects to rects,
compare `scrollWidth` to `documentElement.clientWidth`, and for overflow prefer asking the page
to scroll (`window.scrollTo( 99999, 0 )`, then read `scrollLeft`) over any width arithmetic. For
screenshots, drop `isMobile` entirely — the CSS breakpoints only read the width, and the flag
frames the shot on the wrong part of the document.

**Found here, fixed in 1.4.1** — see the next section. The homepage scrolled sideways, and that
turned out to be the cause of the bottom bar the owner could not see.

## The bottom bar the owner could not see (2026-08-11, theme 1.4.1)

The owner reported that on a phone the new bar was not there — except zoomed far out, where it
appeared tiny across the foot of a page whose content sat in the left eighth of the screen; and
that scrolling to the very bottom showed a single "Shop" icon under the footer. Three symptoms,
one cause, and it was the horizontal overflow recorded above as found-but-not-fixed.

**The mechanism, because it is not obvious.** The homepage's scrollable width exceeded the
viewport, so the browser widened the viewport to fit the content — to **1560px on a 390px
screen**, four times over. Everything measured against the viewport widened with it, and a
`position: fixed` bar is measured against the viewport: the bar really was 1560px wide, laying
its five items across a box four times the width of the glass. Only the first of them, Shop, fell
on screen. Zooming out revealed the whole widened viewport, which is why the bar "appeared" there.

**A correction to the previous section.** That `innerWidth` of 1560 against a `clientWidth` of 390
was written up as an artifact of Chrome's mobile emulation. It was not an artifact. It was this
bug, reproduced exactly, and it was on screen in the measurements before the owner ever saw it.
The emulation was right; the reading of it was wrong.

**Fixed in one rule** — `.sb-slider { overflow-x: clip; overflow-clip-margin: 16px; }`:

- **On `.sb-slider`, not on the page shell.** The leak is a scroll track's overflow escaping its
  wrapper, so it is closed at the wrapper. Clipping `.sb-home-section` and `.sb-hero` instead
  also worked, but only for the homepage — the related-products row on a product page uses the
  same slider and would leak the day a shop has enough related items.
- **`clip`, never `hidden`.** `overflow-x: hidden` with `overflow-y: visible` computes the y axis
  to `auto`, which would turn every slider into a vertical scroll container. `clip` clips one
  axis and creates no scroll container.
- **The clip margin is for the arrows**, which sit at `left: -8px` / `right: -8px` with a shadow
  and would otherwise be sliced in half on desktop. Measured: 16px shows them whole at 1280 and
  does not let the overflow back out at 390. Where `overflow-clip-margin` is unsupported the
  arrows lose 8px and the fix still holds; where `overflow: clip` itself is unsupported the rule
  is dropped and behaviour is what it was, so there is no new failure mode.
- **The tracks still scroll.** Product rows, category circles and the hero track were each driven
  and checked after the change; clipping an ancestor does not disturb a descendant's own scroller.

**Verified with 64 assertions** across home, shop, cart, checkout and a real product page at 360,
390, 768 and 1280: none of them pans sideways, `window.innerWidth` equals the device width on
every one, and below 768 the bar is exactly the viewport's width with all five items inside it.
Plus the 30-assertion bar suite re-run as a regression, and no console errors anywhere.

## Order numbers (2026-08-12, theme 1.5.0)

The owner asked for an order number that is unique per order and mixes letters with digits, instead
of `#234` — which is unique, but is also a running count of everything the shop has ever sold,
printed on every invoice and read down the phone. `simple-bangla/inc/order-number.php`.

- **In the theme, not the CMS plugin.** Fourth application of the block list's rule: the number is in
  the confirmation email, on the thank-you page and in My Account, so with the management interface
  switched off an order must still answer to the number its customer was given.
- **Uniqueness is by construction, not by luck.** The number is three random characters followed by
  the order's own ID re-spelled in a 31-character alphabet. Two orders can no more share a number
  than they can share an ID, so there is no generate-check-retry loop and no race to lose when two
  people check out in the same second. The random head is what stops consecutive orders reading as
  consecutive, which is the whole point. Seven characters until the shop passes 923,520 orders.
- **The alphabet drops `0`, `O`, `1`, `I` and `L`** — every one is half of a look-alike pair, and
  this string gets copied off a screen onto a courier's label by hand.
- **The first character is always a letter**, which is not cosmetic: it means a generated number can
  never be all digits, and therefore can never collide with the plain numeric number an older order
  still carries.
- **Nothing is back-filled.** An order whose customer already holds an email saying `#234` must not
  quietly become `CM7222K` on the invoice; the two would then disagree with no way to tell which was
  real. Orders placed before this file keep their ID, and the point above guarantees the two kinds
  cannot clash. Only orders created from now on are numbered.
- **One filter does the whole display side.** `woocommerce_order_number` reaches the thank-you page,
  every WooCommerce email, My Account, the CMS order list, card, detail screen and invoice, and the
  `invoice` field sent to the courier — every one of those already asked `get_order_number()` rather
  than reading the ID, so **no template and no CMS screen changed**. The CMS's `order.number` is that
  same call, made by WooCommerce's REST controller.
- **Search needs two filters because WooCommerce searches orders twice over** —
  `woocommerce_shop_order_search_fields` when orders are posts and
  `woocommerce_order_table_search_query_meta_keys` when they are in the HPOS tables. Both take a list
  of meta keys and WooCommerce's own docblocks name each as the other's counterpart, so one callback
  serves both. Registering only the mode in use today would make the Orders screen's search — whose
  placeholder promises "order number" — stop working on a day nobody was touching this code.
  **An earlier attempt used `woocommerce_order_table_search_filters` plus
  `woocommerce_hpos_generate_where_for_search_filter` to build a WHERE clause by hand; neither name
  is what WooCommerce 11 calls, the hook never fired, and the search silently found nothing.** Read
  `OrdersTableSearchQuery.php` before guessing at that class's hooks.
- **`simple_bangla_order_id_by_number()` is a direct query, not `wc_get_orders()`.** The obvious
  version passed a `meta_query`, and on the legacy post-table store WooCommerce silently drops it:
  every call came back with the newest order regardless of what was asked for — a lookup that answers
  confidently and wrongly, which is worse than one that fails. Caught by the assertion that a lookup
  of nonsense returns 0.
- The prefix is empty by default (`simple_bangla_order_number_prefix` filters it) because every
  template already prints a `#` in front, and `#SB-CM7222K` says "number" twice.

Verified with **25 assertions run twice — once with HPOS on and once with it off**, against real
WordPress + WooCommerce 11 in Playground: two real orders numbered and reloaded from the database, a
later save not renumbering, no ambiguous characters, the lookup helper in both directions plus its
nonsense case, an order stripped of the meta still reporting its ID and still findable by it, the
`wc/v3/orders` search the CMS actually calls finding each order **by its printed number** without
dragging in the other, search by phone and by a legacy numeric ID still working, the REST `number`
field, the courier parcel's `invoice`, and the theme's own thank-you template and WooCommerce's order
email both printing the generated number. Both storage modes, 25/25.

**Note for future Playground runs:** a fresh WooCommerce 11 install under this blueprint comes up
with **HPOS off**, not on. A suite that assumes otherwise silently tests only the legacy path — the
first run here reported the HPOS search hooks as working when they had never been reached. Force it
with `woocommerce_feature_custom_order_tables_enabled` + `woocommerce_custom_orders_table_enabled` in
a `runPHP` step of its own, and have the script print which mode it is in.

## Translation templates (2026-08-11)

`tools/makepot.php` regenerates both `.pot` files. There is no wp-cli on this machine and no
gettext toolchain either, so it is the project's own extractor — run it after any change that
adds, removes or edits a user-facing string:

```
php tools/makepot.php simple-bangla     simple-bangla     simple-bangla/languages/simple-bangla.pot         "Simple Bangla"     1.3.0
php tools/makepot.php simple-bangla-cms simple-bangla-cms simple-bangla-cms/languages/simple-bangla-cms.pot "Simple Bangla CMS" 1.5.0
```

- **It tokenises with `token_get_all()`, never a regular expression.** Half this codebase's
  strings contain a comma, a parenthesis or an apostrophe — `__( '%1$s of %2$s' )`,
  `Delivery &amp; Return` — and a regex would truncate those or skip them. A `.pot` that is
  quietly short is worse than one that is obviously stale, because nothing reports it.
- **A `translators:` comment is claimed only when it ends on the call's own line or the line
  above**, which is makepot's rule. The first attempt used a whitelist of token types allowed
  between the comment and the call; it missed `'label' => sprintf( __( … ) )` and
  `'plural' => _n_noop( … )`, and widening the whitelist far enough to catch those would have
  started attaching comments belonging to the *previous* statement.
- **It reports what it could not extract** — a text argument that is a variable, a call carrying
  another text domain — rather than dropping it silently.
- **It lives at the repository root, outside both packages.** The zips must contain nothing a
  store does not run.
- **The plugin now ships a `.pot` too.** It declared `Domain Path: /languages` and called
  `load_plugin_textdomain()` against a directory that did not exist, so its 131 strings had never
  been extractable. The CMS interface is English by decision, but that is a choice about the
  default, not a reason to make translating it impossible.

The theme's template had gone stale enough to be misleading: it still carried the three step-bar
strings from `template-parts/checkout/steps.php`, deleted some rounds earlier, along with the
assurances row and the dropped third footer column — and it was missing the order stages, the
social show/hide switches, the block-list refusal and the cart quantity messages.

**Verified by translating, not by counting.** Both files parse with WordPress's own `PO` reader
inside Playground (275 and 131 entries, 295 and 142 references, every entry carrying a source
reference). Then three theme strings and four plugin strings were compiled into a `bn_BD.mo`
straight from the `.pot` and read back off real pages: the 404 view rendered the Bangla and none
of the English, and so did `/manage`. That is the check that matters, because it is the one an
extractor can fail silently — a msgid that does not match what the code passes to gettext looks
perfect in the file and translates nothing.

**Forcing a locale in Playground takes the `locale` filter, not the `WPLANG` option.** Playground
sets the `$locale` global during boot, so `get_locale()` returns early and never reads the option;
the first run of this check reported a working `.pot` as broken, with the page still in English and
`<html lang="en-US">`. The filter is applied on that early return too.

## Packaging for handover (2026-08-10)

**Never build the zips with PowerShell's `Compress-Archive` on this machine.** Windows PowerShell
5.1 writes Windows path separators *inside* the archive — `simple-bangla\style.css` — and the ZIP
specification requires `/`. WordPress's `unzip_file()` then extracts one file whose entire name is
`simple-bangla\style.css`, at the archive root, with no directory at all, and the installer answers:

> The package could not be installed. The theme is missing the style.css stylesheet.

Which is a true statement about a package that in fact contains everything. .NET Framework's
`ZipFile::CreateFromDirectory` has the same fault, so the packaging script builds the archive entry
by entry with `CreateEntryFromFile` and an explicit `.Replace( '\', '/' )`. Any other tool that
writes proper separators is fine — `tar -a -c -f out.zip simple-bangla`, PowerShell 7's
`Compress-Archive`, or 7-Zip.

Two things the packages must contain that are easy to lose:

- **`simple-bangla-cms/assets/vendor/preact-htm.module.js`.** `.gitignore` had a blanket `vendor/`
  rule, so it was untracked — a fresh clone produced a plugin whose interface never booted. There
  is an explicit exception for it now, and the package check asserts it.
- **One top-level folder per archive, named exactly as the theme/plugin slug.** WordPress installs
  to the folder name inside the zip; get it wrong and a "replace" silently becomes a second copy.

**The packaging scripts live in `tools/` now** (2026-08-11), not in a session scratchpad — they
had already been lost once and rewritten from the description in this file. `tools/package.ps1`
builds both zips, naming each from the version in its own header so a package can never be named
for a version it does not carry; `tools/check-zip.php` verifies them; `tools/install-test.php`
plus `tools/install-blueprint.json` install them for real. None of the three is inside either
package.

`tools/check-zip.php` reads both packages with PHP's own `ZipArchive` and asserts what
WordPress's installers assert — no backslash in any entry name, one top-level folder, the
`Theme Name:` and `Plugin Name:` headers, every file `functions.php` and the plugin bootstrap
`require` present, and **every relative ES-module import resolving inside the package**, since one
missing module is a blank `/manage` rather than an error. Then `install-test.php` runs the real
thing in a clean Playground with no mounts: `Theme_Upgrader::install()` and
`Plugin_Upgrader::install()` twice each — once fresh, once with `overwrite_package` set, which is
what wp-admin's "Replace current with uploaded" does — activating both and checking a file left
behind by the older copy is actually gone.

## The delivery record is the courier's, not the shop's (2026-08-12, plugin 1.6.0)

The owner opened the courier report on an order and found it led with a count of *this shop's* own
orders from that number. That is not the question the card exists for, so it is gone.

- **`simple_bangla_cms_local_history()` is deleted, and `local` is off the `/orders/{id}/record`
  response.** It was ~90 lines of raw SQL against `wc_order_addresses` / `_billing_phone` with an
  HPOS branch, and every line of it answered something the Orders screen already answers by
  searching the phone number. Worse than redundant: printed first, in a card headed "fraud", four
  completed local orders read as evidence about how the customer behaves *everywhere* — which is
  the only thing this card can usefully say and the one thing local history cannot know.
- **Steadfast is the report; Pathao and RedX are corroboration** (owner's decision — asked, and
  they chose to keep the other two rather than strip them). `SIMPLE_BANGLA_CMS_RECORD_PRIMARY`
  names the distinction and `simple_bangla_cms_courier_records()` acts on it in one place: the
  primary courier is rendered full size and, **when it is not configured, returns an entry saying
  so** instead of being skipped. A secondary courier nobody set up is simply absent. Before this,
  everything unconfigured was skipped silently — which was survivable while the local figures
  filled the card and would now be an empty box with nothing to explain it.
- **The API key cannot read this figure and the field description now says so.** Steadfast
  publishes no fraud endpoint on `portal.packzy.com`; the number every Bangladeshi fraud-check
  site shows comes from a merchant-portal session on `steadfast.com.bd` calling
  `/user/frauds/check/{phone}`. That was already what the plugin did, but the owner reasonably
  expected "add the Steadfast key" to be enough, so **Portal email / Portal password** is now
  described as the thing the record depends on rather than as an extra.
- **Reports are parsed defensively across nine plausible keys**, not one. There is no published
  schema for the notes other merchants file against a number — `total_delivered` and
  `total_cancelled` are the only two fields the community packages agree on — so
  `simple_bangla_cms_record_reports()` sweeps `reports` / `fraud_reports` / `frauds` / `fraud` /
  `notes` / `remarks` / `comments` / `feedbacks` / `customer_reports`, at the top level and under
  `data` / `result` / `customer`, accepts a bare string or a record, folds a single record that
  arrived unwrapped into a list of one, dedupes across keys, caps at 20 and truncates at 500
  characters. Betting on one key would have cost a silent "no reports" over a customer with
  several; guessing wrong across all nine costs an empty list, which is the honest answer when
  nothing recognisable arrived.
- **`reports_read` is the flag that keeps that honest.** An empty list means "asked and found
  none" only for Steadfast; for Pathao and RedX it means the question was never asked. Same trap
  the `rate === null` line beside it was already written to avoid, and the two readings are
  opposite recommendations.
- **The phone is normalised once, in `simple_bangla_cms_courier_records()`**, to the local
  11-digit form — the block list's rule, applied to a third caller. It was previously passed to
  each courier exactly as typed, so `+8801712345678` and `01712345678` were two cache entries for
  one customer and RedX's own `88`-prefixing was the only normalisation happening anywhere. The
  transient key is versioned to `sb_cms_record2_` because the cached shape gained `reports`, and a
  six-hour-old entry from before this would have rendered as a customer with no reports rather
  than as one never asked about.

**Not verified against a live Steadfast account** — same limitation as when the couriers were
built, since nobody here has portal credentials. What *is* verified is everything that does not
need one: 35 assertions in `php -l`-clean isolation over phone normalisation (five spellings of
one mobile, plus the two that are too short to ask about), the six payload shapes the reports
could arrive in, the six that look like reports and are not (`fraud` as a bool, as a count, a
pagination block), dedupe, the cap, truncation, and the `reports_read` / `rate === null`
distinctions. Send the first real check with the Steadfast portal open beside the screen, and if
the reports list stays empty on a number the portal shows reports for, the fix is one more key in
the list in `simple_bangla_cms_record_reports()`.

## The courier stage is a fact about an order, not a place it waits (2026-08-12, plugin 1.7.0)

The owner asked for the **Courier-এ আছে tab removed**, and for the work it stood for to happen on the
New Orders row itself: press *Send courier* in the Courier column, the consignment number appears
where the button was, and three buttons — **Completed · Canceled · Returned** — appear where the
status badge was. Pressing one moves the order to its section and out of New.

**This reverses the stage the order statuses were rebuilt around on 2026-08-09.** That round was
right that `processing` cannot mean "with the courier" — WooCommerce's COD gateway sets it as the
order is placed — but it answered with a status, and a status is a place an order waits. Handing a
parcel over is not that. It is something that happened *to* an order that is still the shop's
outstanding work, which is why the owner had to open two tabs every morning to see one morning's
orders. The fix is that a dispatch no longer changes the status at all.

- **The dispatch is recorded where it already was.** `simple_bangla_cms_courier_send()` used to write
  `_sb_courier` meta, an order note, *and* a status transition. Only the transition is gone; the meta
  is what the list reads for the consignment number and what refuses a second booking, and the note
  is the trail a human reads. The status was the third telling of one fact, and the only one that
  moved the order somewhere.
- **`isDispatched()` and `offersOutcomes()` in `order-utils.js` are the whole of the new rule.** The
  three buttons appear when an order has a shipment *and* is still in an open status — so a delivered
  parcel does not offer them again, where one stray tap would turn a completed sale into a return.
  The list, the phone cards, the order card and the full order page all ask those two functions
  rather than testing a status themselves.
- **`ORDER_OUTCOMES` replaces `COURIER_OUTCOMES`, and gained Canceled.** Returned stays separate from
  cancelled because a parcel that came back cost a courier fee and a cancelled order did not — the
  two tabs are how that gets counted — and because `sb-returned` restocks while `cancelled`'s restock
  is WooCommerce's own. Where each lands is the owner's mapping, not an obvious one: Completed →
  Completed Orders, Canceled → **Cancel Orders**, Returned → **Failed / Cancelled**.
- **Four tabs plus All**, so the rule that every status appears in exactly one view still holds:
  New = `pending` + `processing` + `on-hold` + `sb-courier`, Completed = `completed`, Cancel Orders =
  `cancelled`, Failed / Cancelled = `sb-returned` + `refunded` + `failed`.
- **`sb-courier` stays registered in the theme and nothing sets it.** The orders dispatched under the
  previous system are in it, so it is folded into New Orders — with its consignment number and its
  three buttons, exactly like a new one — rather than being reachable only from All. Deleting the
  status would have made those orders vanish from every list including the customer's own. It also
  stays on `woocommerce_order_is_paid_statuses` and in `simple_bangla_cms_reported_statuses()` for
  the same reason: a status dropped from the dashboard's list is orders silently missing from every
  figure while the Orders screen goes on listing them.
- **It is out of the bulk-change dropdown**, though — offering it would let the owner park orders in
  a stage nothing else now sets.
- **`statusOptions()` takes the current value and appends it when the list does not contain it.** A
  `<select>` whose value matches no option displays the *first* one, so a legacy `sb-courier` order
  on the full order page would have read as "Pending payment" — and saving a form the owner never
  touched would have moved a real order. This is the general fix, so a status registered by anything
  else behaves too.
- **Each of the last two cells says what happened *or* offers what happens next, never both.** That
  is what keeps a table row from growing a second line of controls: the consignment number replaces
  the Send button, the three buttons replace the badge, and a finished order is back to a badge.

Verified with 32 browser assertions against real WordPress + WooCommerce: the tab strip reading
All · New Orders · Completed Orders · Cancel Orders · Failed / Cancelled with no Courier tab; an
un-dispatched row offering Send courier and still showing its badge; a dispatched row showing
`#<consignment>` with the courier under it, no Send button, and exactly the three outcome buttons; a
legacy `sb-courier` order listed under New with the same three; each button removing the row from New
and — asked of the server, past the interface — writing `completed`, `cancelled` and `sb-returned`;
each order then found on its own tab and absent from the others; a finished order back to a badge;
the order card offering the same three plus a re-send rather than a first send; the phone cards
carrying them too; no sideways pan at 1440/1024/390; and no console errors.

**A dispatch itself is still unverified against a live courier account** — nobody here has portal
credentials, so the shipment record the three buttons key off is written as meta, which is exactly
what a successful dispatch leaves behind. Send the first real parcel with the courier's own panel
open beside the screen.

## The dashboard's month list starts when the shop did (2026-08-12, plugin 1.7.1)

The owner opened the period dropdown and found it offering months back to September 2024 — a shop
that has existed for days, asked to scroll past twenty-three months of guaranteed zeroes to reach
the only two that mean anything.

- **`FIRST_MONTH` in `screens/overview.js` is a floor, not a length.** The list used to be "twenty-four
  months back from today", which is a window that drags its own past along with it. It is now built
  from *today down to* `2026-08`, so it holds exactly the months the store has been open and gains
  next month by itself — nobody edits the file again as the shop ages.
- **It is a constant in the interface, not a query for the first order.** Deriving the floor from the
  earliest order would be self-maintaining, but it costs a request on every dashboard visit to answer
  a question whose answer is one line, and it would give a brand-new store with no orders at all an
  empty dropdown. The month the shop opened is a fact somebody knows.
- **Months are counted as `year * 12 + month` for the loop**, so stepping across a December needs no
  special case, and `Math.max( today, floor )` means a device whose clock is set before the shop
  opened still gets one month rather than a dropdown with no months in it.
- **The rolling spans and All time are untouched.** `Last 90 days` and `Last 12 months` reach back
  before the shop opened, but they are rolling — they will be the right question every month from
  now on, and today they simply read the same as All time.
- **The server is unchanged.** `simple_bangla_cms_resolve_period()` still accepts any `YYYY-MM` from
  2000 to 2100; this is which months are *offered*, and a floor enforced there would only turn a
  hand-typed URL into a silent fallback to `30d`.

Verified by running the generator over four clocks — August 2026 (one month), January 2027 (six,
newest first, ending at August 2026), a clock set to July 2026 (still one), and March 2028 (twenty,
March 2028 down to August 2026). `php -l` clean on the version bump. The plugin version moves to
1.7.1 because the import map versions every module by it — without the bump a browser keeps serving
the cached dashboard.

## Two launch blockers (2026-08-12, theme 1.6.0 / plugin 1.8.0)

A launch-readiness review turned up eleven findings. These are the two that had to be fixed before
the shop could take a real order; the other nine are queued behind them.

### The courier was told to collect ৳0 on every parcel

`simple_bangla_cms_courier_parcel()` derived the amount from `$order->is_paid()`. That is a
**status** test, and WooCommerce's Cash on Delivery gateway moves an order to `processing` the
moment it is placed — so `is_paid()` answered true for every order this shop has ever taken, and
every consignment went to Steadfast, Pathao or RedX with `cod_amount: 0`. Nothing failed, nothing
was logged, and the loss would have surfaced as a month that did not reconcile.

- **`simple_bangla_cms_amount_to_collect()` asks the payment method instead.** Cash on delivery is
  the only gateway where the courier collects; every other one this shop could add — bKash, Nagad,
  SSLCommerz — takes the money before the order exists. `simple_bangla_cms_cod_methods()` is the
  list, filterable, defaulting to `cod` alone.
- **An order with no recorded gateway is treated as cash**, deliberately. The two mistakes are not
  equally expensive: collecting from someone who already paid is a refund and an apology, while
  collecting nothing is money that never arrives.
- **`date_paid` is not the fix either, and this is the part worth remembering.** The review claimed
  the CMS order card was already right because it tests `date_paid` rather than `is_paid()`. It was
  not. `WC_Order::save()` calls `maybe_set_date_paid()`, which sets the date as soon as the order
  reaches the status WooCommerce considers payment-complete — `processing` for a physical product.
  **Measured against WooCommerce 11: a COD order that has paid nothing carries a `date_paid`.** So
  the card and the full order page were both telling the owner a parcel was already paid for on the
  morning they were about to collect the cash for it. Both now ask `collectsOnDelivery()` in
  `order-utils.js`, which mirrors the server's list, and they print the amount rather than a date.

### The custom checkout was inert on a stock install

The block list hooked `woocommerce_after_checkout_validation`, which only the classic checkout
shortcode fires — and **WooCommerce 11 creates the Cart and Checkout pages holding its block
versions.** The decision that those pages are the classic shortcodes (2026-08-06) was enforced only
in the demo importer, which swaps the page content and which nobody has to run. On any store set up
by hand, the checkout rendered as WooCommerce's default block: English, WooCommerce's layout, no
delivery-charge radios, no order summary, and the block list never consulted. Everything worked.
Nothing warned.

- **`simple-bangla/inc/classic-checkout.php` makes the theme answer for its own decision.** A
  `pre_render_block` filter returns `[woocommerce_checkout]` / `[woocommerce_cart]` in place of the
  two blocks. `pre_render_block` rather than `render_block`, because returning a string there
  short-circuits the block entirely — with `render_block` the checkout block's thirty-odd inner
  blocks would each be rendered in full and then discarded.
- **Rendered, not rewritten.** The pages keep whatever markup they hold; only what a visitor is
  shown changes. Switch the theme away and the block checkout returns by itself, which is the same
  reversibility rule the rest of the theme is built on. It also means this file and the importer's
  swap cannot disagree — the swap is now a tidier version of what would happen anyway.
- **Front end only.** In wp-admin the owner sees the block they actually have; an editor showing the
  shortcode's output would be describing a page that does not exist.
- **The block's frontend script and style are dequeued** on the two pages when the block was in fact
  replaced, since they are weight for markup that is not on the page.
- **The block list gained a second hook, and this one was a real hole rather than a consequence of
  the first.** `/wp-json/wc/store/v1/checkout` is registered whatever the checkout page contains, so
  a blocked customer never needed to find the block checkout — the route was there to be posted to.
  `woocommerce_store_api_checkout_update_order_from_request` is the counterpart moment (draft filled
  in, payment not taken), and it throws a `RouteException` because the Store API turns any other
  exception into an unhelpful 500. The draft order exists by then; that is WooCommerce's design, not
  a choice, and a `checkout-draft` is counted nowhere.
- **`simple_bangla_block_message()` is one function because the refusal now has two callers.** A
  shop that said two different things depending on which checkout a customer reached would be the
  detail nobody thought to keep in step.

Verified with **17 assertions** against real WordPress 7.0.3 + WooCommerce 11.0.1 in Playground, on
a site where the demo importer had deliberately *not* been run: a COD order confirmed to report
`is_paid() === true` and to carry a `date_paid` while having paid nothing, its parcel carrying the
full ৳1,500, a `payment_complete()` order collecting 0, an order with no gateway collecting the
total; the checkout page confirmed to still hold `wp:woocommerce/checkout` while rendering 21 KB of
the *theme's* classic form — Bangla copy present, delivery-charge radios present, no block wrapper —
and the cart page likewise; then the block list refusing `+8801712345678` against a stored
`01712345678` on the classic path, letting an unrelated number through, and throwing a
`RouteException` with the same Bangla message on the Store API path.

**Note for future runs:** the checkout shortcode prints a notice rather than a form when the cart is
empty, and renders no delivery-charge list when the store has no shipping rates at all. An early
version of this suite reported both as failures of the fix; they were failures of the fixture. Load
a cart with `wc_load_cart()` and add the two flat rates before rendering.

## The other nine findings (2026-08-12, theme 1.7.0 / plugin 1.9.0)

The rest of the launch review, in the order it was fixed. Two of them turned out to be less serious
than reported and one turned out to be differently serious; all three are written up as found rather
than as claimed.

### Staff permissions ask about the person, not about the verb

Both staff guards used blanket capabilities. Changing a role checked nothing at all beyond "is this
the last administrator", and deleting checked `delete_users` — the plural, which asks "may this
account delete users in general" and never mentions the account being deleted.

- **`promote_user` and `delete_user`, with the target id.** They are *meta* capabilities: WordPress
  resolves them per target, and WooCommerce's `map_meta_cap` filter answers `do_not_allow` when a
  non-administrator aims at an administrator. One argument is the whole fix.
- **`get_editable_roles()` decides which roles may be handed out**, through
  `simple_bangla_cms_assignable_roles()`, and the staff list now reports that instead of the full
  set. It is the same list wp-admin's user screens are built from, and WooCommerce filters it so a
  shop manager is offered `customer` and nothing else. The screen offering a role the save would
  refuse was the previous behaviour.
- **`can_edit` / `can_delete` are on the payload** so the screen stops offering controls that are
  going to 403, with a line saying why. The endpoint is still the control.

**The review's framing was wrong, and the measurement is worth keeping.** It said a shop manager
could demote or delete an administrator. On stock WooCommerce 11 a shop manager holds neither
`promote_users` nor `delete_users` — verified — so `staff.manage` never let one near the route. The
real exposure is any role that *has* been granted those, which is two ticks in any WooCommerce role
editor and exactly what a shop does when it wants an office account that can add staff. The first
version of the suite missed this entirely: with one administrator on the site, every refusal came
back as `sb_cms_last_admin` and the new guards were never reached, so a passing run proved nothing.
**A permission test needs a second administrator and an actor who genuinely holds the capability**,
or the guard under test is masked by the one in front of it.

### The dashboard ability is derived, not mapped

`dashboard.view` mapped to `read`, which every registered customer on a WooCommerce store holds —
and it gates `/manage` itself, so anyone who had ever placed an order with an account could open the
CMS and read order counts, catalogue size, stock figures and version information.

Mapping it to a real management capability would have picked a side: `manage_woocommerce` shuts out
whoever writes the pages, `edit_pages` shuts out the order desk. So it is the one ability that is
not a capability lookup — holding it means holding at least one *other* ability.
`SIMPLE_BANGLA_CMS_SHELL_ABILITY` names it and `simple_bangla_cms_can()` is where the rule lives;
`simple_bangla_cms_user_abilities()` now routes through the same function rather than running its
own capability loop, or the sidebar would have shown a Dashboard item the route then refused.

### The demo importer no longer deletes shipping zones

It looped every zone calling `delete()` before adding its two flat rates, so that they would be the
only rates offered. On a fresh install that is a no-op; on a store that had been set up it destroyed
the entire shipping configuration, with no warning either side of the click and nothing in
`simple_bangla_demo_reset()` able to put it back. Shipping zones are not demo content.

Now: a store with any zone of its own, or any rate on the catch-all zone, is left exactly as it is
and the one-shot flag is set — the owner answered the question by having configured it. A store with
no shipping at all still gets ঢাকার ভেতরে / ঢাকার বাইরে. Same rule as the rest of the importer:
never touch what is already there.

### WooCommerce notices had nowhere to print

Neither `woocommerce_before_single_product` nor `woocommerce_before_shop_loop` was fired, and those
two hooks are where core hangs its notice output. With AJAX add-to-cart off on product pages, adding
something to the cart reloaded the page and said nothing at all; a stock error or a variation that
had not been chosen was equally invisible.

Both templates fire their hook now. On the archive, core's `woocommerce_result_count` and
`woocommerce_catalog_ordering` are unhooked in `inc/woocommerce.php` because the toolbar calls both
directly and would otherwise print each twice — the same fire-the-hook-unhook-the-callback pattern
the product card already uses for `woocommerce_before_shop_loop_item_title`.

### The checkout override kept the part it does not use

`form-billing.php` dropped WooCommerce's account block. This store does not offer registration at
the checkout, which is why nobody noticed and why it had to come back: the moment anyone ticks
"Allow customers to create an account during checkout", WooCommerce starts validating and saving
`account_password` while no field for it exists on the page, and the checkout fails with an error
the customer cannot act on. A template override has to carry the parts it does not use.

### Two smaller ones

- **The recently-viewed cookie.** Writing it on the product template means every product page
  answers with `Set-Cookie`, and most page caches refuse to store such a response — on the shop's
  most-visited template. The write is now skipped when the list has not changed (a reload, a back
  button, a variation), and `simple_bangla_track_recently_viewed` switches the whole thing off for a
  shop that puts a cache in front. That reduces the interaction rather than removing it: a shopper
  moving between products still writes on each one, which is inherent to a server-rendered strip.
  Removing it entirely means rendering the strip client-side, which is a different feature.
  `simple_bangla_viewed_after()` was split out so the "has anything changed?" question can be tested
  without a request, a query and a cookie header in the way.
- **Two CMS labels.** `STATUS_BN` had no entry for `sb-courier` or `sb-returned`, so the one
  deliberately Bangla document in the CMS printed English on exactly the orders most likely to be
  handed to a customer. And the fourth order tab was called "Failed / Cancelled" while sitting beside
  a "Cancel Orders" tab and containing no cancelled orders at all — it is "Returned / Failed" now.
  That is an owner-visible label the owner chose, so it is the one change here worth their veto.

Verified with **40 assertions** against real WordPress + WooCommerce 11.0.1, plus the step-1 suite
re-run as a regression in the same site: 57/57. The staff half covers a role holding `promote_users`
being refused per target while the blanket capability it used to be asked about still passes, both
refusals naming the new guard rather than the last-admin one, WooCommerce's own shop manager not
reaching the route at all, and an administrator still able to do everything. Then the importer
leaving a named zone alone and still configuring a bare store, both notice hooks printing a seeded
notice with no result count leaking, the account password field appearing with registration on and
nothing appearing with it off, and the cookie's three list cases.

**Fixture order matters when two suites share a site.** Run second, the step-1 suite failed four
assertions: the steps-2-4 suite calls the importer, the importer swaps the checkout page to the
shortcode, and step 1's premise is that the page still holds the block. Nothing was broken. Run the
suite that asserts about a pristine install first, or give it its own site.

## The pre-launch audit (2026-08-12, theme 1.8.0 / plugin 1.9.1)

The owner asked whether the site could go live. A full read of both packages plus a live run against
real WordPress 7.0.3 + WooCommerce 11.0.1 said yes — **no HIGH or MEDIUM security finding, no fatal,
no PHP notice on any page, and a real cash-on-delivery order placed end to end** — with five things
worth fixing first. Four were repairs; the fifth was that the packages in `dist/` were two releases
stale, which is the one that would have shipped a site missing the Store API block-list fix.

- **Every store-detail field now ships empty, and the example lives in its description.** This is the
  one that mattered. The defaults were `+880 1XXX-XXXXXX`, `hello@simplebangla.com` and three
  `.../simplebangla` social URLs, on the reasoning that an obvious placeholder invites replacement.
  On a shop that went live before anyone opened the Customizer it did the opposite:
  `simple_bangla_tel_href()` strips the X's, so the homepage rendered `tel:+8801` and `wa.me/8801` —
  **verified on a running install, not reasoned about** — and those two are the most-tapped controls
  on a phone. Nothing looked broken until a customer tapped one. The social defaults were worse than
  dead: they pointed at handles this shop may not own, so the footer sent customers to a stranger.
  Empty is the honest default because every consumer already skips a blank field, so an unconfigured
  shop shows no Call button rather than a dead one. Verified both directions: with the fields empty
  the bottom bar is Shop · Home · Cart and not one `tel:`/`wa.me`/`m.me`/social link is in the
  markup; saving a real number through the CMS brings back `tel:+8801712345678`,
  `wa.me/8801712345678` and the four-item bar.
- **The three `tel:` builders guard on the href, not on the display string.** `drawer.php`,
  `footer/brand.php` and `footer/mobile-bar.php` each tested the raw field and then ran it through
  `simple_bangla_tel_href()` inside the link, so a value with no digits in it — a note left in the
  box, a half-typed entry — produced `href="tel:"`: a Call button identical to every other one that
  does nothing. `woocommerce.php`'s call button and `simple_bangla_social_links()` already had this
  right; now all five agree.
- **All nine WooCommerce overrides carry an `@version`.** WooCommerce → Status reported "Version
  header is missing" for every one of them. The cosmetic half is a red warning on the page a support
  person looks at first; the real half is that **nothing would ever tell the shop a WooCommerce
  update had changed one of those templates**. Each version is the core version of the file it
  overrides, read from WooCommerce 11.0.1 and cross-checked against what the running install
  reported. The version must be **bare** on its line: `get_file_version()` matches
  `@version[\s]*(.*)$` and only strips a trailing `*/`, so an explanatory clause after the number
  becomes part of the version string and `version_compare()` then reads nonsense. Status now reports
  zero warnings. Bump these after reconciling with a WooCommerce update.
- **`simple_bangla_home_hotdeals_heading` is removed rather than wired up.** It was left behind on
  2026-08-06 when the Hot Deals shelf became the hero's left column, and had been dead in the
  Customizer ever since. Wiring it in would have put a heading over one half of a two-column hero the
  owner has approved; removing it costs nothing, because the CMS already declined to render a control
  for it. Gone from `customizer-home.php` and from the plugin's schema, so `?group=hotdeals` now
  returns the count alone. A leftover `theme_mod` on an existing install simply stops being read.
- **`contact-us` and `terms-and-condition` are in the footer.** Both are created by
  `simple_bangla_default_pages()` and neither was linked from anywhere — Contact Us, on a shop,
  reachable only by typing its address. The fallback lists in `simple_bangla_footer_columns()` should
  name every page the theme creates, and that is now written down beside them. The demo importer
  keeps a second copy of the same two lists and had already drifted (it had Terms, the theme did
  not); both were corrected together and each now points at the other.

Verified on a live install after the change: the nine overrides clean on WooCommerce → Status, both
new links rendering in the footer, the hero's Hot Deals column and banner carousel both still there,
a second real order placed and numbered `#GXH227J`, every page free of notices, and the six CMS REST
routes still answering 401 anonymously. `php -l` clean across all 26 PHP files; both `.pot` files
regenerated (280 theme strings, 130 plugin); `dist/` rebuilt with `tools/package.ps1` and
`tools/check-zip.php` passing 23/23.

**Left for the owner, not fixable in code** — the real phone, WhatsApp, Messenger, email and social
addresses; permalinks set to Post name or `/manage` 404s; WooCommerce's "Coming soon" switched off;
HPOS turned on *before* orders accumulate; an SMTP plugin, because `wp_mail()` fails on most shared
Bangladeshi hosts and the confirmation email is the only thing the customer gets; the demo catalogue
deleted; and the first real parcel sent with the courier's own panel open, since **no courier
dispatch has ever run against a live account.**

**One configuration trap worth knowing:** the sign-in throttle keys on `REMOTE_ADDR` alone, which is
right (a forwarded-for header is spoofable, and trusting one makes the control decoration). Behind
Cloudflare or any reverse proxy every visitor shares one bucket, so five failures by anyone locks the
owner out for fifteen minutes. If the shop goes behind a proxy, the host must restore the real client
address before PHP sees it.

## Stored links follow the site when it moves domain (2026-08-12, theme 1.9.0)

The owner moved the shop from its host's temporary address to `simplebangla.net` and found the
homepage banners still sending customers to the old site. WordPress rewrites nothing on a move, and
the theme stores full URLs in nine settings — five hero slide links and four banner links — so it
has to answer for them. `simple-bangla/inc/site-address.php`.

- **In the theme, not the CMS plugin.** Fifth application of the block list's rule: a banner is the
  first thing a customer taps on the homepage, and switching the management interface off must not
  start sending shoppers to a domain the shop no longer controls.
- **The whole mechanism is one remembered fact** — the address the site had last time this ran. When
  it stops matching, every stored link on the old address is moved to the new one, once, in place.
  Nothing is resolved at render time, so the homepage costs exactly what it did before. The
  alternative, rewriting on the way out of `get_theme_mod()`, would have put a URL parse on every
  banner on every page view to answer a question that changes once a year.
- **Compared on host plus path, never on the whole string.** `home_url()` runs its result through
  `set_url_scheme()`, so the same site answers `http://` on one request and `https://` on the next;
  a string comparison would read that as a move and rewrite the links back and forth on every other
  page load.
- **Only the nine link settings and custom menu items are touched.** Not "every setting that looks
  like a URL" — the social, WhatsApp and Messenger fields are addresses on other people's sites. And
  only *custom* menu items: page and category items store an object id and their link is rebuilt on
  every render, so a move never reaches them.
- **The first run is a different problem from every later one, and is solved differently.** Later
  moves know the address being left, so only that address is touched and the rule is exact. The
  first run is healing a move nobody recorded, where a link on a foreign host is genuinely
  ambiguous: stale, or a deliberate link to somebody else's site. The question that separates them
  is whether *this* site can answer the path. `/shop/` and `/product-category/microphone/` can;
  `/simplebangla` on Facebook cannot.
- **That resolver does not use `url_to_postid()`, which is the obvious tool and does not work.**
  Measured against WooCommerce 11 with pretty permalinks and 139 rewrite rules registered, it
  answers **0 for the shop page** — the single most likely destination of a banner on this site —
  while `get_page_by_path()` and a query by `name` both find it immediately. This mattered more than
  a normal wrong answer: a resolver that says no leaves the stale link in place, which is the bug
  the file exists to fix, so its failure mode is silence.
- **On `wp_loaded`, not `init`.** The resolver asks `taxonomy_exists( 'product_cat' )`, and
  WooCommerce registers its taxonomies on `init` at priority 5. It would happen to run first today,
  because plugins add their hooks before a theme does, but a repair whose correctness rests on hook
  registration order is one plugin away from silently deciding the shop has no categories.
- **The prefix test appends a slash to both sides.** Without it the address `old.example` also
  claims `old.example.attacker.test`, and a move would rewrite a link to a site the owner did not
  choose.
- **A directory-style link keeps its trailing slash and a slashless one does not gain one.**
  WordPress redirects between the two spellings, and a banner that costs every shopper a redirect is
  a slower banner for nothing.
- **`add_option()` is the concurrency claim**, because it fails when the row exists and that failure
  is atomic in a way "look, find nothing, write" is not. A move on a live shop is several requests
  arriving together, each seeing the same stale address; the work is idempotent, but the first-run
  repair costs real queries. A lock older than five minutes is broken, so a request that died
  mid-move cannot freeze the mechanism forever.
- **Every change is recorded** in `simple_bangla_address_log` — what moved, from where, to where,
  and when. Code that rewrites the owner's data silently is code nobody can audit after a surprise.
- **Not covered, and deliberately** (the owner chose this scope when asked): URLs written inside
  product descriptions, page bodies and custom fields. Those are edited prose rather than settings,
  and a mechanism that rewrote a shop's written content on every domain change is a different and
  much riskier feature.

Verified twice over. **26 assertions** on the pure URL arithmetic in isolation — trailing slashes,
queries, fragments, `http` on the old address, the look-alike host, external links left exactly
alone, idempotence, and a second move onto a third domain. Then **37 assertions inside Playground
against real WordPress + WooCommerce 11** with the demo catalogue imported, which is the half that
needed a database: theme mods actually written and read back, a real custom menu item rewritten
while a Facebook one beside it is untouched, the address remembered, the log counting exactly the
six seeded stale links, a second run changing nothing, a recorded move to a third address carried
through with its query string intact, the claim lock taken, refused while held and broken when
stale, and the watcher hooked to `wp_loaded` and not to `init`. 37/37 and 26/26.

Two notes for future Playground runs on this machine. **`--mount-dir` takes the host path and the
VFS path as two separate arguments**, and the host path here contains spaces — the repository lives
under `Dev File\simplebangla.com-from-bristy's-PC` — so the theme has to be copied somewhere
space-free before it can be mounted at all. And **PowerShell 5.1's `Out-File -Encoding utf8` writes
a BOM**, which makes a generated blueprint fail as "not a valid JSON file"; write it with a tool
that does not, or with `sed`.

## The bottom bar's icons are solid, and Chat wears Messenger's mark (2026-08-12, theme 1.8.2)

The owner put their phone beside the reference's and found the row lighter and the Chat button
wrong. Two separate things, one screenshot.

- **Chat carries Messenger's own logo now, not a speech bubble.** The button opens `m.me`, and a
  generic bubble could equally be a live-chat widget, a comment form or a support ticket — a
  customer picks a destination out of that row by its logo. This does not undo the 2026-08-11 change
  that replaced the broken ring with a bubble; that fixed a glyph that read as *reload*, and the
  bubble stays exactly where it was earned, on the thank-you page's delivery-note row.
- **A new `messenger` slug rather than editing `chat`**, for that reason: the two callers want
  different pictures, and the thank-you page must not sprout a social logo beside a delivery note.
  Scaled `0.9` and re-centred with a `transform` on the path, because the source mark fills the full
  24 box while every other glyph in the set sits inside roughly 3–21 — at native size it out-weighed
  the four icons beside it.
- **Shop, Call and Cart are solid too** (owner's choice when asked — the alternative on the table was
  changing the Chat icon alone). At the very bottom of a phone screen a filled silhouette is read as
  a shape and a hairline outline as a smudge.
- **Three new slugs — `shop-solid`, `phone-solid`, `cart-solid` — not a CSS `fill` on the originals.**
  The same three glyphs sit in the header, the drawer and the checkout at smaller sizes, where the
  outline is right; and none of those paths is a closed silhouette, so filling one turns the bag's
  handle arc into a blob and the cart's open rail into a wedge. Home needed neither treatment: its
  path was already a closed house and has been filled by CSS since 1.4.0.
- The bag's handle is the **last stroked thing on the bar**, kept at `stroke-width: 2` so it does not
  read as a hairline hanging off a filled body. The CSS comment that described the row as "the
  theme's usual outlines" was corrected rather than left to mislead.

No new translatable string — the five labels are unchanged — so neither `.pot` moved.

**Verified by drawing them, not by reasoning about them.** `php -l` clean, then the real
`simple_bangla_icon()` was rendered into a standalone page from a script stubbing only `esc_attr()`
and `absint()`, and screenshotted in headless Chrome: the new row beside the outline row it replaces,
plus all four at 72px for shape. Worth repeating for any icon change — a path that is subtly wrong is
still perfectly valid markup, and nothing in `php -l` or a browser console will say so.

**Note:** headless Chrome could not write its screenshot into the scratchpad until it was given its
own `--user-data-dir`; the failure surfaces as `Failed to write file …: Access is denied`, which reads
like a permissions problem with the output path and is not one.
