# WP Tools Pro release checklist

Run these checks on a staging copy representative of production before promotion.

## Core gates
- Activate, deactivate, reactivate, and upgrade from the previous release candidate.
- Confirm database index review passes.
- Confirm queue has no stale or failed jobs after normal workloads.
- Run an isolated queue failure and verify retry/failure visibility.

## Compatibility regression
- Test WooCommerce admin, checkout/cart/session, transactional email, and REST-dependent screens.
- Test Elementor editor load, save, preview, embeds, and responsive media.
- Verify Rank Math and Yoast do not create duplicate title/meta/canonical output.

## Webhooks and security
- Verify provider-native webhook signatures and replay rejection.
- Confirm privileged admin handlers require both nonce and capability checks.
- Confirm production does not expose debugging output or credentials.

## Multisite and recovery
- Network activate on staging and verify site-scoped initialization.
- Test migration failure recovery and rollback behavior.
- Verify uninstall/data-retention settings only on a disposable staging copy.

## Accessibility and QA
- Navigate admin screens with keyboard only.
- Verify visible focus, modal focus containment, Escape handling, and 200% zoom.
- Run PHP syntax checks and the supported PHP version matrix in CI.
