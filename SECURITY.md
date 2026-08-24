# Security Policy

## Supported code

Security fixes apply to the current `main` branch of this public portfolio snapshot.

## Reporting a vulnerability

Please do not open a public issue for a vulnerability that could expose credentials, private data, authentication material, or a practical exploit path.

For a private report, use the contact details on https://vovinhlac.com and include:

- affected file or component;
- reproduction steps;
- expected and observed behavior;
- security impact;
- any suggested mitigation.

## Repository safety rules

This repository intentionally excludes production credentials, salts, database dumps, runtime logs, customer data, provider secrets, backups, and site-specific private configuration. If any such material is discovered in Git history, treat it as compromised and rotate/revoke it before removing it from the repository.
