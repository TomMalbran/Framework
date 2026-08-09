# Framework

A PHP 8.1 framework built around attribute discovery and code generation.

## Committing

**Never commit unless you are asked to.** Leave the work in the working tree and
say what is there. This holds even when a change is finished and every check passes,
and it holds for `git add` too — staging sweeps up whatever else is in flight.
When you are asked, stage the paths you touched by name rather than with `-A` or `.`,
so anything being worked on elsewhere stays out of it.

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
./framework docsCheck     # verify the links, and the code examples
./framework docsIndex     # rebuild docs/assets/search.json
./framework docsSchema    # rebuild docs/assets/schema.json
```

`docsCheck` covers six things: that every source link points at a file that exists and,
when labelled `Class::method()`, at a method it declares; that every page link and
`#anchor` resolves; that every `use Framework\...` in a PHP example is a real class whose
called methods exist; that the version in `assets/version.js` and in the `dev-main#v`
snippets matches `composer.json`; that every menu link in `assets/nav.js` resolves and
every page is reachable from it; and that `search.json` and `schema.json` match what they
were built from. Classes under `Framework\System` are skipped, since the build of each app
writes those.

Things that are easy to get wrong:

- **Run `docsIndex` after editing any page.** Nothing does it automatically, and the
  search would keep answering from a stale index — `docsCheck` fails when it is stale.
- **Run `docsSchema` after touching a model.** The published schema is generated from the
  `#[Model]` attributes, so a new field or description leaves it stale, and `docsCheck`
  fails on that too.
- **The sidebar lives in `assets/nav.js`, not in the pages.** A new page has to be listed
  there or nothing links to it, and `docsCheck` says so.
- **Run `docsCheck` before merging to `main`.** The source links point at
  `blob/main/...`, so a link to a file that only exists on `dev` passes locally and
  404s on the published site.
- **`vendor/bin/phpcs` needs a path.** On its own it exits with an error and checks
  nothing, so run `vendor/bin/phpcs src`.
- **Do not hand-edit the version.** `./framework incVersion` (and `decVersion`,
  `setVersion`) rewrites `composer.json`, `README.md` and every docs page — the sidebar
  badge, the GitHub tag it links to, and the `dev-main#v` requires.
- `docs/404.html` is served by Pages for any missing path at any depth, which is why it
  carries a `<base href>` that its siblings do not. It is excluded from the search index.
