# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**Lantaka Reservation System** — a Laravel 9 web app for managing room/venue reservations, food orders, and guest management for a facility (likely a hotel/retreat center). Roles: `admin`, `staff`, `client`.

## Commands

```bash
# Install dependencies
composer install
npm install

# Setup
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed          # seeds initial accounts via UserSeeder
php artisan storage:link     # required for local file uploads

# Development
npm run dev                  # Vite dev server (hot reload)
php artisan serve            # Laravel dev server

# Build for production
npm run build

# Run tests
php artisan test
php artisan test --filter TestName   # single test

# Code style (Laravel Pint)
./vendor/bin/pint

# Generate PDF exports
# Uses barryvdh/laravel-dompdf — no extra setup needed

# Excel exports
# Uses maatwebsite/excel — no extra setup needed
```

## Architecture

### Authentication & Roles

- Auth model is `Account` (table: `Account`, PK: `Account_ID`) — **not** the default Laravel `User` model.
- Roles are stored in `Account_Role` field: `admin`, `staff`, `client`.
- Role-based access is enforced via the `CheckRole` middleware (`app/Http/Middleware/Checkrole.php`), registered as `role` in `Kernel.php`.
- Account types (`Account_Type`): `Internal` vs external — affects pricing (internal clients get `Room_Internal_Price`).
- Client accounts require admin approval (`Account_Status`: `pending` → `active`/`declined`/`deactivate`).

### Route Structure (`routes/web.php`)

| Prefix | Middleware | Who |
|---|---|---|
| `/employee` | `role:admin,staff` | Employee dashboard, reservations, guests |
| `/employee` (nested) | `role:admin` | Accounts CRUD, Room/Venue/Food CRUD, mark-unpaid |
| `/client` | `auth`, `role:client` | My bookings, food options, account |
| Public | none | Room/venue browsing, login, signup |

### Reservation Flow

Reservations are split across two tables — `Room_Reservation` and `Venue_Reservation` — rather than a single polymorphic table. `FoodReservation` links to either via separate FKs.

**Client booking flow:**
1. Browse rooms/venues → `RoomVenueController@show`
2. Add to session cart (`pending_bookings` session key) → `ReservationController@checkout`
3. Select food options → `client/food_option` view
4. Store → `ReservationController@store` (creates both reservation + food reservation rows)

**Employee booking flow:**
1. `employee/create_reservation` → select client + accommodation
2. `employee/create_food_reservation` → attach food
3. `ReservationController@storeReservation` — unified store for employee-created reservations

**Cancellation/Change Request flow:** Requests are stored as columns directly on `Room_Reservation`/`VenueReservation` rows (no separate table), under `cancellation_*` and `change_request_*` column groups.

### Key Controllers

- `ReservationController` — the largest file (~4000+ lines). Handles the full lifecycle: checkout, cart, store, status updates, calendar, SOA export, cancellation/change requests, analytics.
- `RoomVenueController` — room/venue CRUD and browsing.
- `FoodController` — individual food items + food sets (grouped meal packages).
- `AccountController` — admin account management + client self-service.
- `EventLogController` — audit log viewer.
- `NotificationController` — in-app notifications (mark read/all-read).

### Models & DB Conventions

- Table and column names use **PascalCase with underscores** (e.g., `Room_Reservation`, `Room_Reservation_Check_In_Time`) — not Laravel's default snake_case. Always set `$table` and `$primaryKey` explicitly in models.
- `FoodSet` stores `meal_time` and `purpose` as JSON columns.
- `change_request_details` on reservation models is cast to `array`.

### Frontend

- **No CSS framework** (no Bootstrap/Tailwind). All CSS is custom, per-page files in `public/css/` (legacy) and `resources/css/` (Vite-managed).
- Vite (`vite.config.js`) auto-discovers all `.css` and `.js` files under `resources/css/` and `resources/js/` — no manual entry point registration needed.
- JS files in `resources/js/employee/` serve the employee-side views; top-level JS files serve client-side views.
- Icons via `lucide` npm package; date handling via `dayjs`.

### File Storage / Media

- Use the `media_url($path)` and `media_disk()` helpers (defined in `app/helpers.php`) for all media URLs — never `asset('storage/...')` directly.
- `MEDIA_DISK=public` locally (storage symlink), `MEDIA_DISK=s3` in production (S3/Laravel Cloud).

### Mail

Transactional emails are in `app/Mail/`. Triggered from `ReservationController` and `AccountController` on status changes (confirmed, checked-in, cancelled, rejected, account approved/declined).

### Exports

- **SOA (Statement of Account)**: Excel export via `Maatwebsite\Excel` (`app/Exports/SOATemplateExport.php`).
- **Calendar PDF/CSV**: Generated in `ReservationController@exportCalendarPDF` / `exportCalendarCSV`.
- **PDF generation**: `barryvdh/laravel-dompdf`, views in `resources/views/pdf/` and `resources/views/employee/pdf/`.

### Database

- Default `.env.example` uses MySQL. The project targets PostgreSQL in production (composer platform config lists `ext-pgsql`). Set `DB_CONNECTION=pgsql` for Postgres.
- Migrations are ordered by date prefix. The `add_notes_to_reservations` migration (`2026_04_08_...`) is currently uncommitted/pending.
