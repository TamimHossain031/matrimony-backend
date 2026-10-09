# Matrimony Platform — Product & Feature Blueprint (v2, revised)

**Project purpose:** A modern matrimony platform built primarily as a backend-practice project, while keeping a realistic, production-style user experience.

**What changed in v2:** This revision keeps the original architecture (it was sound) and adds the parts that were missing for the Bangladeshi market — guardian roles, photo protection, exit flows, anti-abuse, localization — fixes the data model problems that would have hurt search performance, and adds a differentiation strategy so the product has a reason to exist next to Shaadi, Bibaho and the rest.

**Primary goals**

1. Practice real backend architecture: authentication, authorization, relational database design, APIs, validation, search, filtering, privacy, moderation, notifications, analytics.
2. Build a modern frontend with a simple, trustworthy, mobile-first UX.
3. Keep the architecture modular so the project can grow from an MVP into a production-style application.

---

## 1. Product Vision

A trustworthy matrimony platform where users can:

- Create a detailed marriage profile (themselves, or a guardian on their behalf).
- Discover compatible profiles.
- Search and filter candidates.
- Express interest privately.
- Accept/reject interests.
- Communicate after mutual interest.
- Control who can see sensitive information — including hiding from specific people they know.
- Report/block users.
- Manage profile verification and privacy.
- Close the profile gracefully when they find a match.

### Core UX principles

- **Trust first:** verification ladder, reporting, privacy controls, clear profile information.
- **Low friction:** onboarding should feel like a guided profile setup, not a long form.
- **Intentional discovery:** curated, relevant matches instead of endless random scrolling.
- **Privacy by design:** sensitive contact information is never publicly exposed, and the backend — not React — enforces it.
- **Family-aware:** the product assumes family involvement instead of pretending it doesn't exist.
- **Mobile first:** every important action works comfortably on a small screen.
- **Clear states:** every action has obvious loading, success, empty, and error states.

---

## 2. Differentiation Strategy

Most matrimony sites in this market look identical: a biodata form, a filter panel, a paywall on contact details, and a lot of fake profiles. The blueprint below assumes **three** differentiators are built into the MVP, and the rest are kept as a later roadmap.

### Core differentiators (build these)

**D1. Biodata import and export**

Families already pass around a biodata PDF or image on WhatsApp. Let a user upload theirs and auto-fill the profile, and let them export a clean, printable biodata PDF generated from their profile.

- Backend value: file parsing, queued jobs, PDF generation, mapping unstructured input to a schema.
- Market value: nobody does this well; it matches how people actually behave.

**D2. Privacy from relatives**

The biggest fear is "someone in my family will see my profile." Let a user enter or upload phone numbers that must never see them. Any account registered with those numbers is excluded from discovery, search, and recommendations for that profile — silently, in both directions.

- Backend value: exclusion rules inside every discovery query, hashing phone numbers at rest.
- Market value: this is the single feature that makes women comfortable signing up.

**D3. Transparent matching**

Every competitor hides its algorithm. Show yours. Let users mark each preference as a **deal-breaker** or **nice-to-have**, weight the score accordingly, and display the breakdown:

```text
Match 82%  ·  7 of your 9 must-haves
Missed: location (Rajshahi, you prefer Dhaka), height
```

- Backend value: the `MatchingService` already computes this; you are just returning the explanation object instead of a bare number.
- Market value: honest, and genuinely more useful than a mystery percentage.

### Roadmap differentiators (not in MVP)

- **D4. Daily curated matches** — 5–10 quality matches per day instead of infinite browsing. Cuts spam, protects women from mass interest-blasting, and is a cacheable nightly job.
- **D5. Human profiles** — three short prompt answers ("What does a good Friday look like for you?") so profiles read like people, not CVs.
- **D6. No ghosting** — interests auto-expire after 7 days; each profile shows a response rate.
- **D7. Guardian co-pilot mode** — a parent or sibling gets a linked login with limited permissions and a shared shortlist.
- **D8. Verified matchmaker (ghotok) role** — real ghotoks exist and families trust them; make them a first-class role that manages client profiles with consent.
- **D9. Free contact, paid boost** — competitors paywall contact details, which is exactly where users feel cheated. Charge for verification and visibility instead.

---

## 3. MVP Scope

### A. Authentication

**Registration**

- Name, email, phone, password + confirmation
- Date of birth
- Gender
- **Profile created by:** self / parent / sibling / relative / guardian
- Terms acceptance

**Hard validation rules**

- Minimum legal marriage age in Bangladesh: **18 for women, 21 for men**. Enforce at registration, server-side.
- One active account per phone number (unique index, normalized to E.164).

**Login**

- Email/phone + password
- Session handling, logout
- Rate limiting on login, OTP request, and password reset

**Account recovery**

- Forgot password, reset password
- Email/OTP verification
- Change password

**Roles**

- `user`
- `guardian` (manages another user's profile — see §5)
- `moderator`
- `admin`

---

## 4. Profile System

Account and matrimony profile stay separate concepts.

**Account** — authentication, credentials, role, account status.
**Profile** — personal information, education, career, family, lifestyle, religion detail, preferences, photos, privacy settings.

### Account lifecycle

```text
active → hidden → deactivated → deleted
```

- **hidden** — invisible in discovery, existing connections still work. Used by "I'm engaged".
- **deactivated** — user-initiated pause, login still possible.
- **deleted** — soft delete with a retention window, then hard purge of photos and personal data.

This was missing entirely in v1 and it touches every discovery query, so build it early.

### Basic profile fields

- Full name (English + Bangla)
- Profile ID (public, non-sequential)
- Gender
- Date of birth (**stored and indexed; age is always computed, never stored**)
- Height
- Marital status
- District / upazila (from the lookup table)
- Nationality
- Profile headline
- About me

### Education

- Highest education, institution, subject/major, graduation year

### Career

- Profession (lookup), job title, organization, income range, work location

### Family

- Father's occupation, mother's occupation, siblings, family type, family location, short description

### Lifestyle

- Smoking, drinking, diet, hobbies, languages

### Religion detail (new in v2)

A single flat `religion` field is not enough for this market. Model religion-specific attributes as a grouped attribute set keyed by religion, not as twenty extra columns.

- **Muslim:** maslak/sect, prayer habit, hijab/purdah, beard, Hifz
- **Hindu:** caste, gotra, horoscope details if the user wants them
- **Other / prefer not to say** must remain valid options

Store as a `profile_religion_attributes` table (`profile_id`, `key`, `value`) or a validated JSON column, with the allowed key set defined per religion in code.

---

## 5. Guardian & Managed Profiles (new in v2)

In this market most profiles are created and managed by a parent, sibling, or relative. Model it properly:

```text
Profile
  └── profile_managers
        ├── user_id
        ├── relationship (self | parent | sibling | relative)
        └── permissions (edit, shortlist, send_interest, chat)
```

Rules:

- Every profile has exactly one `self` manager (the candidate) unless it is explicitly a guardian-managed profile.
- Permissions are checked by policy, never by role name alone.
- The profile card publicly shows **who manages it** ("Profile managed by brother") — this is trust information, not a secret.
- A guardian cannot chat if the candidate has disabled it.

---

## 6. Partner Preference

### Preference fields

Age range, height range, marital status, religion, education, profession, income range, district, lifestyle, other expectations.

### Deal-breaker vs nice-to-have (new in v2)

Each preference carries a weight class:

```text
preference_key | value        | importance
age_range      | 24-29        | deal_breaker
district       | Dhaka        | nice_to_have
religion       | Islam        | deal_breaker
```

- Deal-breaker failures remove or heavily penalize a profile.
- Nice-to-have failures only reduce score.
- The breakdown is returned to the frontend for D3.

### Architecture decision (unchanged, still correct)

Never hard-code matching rules in controllers.

```text
Profile → PartnerPreference → MatchingService → CompatibilityScore + Explanation → RecommendedProfiles
```

---

## 7. Profile Discovery

### Search filters

Gender, age range, district, religion (+ religion attributes), marital status, education, profession, income, height, verification level, "has photo", "active in last 30 days".

### Sorting

Newest, recently active, most relevant, verification level.

### Query rules (new in v2)

- **Cursor pagination, not offset.** New signups shift rows, and offset pagination makes users see duplicates and skips across pages.
- **Exclusions applied to every discovery query:** blocked both ways, relative-blocklist (D2), already rejected, hidden/deactivated/deleted accounts, own profile, same gender unless the search explicitly allows it.
- **Saved searches** — store the filter set, reuse it on the dashboard.

### Profile card

Photo (blurred unless approved), name or initials, age, district, profession, education, headline, verification badge, match score, interest status.

Never put sensitive fields in list responses. Shape the API resource for the card; don't return the full profile model.

---

## 8. Interest System

```text
User A → Send Interest → User B
                           ↓
                    Accept / Reject
                           ↓
                   Mutual connection
                           ↓
                         Chat
                           ↓
                   Disconnect (either side)
```

### States

`pending` · `accepted` · `rejected` · `withdrawn` · `expired` · `disconnected`

### Rules

- No duplicate active interest between the same pair.
- A blocked user cannot send an interest.
- **Cooldown after rejection** (e.g. 30 days) before the same sender may try again.
- **Simultaneous interest auto-accepts.** If A and B each send before seeing the other's, the second one should create a mutual connection inside a transaction. Decide this at the model level, not in a controller.
- **Daily interest quota** per account (anti-spam, and the hook for a future paid plan).
- Only accepted connections can chat.
- **Disconnect** ends an accepted connection, closes the conversation, and does not allow a new interest for a cooldown period.
- History preserved — see the schema note in §13.

---

## 9. Shortlist / Favorites

Add, remove, list, private notes. Always private to the owner. A guardian with `shortlist` permission shares the candidate's shortlist.

---

## 10. Messaging

Available only after mutual interest. Start with plain REST + database-backed messages. WebSockets later.

**MVP:** one-to-one conversation, text, read/unread, timestamps, conversation list, block, report, delete conversation.

**Moderation (new in v2):** detect phone numbers, emails, and social handles inside message bodies. Users routinely bypass the privacy system this way. Decide the policy — mask, warn, or flag for review — and enforce it server-side before persisting.

**Future:** attachments, voice messages, typing indicator, online status, reactions.

---

## 11. Privacy System

### Sensitive fields

Phone, email, exact address, family contact information, private photos.

### Visibility levels

`public` · `members_only` · `accepted_connection` · `private`

### Relative blocklist (D2)

```text
profile_contact_blocks
  ├── profile_id
  ├── phone_hash   (hashed, never stored raw)
  └── created_at
```

Applied as an exclusion in every discovery, search, and recommendation query — in both directions, silently. Neither side ever learns they were excluded.

**The backend enforces all of this.** Hiding a field in React is not security.

---

## 12. Photo System

Upload, set primary, delete, reorder, visibility control.

### Protection (new in v2 — this was only named, never specified)

- Photos **blurred by default** for non-connections.
- **Photo request flow:** viewer requests → owner approves/denies → viewer sees the unblurred image.
- **Per-viewer watermark** containing the viewer's profile ID, so a misused screenshot is traceable.
- Signed, expiring URLs. Private photos must not be reachable by guessing a path.

### Backend requirements

File type / size / dimension validation, safe file naming, storage abstraction, orphan file cleanup, moderation status (`pending` / `approved` / `rejected`), image optimization in a queued job.

---

## 13. Database Architecture

### Core tables

```text
users
profile_managers
profiles
profile_religion_attributes
profile_photos
photo_requests
educations
partner_preferences
preference_weights
interests
interest_events
shortlists
conversations
messages
notifications
notification_preferences
profile_views
blocks
profile_contact_blocks
reports
verifications
privacy_settings
entitlements
audit_logs
```

### Lookup tables — mandatory, not optional

```text
locations (division → district → upazila)
religions
professions
education_levels
```

Free-text profession means filtering is impossible forever. Make these real tables from day one.

### Normalization fixes (v1 problems)

- **Drop the one-to-one tables.** `careers`, `families`, `lifestyles` as separate tables buy nothing and cost a join each. Keep them as columns on `profiles` (or a validated JSON column for lifestyle). Only genuinely multi-row data gets its own table: photos, education history.
- **Keep every searchable field on `profiles`**, indexed. A filter query joining six tables with ten conditions is the query that will kill this project. If the profile table grows too wide, build a denormalized `profile_search` projection table updated by a model event, and query that.
- **`interests` + `interest_events`.** "No duplicate active interest" and "preserve rejected/withdrawn history" cannot both hold with a plain unique key on `(sender_id, receiver_id)`. Keep one current-state row per pair plus an append-only `interest_events` history table.
- **`conversations` store an ordered pair** — `user_one_id < user_two_id` with a unique index — or you will create duplicate threads.
- **Soft deletes** on profiles, photos, messages. Moderation needs the history.

### Relationships

```text
User
 ├── Profile (via profile_managers)
 │    ├── Photos
 │    ├── Education
 │    ├── ReligionAttributes
 │    ├── PartnerPreference → PreferenceWeights
 │    ├── PrivacySettings
 │    └── ContactBlocks
 ├── Interests → InterestEvents
 ├── Shortlists
 ├── Conversations → Messages
 ├── Notifications
 ├── ProfileViews
 ├── Blocks
 └── Reports
```

---

## 14. Entitlements (new in v2)

Do **not** build a payment gateway in the MVP. Do build the abstraction, because retrofitting it later touches every endpoint:

```text
entitlements
  ├── user_id
  ├── plan (free)
  ├── daily_interest_limit
  ├── daily_contact_view_limit
  └── expires_at
```

Everyone sits on `free`. The quotas double as your anti-spam system today, and become the paid tier later.

---

## 15. Verification

A verification **ladder**, shown as a level — not a single on/off badge:

```text
Level 0  Unverified
Level 1  Phone + email verified
Level 2  NID verified
Level 3  Selfie matched to NID photo
Level 4  Manually reviewed by admin
```

Users can filter discovery by minimum level. Verification documents are never public, never returned by any user-facing API, and are purged after review.

---

## 16. Safety & Moderation

**Block:** cannot send interest, cannot message, disappears from discovery both ways.

**Report reasons:** fake profile, harassment, scam, inappropriate content, misleading information, other.

**Admin can:** review reports, suspend, ban, restore, review profiles and photos, manage verification, view moderation history, view audit logs.

**Abuse controls (new in v2):** daily interest quota, rejection cooldown, one account per phone, contact-info detection in chat, report-rate monitoring per account, and rate limits on search and photo requests.

---

## 17. Notifications

**Types:** new interest, interest accepted, interest rejected, interest expiring, new message, photo request, verification update, report status, system announcement.

**Model:** `type`, `title`, `message`, `user_id`, `data`, `read_at`, `created_at`.

**New in v2:**

- `notification_preferences` per type and channel.
- **Throttling and digests.** One popular profile generates fifty notifications a day. Batch low-priority types into a daily digest.

**Future channels:** email, push, SMS.

---

## 18. Dashboard

**User:** profile completion, new interests, accepted connections, shortlist, recommended matches with score breakdown, unread messages, verification level, recent viewers, saved searches.

**Profile completion** is computed by a service, never maintained by hand in several places.

**Admin:** total/active/new/verified users, new profiles, pending reports, suspended accounts, interests sent, successful connections, report resolution time.

---

## 19. Localization (new in v2)

- Bangla and English UI from the start — wire up i18n before writing fifty components.
- Bangla name and address fields alongside English.
- Bangladeshi division → district → upazila taxonomy, not a free-text city box.
- BDT formatting for income ranges.
- Bangla search: decide collation and whether to support transliterated input. This is a genuinely interesting backend problem.

---

## 20. API Architecture

REST, versioned, clear resource boundaries.

```text
/api/v1/auth
/api/v1/profile
/api/v1/profiles
/api/v1/managers
/api/v1/preferences
/api/v1/interests
/api/v1/shortlists
/api/v1/conversations
/api/v1/messages
/api/v1/photos
/api/v1/photo-requests
/api/v1/notifications
/api/v1/reports
/api/v1/blocks
/api/v1/contact-blocks
/api/v1/verifications
/api/v1/biodata
```

### Example endpoints

```http
POST   /api/v1/auth/register
POST   /api/v1/auth/login
POST   /api/v1/auth/logout
POST   /api/v1/auth/forgot-password

GET    /api/v1/profile
PUT    /api/v1/profile
PATCH  /api/v1/profile/status          # active | hidden | deactivated
DELETE /api/v1/profile

GET    /api/v1/profiles                # cursor paginated
GET    /api/v1/profiles/{profileId}

POST   /api/v1/interests
GET    /api/v1/interests/received
GET    /api/v1/interests/sent
PATCH  /api/v1/interests/{id}          # accept | reject | withdraw | disconnect

POST   /api/v1/photo-requests
PATCH  /api/v1/photo-requests/{id}

POST   /api/v1/contact-blocks
DELETE /api/v1/contact-blocks/{id}

POST   /api/v1/biodata/import
GET    /api/v1/biodata/export          # returns a generated PDF
```

---

## 21. Backend Practice Topics

**Beginner:** routing, controllers, models, migrations, seeders, validation, authentication, authorization, CRUD, relationships, pagination.

**Intermediate:** service classes, query objects, API resources, policies, middleware, file uploads, notifications, queues, events/listeners, caching, complex filter queries, transactions.

**Advanced:** rate limiting, query optimization, indexing, N+1 prevention, full-text search, background jobs, WebSockets, analytics, audit logs, security hardening, automated tests.

---

## 22. Frontend Architecture

```text
Public
├── Landing Page
├── Search
├── Public Profile (limited)
├── About / Safety / FAQ
└── Login / Register

Authenticated
├── Dashboard
├── Discover
├── Interests
├── Shortlist
├── Messages
├── Profile (+ Managers)
├── Preferences
├── Privacy (incl. contact blocklist)
├── Notifications
└── Settings

Admin
├── Dashboard
├── Users
├── Reports
├── Verification
├── Photos
└── Moderation
```

### Components

ProfileCard, ProfileGrid, ProfileHeader, ProfileSection, MatchBreakdown, FilterPanel, SearchBar, InterestButton, ShortlistButton, VerificationBadge, PhotoGallery, BlurredPhoto, PhotoRequestButton, CompletionProgress, EmptyState, LoadingSkeleton, ErrorState, NotificationItem, ChatList, ChatWindow, Modal, ConfirmDialog, Toast.

### Required UX states

`Loading` · `Success` · `Empty` · `Error` · `Unauthorized` · `Forbidden` · `Not Found`

---

## 23. Security Requirements

Password hashing · token/session security · authorization policies · request validation · rate limiting · CSRF where applicable · SQL injection protection · XSS protection · secure file upload · signed expiring URLs for private media · access control for private fields · mass-assignment protection · audit logs for sensitive admin actions.

**Never trust:** frontend validation, hidden form fields, user-provided role values, client-side privacy rules, client-side permission checks.

---

## 24. SEO

Public pages: homepage, search landing pages, limited public profiles, about, safety, FAQ.

Never expose private profile information to search engines. Use metadata, Open Graph, structured data, canonical URLs, sitemap, robots rules. Profile pages must respect account status — a hidden or deleted profile must return 404 and be removed from the sitemap.

---

## 25. Performance

**MVP:** cursor pagination, lazy image loading, image optimization, database indexes, eager loading, API response shaping, caching where useful.

Avoid premature optimization. Measure first: query count, response time, largest images, payload size, bundle size.

The query to watch is the filtered discovery query. Benchmark it against 50,000 seeded profiles before you consider it done.

---

## 26. MVP User Journey

```text
Landing Page
      ↓
Register (self or guardian)
      ↓
Verify phone/email
      ↓
Create profile  (or import biodata — D1)
      ↓
Upload photo (blurred by default)
      ↓
Set partner preference + deal-breakers
      ↓
Add contact blocklist (D2)
      ↓
Discover profiles with match breakdown (D3)
      ↓
View profile → Send interest
      ↓
Other side accepts
      ↓
Mutual connection → Conversation
      ↓
Found a match → Hide profile
```

---

## 27. Development Phases

Build **vertical slices** — each phase goes from migration to working API to a usable screen. Do not build the entire backend and then start the frontend; it hides API design mistakes until the end and kills momentum.

| Phase | Scope |
|---|---|
| 1 | Foundation: repos, environment, DB connection, API versioning, error handling, git workflow |
| 2 | Auth: register (with age + role rules), login, logout, reset, verification, middleware, policies |
| 3 | Profile: migrations, CRUD, religion attributes, education, photos, privacy settings, account lifecycle |
| 4 | Managers: guardian linking and permissions |
| 5 | Discovery: listing, filters, cursor pagination, sorting, exclusions, profile detail |
| 6 | Matching: preferences, weights, MatchingService, score + explanation, recommendations |
| 7 | Interest & shortlist: send, accept/reject, withdraw, disconnect, quota, cooldown, block |
| 8 | Photos round 2: blur, photo requests, watermarks, signed URLs, moderation |
| 9 | Messaging: conversations, messages, read state, access control, contact-info detection |
| 10 | Notifications: in-app, preferences, throttling, digest |
| 11 | Biodata import/export (D1) |
| 12 | Moderation: reports, admin, suspension, ban, audit logs |
| 13 | Polish: performance, security pass, tests, SEO, responsive, accessibility, deployment |

**Rule:** do not start the next phase until the current one has passing API tests.

---

## 28. Testing Strategy

**Unit:** matching score and explanation, profile completion, privacy resolution, interest state transitions, age validation.

**Feature/API:** registration, login, profile CRUD, manager permissions, search + exclusions, interest flow, simultaneous interest, shortlist, block, messaging authorization, reports.

**Security:** a user cannot read another user's private fields · cannot edit another user's profile · blocked users cannot communicate · non-admins cannot reach admin endpoints · private photos cannot be fetched without approval · a contact-blocked profile never appears in any discovery response · a hidden/deleted profile returns 404 everywhere.

---

## 29. What NOT to Build Initially

AI matchmaking · video calls · subscription plans and payment gateway · mobile app · microservices · Elasticsearch · recommendation ML · advanced real-time infrastructure · large admin analytics.

(Entitlements are the one exception — build the abstraction, skip the payments.)

---

## 30. Technology Direction

**Backend:** Laravel · MySQL · REST · Sanctum · queues · events · policies · notifications

**Frontend:** Next.js · React · TypeScript · Tailwind · TanStack Query

The frontend consumes the API; it never touches the database.

---

## 31. Definition of Done for MVP

A user can: register (self or guardian) · verify · create or import a profile · upload photos with blur · set preferences and deal-breakers · add a contact blocklist · search and filter · view a profile with a match breakdown · send interest · accept/reject · shortlist · request photos · block · report · chat after mutual acceptance · receive notifications · manage privacy · hide or delete their profile · export biodata · log out.

An admin can: log in · view users · review reports · moderate profiles and photos · suspend users · manage verification levels · read audit logs.

---

## 32. Architecture Principle

```text
Database Design → Domain Rules → Backend Services → API → Frontend Data Layer → UI Components → UX Polish
```

Applied **per feature slice**, not to the whole product at once.

The backend domain model defines what the product can safely do. The frontend makes those capabilities easy to use.

---

## 33. Success Metric

For a practice project, ignore vanity metrics. Measure whether the architecture correctly and securely supports the core loop:

**Profile created → relevant profile discovered → interest sent → interest accepted → conversation started → profile closed.**

If that loop works cleanly — including the exclusions, the privacy rules, and the quotas — the foundation is strong enough for everything else.
