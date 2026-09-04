# Golyv Fleet Management / Bus Booking System

Senior Full Stack assessment submission: Laravel API + PostgreSQL + Next.js TypeScript UI for segment-aware bus seat booking across Egypt routes.

## Stack

| Layer | Technology |
|-------|------------|
| Backend | Laravel 13 (PHP 8.3+), PostgreSQL 16 |
| Frontend | Next.js 16 (App Router), React 19, TypeScript, Tailwind CSS, shadcn/ui |
| Tests | PHPUnit (backend), Vitest + Testing Library (frontend) |
| Tooling | Docker Compose, OpenAPI + Postman, GitHub Actions CI |

## Environment requirements

### Backend (local)

- PHP **8.3+** with `pdo_pgsql` (and Composer 2)
- PostgreSQL **16+** (or run Postgres via Docker only)

### Frontend (local)

- Node.js **22+**
- npm (lockfile is npm; use `--legacy-peer-deps` on install)

### Docker alternative

Docker and Docker Compose are enough to run the full stack without installing PHP, Node, or Postgres on the host.

## Install and run

### Quick start with Docker (recommended)

```bash
docker compose up --build
```

| Service | URL / connection |
|---------|------------------|
| API (nginx → PHP-FPM) | http://localhost:8000 |
| Swagger UI | http://localhost:8000/api/documentation |
| Frontend | http://localhost:43123 |
| Postgres | `localhost:5432` — user `fleet`, password `fleet_secret`, database `fleet_booking` |

On boot the backend container runs `composer install`, generates the app key, runs migrations, and generates OpenAPI docs. **It does not seed sample data.** After the stack is healthy:

```bash
docker compose exec backend php artisan db:seed
```

To reset schema and seed in one step:

```bash
docker compose exec backend php artisan migrate:fresh --seed --force
```

### Local development (without full Docker stack)

You can still use Docker for Postgres only:

```bash
docker compose up -d postgres
```

#### Backend

```bash
cd backend
cp .env.example .env
composer install
php artisan key:generate
php artisan migrate --seed
php artisan serve --port=8000
```

API: http://localhost:8000

#### Frontend

```bash
cd frontend
cp .env.example .env.local
npm install --legacy-peer-deps
npm run dev -- --port 43123
```

UI: http://localhost:43123

Ensure `NEXT_PUBLIC_API_URL` in `.env.local` is `http://localhost:8000/api` (see [How the frontend connects to the backend](#how-the-frontend-connects-to-the-backend)).

## Database setup

Default connection values (from [`backend/.env.example`](backend/.env.example) and Compose):

| Variable | Default |
|----------|---------|
| `DB_CONNECTION` | `pgsql` |
| `DB_HOST` | `127.0.0.1` locally; `postgres` inside Docker |
| `DB_PORT` | `5432` |
| `DB_DATABASE` | `fleet_booking` |
| `DB_USERNAME` | `fleet` |
| `DB_PASSWORD` | `fleet_secret` |

Also set:

- `APP_URL` — e.g. `http://localhost:8000`
- `FRONTEND_URL` — e.g. `http://localhost:43123` (used for CORS on `api/*`)

Create the database user/DB to match these credentials, or start the Compose Postgres service as above.

## Migrations and seeders

From `backend/`:

```bash
php artisan migrate              # apply pending migrations
php artisan db:seed              # run DatabaseSeeder
php artisan migrate --seed       # migrate then seed
php artisan migrate:fresh --seed # drop all tables, re-migrate, seed
```

### Domain tables (migration order)

| Migration | Table |
|-----------|--------|
| `2026_03_02_000001_create_stations_table` | `stations` |
| `2026_03_02_000002_create_buses_table` | `buses` |
| `2026_03_02_000003_create_seats_table` | `seats` |
| `2026_03_02_000004_create_trips_table` | `trips` |
| `2026_03_02_000005_create_trip_station_table` | `trip_station` (ordered stops) |
| `2026_03_02_000006_create_bookings_table` | `bookings` |

### Seeded sample data

[`DatabaseSeeder`](backend/database/seeders/DatabaseSeeder.php) creates:

- **Stations:** Cairo (CAI), Giza (GIZ), Al Fayyum (FAY), Al Minya (MIN), Asyut (ASY)
- **Trip 1** — Golyv Express 12 (12 seats): Cairo → Al Fayyum → Al Minya → Asyut
- **Trip 2** — Nile Connector (12 seats): Cairo → Giza → Al Minya → Asyut
- **Sample booking:** Trip 1, seat 5, Cairo → Al Minya

## Automated tests

### Backend (PHPUnit)

```bash
cd backend
php artisan test
# or: composer test
```

PHPUnit is configured in [`backend/phpunit.xml`](backend/phpunit.xml) to use **SQLite in-memory** (`DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`), so tests do not need Postgres. Feature coverage lives mainly in `tests/Feature/BookingApiTest.php` (trips, availability, booking, overlaps, validation, rate limiting).

### Frontend (Vitest)

```bash
cd frontend
npm test
# watch mode: npm run test:watch
```

## API documentation

| Resource | Location |
|----------|----------|
| OpenAPI spec | [`docs/openapi.yaml`](docs/openapi.yaml) |
| Postman collection | [`docs/Golyv-Fleet-Booking.postman_collection.json`](docs/Golyv-Fleet-Booking.postman_collection.json) |
| Interactive Swagger UI | http://localhost:8000/api/documentation (when the API is running) |

There is **no authentication** on these endpoints. Bookings store passenger name and email only.

`POST /api/bookings` is rate-limited to **3 requests per minute per IP**. Excess requests return **429**.

## How to test / use the APIs

Base URL: `http://localhost:8000/api`

| Method | Path | Description |
|--------|------|-------------|
| `GET` | `/trips` | List trips with ordered stations and bus seat count |
| `GET` | `/trips/{id}` | Trip detail |
| `GET` | `/trips/{id}/available-seats` | Seat availability for a segment |
| `POST` | `/bookings` | Book a seat for a segment |

### Query / body parameters

**Available seats** — required query params:

- `start_station_id` (integer)
- `end_station_id` (integer; must be a later stop on the same trip)

**Create booking** — JSON body:

```json
{
  "trip_id": 1,
  "seat_id": 1,
  "start_station_id": 1,
  "end_station_id": 4,
  "customer_name": "Youssof Okiel",
  "customer_email": "youssof@example.com"
}
```

### Status codes

| Code | Meaning |
|------|---------|
| `200` | Success (list/detail/availability) |
| `201` | Booking created |
| `404` | Trip not found |
| `409` | Seat unavailable for that segment (`error: seat_unavailable`) |
| `422` | Validation or invalid trip segment (`error: invalid_trip_segment`) |
| `429` | Booking rate limit exceeded |

### Example requests

List trips:

```bash
curl -s http://localhost:8000/api/trips | jq
```

Available seats (adjust IDs after seeding):

```bash
curl -s "http://localhost:8000/api/trips/1/available-seats?start_station_id=1&end_station_id=4" | jq
```

Create a booking:

```bash
curl -s -X POST http://localhost:8000/api/bookings \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "trip_id": 1,
    "seat_id": 1,
    "start_station_id": 1,
    "end_station_id": 4,
    "customer_name": "Youssof Okiel",
    "customer_email": "youssof@example.com"
  }' | jq
```

## How to install and run the frontend

See [Install and run](#install-and-run) for Docker and local steps. Summary for frontend only:

```bash
cd frontend
cp .env.example .env.local
npm install --legacy-peer-deps
npm run dev -- --port 43123
```

| Script | Purpose |
|--------|---------|
| `npm run dev` | Development server (use `--port 43123`) |
| `npm run build` / `npm start` | Production build and serve |
| `npm run lint` | ESLint |
| `npm test` | Vitest |

## How the frontend connects to the backend

1. **Env:** `NEXT_PUBLIC_API_URL` defaults to `http://localhost:8000/api` ([`frontend/.env.example`](frontend/.env.example)). Compose injects the same value for the frontend service.
2. **Client:** [`frontend/src/lib/api/client.ts`](frontend/src/lib/api/client.ts) uses native `fetch` (JSON, `cache: "no-store"`) and exposes:
   - `fetchTrips()` → `GET /trips`
   - `fetchAvailableSeats(tripId, start, end)` → `GET /trips/{id}/available-seats?...`
   - `createBooking(payload)` → `POST /bookings`
3. **No Next.js rewrite/proxy** — the browser calls the Laravel API directly. Backend CORS allows `FRONTEND_URL` (default `http://localhost:43123`).
4. **UX:** [`BookingWorkbench`](frontend/src/components/BookingWorkbench.tsx) loads trips, lets the user pick a segment, shows a seat map, and books with name/email. On success or `409`, it refreshes availability so the API remains the source of truth.

## Architectural and technical decisions

### Backend

**Segment overlap model.** Stops are ordered by `sequence` on `trip_station`. A booking occupies the half-open interval `[start_sequence, end_sequence)`. Two bookings on the same trip and seat conflict when:

```text
A.start < B.end AND B.start < A.end
```

So Cairo → Minya and Minya → Asyut on the same seat are allowed; overlapping segments are not.

**Concurrency.** [`BookingService`](backend/app/Services/BookingService.php) runs inside a DB transaction and uses `lockForUpdate()` on the trip, seat, and existing bookings for that trip+seat. Availability is re-checked under the lock before insert. Conflicts return HTTP `409` with `error: seat_unavailable`.

**Denormalized sequences.** Bookings store both station IDs and `start_sequence` / `end_sequence` so overlap checks do not re-join the pivot on every read.

**Auth.** Passenger name + email only; JWT/OAuth omitted to keep the assessment focused on booking integrity.

### Frontend

**App Router, single workbench.** One page drives the booking flow (`src/app/page.tsx` → `BookingWorkbench`).

**Thin typed API layer.** No React Query/SWR/axios — local React state plus the small `client.ts` wrapper and `ApiError` for status/body handling.

**Server as source of truth.** After book or conflict (`409`), the UI reloads seats and clears selection rather than trusting optimistic local availability.

**Segment-aware station pickers.** End-station options are filtered by higher `sequence` so the UI mirrors the backend segment rules.

**UI kit.** Tailwind CSS v4 and shadcn/ui primitives for forms, alerts, and the seat map.

## Scalability notes

- Cache trip/station catalogs; do not cache live seat availability without invalidation
- Consider idempotency keys on `POST /bookings` for safe client retries
- Scale API instances behind a Postgres connection pooler (e.g. PgBouncer)

## Author

Youssof Okiel — Golyv Senior Full Stack Engineer technical assessment.

