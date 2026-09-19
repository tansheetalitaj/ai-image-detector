# Testing strategy

## Test pyramid

| Layer | Scope | Command |
| --- | --- | --- |
| PHP unit | Model schema parsing, score decisions, file validation, metadata | `composer test:unit` |
| JavaScript unit | Deterministic scoring, backend payload parsing, upload limits, result copy | `npm run test:unit` |
| Browser | Upload, reset, invalid input, backend-error fallback and UI unlock | `npm run test:browser` |
| Static quality | PSR-12, ESLint, Prettier | `composer lint:php` and `npm run check` |

## Coverage targets

- Exercise every model decision boundary: human-like, inconclusive, and AI-like.
- Exercise every strict model-response rejection family: shape, labels, score
  range, duplicates, and normalization.
- Exercise valid image metadata plus unsupported and invalid-dimension cases.
- Keep every user-critical upload state covered by a browser test: selected,
  reset, rejected, analyzing, successful fallback, and unlocked after failure.
- Never call the real inference provider from automated tests. Provider behavior
  is represented by validated fixtures or an intercepted browser response.

## CI policy

Every push and pull request must pass both CI jobs:

1. `quality`: Composer validation/install, PHP_CodeSniffer, PHPUnit, ESLint,
   Prettier verification, and JavaScript unit tests.
2. `browser`: a clean Node install, Chromium installation, and Playwright tests
   against PHP's temporary development server.

## Known gaps

- Provider connectivity requires a separately authorized integration test with a
  server-side token; it is deliberately excluded from CI.
- Visual regression snapshots are not yet maintained.
- Automated accessibility scanning is not yet included; keyboard behavior has
  browser coverage only where it intersects the tested upload flows.
- Evaluation accuracy remains blocked on the provenance-reviewed labeled image
  set described under `evaluation/`.
