# Contributing

Install PHP 8.4 or newer and Composer, then run:

```bash
composer install
```

Keep changes focused, update tests and documentation with behavior changes,
and run the complete local suite before opening a pull request:

```bash
composer ic:tests
```

Use `composer ic:process` for configured automated fixes. Do not commit `.env`,
credentials, private keys, or production data. Report security issues through
the private process in `SECURITY.md`.
