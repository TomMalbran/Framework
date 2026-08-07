# Framework

A PHP 8.3 framework built around attribute discovery and code generation.

## Checks

Run all three before considering work done. PHPStan is at level 10 with strict rules
plus the custom ones in `src/Analysis`, so new code needs typed returns, `list<...>`
shapes, named bool arguments and `Optional.` on optional params.

```bash
vendor/bin/phpstan analyse
vendor/bin/phpcs                # 100 character lines
vendor/bin/phpunit
```

## The documentation site

`docs/` is the site published by GitHub Pages, served from `/docs` on `main`.

**The HTML is the source of truth.** The pages are hand-maintained — there is no
generator, no templating step and no markdown behind them. Edit the HTML directly, and
match the structure of a neighbouring page in the same folder.

Three private console commands back it, all only visible from inside this repo:

```bash
./framework docs          # serve it locally on port 3005
./framework docsCheck     # verify every link still resolves
./framework docsIndex     # rebuild docs/assets/search.json
```

Things that are easy to get wrong:

- **Run `docsIndex` after editing any page.** Nothing does it automatically, so the
  search silently keeps answering from a stale index.
- **Run `docsCheck` before merging to `main`.** The source links point at
  `blob/main/...`, so a link to a file that only exists on `dev` passes locally and
  404s on the published site.
- **Do not hand-edit the version.** `./framework incVersion` (and `decVersion`,
  `setVersion`) rewrites `composer.json`, `README.md` and every docs page — the sidebar
  badge, the GitHub tag it links to, and the `dev-main#v` requires.
- `docs/404.html` is served by Pages for any missing path at any depth, which is why it
  carries a `<base href>` that its siblings do not. It is excluded from the search index.
