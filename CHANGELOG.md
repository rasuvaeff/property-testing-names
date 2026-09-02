# Changelog

## Unreleased

- Allows `rasuvaeff/property-testing-core` `^0.6` beside `^0.4 || ^0.5`; the dataset draws are `Gen::elements()` over fixed lists, which 0.6 leaves untouched.

## 0.2.0 — 2026-09-02

- The `ru` lists are now ordered by length throughout (stable within a
  length; inflected pairs keep their alignment), so shrinking really moves
  toward the shortest entry as documented — `Ульяна` no longer shrinks into
  `Татьяна`. A given seed produces different `ru` values than before.
- `PersonName` declares its parts as `non-empty-string` in psalm types, so
  `Gen::forClass(PersonName::class)` builds instances the constructor
  accepts instead of throwing on an empty part.
- CI: the backward-compatibility step tolerates a break declared by a
  higher 0.x minor in `CHANGELOG.md` (template of 2026-08-15), `release.yml`
  validates the tag against `master` before publishing, and `zizmor.yml`
  audits the workflows. `infection/infection` is required as `^0.35`.
- Requires `rasuvaeff/property-testing-core` `^0.4 || ^0.5` (was `^0.1 || … || ^0.4`):
  the older lines have no `Gen::forClass()`, which the package now tests
  itself against; 0.5 is the line the adapters moved to.

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## 0.1.3 — 2026-08-20

- Removed a stray empty `shrink.php` from the package root; it was committed by
  accident and shipped in the dist archive.

## 0.1.2 — 2026-08-20

- Documentation: reflected the widened core `^0.1 || ^0.2 || ^0.3 || ^0.4`
  constraint in `llms.txt` and `AGENTS.md` (the `composer.json` `require` and
  the READMEs already carried it in 0.1.1).

## 0.1.1 — 2026-08-20

- Widened `rasuvaeff/property-testing-core` to `^0.1 || ^0.2 || ^0.3 || ^0.4`.
  The constraint stopped at 0.2 while the engine had moved on to 0.4, so a
  project on the current Testo or PHPUnit adapter — which require core `^0.4` —
  could not install this package at all. Nothing here uses a 0.2/0.3/0.4 API;
  the generators are built on the 0.1 surface and stay there.

## 0.1.0 — 2026-08-13

- Initial package: `Names::first()`, `last()`, `middle()`, `full()` and
  `person()` over the bundled `en` and `ru` datasets, the `PersonName` value
  object with `full()`/`initialLast()`/`lastInitials()`, and the `Gender` enum.
