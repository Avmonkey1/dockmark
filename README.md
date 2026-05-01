# Dockmark

Dockmark is a small self-hosted start page with a visual admin panel. It is meant to feel like a polished private dashboard while still producing portable config files for self-hosted dashboard tools like Glance and Homepage.

## What it does

- Adds and stores bookmark groups in `data/bookmarks.json`
- Organizes links by page, group, icon, note, and accent color
- Adds tags and favicon previews for faster scanning
- Supports drag-and-drop ordering for groups and links
- Imports browser bookmark HTML exports
- Restores Dockmark JSON backups
- Imports Glance YAML, Homepage YAML, Start.me CSV, and browser/Start.me bookmark HTML
- Exports Glance YAML, Homepage YAML, Start.me CSV, bookmark HTML, and JSON backup
- Installs as a lightweight PWA on mobile/desktop
- Provides a polished dashboard UI inspired by the William Lodge visual style
- Exports:
  - `export.php?format=glance` for Glance-style YAML
  - `export.php?format=homepage` for Homepage bookmark YAML
  - `export.php?format=json` for a full backup
- Includes dark and light theme support
- Includes built-in widgets for styled clocks, Open-Meteo weather, GitHub repo watching, and RSS/reddit feeds
- Supports Google account-slot hints on links, such as `authuser=1` and `/u/1/`, for multi-account Google workflows

## Phase 2 additions

- `extensions/chrome/` contains the first Dockmark Clipper extension scaffold.
- `website/` contains a static project website for a public repo/demo.
- `docs/CLAW.md` describes the cleanup/discovery agent direction.
- `docs/MOBILE_APPS.md` describes the PWA-first mobile plan.

## Run locally

```powershell
php -S 127.0.0.1:8088 -t .
```

Then open:

```text
http://127.0.0.1:8088
```

## Deploy

Upload this folder to a PHP-capable subdomain such as:

```text
start.williamlodge.com
desk.williamlodge.com
dock.yourdomain.com
```

Make sure `data/bookmarks.json` is writable by PHP.

Before the app is public, copy:

```powershell
Copy-Item config.example.php config.php
```

Then change `LODGEBOARD_ADMIN_PASSWORD` inside `config.php`.

For VPS notes, see `DEPLOY.md`.

## Next upgrades

- Add widgets for weather, RSS, uptime checks, and notes
- Add a widget adapter API
- Add link audit through Claw
- Add one-command Docker install
- Add a one-click GitHub release workflow
