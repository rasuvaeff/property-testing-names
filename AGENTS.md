# AGENTS.md — property-testing-names

Guidance for AI agents working on this package. Read before changing code.

## What this is

Person-name arbitraries for the property-testing family: given names, surnames
and patronymics for `en` and `ru`, exposed through the static facade
`Rasuvaeff\PropertyTesting\Names\Names` (`first`, `last`, `middle`, `full`,
`person`). `PersonName` is the value object holding the parts plus three
display forms; `Gender` is a two-case enum. Everything under
`Rasuvaeff\PropertyTesting\Names\Internal\` — `Dataset`, `Locales` and the
per-locale data classes — is `@internal`.

The package depends on `rasuvaeff/property-testing-core`
(`^0.1 || ^0.2 || ^0.3 || ^0.4`) and
composes only its public API: `Gen::elements()`, `tuple()`, `map()`,
`flatMap()`, `enum()`, `constant()`.

## Golden rules

1. **Verification is mandatory.** Never claim "done" without a fresh green
   `composer build`. "Should work" does not count.
2. **No suppressions.** No `@psalm-suppress`, no baseline. Fix the root cause.
3. **`Gen::name()` will never exist, and the datasets stay `@internal`.** The
   facade is the only entry point: a core method that silently depends on this
   optional package would pass `composer-require-checker` in a consumer and
   fail at runtime, and a public `Dataset` would freeze the data shape into
   user code before 1.0. Custom locales are a feature request with a real user
   behind it, not a speculative escape hatch. See
   `property-testing-names-plan.md` in the monorepo root.
4. **Preserve the public contract.** Update README + README.ru + llms.txt +
   tests with any API change.

## Commands

No PHP/Composer on the host — run in Docker via the `composer:2` image.

```bash
docker run --rm -v "$PWD":/app -w /app composer:2 composer build
docker run --rm -v "$PWD":/app -w /app composer:2 composer cs:fix
docker run --rm -v "$PWD":/app -w /app composer:2 composer psalm
docker run --rm -v "$PWD":/app -w /app composer:2 composer test
docker run --rm -v "$PWD":/app -w /app composer:2 composer release-check
```

Or with Make:

```bash
make build
make cs-fix
make psalm
make test
make test-coverage
make mutation
make release-check
```

`composer.lock` is gitignored (library).
`make test-coverage` and `make mutation` bootstrap `pcov` inside the
`composer:2` container because the base image has no coverage driver.

## Invariants & gotchas

- **Dataset order is the shrink order.** Generators walk toward the head of
  each list, so the shortest, most ordinary entries come first. Appending to
  the head changes what every counterexample minimises to; `LocalesTest`
  asserts the head is not longer than the tail.
- **`ru` lists are index-aligned pairs.** Entry `i` of `MALE_LAST_NAMES` and of
  `FEMALE_LAST_NAMES` is the same surname in two genders; same for
  patronymics. Adding a surname means adding both forms at the same position —
  `LocalesTest` asserts equal counts and that female entries end with `а` while
  male ones do not.
- **A dataset change is a minor release**, never a patch: it changes the values
  a given seed produces, which is observable to every consumer with a
  regression corpus.
- **Gender consistency lives in `person()` only.** `first()` and `last()` are
  deliberately independent draws; do not "fix" that by making them agree — the
  merged pool is what makes a single-part generator useful.
- **Validation happens when the arbitrary is built.** `Locales::get()` and the
  middle-name check run in the facade, not inside the generator closure, so an
  unknown locale fails at the generators method rather than mid-run. Keep it
  that way when adding factories.
- **Two distinct failures, two distinct messages.** `Unknown locale "xx"; …`
  for an unregistered locale, `Locale "en" has no middle-name (patronymic)
  dataset` for a registered locale without that list. Never collapse them:
  reporting `en` as unknown would be false.
- **Initials are `mb_substr`-based.** Anything touching display forms must stay
  multibyte-safe; `printf` padding counts bytes and misaligns Cyrillic (see the
  comment in `examples/person-and-display-forms.php`).
- Code: `declare(strict_types=1)`, `final readonly class`, `#[\Override]`,
  explicit types, named arguments.
- `examples/` is part of the public contract: keep scripts runnable and update
  `examples/README.md` when example usage changes.
- **CI workflows are SHA-pinned.** Every `uses:` in `.github/workflows/*.yml`
  references a 40-char commit SHA with a `# vN` trailing comment
  (e.g. `actions/checkout@<sha> # v4`). Never revert to floating `@vN` tags.
  Updates go through Dependabot, which bumps the SHA and preserves the comment.
  Workflows also carry `permissions: { contents: read }` at workflow level and
  `persist-credentials: false` on every `actions/checkout` step. Verify with
  `zizmor --persona=auditor .github/` — must report no `unpinned-uses`,
  `excessive-permissions`, or `artipacked` findings.
- **The mutation gate is `minMsi: 100` and it is honest** — no `ignore`, no
  `global-ignore`. Randomness must never be the only thing killing a mutant: a
  property that draws the interesting branch through `Gen::draw(Gen::bool())`
  kills it in *some* runs, and Infection then reports it escaped at random.
  Every branch has a deterministic counterpart on a fixed seed — see
  `NamesTest::personAlwaysCarriesAMiddleNameWhenAsked`, which exists solely
  because two mutants in the eager middle-name lookup survived otherwise.
- **Local mutation runs: build the pcov image once.** `make mutation` reinstalls
  pcov inside `composer:2` on every call (minutes per run). Build
  `composer-pcov:local` once, then
  `docker run --rm -v "$PWD":/app -w /app --entrypoint composer composer-pcov:local mutation`
  finishes in seconds.
- **`ext-mbstring` is required in every CI job**, including
  `static-analysis.yml`: the local `composer:2` image ships it, so a missing
  entry is green locally and red in CI.
- **The property regression corpus is cached in CI** (restore → `PROPERTY_DB`
  → save around `composer test:coverage:ci`). The restore/save split is
  deliberate: a combined `actions/cache` declares `post-if: success()` and
  would never save the corpus written by the failing run that produced it.

## Property-testing API coverage

Per the monorepo rule, new or extended property tests must walk the checklist
in the root `AGENTS.md`. Current state, so the next agent does not re-derive it:

| Feature | Where / why not |
|---|---|
| `<method>Examples()` | `NamesTest::personPartsAgreeWithTheDrawnGenderExamples`, `bcp47StyleTagsAreRejectedExamples`, `PersonNameTest::initialsAreSingleCharactersOfTheirPartsExamples` |
| `Classify::cover()` | Gender and middle-name branches in `NamesTest` and `PersonNameTest` |
| `Classify::when()` / `label()` | Locale distribution labels in `NamesTest` |
| `#[Property(timeoutMs:)]` | On every property here — the bodies run PCRE (`\p{C}`) over generated text |
| `Gen::stringMatching()` | `NamesTest::bcp47StyleTagsAreRejected` builds tags that must be rejected |
| `Gen::draw()` | Dependent draws: the middle-name flag is derived from the locale, then the person is drawn |
| `Gen::commands()` + `StateMachine` | **N/A** — the package has no lifecycle or mutable state; every factory is pure |
| `Gen::datetime`/`bytes`/`uuid`/`record`/`dictOf`/`uniqueArrayOf` | **N/A** — the inputs are a locale tag, a gender and a flag; there is no temporal, binary or collection input to generate |
| `Assume::that()` | **N/A** — every dependent value is constructed (`$middle = $locale === 'ru' && …`), so nothing is discarded |

## When you finish

- Update `README.md` **and `README.ru.md`** (both languages, same commit; and
  `llms.txt` + `examples/` if usage changed); update `CHANGELOG.md` when
  releasing.
- Re-run `composer build`; if the change affects public API or release safety,
  also run `make release-check`. Paste the output.
