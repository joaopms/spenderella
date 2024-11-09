# Spenderella

> "Spenderella" was inspired by the name "Cinderella", a well-known fairytale character. The idea behind the name "Spenderella" was to create a playful and catchy name that reflects the idea of managing expenses and spending money. The name is meant to convey a sense of fun and lightheartedness, while still being relevant to the core purpose of the application.

## What's Spenderella?

The project consists of an application to manage your money and expenses.
It will connect to your bank accounts so you quickly have access to your latest transactions.
You'll then be able to categorize them and attach transactions to each other, like when you split a bill with someone.

## How to run?

Docker needs to be installed on the host system.

### If using NixOS

Just run `nix develop` and you'll have a shell set-up with PHP and Composer, along with a `sail` alias.

```sh
nix develop
sail up -d
```

When using PhpStorm, a remote PHP interpreter must be set-up and pointed to the `spenderella` container.
The lifecycle must be set to "connect to existing container".

## Tech Info

The project uses Laravel and runs, in development, with Laravel Sail. Formatting is done with Laravel Pint.

## Deployment

The `Dockerfile` located on the `docker` directory creates a Docker image that runs `nginx` on port `80`, proxying requests to `php-fpm`.

`supervisord` is the main process, running `nginx`, `php-fpm`, `crond` (that runs the Laravel scheduler), and Laravel's queue worker.

### Setup

1. **Create a directory for the app**
2. **Clone the repo to, for example, `repo`**
3. **Copy `repo/storage` to where persistent data is going to be stored, for example, `storage`**<br/>
   (with `cp -r repo/storage storage`)
4. **Setup Docker Compose**<br/>
   Use the `docker-compose.yml` file in the `docker` directory as a guide
5. **Start the Docker container and run the commands below in it**<br/>
6. **Edit `.env` to your needs**
7. **Restart the Docker container**
8. **Success! 🎉**

```sh
# Install dependencies
composer install --optimize-autoloader --no-dev

# Prepare .env
cp .env.prod.example .env

# Generate the app key
php artisan key:generate

# Create the symbolic link for public storage
php artisan storage:link
```
