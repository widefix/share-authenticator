# Shared Team Authenticator

A web application for teams to organize and share authenticator codes through
workspaces and vaults.

## Run it locally

### Requirements

- PHP 8.4 or newer with SQLite and Sodium support.
- Composer 2.
- Node.js 20.19+ or 22.12+ with npm.
- Git.

### Installation

Clone the repository and install its dependencies:

```bash
git clone <repository-url> shared-team-authenticator
cd shared-team-authenticator
composer install
npm ci
```

Create the local configuration and SQLite database.

On macOS or Linux:

```bash
cp .env.example .env
touch database/database.sqlite
```

On Windows Command Prompt:

```bat
copy .env.example .env
type nul > database\database.sqlite
```

Finish the initial setup:

```bash
php artisan key:generate
php artisan vault:key
php artisan migrate
```

Start the application:

```bash
composer run dev
```

Open <http://127.0.0.1:8001/register> and create your first account. Keep the
terminal open while using the application and press `Ctrl+C` to stop it.

To start the same installation again later, run:

```bash
composer run dev
```

## Backups

Your local data is stored in `database/database.sqlite`, and the keys required
to read it are stored in `.env`. Stop the application and back up both files
together.

Never commit `.env`, regenerate its keys after saving data, or run
`php artisan migrate:fresh` on an installation you want to keep.

## Updating

Back up your data, stop the application, and run:

```bash
git pull --ff-only
composer install
npm ci
php artisan migrate
composer run dev
```

## Tests

```bash
composer test
npm test
npm run build
```

## Troubleshooting

### The SQLite database does not exist

Create `database/database.sqlite` and run:

```bash
php artisan migrate
```

### Port 8001 is already in use

Run Laravel and Vite separately on another port:

```bash
php artisan serve --host=127.0.0.1 --port=8010
```

```bash
npm run dev
```

Then open <http://127.0.0.1:8010>.

### Configuration changes are not taking effect

```bash
php artisan optimize:clear
```

Restart the application afterward.

## Public deployment

The local development server is only available from your computer. Publishing
the application on a network or the internet requires a separate production
deployment with HTTPS and a production web server.

See [Deploy with Kamal](docs/KAMAL.md) for the repository-specific production
configuration.

## License

MIT
