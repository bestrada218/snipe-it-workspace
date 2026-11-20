### Contributing

Thanks for helping improve Snipe-IT Workspace.

- **Ideas and problems:** open an issue with the bug report or feature request form. For security problems follow the [security policy](SECURITY.md) instead.
- **Changes:** open a pull request against `main`. Keep it focused on one change and describe what it does and how you tested it.
- **Tests:** add or update tests for behaviour you change, and run them (`php vendor/bin/phpunit`, one `tests/` folder at a time on machines with little memory). The GitHub workflow runs them on PHP 8.3 with MariaDB 11.4.
- **Style:** format PHP with `vendor/bin/pint --dirty`. Rebuild front-end assets with `npm run prod` and commit the compiled files.
- **Data in English:** text people type into the application (names, notes, codes) is kept in English; the interface is translated in `resources/lang/en-US` and `resources/lang/es-CO`.
- **AI assistants:** `AGENTS.md`, `CLAUDE.md` and the path-scoped rules in `.ai/rules` describe the architecture and conventions to follow.

If a change is useful for every Snipe-IT installation and not only for this project, consider contributing it to [Snipe-IT](https://github.com/grokability/snipe-it) as well (see their [contributing guide](https://snipe-it.readme.io/docs/contributing-overview)).

Please note that this project is released with a [Contributor Code of Conduct](CODE_OF_CONDUCT.md). By participating in this project you agree to abide by its terms.
