# 🎸 Battle of the Bands Tabulator System
## AI Build Prompt + QA Tracker
**NEUST 2026 | Version 1.0 | February 18, 2026**

---

## HOW TO USE THIS PROMPT

Paste this entire document into your AI builder (Cursor, Claude, ChatGPT, Gemini, etc.).

**Workflow:**
1. Tell the AI to build **one phase at a time**
2. After each phase, you (the QA) test the checklist items
3. Only move to the next phase when all checkboxes pass
4. If something fails, paste the error back to the AI with: *"Phase X, item Y failed — [describe issue]"*

---

---

# MASTER BUILD PROMPT
### (Paste everything below this line to your AI)

---

## PROJECT CONTEXT

Build a **Battle of the Bands Tabulator System** for NEUST's 118th Founding Anniversary / 28th Charter Day 2026. This is a locally hosted web app running on **XAMPP (Apache + MySQL + PHP)**. Judges connect via local Wi-Fi on the same network. No internet connection is required during the event.

**Two roles:**
- **Admin** — manages judges and bands, toggles the active performing band, views and prints rankings
- **Judge** — scores the currently active band, sees live score computation, finalizes and locks their scores

**Two rounds:**
- **Elimination** (3 criteria)
- **Grand Finals** (5 criteria)

---

## TECH STACK

| Layer | Technology |
|-------|-----------|
| Server | XAMPP — Apache + MySQL + PHP 8+ |
| Backend | PHP 8 (PDO, sessions, bcrypt) |
| Database | MySQL 8 (InnoDB, transactions) |
| Frontend | HTML5 + Bootstrap 5.3 + Vanilla JS (ES6+) |
| Styling | Bootstrap 5.3 utility classes only |
| Data Exchange | JSON via JS Fetch API (AJAX) |
| Print | CSS `@media print` |
| Optional | Python 3 for CSV export utility |

**Reference Docs:**
- XAMPP: https://www.apachefriends.org/documentation.html
- PHP 8 Manual: https://www.php.net/manual/en/
- PHP PDO: https://www.php.net/manual/en/book.pdo.php
- PHP Sessions: https://www.php.net/manual/en/book.session.php
- PHP password_hash: https://www.php.net/manual/en/function.password-hash.php
- MySQL 8: https://dev.mysql.com/doc/refman/8.0/en/
- MySQL InnoDB Transactions: https://dev.mysql.com/doc/refman/8.0/en/innodb-transaction-model.html
- Bootstrap 5.3: https://getbootstrap.com/docs/5.3/getting-started/introduction/
- Bootstrap Modal: https://getbootstrap.com/docs/5.3/components/modal/
- Bootstrap Forms: https://getbootstrap.com/docs/5.3/forms/overview/
- Bootstrap Table: https://getbootstrap.com/docs/5.3/content/tables/
- JS Fetch API: https://developer.mozilla.org/en-US/docs/Web/API/Fetch_API/Using_Fetch
- CSS @media print: https://developer.mozilla.org/en-US/docs/Web/CSS/@media/print
- OWASP PHP Security: https://owasp.org/www-project-cheat-sheets/cheatsheets/PHP_Configuration_Cheat_Sheet

---

## ARCHITECTURE & PRINCIPLES

### SOLID (enforce throughout)
- **Single Responsibility:** One class per concern — `AuthService`, `BandService`, `JudgeService`, `ScoreService`, `RankingService`
- **Open/Closed:** Scoring logic behind a `ScoringStrategy` interface — new rounds don't break existing code
- **Liskov Substitution:** `AdminController` and `JudgeController` extend `BaseController`
- **Interface Segregation:** `CRUDInterface` for management; `ScoringInterface` for judges only
- **Dependency Inversion:** Controllers receive services via constructor injection, not direct DB calls
- Reference: https://www.digitalocean.com/community/conceptual-articles/s-o-l-i-d-the-first-five-principles-of-object-oriented-design

### ACID (enforce on all critical DB operations)
- Wrap toggle-active-band and score finalization in MySQL transactions
- Use InnoDB engine on all tables
- Reference: https://dev.mysql.com/doc/refman/8.0/en/mysql-acid.html

### Database: 3rd Normal Form (3NF)
- No repeating groups, no partial dependencies, no transitive dependencies
- Criteria weights live in `criteria` table — never duplicated per score row
- Reference: https://www.guru99.com/third-normal-form.html

---

## DATABASE SCHEMA

Database name: `botb_tabulator`

```sql
-- 1. Rounds lookup table
CREATE TABLE rounds (
  id    INT AUTO_INCREMENT PRIMARY KEY,
  name  ENUM('elimination', 'grand_finals') NOT NULL UNIQUE
);
INSERT INTO rounds (name) VALUES ('elimination'), ('grand_finals');

-- 2. Users (admin + judges)
CREATE TABLE users (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  name       VARCHAR(100) NOT NULL,
  email      VARCHAR(150) NOT NULL UNIQUE,
  password   VARCHAR(255) NOT NULL,  -- bcrypt via password_hash()
  role       ENUM('admin', 'judge') NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
-- Seed one admin: email=admin@botb.com password=Admin@2026

-- 3. Criteria (3NF: weights not repeated in scores)
CREATE TABLE criteria (
  id       INT AUTO_INCREMENT PRIMARY KEY,
  round_id INT NOT NULL,
  name     VARCHAR(100) NOT NULL,
  weight   DECIMAL(5,2) NOT NULL,
  FOREIGN KEY (round_id) REFERENCES rounds(id)
);
-- Seed Elimination:
INSERT INTO criteria (round_id, name, weight) VALUES
  (1, 'Musicality', 50.00),
  (1, 'Originality', 30.00),
  (1, 'Stage Presence', 20.00);
-- Seed Grand Finals:
INSERT INTO criteria (round_id, name, weight) VALUES
  (2, 'Musicality', 30.00),
  (2, 'Creativity & Originality', 25.00),
  (2, 'Stage Presence & Audience Engagement', 20.00),
  (2, 'Overall Impact', 10.00),
  (2, 'Original Composition', 15.00);

-- 4. Bands
CREATE TABLE bands (
  id                INT AUTO_INCREMENT PRIMARY KEY,
  name              VARCHAR(150) NOT NULL,
  round_id          INT NOT NULL,
  performance_order INT NOT NULL,
  is_active         TINYINT(1) DEFAULT 0,
  created_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (round_id) REFERENCES rounds(id)
);

-- 5. Scores
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

---

## SCORING LOGIC

```
Judge's weighted total for a band =
  Σ ( score × (weight / 100) ) for each criterion

Band's final score =
  AVG of all judges' weighted totals

Ranking = bands sorted by final_score DESC
```

Example (Elimination):
```
Musicality=80 × 0.50 = 40.00
Originality=70 × 0.30 = 21.00
Stage Presence=90 × 0.20 = 18.00
Weighted Total = 79.00
```

---

## SECURITY REQUIREMENTS

### Frontend
- All score inputs: numeric only, 0–100 range validation before fetch call
- CSRF token in a `<meta>` tag; inject into every AJAX request header
- Role checked on page load — redirect unauthorized users immediately

### Backend (PHP)
- Passwords: `password_hash()` / `password_verify()` (bcrypt) — never store plain text
- Sessions: `session_regenerate_id(true)` on login; `session_destroy()` on logout
- CSRF: generate token on login, store in session, validate on every POST
- SQL: PDO prepared statements only — zero raw string queries
- Output: `htmlspecialchars()` on all echoed data
- Input: `filter_var()` / `intval()` / `floatval()` on all inputs
- Role check: every PHP endpoint reads `$_SESSION['role']` before any logic
- Score immutability: reject any modification where `is_finalized = 1`
- Active band toggle: wrapped in a transaction — set all to 0, then set target to 1

---

## UI/UX SPECIFICATIONS

### General
- Bootstrap 5.3 utility classes only — no custom CSS frameworks
- Mobile-first responsive layout (judges score on phones/tablets)
- High contrast text for outdoor/stage lighting environments
- Primary color: deep blue `#003366`, accent: gold `#FFD700`
- Font: Bootstrap default (system font stack)
- No page reloads for CRUD — use Bootstrap Modals + AJAX

### Shared
- **Login page:** centered card, university name as header, minimal fields (email, password, login button)
- Show validation errors inline under each field (Bootstrap `invalid-feedback`)
- Loading spinner (Bootstrap spinner) on any AJAX call

### Admin Dashboard
- **Layout:** fixed left sidebar (Bootstrap offcanvas on mobile) with links: Bands | Judges | Rankings | Logout
- **Bands page:** responsive table — columns: #, Band Name, Round, Order, Status (badge), Actions (Edit / Delete / Activate)
  - Active band: green `Now Performing` badge
  - Inactive: gray `Standby` badge
  - Activate button triggers confirmation, then AJAX toggle
- **Judges page:** responsive table — columns: #, Name, Email, Actions (Edit / Delete)
- **Add/Edit forms:** Bootstrap Modal with inline validation
- **Rankings page:**
  - Round selector tabs (Elimination | Grand Finals)
  - Table: Rank, Band Name, Judge 1 total, Judge 2 total... Average Score
  - Gold/Silver/Bronze row highlights for top 3
  - Print button (top right): `onclick="window.print()"`

### Judge Scoring View
- **Active band header:** large bold band name, round badge (Elimination / Grand Finals)
- **Waiting state:** centered card — *"⏳ Waiting for the next band to be activated…"* — auto-refresh via polling every 5 seconds
- **Scoring form:**
  - One row per criterion: label, weight shown in muted text (e.g., *Musicality — 50%*), number input (0–100)
  - Live weighted total updates on every `input` event — shown in a highlighted box at bottom
- **Submit button:** disabled until all criteria are filled
- **Confirmation modal:** *"Are you sure you want to finalize your scores? This cannot be undone."* — Cancel / Confirm buttons
- **Post-submission:** read-only score card showing submitted values and weighted total; no further interaction

### Print Stylesheet (`@media print`)
- Hide: sidebar, navbar, buttons, badges, action columns
- Show: university header, round name, rankings table, date
- Clean white background, black text, no borders on nav elements

---

## FILE STRUCTURE

```
/botb_tabulator/
│
├── index.php                  # Login page (redirects by role)
├── logout.php
│
├── /admin/
│   ├── dashboard.php          # Redirect to bands by default
│   ├── bands.php              # Band management UI
│   ├── judges.php             # Judge management UI
│   ├── rankings.php           # Rankings view + print
│   └── /ajax/
│       ├── band_create.php
│       ├── band_update.php
│       ├── band_delete.php
│       ├── band_activate.php
│       ├── judge_create.php
│       ├── judge_update.php
│       ├── judge_delete.php
│       └── get_rankings.php
│
├── /judge/
│   ├── score.php              # Main judge scoring view
│   └── /ajax/
│       ├── get_active_band.php
│       └── submit_scores.php
│
├── /services/
│   ├── AuthService.php
│   ├── BandService.php
│   ├── JudgeService.php
│   ├── ScoreService.php
│   └── RankingService.php
│
├── /interfaces/
│   ├── CRUDInterface.php
│   └── ScoringInterface.php
│
├── /controllers/
│   ├── BaseController.php
│   ├── AdminController.php
│   └── JudgeController.php
│
├── /config/
│   └── db.php                 # PDO connection singleton
│
├── /assets/
│   ├── /css/
│   │   ├── app.css            # Minimal custom styles + brand colors
│   │   └── print.css          # @media print overrides
│   └── /js/
│       ├── admin.js           # Admin AJAX + modals
│       └── judge.js           # Judge polling + live score computation
│
└── /includes/
    ├── header.php
    ├── sidebar_admin.php
    └── footer.php
```

---

## BUILD ORDER (Phases)

> **IMPORTANT FOR AI:** Build exactly one phase at a time. Do not proceed to the next phase until instructed. After each phase output, wait for QA confirmation.

---

### ▶ PHASE 1 — Database + Config
Build: `config/db.php`, full `botb_tabulator` schema SQL file with all seeds.

**Deliverables:**
- `database.sql` — full schema + seed data (run in phpMyAdmin)
- `config/db.php` — PDO singleton with error handling

---

### ▶ PHASE 2 — Authentication
Build: Login page, session management, role-based redirect, logout.

**Deliverables:**
- `index.php` — login form (Bootstrap card, centered)
- `AuthService.php` — login, session creation, CSRF token generation
- `logout.php`
- Role redirect: admin → `/admin/dashboard.php`, judge → `/judge/score.php`

---

### ▶ PHASE 3 — Admin: Judge Management
Build: Judge list page + CRUD via Bootstrap Modal + AJAX.

**Deliverables:**
- `admin/judges.php` — judge table + add/edit/delete modals
- `JudgeService.php`
- `admin/ajax/judge_create.php`, `judge_update.php`, `judge_delete.php`
- `assets/js/admin.js` (start file — add judge CRUD functions)

---

### ▶ PHASE 4 — Admin: Band Management
Build: Band list page + CRUD + active band toggle.

**Deliverables:**
- `admin/bands.php` — band table with status badges + modals
- `BandService.php`
- `admin/ajax/band_create.php`, `band_update.php`, `band_delete.php`, `band_activate.php`
- Active toggle: ACID transaction (all → 0, target → 1)
- Update `assets/js/admin.js` with band functions

---

### ▶ PHASE 5 — Judge: Scoring View
Build: Judge scoring page with active band detection, form, live computation, finalization.

**Deliverables:**
- `judge/score.php` — full scoring UI
- `ScoreService.php`
- `judge/ajax/get_active_band.php` — returns active band + criteria JSON
- `judge/ajax/submit_scores.php` — validates, checks finalization, inserts scores
- `assets/js/judge.js` — polling (5s), live weighted total, modal, submission

---

### ▶ PHASE 6 — Admin: Rankings + Print
Build: Rankings view with per-judge breakdown and print layout.

**Deliverables:**
- `admin/rankings.php` — tabbed (Elimination / Grand Finals), table with averages, top-3 highlights
- `RankingService.php` — SQL query with AVG computation
- `admin/ajax/get_rankings.php`
- `assets/css/print.css` — clean print layout

---

### ▶ PHASE 7 — Security Hardening + Final Polish
Apply all remaining security controls and UI finishing touches.

**Deliverables:**
- CSRF token validation on every POST endpoint
- `htmlspecialchars()` audit on all output
- Role guard on every admin and judge PHP file
- Score immutability enforcement audit
- Mobile responsiveness check (Bootstrap breakpoints)
- Sidebar offcanvas for mobile admin
- Loading spinners on all AJAX calls
- Final error handling (graceful messages, no raw PHP errors shown)

---

---

# QA TRACKER
### (Use this section yourself to track progress)

---

## ✅ PHASE 1 — Database + Config

| # | Test | Status |
|---|------|--------|
| 1.1 | Import `database.sql` into phpMyAdmin with no errors | ⬜ |
| 1.2 | All 5 tables exist: `rounds`, `users`, `criteria`, `bands`, `scores` | ⬜ |
| 1.3 | Seed data present: 2 rounds, 8 criteria, 1 admin user | ⬜ |
| 1.4 | `config/db.php` connects without error (test via a simple `var_dump($pdo)`) | ⬜ |
| 1.5 | All tables use InnoDB engine | ⬜ |
| 1.6 | Foreign keys enforced (try inserting a score with invalid band_id — should fail) | ⬜ |

---

## ✅ PHASE 2 — Authentication

| # | Test | Status |
|---|------|--------|
| 2.1 | Login page renders correctly on desktop and mobile | ⬜ |
| 2.2 | Admin login (admin@botb.com / Admin@2026) redirects to `/admin/dashboard.php` | ⬜ |
| 2.3 | Judge login redirects to `/judge/score.php` | ⬜ |
| 2.4 | Wrong password shows inline error message | ⬜ |
| 2.5 | Accessing `/admin/dashboard.php` without login redirects to login | ⬜ |
| 2.6 | Accessing `/judge/score.php` without login redirects to login | ⬜ |
| 2.7 | Judge accessing `/admin/` is redirected away | ⬜ |
| 2.8 | Logout destroys session and redirects to login | ⬜ |
| 2.9 | Password stored as bcrypt hash in DB (not plain text) | ⬜ |
| 2.10 | CSRF token present in session after login | ⬜ |

---

## ✅ PHASE 3 — Admin: Judge Management

| # | Test | Status |
|---|------|--------|
| 3.1 | Judge list page loads with correct table layout | ⬜ |
| 3.2 | "Add Judge" modal opens and closes correctly | ⬜ |
| 3.3 | Create judge with valid data — appears in table without page reload | ⬜ |
| 3.4 | Create judge with duplicate email — shows error | ⬜ |
| 3.5 | Create judge with empty fields — shows validation error | ⬜ |
| 3.6 | Edit judge — modal pre-populates with existing data | ⬜ |
| 3.7 | Edit judge — changes save and reflect in table | ⬜ |
| 3.8 | Delete judge — confirmation shown, then removed from table | ⬜ |
| 3.9 | Newly created judge can log in with set credentials | ⬜ |
| 3.10 | Judge password updated via edit — new password works on login | ⬜ |

---

## ✅ PHASE 4 — Admin: Band Management

| # | Test | Status |
|---|------|--------|
| 4.1 | Band list page loads correctly | ⬜ |
| 4.2 | Add band (name, round, order) — appears in table | ⬜ |
| 4.3 | Edit band — changes save correctly | ⬜ |
| 4.4 | Delete band — removed from table | ⬜ |
| 4.5 | Activate Band A — shows "Now Performing" green badge | ⬜ |
| 4.6 | Activate Band B — Band A goes to "Standby", Band B becomes active | ⬜ |
| 4.7 | Only one band is active at any time (check DB: count of is_active=1 rows = 1) | ⬜ |
| 4.8 | Activate toggle uses a transaction (verify no partial state on DB error) | ⬜ |
| 4.9 | Elimination and Grand Finals bands show correctly separated by round | ⬜ |

---

## ✅ PHASE 5 — Judge: Scoring View

| # | Test | Status |
|---|------|--------|
| 5.1 | With no active band: judge sees waiting screen | ⬜ |
| 5.2 | Admin activates a band — judge screen updates within 5 seconds (auto-poll) | ⬜ |
| 5.3 | Active band name and round display correctly on judge screen | ⬜ |
| 5.4 | Correct criteria shown for Elimination round (3 criteria) | ⬜ |
| 5.5 | Correct criteria shown for Grand Finals round (5 criteria) | ⬜ |
| 5.6 | Entering scores updates live weighted total in real time | ⬜ |
| 5.7 | Weighted total math is correct (verify manually with example values) | ⬜ |
| 5.8 | Submit button is disabled until all criteria fields are filled | ⬜ |
| 5.9 | Confirmation modal appears before submission | ⬜ |
| 5.10 | Cancel on modal — scores not submitted, form still editable | ⬜ |
| 5.11 | Confirm on modal — scores saved to DB, `is_finalized = 1` | ⬜ |
| 5.12 | After submission — read-only score card shown, no re-submit possible | ⬜ |
| 5.13 | Score out of range (e.g., 150 or -5) — rejected with error | ⬜ |
| 5.14 | Two judges score the same band simultaneously — both scores saved correctly | ⬜ |
| 5.15 | Judge cannot submit scores for the same band twice | ⬜ |

---

## ✅ PHASE 6 — Rankings + Print

| # | Test | Status |
|---|------|--------|
| 6.1 | Rankings page loads for admin only | ⬜ |
| 6.2 | Elimination tab shows only elimination bands | ⬜ |
| 6.3 | Grand Finals tab shows only grand finals bands | ⬜ |
| 6.4 | Rankings sorted correctly (highest average score = rank 1) | ⬜ |
| 6.5 | Per-judge score columns shown correctly | ⬜ |
| 6.6 | Average score computed correctly (verify manually) | ⬜ |
| 6.7 | Top 3 bands have gold/silver/bronze row highlights | ⬜ |
| 6.8 | Print button triggers browser print dialog | ⬜ |
| 6.9 | Print preview shows only table + header (no nav, no buttons) | ⬜ |
| 6.10 | Rankings update when a new judge submits scores (refresh or AJAX) | ⬜ |

---

## ✅ PHASE 7 — Security Hardening + Final Polish

| # | Test | Status |
|---|------|--------|
| 7.1 | Submit a form with a tampered/missing CSRF token — request rejected | ⬜ |
| 7.2 | Try SQL injection in login email field (e.g., `' OR 1=1 --`) — blocked | ⬜ |
| 7.3 | Try accessing `admin/ajax/band_activate.php` as a judge — rejected | ⬜ |
| 7.4 | Try submitting scores via Postman/fetch for a finalized band — rejected | ⬜ |
| 7.5 | XSS test: enter `<script>alert(1)</script>` as band name — displays as text, not executed | ⬜ |
| 7.6 | All pages render correctly on mobile (375px width) | ⬜ |
| 7.7 | Admin sidebar collapses to offcanvas menu on mobile | ⬜ |
| 7.8 | Loading spinner appears on all AJAX operations | ⬜ |
| 7.9 | No raw PHP errors shown to users (error reporting off in production config) | ⬜ |
| 7.10 | Full event dry run: add 3 bands, 2 judges, score all bands, view rankings, print | ⬜ |

---

## 📊 QA PROGRESS SUMMARY

| Phase | Total Tests | Passed | Failed | Status |
|-------|------------|--------|--------|--------|
| Phase 1 — DB + Config | 6 | 0 | 0 | ⬜ Not Started |
| Phase 2 — Auth | 10 | 0 | 0 | ⬜ Not Started |
| Phase 3 — Judge CRUD | 10 | 0 | 0 | ⬜ Not Started |
| Phase 4 — Band CRUD | 9 | 0 | 0 | ⬜ Not Started |
| Phase 5 — Scoring | 15 | 0 | 0 | ⬜ Not Started |
| Phase 6 — Rankings | 10 | 0 | 0 | ⬜ Not Started |
| Phase 7 — Security | 10 | 0 | 0 | ⬜ Not Started |
| **TOTAL** | **70** | **0** | **0** | **⬜ In Progress** |

---

## 🐛 BUG LOG

Use this to track issues found during QA:

| # | Phase | Test # | Description | Status |
|---|-------|--------|-------------|--------|
| — | — | — | No bugs logged yet | — |

---

## HOW TO REPORT A BUG TO THE AI

When a test fails, paste this to the AI:

```
Phase [X], Test [Y.Z] FAILED.

What I did: [describe exact steps]
Expected: [what should happen]
Actual: [what actually happened]
Error message (if any): [paste full error]
Relevant file: [filename]
```

---

## KEY REFERENCES (Quick Access)

| Resource | URL |
|----------|-----|
| XAMPP | https://www.apachefriends.org/documentation.html |
| PHP 8 Manual | https://www.php.net/manual/en/ |
| PHP PDO | https://www.php.net/manual/en/book.pdo.php |
| PHP Sessions | https://www.php.net/manual/en/book.session.php |
| PHP password_hash | https://www.php.net/manual/en/function.password-hash.php |
| MySQL 8 | https://dev.mysql.com/doc/refman/8.0/en/ |
| MySQL Transactions | https://dev.mysql.com/doc/refman/8.0/en/innodb-transaction-model.html |
| MySQL ACID | https://dev.mysql.com/doc/refman/8.0/en/mysql-acid.html |
| Bootstrap 5.3 | https://getbootstrap.com/docs/5.3/getting-started/introduction/ |
| Bootstrap Modal | https://getbootstrap.com/docs/5.3/components/modal/ |
| Bootstrap Forms | https://getbootstrap.com/docs/5.3/forms/overview/ |
| Bootstrap Tables | https://getbootstrap.com/docs/5.3/content/tables/ |
| Bootstrap Badges | https://getbootstrap.com/docs/5.3/components/badge/ |
| Bootstrap Offcanvas | https://getbootstrap.com/docs/5.3/components/offcanvas/ |
| Bootstrap Spinners | https://getbootstrap.com/docs/5.3/components/spinners/ |
| JS Fetch API | https://developer.mozilla.org/en-US/docs/Web/API/Fetch_API/Using_Fetch |
| CSS @media print | https://developer.mozilla.org/en-US/docs/Web/CSS/@media/print |
| OWASP PHP Security | https://owasp.org/www-project-cheat-sheets/cheatsheets/PHP_Configuration_Cheat_Sheet |
| SOLID Principles | https://www.digitalocean.com/community/conceptual-articles/s-o-l-i-d-the-first-five-principles-of-object-oriented-design |
| 3NF Normalization | https://www.guru99.com/third-normal-form.html |
| Python 3 (CSV export) | https://docs.python.org/3/ |
