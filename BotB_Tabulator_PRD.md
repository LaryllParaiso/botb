# Product Requirements Document
## Battle of the Bands Tabulator System — NEUST 2026
**Version:** 1.0 | **Updated:** February 18, 2026

---

## Overview

A locally hosted, real-time tabulator web system for NEUST's Battle of the Bands event (118th Founding Anniversary / 28th Charter Day, 2026). The system manages two rounds — **Elimination** and **Grand Finals** — with two roles: **Admin** (controls competition flow, views rankings) and **Judge** (scores the active band). Built on XAMPP and served over a local network during the live event.

---

## Core Features

### Admin
- Secure login (email + password)
- **Judge Management (CRUD):** create, read, update, delete judge accounts (name, email, password)
- **Band Management (CRUD):** create, read, update, delete bands (name, round, performance order)
- **Active Band Toggle:** set exactly one band as "Now Performing" — auto-deactivates the previous
- **Rankings View & Print:** view final computed rankings per round; print-ready layout via browser print

### Judge
- Secure login (email + password)
- View currently active band name and round
- Fill scoring form with per-criteria input fields (0–100 each)
- Live weighted score computation shown as they type
- Finalization confirmation modal before locking scores
- Read-only score summary after submission
- No access to other judges' scores or admin features

---

## Technical Stack

| Layer | Technology | Docs |
|-------|-----------|------|
| Server | XAMPP (Apache + MySQL) | https://www.apachefriends.org/documentation.html |
| Backend | PHP 8+ | https://www.php.net/manual/en/ |
| Database | MySQL 8 via XAMPP | https://dev.mysql.com/doc/ |
| Frontend | HTML5, CSS3, Bootstrap 5 | https://getbootstrap.com/docs/5.3/ |
| Scripting | Vanilla JavaScript (ES6+) | https://developer.mozilla.org/en-US/docs/Web/JavaScript |
| Data Interchange | JSON (AJAX fetch API) | https://developer.mozilla.org/en-US/docs/Web/API/Fetch_API |
| Automation/Utils | Python 3 (optional: report generation, CSV export) | https://docs.python.org/3/ |
| Print | Browser `window.print()` + CSS `@media print` | https://developer.mozilla.org/en-US/docs/Web/CSS/@media/print |

> All components run locally under XAMPP. No internet connection required during the live event.

---

## UI/UX Design

### General Principles
- **Mobile-first** using Bootstrap 5 responsive grid — judges will score on phones/tablets
- Clean, minimal interface; avoid cognitive overload during a live event
- High contrast text for readability in outdoor/stage lighting conditions
- Use Bootstrap's built-in components: cards, modals, badges, tables, forms

### Admin Dashboard
- Sidebar navigation: Bands | Judges | Rankings | Logout
- **Band list** displayed as a table with an "Activate" toggle button per row — active band highlighted with a green badge `Now Performing`
- CRUD modals (Bootstrap Modal) for adding/editing bands and judges — no full page reloads
- Rankings page: clean table showing rank, band name, average score, per-judge breakdown; **Print** button top-right

### Judge Scoring View
- Single-page view after login
- Top: active band name displayed prominently (large heading, bold)
- If no band is active: centered waiting message with a subtle animated indicator
- Scoring form: one input per criterion with label, weight shown beside it (e.g., *Musicality — 50%*)
- Live total score computed and displayed at the bottom of the form as judge types
- On submit: Bootstrap modal confirmation — *"Finalize scores? This cannot be undone."*
- After submission: read-only score card showing what was submitted

### Color & Style
- Primary color: use NEUST brand colors if available (fallback: deep blue `#003366` + gold `#FFD700`)
- Bootstrap utility classes only — no custom CSS frameworks
- Print stylesheet: white background, no nav/buttons, clean table layout

---

## Data Requirements

### Database: `botb_tabulator` (MySQL, 3NF-compliant)

**Normalization rules applied:**
- Every non-key attribute depends on the whole primary key (2NF)
- No transitive dependencies — lookup data separated into their own tables (3NF)
- Foreign keys enforce referential integrity

---

#### Table: `users`
```sql
CREATE TABLE users (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  name        VARCHAR(100) NOT NULL,
  email       VARCHAR(150) NOT NULL UNIQUE,
  password    VARCHAR(255) NOT NULL,  -- bcrypt hashed
  role        ENUM('admin', 'judge') NOT NULL,
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

#### Table: `rounds`
```sql
CREATE TABLE rounds (
  id    INT AUTO_INCREMENT PRIMARY KEY,
  name  ENUM('elimination', 'grand_finals') NOT NULL UNIQUE
);
-- Seed: INSERT INTO rounds (name) VALUES ('elimination'), ('grand_finals');
```

#### Table: `bands`
```sql
CREATE TABLE bands (
  id                INT AUTO_INCREMENT PRIMARY KEY,
  name              VARCHAR(150) NOT NULL,
  round_id          INT NOT NULL,
  performance_order INT NOT NULL,
  is_active         TINYINT(1) DEFAULT 0,
  created_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (round_id) REFERENCES rounds(id)
);
```

#### Table: `criteria`
```sql
CREATE TABLE criteria (
  id       INT AUTO_INCREMENT PRIMARY KEY,
  round_id INT NOT NULL,
  name     VARCHAR(100) NOT NULL,
  weight   DECIMAL(5,2) NOT NULL,  -- e.g., 50.00 for 50%
  FOREIGN KEY (round_id) REFERENCES rounds(id)
);
-- Seed Elimination: Musicality(50), Originality(30), Stage Presence(20)
-- Seed Grand Finals: Musicality(30), Creativity & Originality(25),
--   Stage Presence & Audience Engagement(20), Overall Impact(10),
--   Original Composition(15)
```

#### Table: `scores`
```sql
CREATE TABLE scores (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  judge_id     INT NOT NULL,
  band_id      INT NOT NULL,
  criteria_id  INT NOT NULL,
  score        DECIMAL(5,2) NOT NULL CHECK (score BETWEEN 0 AND 100),
  is_finalized TINYINT(1) DEFAULT 0,
  created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY unique_score (judge_id, band_id, criteria_id),
  FOREIGN KEY (judge_id)    REFERENCES users(id),
  FOREIGN KEY (band_id)     REFERENCES bands(id),
  FOREIGN KEY (criteria_id) REFERENCES criteria(id)
);
```

> **3NF compliance:** Criteria weights live in `criteria`, not duplicated per score row. Round info lives in `rounds`, not stored as string fields in `bands` or `scores`.

---

## Scoring Logic

```
Judge's weighted total for a band =
  Σ (score × (weight / 100)) for each criterion

Band's final score =
  AVG of all judges' weighted totals for that band

Ranking = bands ordered by final score DESC
```

**Example (Elimination):**
```
Musicality=80, Originality=70, Stage Presence=90
= (80×0.50) + (70×0.30) + (90×0.20)
= 40 + 21 + 18 = 79.00
```

---

## Security

### Frontend
- All score inputs validated client-side (numeric, 0–100 range) before submission
- CSRF token embedded in every form (meta tag + JS header injection)
- Judges cannot navigate to admin routes — role checked on page load, redirect if unauthorized
- Confirmation modal prevents accidental score submission

### Backend (PHP)
- **Passwords:** hashed with `password_hash()` / `password_verify()` (bcrypt) — never stored plain
- **Sessions:** PHP sessions with `session_regenerate_id(true)` on login; destroyed on logout
- **CSRF Protection:** validate token on every POST request server-side
- **SQL Injection:** use PDO prepared statements exclusively — no raw string queries
- **Input Sanitization:** `htmlspecialchars()` on all output; `filter_var()` on all input
- **Role enforcement:** every PHP endpoint checks `$_SESSION['role']` before processing
- **Score immutability:** backend rejects any update to a score where `is_finalized = 1`
- **Active band constraint:** use a DB transaction when toggling active band (set all to 0, then set target to 1 atomically)

---

## ACID Compliance

All critical operations must be wrapped in MySQL transactions:

```php
// Example: Toggle active band (atomic)
$pdo->beginTransaction();
$pdo->exec("UPDATE bands SET is_active = 0");
$stmt = $pdo->prepare("UPDATE bands SET is_active = 1 WHERE id = ?");
$stmt->execute([$bandId]);
$pdo->commit();
```

- **Atomicity:** Toggle and finalize operations are all-or-nothing transactions
- **Consistency:** FK constraints and CHECK constraints enforce data validity at DB level
- **Isolation:** Use `PDO::ATTR_DEFAULT_FETCH_MODE` and transaction isolation to prevent dirty reads during scoring
- **Durability:** MySQL InnoDB engine ensures committed scores survive server restarts

---

## SOLID Principles (PHP Architecture)

| Principle | Implementation |
|-----------|---------------|
| **Single Responsibility** | Separate classes: `AuthService`, `BandService`, `JudgeService`, `ScoreService`, `RankingService` |
| **Open/Closed** | Scoring formula logic abstracted into a `ScoringStrategy` interface — add new round types without modifying existing code |
| **Liskov Substitution** | `AdminController` and `JudgeController` both extend `BaseController`; substitutable where a controller is expected |
| **Interface Segregation** | `CRUDInterface` for band/judge management; `ScoringInterface` separate — judges only implement `ScoringInterface` |
| **Dependency Inversion** | Controllers depend on service abstractions injected via constructor, not on concrete DB calls directly |

---

## Technical Dependencies

| Dependency | Version | Purpose |
|------------|---------|---------|
| XAMPP | 8.2+ | Apache + MySQL + PHP local server |
| PHP | 8.0+ | Backend logic |
| MySQL | 8.0+ | Relational database (InnoDB engine) |
| Bootstrap | 5.3 | Responsive UI |
| jQuery (optional) | 3.7 | AJAX/DOM helpers |
| Python | 3.10+ | Optional: CSV export, report scripts |

---

## Implementation Phases

### Phase 1 — Setup & Auth (Day 1–2)
- Configure XAMPP, create database and seed tables
- Build login page (shared for both roles)
- Implement session-based auth with role routing
- Enforce CSRF protection and password hashing

### Phase 2 — Admin Module (Day 3–4)
- Judge CRUD (modal-based forms, AJAX)
- Band CRUD with round assignment and performance order
- Active band toggle with atomic DB transaction

### Phase 3 — Judge Module (Day 5–6)
- Scoring view: detect active band via polling (AJAX every 5s)
- Scoring form with live weighted total computation
- Finalization modal + score lock on submission

### Phase 4 — Rankings & Print (Day 7)
- Admin rankings page: compute and display per-round results
- Per-band breakdown table (per judge + average)
- Print stylesheet (`@media print`)

### Phase 5 — Testing & Hardening (Day 8–9)
- Test multi-judge simultaneous scoring
- Validate all security controls (CSRF, SQL injection, role bypass)
- Cross-browser and mobile responsiveness checks
- Dry run with sample bands and judges

### Phase 6 — Deployment & Event (Day 10)
- Deploy to XAMPP on event laptop
- Connect judges via local Wi-Fi (same network)
- Final smoke test before competition begins

---

## Constraints

- **Offline only** — no internet required; system runs entirely on local XAMPP server
- **Single active band** — only one band can be marked active at any time
- **Score immutability** — finalized scores cannot be modified or deleted
- **No self-registration** — only Admin can create judge accounts
- **No violation deductions** — no time penalty logic in system scope
- **Single device admin** — only the admin laptop manages the event flow

---

## Success Metrics

- All judges can log in and submit scores simultaneously without errors
- Active band toggle reflects on all judge devices within 5 seconds
- Rankings computed correctly match manual calculation
- Finalized scores cannot be altered under any user action
- Print output is clean and presentable for announcing results
- Zero SQL injection or auth bypass vulnerabilities in testing

---

## Key References

| Resource | URL |
|----------|-----|
| XAMPP Documentation | https://www.apachefriends.org/documentation.html |
| PHP 8 Manual | https://www.php.net/manual/en/ |
| PHP PDO (Prepared Statements) | https://www.php.net/manual/en/book.pdo.php |
| PHP Sessions | https://www.php.net/manual/en/book.session.php |
| PHP password_hash | https://www.php.net/manual/en/function.password-hash.php |
| MySQL 8 Docs | https://dev.mysql.com/doc/refman/8.0/en/ |
| MySQL Transactions (InnoDB) | https://dev.mysql.com/doc/refman/8.0/en/innodb-transaction-model.html |
| Bootstrap 5.3 | https://getbootstrap.com/docs/5.3/getting-started/introduction/ |
| Bootstrap Modal | https://getbootstrap.com/docs/5.3/components/modal/ |
| Bootstrap Forms | https://getbootstrap.com/docs/5.3/forms/overview/ |
| JavaScript Fetch API | https://developer.mozilla.org/en-US/docs/Web/API/Fetch_API/Using_Fetch |
| CSS @media print | https://developer.mozilla.org/en-US/docs/Web/CSS/@media/print |
| Python 3 Docs | https://docs.python.org/3/ |
| OWASP PHP Security | https://owasp.org/www-project-cheat-sheets/cheatsheets/PHP_Configuration_Cheat_Sheet |
| SOLID Principles | https://www.digitalocean.com/community/conceptual-articles/s-o-l-i-d-the-first-five-principles-of-object-oriented-design |
| Database Normalization (3NF) | https://www.guru99.com/third-normal-form.html |
| ACID Properties | https://dev.mysql.com/doc/refman/8.0/en/mysql-acid.html |
