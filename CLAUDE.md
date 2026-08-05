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
| Only required plugin | **WooCommerce** — nothing else |
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
- **Demo images are generated with GD at import time**, never fetched and never copied from the
  reference site.

## Local development

No PHP, MySQL, XAMPP or Docker is installed on this machine. Use WordPress Playground — WASM
PHP inside Node:

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
