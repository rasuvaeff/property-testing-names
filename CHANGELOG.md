# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## Unreleased

- Widened `rasuvaeff/property-testing-core` to `^0.1 || ^0.2 || ^0.3`. The
  constraint stopped at 0.2 while the engine had moved on, so a project on
  core 0.3 — which is what the current Testo and PHPUnit adapters require —
  could not install this package at all. Nothing here uses a 0.2 or 0.3 API;
  the generators are built on the 0.1 surface and stay there.

## 0.1.0 — 2026-08-13

- Initial package: `Names::first()`, `last()`, `middle()`, `full()` and
  `person()` over the bundled `en` and `ru` datasets, the `PersonName` value
  object with `full()`/`initialLast()`/`lastInitials()`, and the `Gender` enum.
