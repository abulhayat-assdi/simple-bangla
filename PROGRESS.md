# Simple Bangla — Progress

Last updated: 2026-08-11

## The bottom bar the owner could not see (2026-08-11, theme 1.4.1)

The owner reported the new bar missing on a phone: visible only when zoomed far out, tiny, across
the foot of a page whose content sat in the left eighth of the screen — and, scrolled to the very
bottom, a lone "Shop" icon below the footer. One cause behind all three, and it was the horizontal
overflow written up in the previous round as found-but-not-fixed.

- ✅ **The page's own sideways scroll was making the viewport four times too wide** — 1560px on a
  390px screen. A `position: fixed` bar is sized against the viewport, so the bar really was
  1560px wide with its five items spread across it; only Shop landed on the glass.
- ✅ **Fixed with one rule**: `.sb-slider { overflow-x: clip; overflow-clip-margin: 16px }`. On the
  slider rather than the page shell, so a product page's related-products row is covered too;
  `clip` rather than `hidden`, which would have made every slider a vertical scroll container;
  and the clip margin so the desktop arrows, which overhang by 8px, are not sliced in half.
- ✅ **The carousels still scroll** — product rows, category circles and the hero track each
  driven and re-checked after the change.

**A correction worth recording.** In the previous round I measured `innerWidth` 1560 against
`clientWidth` 390 and wrote it off as an artifact of Chrome's mobile emulation. It was not an
artifact — it was this bug, reproduced exactly, sitting in the output before the owner ever saw
it on a real phone. When a measurement disagrees with the tool, the tool is not automatically
the one that is wrong.

Verified with **64 assertions** across home, shop, cart, checkout and a real product page at 360,
390, 768 and 1280 — nothing pans sideways, `innerWidth` equals the device width everywhere, and
below 768 the bar is exactly the viewport's width with all five items inside it — plus the
30-assertion bar suite re-run as a regression, and no console errors.

## Packages rebuilt for theme 1.4.0 (2026-08-11)

`dist/simple-bangla-1.4.0.zip` (70 files) and `dist/simple-bangla-cms-1.5.0.zip` (53 files — the
plugin version is unchanged, it only gained its new `.pot`). The superseded 1.3.0 zip is gone.

- ✅ **The packaging scripts are in `tools/` now**, not in a session scratchpad. They had already
  been lost once and rebuilt from the description in `CLAUDE.md`; that is not a thing to do twice.
- ✅ **23 package assertions** — no backslash in any entry name, one top-level folder named for
  the slug, the `Theme Name:` and `Plugin Name:` headers, all 16 and 13 required files present,
  all 112 relative ES-module imports resolving inside the plugin, the vendored Preact bundle
  present, no editor or VCS cruft.
- ✅ **26 install assertions** in a clean Playground with the source *not* mounted: both packages
  installed with WordPress's own `Theme_Upgrader` / `Plugin_Upgrader`, fresh and again with
  `overwrite_package` — the "Replace current with uploaded" path — with a sentinel file planted
  between the two runs and confirmed gone, so the replace is proved to remove the old copy rather
  than merge into it. Then the theme activated, the plugin activated, and the storefront served
  200 with the phone bar in its markup.

One more test bug of the usual kind: the backslash check asked `file_exists()` about
`simple-bangla\style.css`, which answered **yes** on Playground's filesystem for a file that does
not exist, and reported a correct package as broken. It reads the themes directory now and looks
at the names it actually holds — with a control assertion beside it, which is what was missing.

## White cards and the phone bottom bar (2026-08-11, theme 1.4.0)

The owner sent the reference site's bottom bar with the buttons circled, and asked for white and
for a bar like that one. Both are storefront-only; the CMS is untouched.

- ✅ **Product cards are white**, the page stays warm cream. Confirmed with the owner first — the
  literal reading would have whitened the page too, and cards have no border or shadow, so the
  page colour is the only thing that separates them from it.
- ✅ **`--sb-hover`, and it is why that was safe to do.** The live-search results and the mega
  menu's sub-links were tinting their hover with `--sb-bg-alt` on top of white panels, so white
  cards would have made both hovers invisible — silently, with nothing to fail. It is a 5% black
  tint now, which cannot disappear whatever colour sits under it.
- ✅ **A red Home circle**, `#e8262d`, as a tenth Customizer token rather than a value buried in
  `footer.css` — it is the loudest thing on the screen and so the most likely to be changed. It
  appears in CMS → Settings on its own, because that schema is generated from the theme.
- ✅ **The bar reads at arm's length**: ink instead of grey, 24px icons, 13px labels, a heavier
  stroke, and a solid white house in the circle instead of a hairline outline.
- ✅ **The Chat icon was a reload symbol.** An arc with a gap and a loose dash — passable at 22px
  grey, plainly wrong at 24px black. It is a speech bubble now.
- ✅ **The space reserved under the bar is one number instead of two copies of `68px`.**

Verified with **30 browser assertions** against real WordPress + WooCommerce with the demo
catalogue, plus a close-up screenshot of the bar checked against the owner's reference.

**Three false failures, all one mistake.** Chrome's mobile emulation reports
`getBoundingClientRect()` in a space four times the layout viewport — 1560 and 3376 for a 390×844
window — so the overflow check passed a page that scrolls sideways, failed one that does not, and
called a visible copyright line off-screen. Compare rects to rects; for overflow, ask the page to
scroll and read `scrollLeft` rather than doing arithmetic on widths.

**One real bug found here and fixed in 1.4.1** (next section): the homepage scrolled sideways.
It turned out to be why the owner could not see the bar at all.

## Translation templates regenerated (2026-08-11)

The theme's `.pot` had not been rebuilt for several rounds and had drifted far enough to mislead
anyone using it: it still listed the three checkout step-bar strings from a template deleted rounds
ago, plus the assurances row and the third footer column, and it was missing everything added
since — the two order stages, the social show/hide switches, the block-list refusal, the cart
quantity messages.

- ✅ **`tools/makepot.php`** — the project's own extractor, because there is no wp-cli and no
  gettext toolchain on this machine. It tokenises with `token_get_all()` rather than matching a
  regex, which is what lets it keep strings containing commas, parentheses and apostrophes whole.
  It reports any call it could not extract instead of dropping it.
- ✅ **Theme: 275 strings** (was 274, and wrong about which). **Plugin: 131 strings, its first
  `.pot` ever.** The plugin declared `Domain Path: /languages` and called
  `load_plugin_textdomain()` against a folder that did not exist, so none of its strings had ever
  been extractable. The interface stays English by decision — that is a choice about the default,
  not a reason to make translating it impossible.
- ✅ **Checked by translating, not by counting.** Both files parse with WordPress's own `PO`
  reader; three theme strings and four plugin strings were then compiled into a `bn_BD.mo` from
  the `.pot` itself and read back off live pages — the 404 view and `/manage` both rendered the
  Bangla with no English left. A msgid that does not match what the code passes to gettext looks
  perfect in the file and translates nothing, so nothing short of this proves it.

Two false failures before it was right, both worth remembering. **A `translators:` comment must be
claimed by a line test, not by a whitelist of tokens allowed between it and the call** — the
whitelist version missed `'label' => sprintf( __( … ) )` and `'plural' => _n_noop( … )`, and
widening it enough to reach those would have started stealing the previous statement's comment.
And **Playground sets the `$locale` global during boot**, so writing the `WPLANG` option changes
nothing and `get_locale()` never reads it; the first run reported a perfectly good `.pot` as broken
with the page still English at `<html lang="en-US">`. Use the `locale` filter.

`dist/` now lags the source by these two files. Nothing at runtime depends on them, so no version
was bumped; rebuild the zips at the next release.

## The order card, and Content Pages narrowed (2026-08-10, theme 1.3.0 / plugin 1.5.0)

Three things the owner asked for after working on the live shop.

- ✅ **Content Pages listed every page WordPress had** — fourteen rows, of which four were writing
  and the rest were Cart, Checkout, My account, Sample Page and the homepage. It now lists exactly
  the pages the footer links to, derived by the **theme** (`simple_bangla_footer_pages()`) so the
  list and the footer cannot disagree, and exposed through one thin `/footer-pages` endpoint. Add
  page, delete and the footer tick went with it: each became a trap once the list was derived — a
  new page could not appear in it, a deleted page takes a footer link with it, and unticking a page
  would remove it from the only screen that could tick it back. Which pages appear is the Menu
  screen's question now; this screen answers what they say.
- ✅ **Reviews opened on Pending and usually showed "Nothing waiting"**, which read as "this shop has
  no reviews". It opens on All; the filter is one tap away.
- ✅ **Clicking an order did nothing unless you hit the order number**, and the detail replaced the
  whole list. An order now opens as a card over the list — items, delivery details, money, and the
  one action it is waiting for, plus Edit info and a permanent delete — built to the owner's
  reference but in the shop's own black and cream. Notes, refunds and the invoice stay on the full
  page, one click away in the card's footer.
- ✅ **`order-parts.js`**: items, totals, delivery, payment and the stage buttons are now one
  component each, drawn by both the card and the full page rather than existing twice.

## The tidy-up pass (2026-08-09, plugin 1.4.0)

The owner opened the CMS and reported it as messy: alignment off, empty space around everything, the
profile chip in the top right wrong. Audited against a real install at 1440 / 1024 / 390 rather than
from the screenshot, it came down to four layout faults repeating across the screens, one class of
text bug, and one figure that was quietly wrong.

- ✅ **Revenue read ৳0 on a shop with paid orders.** It summed `wc_order_stats`, which WooCommerce
  Analytics fills from a *scheduled* action — so on any shop whose cron is late, throttled or off,
  the first number an owner looks at was zero beside an Orders screen listing real orders. It is now
  an indexed `SUM()` over the same order table the counts on that screen already come from, so the
  two can no longer disagree. HPOS reads `wc_orders.total_amount`, legacy joins `_order_total`.
- ✅ **Raw HTML entities on screen.** `Airpod&#8217;s` throughout the Menu tree,
  `Bangladeshi taka (&#2547;&nbsp;)` in the currency select. `assets/js/text.js` is now the one place
  an API's HTML becomes text, replacing three private half-copies; the menu edit dialog had been
  loading the encoded title as the value to save, which would have re-encoded it each time.
- ✅ **The profile chip only displayed.** Sign out and the wp-admin link lived in small grey type at
  the foot of the rail, the second wrapping onto two lines. Both are now in a proper profile menu
  under the avatar, with the account's email shown in full; the rail keeps "View the store" alone.
- ✅ **Row actions stacked.** `.sb-row` wrapped inside the shrink-to-fit actions cell, making every
  Categories and Content Pages row two buttons tall.
- ✅ **Category Circles was ragged.** Its picker column was as wide as whichever buttons a category
  needed — two with a picture, one without — so no two rows started their text at the same place. It
  is a three-column grid now.
- ✅ **Settings and Footer fields ran the full width of the page.** They sit two abreast at 1440px,
  and a card's explanatory sentence moved out of the body (where it read as help for the first
  field) into the card header. The three couriers fold shut, which took roughly two thirds off the
  length of the Settings screen.
- ✅ **The sidebar did not fit a laptop** and the owner's screenshot showed it cut off mid-list.
- ✅ **Hot Deals overflowed a phone by 17px.** A grid column sized `auto` is never narrower than its
  widest item's min-content, so one long product name widened the page; the row could not truncate
  until the column was capped.

Verified with 31 browser assertions against real WordPress + WooCommerce with the demo catalogue and
five seeded orders — including the revenue tile matched against a total summed independently from
`wc/v3/orders` — plus no horizontal overflow at 1440/1024/390 across all seventeen screens and zero
console errors.

## Content Pages (2026-08-09)

The owner asked where the About Us, Privacy Policy and Refund pages in the footer are edited. The
honest answer was wp-admin, which is the one answer this project exists to stop giving. **Content
Pages** is now the first item in the sidebar's Site group: pick an existing page, write it in the
rich-text editor, or add a new one with a name, an address and a tick for the footer.

- ✅ **Built on core's `wp/v2/pages`**, which has covered pages since WordPress 4.7. The plugin adds
  one file — `inc/rest-pages.php`, 62 lines — registering the theme's footer tick for REST. Same
  split as the menu-item icon.
- ✅ **The tick lives in the theme** — `simple-bangla/inc/pages.php`, the third file the theme has
  taken for the CMS's sake, after the block list and the order statuses, and for the same reason: the
  footer is the theme's, so switching the plugin off must not empty it.
- ✅ **A tick beats the menu assigned to `footer-1`.** The demo importer assigns one there, so if the
  menu won, the tick box would silently do nothing on exactly the installs that had been set up
  properly. Tick nothing and the column is unchanged; columns two and three stay menu-driven.
- ✅ **Only published pages appear**, and the tick's hint says so while the status is Draft.
- ✅ **The homepage and the Cart/Checkout/My Account pages are marked, not blocked** — the first
  because the theme builds it from the Homepage screens and nothing typed there is ever printed, the
  others because their body is one WooCommerce shortcode, so they open in the HTML view with a
  warning.
- ✅ **An empty slug is omitted, never sent blank.** On update, blank asks WordPress to regenerate,
  which silently changes the address of a page that was only being renamed.
- ✅ **Deleting trashes rather than erases.** A page is usually the only copy of writing nobody wants
  to do twice.

**The half of it that was missing.** The screen, the editor, the REST registration, the theme query,
the footer rendering, the ability and the sidebar entry were all complete — but `resolve()` in
`app.js` had no pattern for `/content/:id`, so `PageEdit` sat imported and unreachable and both "Add
page" and "Edit" landed on "Not found". Thirteen lines. Nothing warns about an imported-but-unused
component, which is why the suite now asserts the route and not only the screen.

Verified with 60 assertions in Playground against real WordPress + WooCommerce: the meta registered
with its auth callback once `rest_api_init` has fired (it is genuinely absent before that, by
design), a page created through `wp/v2/pages` with the tick echoed true *and* stored as `'1'`, the
list returning drafts with `title.raw` and `content.raw` — then the storefront, with the theme's own
footer template rendering the link and both the unticked page and the ticked draft absent from the
markup. Then a subscriber refused with 403 and the stored tick untouched, a rename leaving the slug
alone, unticking restoring the menu, a delete landing in the trash, and the route, sidebar entry and
import map all covering the two new modules. 60/60.

Two false failures in the suite before it was right, both worth remembering: **`register_post_meta`
on `rest_api_init` is absent until a REST request has been dispatched**, so checking on a plain page
load reports a working feature as broken; and **`wp_json_encode` escapes forward slashes**, so
`screens/content.js` is not a substring of the import map it is plainly in.

## Round two, corrected by a real order (2026-08-09, same day)

The owner placed a test order and it appeared under **Courier-এ আছে** instead of **New Orders**.
The mapping was wrong at the source, not in the interface: **WooCommerce's Cash on Delivery gateway
sets an order to `processing` the moment it is placed**, and `processing` was standing in for "with
the courier". Every new order therefore looked already dispatched. Nothing about the interface could
have fixed that; the stage did not exist.

- **The theme now registers `sb-courier` and `sb-returned`** — `simple-bangla/inc/order-status.php`.
  This reverses the "map onto existing statuses" decision taken a few hours earlier. There was no
  spare status to borrow: `on-hold` means "waiting for payment" everywhere else in WooCommerce and
  in its emails.
- **In the theme, not the plugin**, for the block list's reason — a status is customer-visible in
  My Account and in every WooCommerce email, so with the plugin off real orders must still have a
  name.
- New = `pending` + `processing` + `on-hold`. Courier = `sb-courier`, entered only by Send to
  Courier. Completed = `completed`. Returned = `sb-returned` + `refunded`. Failed = `failed` +
  `cancelled`.
- **At the courier stage the screen offers exactly two buttons** — *Delivered — mark completed* and
  *Returned / not received* — on the order screen and on the phone card. "Returned", not
  "Cancelled": a parcel that came back is a different number from an order killed before it shipped,
  and the returns are what the courier fees were spent on.
- **The phone card carries the next action**, matching the reference: Send to courier on a new
  order, the two outcomes on a dispatched one. Working through a morning's orders is a list of
  one-tap decisions, and making each cost an open-and-go-back is what stops a screen being used.
- `sb-courier` joins `woocommerce_order_is_paid_statuses` or stock is reduced twice; `sb-returned`
  restocks, guarded by WooCommerce's own `_order_stock_reduced` flag so toggling twice cannot
  restock twice.
- **The dashboard tiles now sum the same `ORDER_VIEWS` table**, so it can no longer say
  "Processing 1" beside an Orders screen calling the same order new.

Two smaller fixes in the same pass:

- **`/manage` with no trailing slash answered "Not found."** `SB.base` carries the slash, so the
  path anyone actually types did not start with it and fell through as a literal route matching no
  screen — a 404 at the CMS's own front door. `currentPath()` strips both spellings.
- **The Customers screen is gone** (owner's decision), and `customers.view` with it. It could only
  list registered accounts, and on a cash-on-delivery shop almost nobody registers — so it showed an
  empty table beside a sentence pointing at the Orders screen. Searching Orders by phone number is
  the answer and always was; the `?search=` seeding stays for it.

Verified with a further **20 REST assertions** — including placing an order through COD's own
`process_payment()` and asserting where it lands, which is the check that would have caught this the
first time — and **22 browser assertions** covering both `/manage` spellings, the sidebar, the three
tab queries, and both outcome buttons re-read after a reload. The two earlier suites were re-run as
regressions: 54/54 and 58/58 after updating the two assertions that encoded the old mapping.

## CMS round two — the owner's ten changes (2026-08-09)

Plugin **1.1.0**. Ten requests from one message, four of which needed a decision before anything
could be written. What was decided, and what it cost:

| Asked for | Shipped |
|---|---|
| A back button on every screen | One button in the topbar, not twenty in twenty screens |
| Show/hide switches on the three social links | A `*_show` theme mod per network, default on |
| Set a staff password when adding one | Password on the form, email as the sign-in name |
| Auto slug for a new category, and "what is Parent for?" | Slug generated, Parent kept but folded away |
| A rich-text editor for product descriptions | Own ~9 KB `editor.js`, no library, no build step |
| Five business filters on Orders, opening on New | `ORDER_VIEWS` in `order-utils.js`, tabs with counts |
| The mobile order list and detail from two screenshots | Card layout under 900px, detail rebuilt |
| Send to Courier, via API keys | `inc/courier.php` — Steadfast, Pathao, RedX |
| The global fraud report seen on other sites | Same source those sites use, with the caveat attached |
| Block by IP instead of by email | `type: ip` in the theme's block list, email dropped |

### The four decisions

- **Statuses are mapped, not invented** (owner's choice). New = `pending` + `on-hold`,
  Courier-এ আছে = `processing`, Completed = `completed`, Returned = `refunded`, Failed/Cancelled =
  `failed` + `cancelled`. Every status appears in exactly one tab, so no order can become
  unreachable. `ORDER_VIEWS` is the whole of that mapping and nothing else decides it.
- **The order screens borrow the reference's layout, not its colours.** The screenshots are a dark
  dashboard; the CMS stays black-and-cream so all twenty screens still look like one product.
- **All three couriers, chosen in Settings** rather than one hard-coded.
- **A password is set on the staff form and shown, not masked.** It is typed once in order to be
  read out to someone in the same room; masking it would only add the typo a confirm field then
  exists to catch. Sign-in already accepted an email address — core registers
  `wp_authenticate_email_password` — so that half needed no code, only honest labels.

### What the courier research actually found

The fraud figure every Bangladeshi checker site shows **is not in any courier's documented API.**
Each one signs in to the courier's own merchant portal and calls the endpoint that portal's
dashboard calls:

| | Dispatch (documented) | Delivery record (portal session) |
|---|---|---|
| Steadfast | `portal.packzy.com/api/v1/create_order`, Api-Key + Secret-Key | `steadfast.com.bd/login` → `/user/frauds/check/{phone}` |
| Pathao | `api-hermes.pathao.com/aladdin/api/v1/orders`, OAuth | `merchant.pathao.com/api/v1/login` → `/api/v1/user/success` |
| RedX | `openapi.redx.com.bd/v1.0.0-beta/parcel`, access token | `api.redx.com.bd/v4/auth/login` → `customer-success-return-rate` |

So each courier takes **two** sets of credentials and either can be filled in alone. Because the
record half is undocumented, it is treated as something that will break: cached six hours, never
in the way of a dispatch, and a failure is reported per courier as "could not be read" rather than
as a number. The local half — this shop's own orders for that number — needs nobody and cannot
break, which is why it is shown first.

Pathao and RedX both address parcels by **numeric city/zone/area ID**, not by the text a customer
types, so each carries a default ID in Settings. That is a real limitation and the field says so.

### Also worth knowing

- **`register_rest_field( 'shop_order', 'sb_courier' )`** rather than reading `_sb_courier` out of
  `meta_data`. Which meta WooCommerce exposes has changed more than once, and the order list would
  have quietly lost its courier column the next time it did.
- **A dispatch is refused a second time** unless `force` is sent — two consignments for one parcel
  is a bill the shop pays twice — so the interface turns that 409 into "send it again?" rather than
  a red toast.
- **Deleting an order erases it** rather than trashing it, because there is no trash view here to
  retrieve it from and a "deleted" order that kept appearing in search would be worse.
- **The import map is new and is a real fix, not tidying.** Versioning the entry script never
  versioned the modules it imports, so this release — which changes `ui.js`, `router.js` and
  `order-utils.js` under a changed `app.js` — would have shipped a new entry point beside cached
  copies of its own dependencies. `simple_bangla_cms_import_map()` reads the directory and rewrites
  every import to a versioned URL. No build step; the list cannot go stale.
- **Block-list entries stored as `type: email` are now ignored** and dropped by the next save.
  IP matching uses `WC_Geolocation::get_ip_address()` — deliberately *not* `REMOTE_ADDR`, unlike the
  sign-in throttle — so the address blocked is the same one WooCommerce writes on the order and the
  CMS shows, which is where the owner copies it from.

### Verified

`php -l` on every PHP file in both the theme and the plugin, every ES module parsed, and no unused
imports introduced. Runtime checks against real WordPress + WooCommerce under Playground are
recorded below.

**Not verified against a live courier account.** Nobody has real Steadfast, Pathao or RedX
credentials here, so dispatch and the delivery record are written to their documented and observed
request shapes and exercised only for their failure paths. The first real parcel should be sent
with the courier's own panel open beside the screen.

## Thank-you page rebuilt to the owner's screenshot (2026-08-07)

The owner sent the current thank-you page beside a reference design and asked for the reference's
layout. Four things were settled first: Bangla copy, the site's black-and-cream palette instead of
the reference's green, keep the step bar, and drop WooCommerce's default tables.

What shipped:

- **Black gradient banner** — step bar, then a ringed green tick, "আপনার অর্ডারের জন্য ধন্যবাদ!"
  and a one-line confirmation.
- **Four cards**: order number / date / payment pill · কাস্টমারের তথ্য (name, mobile, email,
  delivery address, delivery note) · অর্ডার করা পণ্য (thumbnail, unit price × quantity, variation
  chips, line total, then subtotal / discount / fees / delivery charge / tax / সর্বমোট) ·
  পেমেন্টের তথ্য (method, amount due, and for cash on delivery a line asking the customer to have
  the money ready).
- **WooCommerce's duplicate order and address tables removed**; its `woocommerce_thankyou` hooks
  still fire, buffered so the wrapper only appears when a plugin actually renders something.
- **A failed payment** gets its own banner — red-tinted mark, "পেমেন্টটি সম্পন্ন হয়নি", a retry
  link — and the step bar stops at the order step instead of ticking "সম্পন্ন".
- **Cash on delivery's title and description are now Bangla and its `instructions` field blank**,
  set by the demo importer, because WooCommerce printed that English line on this page under a
  Bangla card that already said it.
- Two icons added (`box`, `card`); `.pot` regenerated at 274 strings.

Verified in Playground against real orders: a two-item cash-on-delivery order with a delivery
note (৳6,380 + ৳70 = ৳6,450), a single-item order, and a failed order — all three render with
zero PHP notices, no duplicate tables, and the step bar in the right state for each.

The checkout stylesheet is now ~26 KB, taking that view to ~60 KB of CSS. Noted in CLAUDE.md; it
is one sheet shared by cart, checkout and thank-you, so splitting it is the fix if it grows again.

### Sized down for phones (same day)

The owner saw it on a device: the banner alone filled the screen and reading the page took a lot of
scrolling. The first build used one scale at every width. Fixed by making the whole page
mobile-first and stepping it up at 600px — medallion 78→52px, title `2xl`→`xl`, cards, item rows,
totals and callouts each down one step, all restored above 600px. The step bar is hidden below
600px (the owner's call), and the banner is rounded now that it plainly sits inside the page
container rather than bleeding to the edges.

Also removed the English "Order received" h1 that WordPress was printing directly above the Bangla
banner — `simple_bangla_hide_entry_title()`, checked in `template-parts/content.php`. It is scoped
to the checkout and order-received views only: the cart and the empty-cart checkout have no banner
of their own, so suppressing the title there would have left them with no heading at all.

Verified with Chrome DevTools device emulation at 390 / 600 / 1280 px — `scrollWidth` equals the
viewport at each, nothing overflows, no PHP notices. **`chrome --headless --window-size` does not
set the layout viewport**; it produced convincing screenshots of content apparently cut off at the
right edge that CDP emulation then disproved. Use `Emulation.setDeviceMetricsOverride`.

## Checkout rebuilt to the owner's screenshot (2026-08-06)

The owner sent a screenshot of the checkout they want. Before changing anything, the running
Playground instance was checked against it — the theme's own banner and step bar were rendering
and "আপনার অর্ডার" was already in Bangla, neither of which the screenshot shows, so the
screenshot is a target rather than a report of a broken page. Everything above the instruction
line is scrolled out of frame in it, so the banner and step bar were left alone.

Rebuilt to match:

- **Delivery charge moved out of the totals table** into a bordered two-row chooser under the
  address — label left, price right, the whole row tappable.
  (`template-parts/checkout/shipping.php`)
- **Order summary** now carries a product thumbnail, the quantity on its own line and a remove
  link, with Bangla column headings. No delivery row; subtotal then total, as in the screenshot.
  (`woocommerce/checkout/review-order.php`)
- **Billing block** heading is "ডেলিভারি তথ্য", which is what the address is for on a COD store.
  Name and mobile share a line; address, country and email take full lines. Labels are visually
  hidden — the placeholder carries the question and the asterisk.
  (`woocommerce/checkout/form-billing.php`)
- **Country is displayed, not chosen.** The importer now sells to Bangladesh only, so
  WooCommerce prints the name and a hidden input instead of a 250-option select.
- **Instruction line** is set as a headline closed by a rule, and repeated in small type directly
  above the Place Order button.
- **Cart notice** restyled: bordered, green tick, message left, button right in the theme's black
  rather than WooCommerce's blue.
- The empty second column — WooCommerce's separate shipping-address slot, which this store does
  not use — is gone.

**One bug caught in the build.** The coupon form was first moved into the order summary, where
the screenshot has it. That put a `<form>` inside the checkout `<form>`; browsers drop the inner
tag, so "Apply coupon" would have submitted the checkout instead. It is back above the form,
styled down to a single line of text.

Verified on the running install: checkout renders with no PHP notices, and a real order placed
through it — order #181, ৳1,410 = ৳1,290 + ৳120 — confirms the relocated delivery radios still
drive the total. Cart/checkout CSS is 50 KB of the 60 KB per-view budget.

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
  page fallbacks; payment-methods strip; copyright.
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
- ✅ **The browser tab showed the WordPress logo** (2026-08-09). With `site_icon` unset,
  WordPress 6.1 and later serve a default favicon of their own, so the shop wore somebody else's
  mark in every tab and bookmark. `simple-bangla/inc/site-icon.php` filters `get_site_icon_url()`
  to fall back to the site logo, which turns on the whole of core's icon handling — the 32/192px
  icons, the apple-touch-icon and the Windows tile — with nothing printed twice. A proper square
  icon can now be chosen in the CMS under Settings → Logo and tab icon, and takes precedence.
  Verified with 23 assertions in Playground.

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
