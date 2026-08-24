# Contributing

This repository is maintained as a focused WordPress/PHP engineering portfolio. Changes should be small, reviewable, and traceable.

## Workflow

1. Start from an issue that explains the problem, scope, and acceptance criteria.
2. Branch from `main` using one of these prefixes:
   - `feat/` for user-visible capabilities
   - `fix/` for defects
   - `refactor/` for internal restructuring
   - `perf/` for performance work
   - `security/` for security hardening
   - `ci/` for automation
   - `docs/` for documentation
   - `chore/` for repository maintenance
3. Keep commits atomic. Each commit should leave the branch in a coherent state.
4. Use Conventional Commit-style subjects, for example:
   - `feat: add redirect loop protection`
   - `fix: recover stale queue locks safely`
   - `perf: add index for pending queue lookup`
   - `ci: validate PHP compatibility matrix`
5. Open a pull request and link the issue with `Closes #<number>` when appropriate.
6. Do not merge until required CI checks pass.

## Local quality checks

```bash
composer install
composer validate --strict --no-check-lock
composer lint
find . -type f -name '*.php' -not -path './vendor/*' -print0 | xargs -0 -n1 php -l
```

## WordPress engineering expectations

- Validate and sanitize external input as early as possible.
- Escape output for the final rendering context.
- Require both capability checks and nonces for privileged mutations.
- Use `$wpdb->prepare()` for dynamic SQL values.
- Prefer WordPress APIs over direct filesystem/network operations where practical.
- Keep migration and queue operations idempotent and recoverable.
- Never commit production credentials, salts, customer data, database exports, logs, or private provider configuration.

## Pull request scope

A strong PR explains:

- the problem and why it matters;
- the implementation approach and important trade-offs;
- security, data, migration, or performance impact;
- how the change was validated;
- follow-up work that is deliberately out of scope.
