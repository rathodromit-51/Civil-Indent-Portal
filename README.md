# Civil Indent Portal — Docker Project Setup Guide

A complete Docker-based development and production stack for a Laravel application.

---

## Table of Contents

1. [Project Overview](#step-1-understand-the-project-structure)
2. [Prerequisites](#step-2-install-prerequisites)
3. [Initial Setup](#step-3-initial-setup)
4. [Start the Development Stack](#step-4-start-the-development-stack)
5. [Verify Everything Is Running](#step-5-verify-everything-is-running)
6. [Common Day-to-Day Commands](#step-6-common-day-to-day-commands)
7. [Enable Optional Services](#step-7-enable-optional-services)
8. [Deploy to Production](#step-8-deploy-to-production)
9. [Troubleshooting](#step-9-troubleshooting)

---

## Step 1: Understand the Project Structure

```
docker-project-template/
├── .env.example          # Template for environment variables
├── .env                  # Your local config (created from .env.example)
├── Dockerfile            # Production image build instructions
├── docker-compose.yml    # Development stack definition
├── docker-compose.prod.yml  # Production stack definition
└── src/                  # Your Laravel application lives here
    ├── artisan
    ├── public/
    ├── app/
    └── ...
```

### Services in the Stack

| Service | Image | Default Port | Purpose |
|---|---|---|---|
| **app** | `serversideup/php:<PHP_VERSION>-fpm-nginx` | `APP_PORT` (8000) | Laravel web app with Nginx + PHP-FPM |
| **queue** | same as app | — | Background queue worker (`php artisan queue:work`) |
| **mysql** | `mysql:8.0` | `MYSQL_PORT` (3307) | MySQL database |
| **redis** | `redis:7.2-alpine` | — | Cache, queue broker, and sessions |

### Optional Services (enabled via `--profile`)

| Service | Profile | Default Port | Purpose |
|---|---|---|---|
| **phpmyadmin** | `tools` | `PMA_PORT` (8081) | Database management UI |
| **soketi** | `realtime` | `SOKETI_PORT` (6001) | Pusher-compatible WebSocket server |
| **cloudflared** | `tunnel` | — | Expose the app publicly via Cloudflare Tunnel |
| **watchtower** | `autoupdate` (prod) | — | Auto-pull new CI-built Docker images |

---

## Step 2: Install Prerequisites

Make sure the following are installed on your machine:

- [ ] **Docker Desktop** — [Download here](https://www.docker.com/products/docker-desktop/)
- [ ] **Git** — [Download here](https://git-scm.com/)
- [ ] **Composer** (optional, for creating a new Laravel project locally)

> **Tip:** On Windows, ensure Docker Desktop is set to use **Linux containers** (the default).

---

## Step 3: Initial Setup

### 3.1 — Clone or copy the template

```bash
cp -r docker-project-template my-new-project
cd my-new-project
```

### 3.2 — Create your `.env` file

```bash
cp .env.example .env
```

Then edit `.env` and set your values:

```env
# Give your project a unique name (used for container names)
PROJECT_NAME=civil-indent-portal
COMPOSE_PROJECT_NAME=civil-indent-portal

# PHP version: use 8.2 for Laravel 10/11, or 8.4 for Laravel 12+
PHP_VERSION=8.4

# Host ports (change if they conflict with other services)
APP_PORT=8000
MYSQL_PORT=3307

# Database credentials
DB_DATABASE=app
DB_PASSWORD=root_password
```

### 3.3 — Add your Laravel app to `src/`

**Option A — Fresh Laravel install:**

```bash
composer create-project laravel/laravel src
```

**Option B — Existing project:**

Copy your Laravel project files into `./src/` so that `./src/artisan` and `./src/public/` exist.

### 3.4 — Initialize Git

```bash
git init
```

---

## Step 4: Start the Development Stack

Open Docker Desktop, wait for it to fully start, then run:

```bash
docker compose up -d --build
```

**What this does:**

1. **Pulls** the required images (`serversideup/php`, `mysql:8.0`, `redis:7.2-alpine`)
2. **Builds** the app container with your PHP version
3. **Starts** all 4 core services (app, queue, mysql, redis)
4. **Bind-mounts** `./src` into the containers — code changes reflect instantly
5. **Auto-runs** Laravel migrations, seeders, and storage link (via the `AUTORUN_*` env vars)

---

## Step 5: Verify Everything Is Running

### 5.1 — Check container status

```bash
docker compose ps
```

You should see all 4 services with status `Up`:

```
NAME                          STATUS
civil-indent-portal_app       Up
civil-indent-portal_queue     Up
civil-indent-portal_mysql     Up
civil-indent-portal_redis     Up
```

### 5.2 — Open the app in your browser

Visit: **http://localhost:8000** (or whatever `APP_PORT` you set)

### 5.3 — Check the logs if something isn't working

```bash
docker compose logs -f app
```

---

## Step 6: Common Day-to-Day Commands

### Start / Stop the stack

```bash
# Start all services in the background
docker compose up -d

# Stop all services (data is preserved in volumes)
docker compose down

# Stop and DELETE all data (databases, redis, etc.)
docker compose down -v
```

### Run Artisan commands

```bash
docker compose exec app php artisan migrate
docker compose exec app php artisan tinker
docker compose exec app php artisan optimize:clear
docker compose exec app php artisan make:model Post -m
```

### Run Composer commands

```bash
docker compose exec app composer install
docker compose exec app composer require laravel/sanctum
```

### Run npm commands (if Node is in the image)

```bash
docker compose exec app npm install
docker compose exec app npm run dev
```

### Access the app container shell

```bash
docker compose exec app bash
```

### View logs

```bash
# All services
docker compose logs -f

# Specific service
docker compose logs -f app
docker compose logs -f mysql
docker compose logs -f queue
```

### Rebuild after Dockerfile or compose changes

```bash
docker compose up -d --build
```

---

## Step 7: Enable Optional Services

Optional services use Docker Compose **profiles**. Add the `--profile` flag to enable them:

### phpMyAdmin (Database UI)

```bash
docker compose --profile tools up -d
```

Then open: **http://localhost:8081**

### Soketi (WebSocket Server)

```bash
docker compose --profile realtime up -d
```

Configure your Laravel `.env` (`src/.env`):

```env
BROADCAST_DRIVER=pusher
PUSHER_APP_ID=app
PUSHER_APP_KEY=app-key
PUSHER_APP_SECRET=app-secret
PUSHER_HOST=localhost
PUSHER_PORT=6001
PUSHER_SCHEME=http
```

### Cloudflare Tunnel (Public Access)

1. Create a tunnel in the [Cloudflare Dashboard](https://dash.cloudflare.com/)
2. Add the tunnel token to your `.env`:
   ```env
   CLOUDFLARE_TUNNEL_TOKEN=your-token-here
   ```
3. Configure public hostnames in the dashboard:
   - `app.example.com` → `http://app:8080`
   - `ws.example.com` → `http://soketi:6001` (optional)
4. Start the tunnel:
   ```bash
   docker compose --profile tunnel up -d
   ```

### Enable multiple profiles at once

```bash
docker compose --profile tools --profile realtime --profile tunnel up -d
```

> **Important:** When stopping, include the same `--profile` flags:
> ```bash
> docker compose --profile tools --profile tunnel down
> ```

---

## Step 8: Deploy to Production

### 8.1 — Build the production image (via CI or locally)

```bash
docker build -t ghcr.io/<org>/<repo>/app:main .
docker push ghcr.io/<org>/<repo>/app:main
```

A GitHub Actions workflow is provided at `.github/workflows/docker-publish.yml`.

### 8.2 — Configure the production `.env`

```env
APP_IMAGE=ghcr.io/<org>/<repo>/app:main
DB_PASSWORD=<strong-production-password>
# For private registries:
REGISTRY_USER=<github-username>
REGISTRY_TOKEN=<github-pat-with-read:packages>
```

### 8.3 — Start the production stack

```bash
docker compose -f docker-compose.prod.yml up -d
```

### 8.4 — Enable auto-update (optional)

Watchtower will auto-pull new images when CI pushes them:

```bash
docker compose -f docker-compose.prod.yml --profile autoupdate up -d
```

---

## Step 9: Troubleshooting

### "Port already in use"

Change the conflicting port in `.env`:

```env
APP_PORT=8080    # instead of 8000
MYSQL_PORT=3308  # instead of 3307
```

Then restart: `docker compose up -d`

### "Permission denied" on storage/

The app container's entrypoint automatically fixes permissions. If issues persist:

```bash
docker compose exec app chown -R www-data:www-data /var/www/html/storage
docker compose exec app chmod -R 775 /var/www/html/storage
```

### Database connection refused

1. Make sure the `mysql` container is running: `docker compose ps`
2. Check that `src/.env` uses the Docker-internal host:
   ```env
   DB_HOST=mysql
   DB_PORT=3306
   ```
   > The compose file sets these automatically via environment variables that override `src/.env`.

### Containers keep restarting

Check the logs for the failing service:

```bash
docker compose logs --tail=50 app
```

### Reset everything from scratch

```bash
docker compose down -v          # removes containers AND volumes (all data)
docker compose up -d --build    # fresh start
```

---

## Quick Reference

| What you want to do | Command |
|---|---|
| Start dev stack | `docker compose up -d --build` |
| Stop dev stack | `docker compose down` |
| View logs | `docker compose logs -f app` |
| Run artisan | `docker compose exec app php artisan <command>` |
| Open shell | `docker compose exec app bash` |
| Add phpMyAdmin | `docker compose --profile tools up -d` |
| Start production | `docker compose -f docker-compose.prod.yml up -d` |
| Nuke everything | `docker compose down -v` |
