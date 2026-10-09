# Matrimony Platform — Backend (Laravel + MySQL)

A REST API for a trustworthy, Bangladesh-focused matrimony platform, implemented
from [`docs/matrimony_platform_blueprint_v2.md`](docs/matrimony_platform_blueprint_v2.md).

**Stack:** Laravel 13 · MySQL · Sanctum (token auth) · REST, versioned at `/api/v1`.

## Getting started

```bash
cp .env.example .env            # already configured for MySQL `matrimony`
php artisan key:generate        # if needed
php artisan migrate:fresh --seed
php artisan serve
```

The `.env` expects a MySQL database named `matrimony`:

```bash
mysql -u root -p -e "CREATE DATABASE matrimony CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

Seeding creates the lookup data (BD divisions + 64 districts, religions,
professions, education levels) and an admin account:
`admin@matrimony.test` / `password`.

## Tests

```bash
php artisan test
```

Tests run against an in-memory SQLite database and cover registration rules,
the core loop, matching, privacy, and the relative blocklist.

## Architecture

Domain rules live in services, never in controllers (blueprint §32).

```
Controllers (thin)  →  Services (domain logic)  →  Models  →  MySQL
       ↓
 Form Requests (validation) · Policies (authorization) · Resources (output shaping)
```

Key services (`app/Services`):

| Service | Responsibility | Blueprint |
|---|---|---|
| `ProfileService` | Create/update profiles + self-manager + privacy rows | §4, §5 |
| `ProfileCompletionService` | Weighted profile-completion % | §18 |
| `MatchingService` | Compatibility **score + explanation** (transparent matching) | D3, §6 |
| `DiscoveryService` | Filtered discovery query with all exclusions | §7, §25 |
| `InterestService` | Interest state machine, quota, cooldown, auto-accept | §8 |
| `ContactBlockService` | Relative blocklist (phone hashing) | D2, §11 |
| `PrivacyResolver` | Per-field visibility enforcement | §11 |
| `EntitlementService` | Free-plan quotas (anti-spam / future paid) | §14 |
| `NotificationService` | In-app notifications with per-type prefs | §17 |

### Differentiators implemented

- **D2 — Privacy from relatives:** phone numbers are normalized to E.164 and
  stored only as a salted HMAC (`users.phone_hash`, `profile_contact_blocks`).
  The exclusion runs inside every discovery query, in both directions, silently.
- **D3 — Transparent matching:** `MatchingService` returns a score plus a
  `met` / `missed` breakdown and the must-have tally, surfaced on profile cards
  and detail.

### Domain rules you can tune

See [`config/matrimony.php`](config/matrimony.php): minimum marriage age, interest
expiry, rejection/disconnect cooldowns, free-plan quotas, deal-breaker penalty,
photo limits.

## API surface (`/api/v1`)

Run `php artisan route:list --path=api` for the full list. Highlights:

```
POST   /auth/register            # age (18 F / 21 M) + one-account-per-phone enforced
POST   /auth/login               # email or phone
GET    /profile  POST /profile  PUT /profile  PATCH /profile/status  DELETE /profile
GET    /preferences  PUT /preferences          # with deal-breaker weights (D3)
GET    /profiles                 # discovery, cursor-paginated, with match scores
GET    /profiles/{publicId}      # 404 for hidden/deleted
POST   /interests  PATCH /interests/{id}        # accept|reject|withdraw|disconnect
POST   /contact-blocks           # relative blocklist (D2)
POST   /photos  GET /photos/{photo} (signed)    # blurred by default, signed URLs
GET    /conversations/{id}/messages  POST …     # masks phone/email/handles
GET    /dashboard · /notifications · /shortlists · /blocks · /reports
```

## Database

19 domain tables plus Laravel defaults. The schema follows the blueprint's
normalization fixes (§13): searchable fields kept on `profiles` and indexed,
`interests` + append-only `interest_events`, ordered-pair `conversations`, soft
deletes on profiles/photos/messages.

## Status vs. the blueprint phases

Implemented end-to-end: Foundation, Auth, Profile, Discovery, Matching, Interest
& shortlist, Messaging (REST + masking), Notifications, Privacy/blocklist, Photos
(upload, signed URLs, request flow), Reports/blocks, Dashboard. The schema and
models for Verification, Entitlements, Audit logs and Managed/guardian profiles
are in place for the remaining phases (biodata import/export, full admin
moderation, WebSockets) to build on.
