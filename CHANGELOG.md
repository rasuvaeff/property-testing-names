# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## 0.1.2 — 2026-08-20

- Removed a stray empty `shrink.php` from the package root; it was committed by
  accident and shipped in the dist archive.

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
