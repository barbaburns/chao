# Chao Theme

Custom WordPress theme for Chao Editora built on Sage 9.

This repository is a legacy Sage 9 project with a modern-enough local workflow for maintaining the theme, building assets, and packaging a deployable artifact without shipping `node_modules/`.

## Stack

- Sage 9
- Blade templates in `resources/views`
- Controllers in `app/Controllers`
- ACF JSON sync in `resources/acf-json`
- Webpack 3 for asset builds
- WooCommerce-specific theme logic in `app/woocommerce.php`

Validated locally on March 26, 2026 with:

- Node `18.15.0`
- npm `9.5.0`
- Yarn `1.22.19`
- PHP `7.4.19`

## Project Structure

```text
chao/
|-- app/                    PHP theme logic and controllers
|-- config/                 Sage runtime config
|-- resources/
|   |-- acf-json/           ACF field group sync files
|   |-- assets/             Source JS, SCSS, images, fonts, build config
|   |-- functions.php       Theme bootstrap
|   |-- style.css           Theme headers used by WordPress
|   `-- views/              Blade templates
|-- dist/                   Compiled production assets
|-- vendor/                 Composer runtime dependencies
|-- scripts/
|   |-- lint-php.sh         PHP syntax validation
|   `-- package-theme.sh    Deployment packaging
|-- composer.json
`-- package.json
```

## Local Development

Install dependencies from the theme root:

```sh
composer install
yarn install
```

Asset development uses the project values in `resources/assets/config.json`:

- `devUrl`: `http://chao.local`
- `proxyUrl`: `http://localhost:8001`
- `publicPath`: `/wp-content/themes/chao`

If your local URLs differ, update that file or add a local-only override in `resources/assets/config-local.js`.

## Commands

```sh
yarn start            # Watch assets and run BrowserSync
yarn build            # Build non-production assets
yarn build:production # Build hashed production assets into dist/
npm test              # JS + SCSS lint
npm run lint:php      # PHP syntax lint
composer test         # PHP syntax lint
npm run verify        # Lint everything and build production assets
npm run package:theme # Validate, build, and create a deployable zip in artifacts/
```

## Deployment Workflow

The deployable artifact is created by `scripts/package-theme.sh`.

What it does:

1. Runs `npm test`
2. Runs `npm run lint:php`
3. Runs `yarn build:production`
4. Copies the runtime theme files into a staging directory
5. Creates `artifacts/chao-<version>.zip`

Included in the package:

- `app/`
- `config/`
- `dist/`
- `resources/`
- `vendor/`
- `composer.json`
- `composer.lock`
- `screenshot.png`

Not included in the package:

- `node_modules/`
- `.cache-loader/`
- local editor and OS files

Use the zip as a server deployment artifact. Extract it into `wp-content/themes/` so the final path is `wp-content/themes/chao/`.

## Notes For This Theme

- `vendor/` is required at runtime because `resources/functions.php` loads Composer autoloading.
- `dist/assets.json` is required at runtime because Sage resolves hashed assets through that manifest.
- This is still a legacy Sage 9 / Webpack 3 stack, so the workflow here focuses on stability and repeatable packaging instead of a full framework upgrade.

## Maintenance Baseline

The repository now includes:

- `.gitignore` for generated and local-only files
- `.editorconfig` for basic formatting consistency
- `.nvmrc` to pin the Node version that was validated locally
- project-level ESLint and Stylelint configs so `npm test` is usable

If we want, the next step can be a second pass to modernize the asset toolchain or replace the legacy Composer/PHP_CodeSniffer setup entirely.
