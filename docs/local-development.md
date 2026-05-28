# STOCK365 — Local Development Guide

## Quick Start (every time)

```powershell
# Terminal 1 — Apply local settings + run server
php artisan stock365:local          # first time only
npm run rebuild                     # first time or after any CSS change
php artisan serve --host=127.0.0.1 --port=8000

# Terminal 2 — Hot reload (optional but recommended)
npm run dev
```

Open Chrome: `http://127.0.0.1:8000`

---

## Environment Doctor

Before reporting any issue, run:

```powershell
php artisan stock365:doctor
```

It checks everything: APP_URL, APP_ENV, DB connection, Vite build freshness,
CSS integrity (all 12 design-system classes), pending migrations, and `.env.local`.

---

## How Local Env Works

### Files
| File | Purpose | Git |
|---|---|---|
| `.env` | Runtime config (VPS or local) | gitignored |
| `.env.local` | Local overrides, loaded before `.env` | gitignored |
| `.env.local.example` | Template for `.env.local` | committed |

### Loading Order
`bootstrap/app.php` reads `.env.local` with `putenv()` **before** Laravel's
`LoadEnvironmentVariables` bootstrapper runs. Since DotEnv uses immutable
loading, `.env.local` values always win over `.env`.

### One-time setup
```powershell
php artisan stock365:local
```
This reads `.env.local.example`, applies the local overrides to `.env`,
writes `.env.local`, clears all caches, and runs migrations.

### `.env.local.example` contains
```env
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000
```

---

## npm Scripts

| Command | What it does |
|---|---|
| `npm run dev` | Vite dev server with HMR (hot reload) |
| `npm run build` | Production build (with CSS integrity check) |
| `npm run rebuild` | **Clean** build: wipes `public/build`, rebuilds, validates CSS |
| `npm run validate` | Validate existing build without rebuilding |

### When to use `rebuild` vs `build`

Use `npm run rebuild` when:
- First time setting up locally
- You've been developing on VPS and pulling down to local
- The CSS looks wrong (stale styles visible)
- `npm run validate` reports missing classes

Use `npm run build` when:
- You just made a CSS/JS change and want a fresh production build
- You're sure `public/build` is clean

Use `npm run dev` when:
- Actively developing — gives instant HMR without rebuilding

---

## CSS Integrity System

Every `npm run build` and `npm run rebuild` automatically verifies that these
STOCK365 design-system classes exist in the compiled CSS:

```
page-title        page-subtitle      metric-value       metric-label
btn-primary       btn-secondary      btn-ghost          btn-success
btn-danger        shadow-card        glow-emerald       glow-amber
glow-red          form-label         empty-state        animate-status-ring
skeleton
```

If any are missing → **build exits non-zero** and prints the missing classes.

To add new required classes, edit `REQUIRED_CSS` in `vite.config.js`.

---

## The Stale CSS Problem (and why it's solved)

### What was happening
`public/build/` is gitignored. The VPS has its own build. When you clone or
pull locally, `public/build/` either doesn't exist or contains a very old
build compiled before the design system was complete. Running `php artisan serve`
serves that old CSS — you see Bootstrap-era styles instead of the premium design.

### How it's fixed
1. `vite.config.js` now has `build.emptyOutDir: true` — always wipes `public/build` before building
2. `npm run rebuild` script explicitly deletes `public/build` before calling Vite
3. `stock365:doctor` compares `resources/css/app.css` mtime vs build mtime and warns when source is newer
4. The CSS integrity plugin fails fast if any design-system class is missing

### Fix it right now
```powershell
npm run rebuild
```

---

## Chrome Debug Checklist

### Confirm you're seeing the right build

1. `F12` → **Sources** → `127.0.0.1:8000` → `build/assets/app-HASH.css`
2. `Ctrl+F` inside the CSS file, search for: `page-title`
   - **Found** → premium build loaded ✓
   - **Not found** → stale build → run `npm run rebuild`

### Confirm you're on local (not VPS)

Look for the **LOCAL BUILD badge** at the bottom of every page when `APP_ENV=local`.
It shows: server URL, database name, and current time. Fades after 5s.

### Hard refresh to bust browser cache
```
Ctrl+Shift+R   (hard reload, bypasses cache)
Ctrl+Shift+Delete → "Cached images and files" → Clear
```

Or use **Incognito** (`Ctrl+Shift+N`) — always fresh.

### Check Livewire polling
`F12` → **Network** → filter by `livewire` → you should see `POST /livewire/update` every 15–20s.

If requests go to `192.168.100.16` → APP_URL is wrong → run `php artisan stock365:local`.

---

## Troubleshooting

### CSS looks wrong / old styles
```powershell
npm run validate          # check what's missing
npm run rebuild           # clean build
php artisan view:clear    # clear compiled Blade
```

### Livewire 419 / CSRF error
```powershell
php artisan config:clear
php artisan cache:clear
```

### White screen / 500 error
```powershell
php artisan config:clear
php artisan route:clear
php artisan view:clear
Get-Content storage\logs\laravel.log -Wait -Tail 30   # live logs
```

### Migrations out of sync
```powershell
php artisan migrate:status
php artisan migrate
```

### Composer class not found
```powershell
composer dump-autoload
```

### Alpine doesn't respond (x-data inert)
- Means Vite JS isn't loaded. Check `npm run dev` is running or `npm run build` has been run.
- `F12` → Console → should show `Alpine.js v3.x initialized`

---

## Accounts for Local Review

| Role | Email | Password |
|---|---|---|
| Boss | `boss@stock365.com` | `Boss123456` |
| Supervisor | `supervisor@stock365.com` | `123456` |
| Operador (Centenario) | `centenario@stock365.com` | `123456` |
| Operador (Bahía) | `bahia@stock365.com` | `123456` |

---

## Key Routes

```
/dashboard                → Boss: global overview / Operator: live cash engine
/caja/abrir               → Operator: open cash session
/caja/estado              → Operator: session status
/aprobaciones             → Boss/Supervisor: approval hub
/cash-closings/pending    → Boss: approve cash closings
/caja/movimientos         → Cash movements
/finances                 → Financial KPIs
/activity-logs            → Audit trail
/kardex                   → Inventory kardex
/recepciones              → Inventory receipts
/transfers                → Sede transfers
/reports/sales            → Sales report
/sales/history            → Sales history
/suppliers                → Suppliers
```

---

## Poll Rates (for realtime testing)

| Component | Interval | Keep-alive |
|---|---|---|
| Live Cash Box | 15s | ✓ (pauses when tab hidden) |
| Cash Timeline | 20s | ✓ |
| Cache TTL: snapshot | 10s | per session |
| Cache TTL: event stream | 8s | per session |
