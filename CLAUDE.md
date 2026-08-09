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
├── languages/simple-bangla.pot   204 strings
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
│   ├── app.php                   login page, no-access page, app shell + boot payload
│   ├── rest-session.php          /session — user, abilities, store, environment
│   ├── rest-settings.php         /settings — GET schema+values, POST batch write
│   ├── rest-dashboard.php        /dashboard — one request, every figure
│   ├── menu-icon.php             menu-item icon meta for REST + /menu-locations
│   ├── rest-blocklist.php        /blocklist — editor for the theme's list
│   └── rest-staff.php            /staff — staff accounts, with the lockout guards
└── assets/
    ├── css/cms.css               ~40 KB
    ├── js/    api · ui · nav · router · media · order-utils · settings-form · app
    │           screens/  overview · products · product-edit · categories
    │                     orders · order-detail · invoice
    │                     hero · hot-deals · circles · rows · banners
    │                     menu · footer · reviews · settings · coupons
    │                     customers · blocked · admins
    └── vendor/preact-htm.module.js   13 KB, vendored
```

The theme gained one file for the CMS's sake — and only because the CMS could not be allowed to own
it: `simple-bangla/inc/blocklist.php`, which stores the order block list and enforces it at the
checkout. See phase 7 below.

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
- **`/manage` sidebar** (final, 17 items in four groups): **Store** — Overview · Orders ·
  Products · Categories · Coupons. **Homepage** — Hero Slider · Hot Deals · Category Circles ·
  Product Rows · Banners. **Site** — Menu · Footer · Reviews · Settings. **People** — Customers ·
  Blocked List · Manage Admins. Every item is visible from day one, filtered only by ability and
  marked "soon" until its phase lands; a sidebar that grew week by week would hide what is still
  owed.

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
- **Descriptions are textareas holding raw HTML, not a rich-text editor.** A half-working editor
  that silently mangles markup is worse than a box that does exactly what it appears to.
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

- **No password is set or sent.** WordPress emails the new account a link to choose its own, which
  keeps it out of the request, out of the response, and out of whatever the owner would otherwise
  have typed it into to pass it on.
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
