# ADR-001: Separate public, application, and test code

**Status:** Accepted
**Date:** 2026-09-19
**Decider:** Project maintainer

## Context

The application originally combined HTML, CSS, JavaScript, upload handling,
metadata extraction, model transport, and model-response parsing in two large
PHP files. That made isolated tests and safe changes difficult.

The existing `/ai-image-detector.php` URL must remain usable in local XAMPP
setups, and the project should remain lightweight without adopting a full PHP
framework or frontend bundler.

## Decision

- Keep `ai-image-detector.php` as a compatibility entry point.
- Put the rendered page and browser assets under `public/`.
- Split browser behavior into upload, analysis/API, result, and orchestration
  modules using native ES modules.
- Put backend validation, API transport, and response parsing into focused PHP
  classes under `src/`.
- Use Composer only for development tooling and PSR-4 autoloading.
- Use Node's built-in test runner for pure JavaScript units and Playwright for a
  small number of critical browser journeys.

## Options considered

### Keep the monolith

Lowest short-term effort, but it preserves tight coupling and makes unit tests
depend on request globals or a browser.

### Introduce a full framework and frontend build system

Provides conventions but adds deployment and maintenance complexity that is not
justified by this application's current size.

### Native modules and small PHP classes

Adds clear boundaries while retaining direct XAMPP compatibility and a minimal
runtime footprint. This option was selected.

## Consequences

- Pure scoring, parsing, and validation logic can be tested without HTTP.
- Browser tests cover integration behavior rather than every calculation.
- Static assets require the project to remain available at the virtual host
  root unless the asset base is made configurable later.
- Composer and npm are now required for contributors and CI, not for serving the
  application in production.
