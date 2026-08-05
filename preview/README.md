# Preview harness — superseded

> **These pages mirror the Phase 1 templates only and are no longer kept in sync.** The theme
> now runs on real WordPress + WooCommerce under WordPress Playground — see the bottom of this
> file, or `PROGRESS.md`. Use that instead. This folder is kept only as a way to eyeball the
> base stylesheet without booting anything.


Static HTML copies of the Phase 1 templates, for looking at the design without a PHP stack.
**Not part of the theme** — this folder is never shipped and nothing inside it is loaded by
WordPress.

Each page links `../simple-bangla/assets/css/base.css` directly, so any edit to the real
stylesheet shows up on refresh. The markup is transcribed from the PHP templates by hand and
has to be re-synced manually when a template changes.

## Running it

```
node <scratchpad>/static-server.mjs      # serves the repo root on :8080
```

Or open `preview/index.html` straight from disk — the only thing that breaks is nothing;
relative paths work either way.

| Page | Template it mirrors |
|---|---|
| `index.html` | `index.php` + `template-parts/content.php` + `sidebar.php` |
| `single.html` | `single.php` |
| `page.html` | `page.php` (full width — `simple_bangla_active_sidebar_id()` returns `''` on pages) |
| `search.html` | `search.php` + `template-parts/content-none.php` |
| `404.html` | `404.php` |

## The real thing

For actual WordPress rendering, WordPress Playground runs PHP as WebAssembly under Node —
no XAMPP, no MySQL:

```
npx @wp-playground/cli@latest server --port=8882 \
  --mount-dir <abs path>/simple-bangla /wordpress/wp-content/themes/simple-bangla \
  --blueprint <scratchpad>/blueprint.json
```

Two Windows gotchas, both already worked around above:

- `--mount host:vfs` cannot be used, because `C:\…` contains a colon. Use `--mount-dir`.
- `@wp-now/wp-now` (the older tool) crashes here: it resolves its own install path against the
  current drive, producing `D:\C:\Users\…`. Use `@wp-playground/cli` instead.
