# Battle of the Bands Tabulator System (BotB)

A real-time scoring and tabulation system for Battle of the Bands competitions. Built with PHP, MySQL, Bootstrap 5, and Node.js WebSocket for live updates.

---

## Features

- **Admin Dashboard** — Manage judges, bands, view live scoring status, rankings
- **Judge Interface** — Real-time scoring with live weighted total computation
- **Real-time Updates** — WebSocket-powered instant notifications (with polling fallback)
- **Two Rounds** — Elimination and Grand Finals with separate criteria/weights
- **Tie-aware Rankings** — Bands with identical scores share the same rank
- **Top-N Highlighting** — Configurable top-N qualifier display with print support
- **Score Management** — Admin can view, edit, and delete individual scores
- **System Reset** — Full data reset with confirmation safeguard
- **Responsive Design** — Mobile-friendly with horizontal-scrolling tables
- **SweetAlert2 Notifications** — Beautiful popup confirmations and alerts

---

## Requirements

| Software | Version |
|----------|---------|
| **XAMPP** (Apache + MySQL + PHP) | 8.0+ (PHP 8.0+) |
| **Node.js** | 16.0+ |
| **npm** | 8.0+ |
| **Browser** | Chrome, Firefox, Edge, Safari (modern) |

---

## Quick Setup

### 1. Clone / Copy the Project

Place the project folder inside your XAMPP `htdocs` directory:

```
C:\xampp\htdocs\BOB_SYSTEM\
```

### 2. Start XAMPP Services

Open **XAMPP Control Panel** and start:
- **Apache**
- **MySQL**

### 3. Create the Database

Open phpMyAdmin at [http://localhost/phpmyadmin](http://localhost/phpmyadmin), or use the MySQL CLI:

```bash
mysql -u root < C:\xampp\htdocs\BOB_SYSTEM\database.sql
```

This creates the `botb_tabulator` database with all tables and seeds:
- Default admin account
- Rounds (Elimination, Grand Finals)
- Criteria with weights

### 4. (Optional) Load Test Data

To populate sample bands, judges, and scores for testing:

```bash
mysql -u root botb_tabulator < C:\xampp\htdocs\BOB_SYSTEM\test_data.sql
```

### 5. Configure Database Connection

Edit `config/db.php` if your MySQL credentials differ from the defaults:

```php
private const DB_HOST = '127.0.0.1';
private const DB_NAME = 'botb_tabulator';
private const DB_USER = 'root';      // Change if needed
private const DB_PASS = '';           // Change if needed
```

### 6. Install & Start the WebSocket Server

```bash
cd C:\xampp\htdocs\BOB_SYSTEM\websocket
npm install
node server.js
```

You should see:
```
[WS] WebSocket server running on ws://localhost:8081
[HTTP] Internal notification server on http://127.0.0.1:8082
```

> **Note:** The system works without the WebSocket server (falls back to polling), but real-time updates will be delayed by ~10 seconds instead of instant.

### 7. Access the Application

| Page | URL |
|------|-----|
| **Login** | [http://localhost/BOB_SYSTEM/](http://localhost/BOB_SYSTEM/) |
| **Admin Dashboard** | [http://localhost/BOB_SYSTEM/admin/bands.php](http://localhost/BOB_SYSTEM/admin/bands.php) |
| **Judge Scoring** | [http://localhost/BOB_SYSTEM/judge/score.php](http://localhost/BOB_SYSTEM/judge/score.php) |

---

## Default Credentials

| Role | Email | Password |
|------|-------|----------|
| **Admin** | `admin@botb.com` | `Admin@2026` |

Judges are created by the admin through the dashboard.

---

## Project Structure

```
BOB_SYSTEM/
├── admin/                  # Admin pages & AJAX endpoints
│   ├── ajax/               # AJAX handlers (band, judge, score, ranking APIs)
│   ├── bands.php           # Band management page
│   ├── judges.php          # Judge management page
│   ├── rankings.php        # Rankings & top-N display
│   ├── scores.php          # Score management & system reset
│   └── dashboard.php       # Redirects to bands.php
├── assets/
│   ├── css/
│   │   ├── app.css         # Main application styles
│   │   └── print.css       # Print-specific styles
│   └── js/
│       ├── admin.js        # Admin dashboard logic
│       └── judge.js        # Judge scoring logic
├── config/
│   └── db.php              # Database configuration (PDO singleton)
├── controllers/
│   ├── AdminController.php # Admin auth & CSRF validation
│   └── JudgeController.php # Judge auth & CSRF validation
├── includes/
│   ├── header.php          # HTML head, CSS, meta tags
│   ├── footer.php          # Bootstrap JS, SweetAlert2, page scripts
│   ├── sidebar_admin.php   # Admin navigation sidebar
│   └── sidebar_judge.php   # Judge navigation bar
├── interfaces/             # PHP interfaces
├── judge/                  # Judge pages & AJAX endpoints
│   ├── ajax/               # AJAX handlers (scoring, SSE, history)
│   ├── score.php           # Main scoring interface
│   └── my_scores.php       # Score history page
├── services/
│   ├── BandService.php     # Band CRUD operations
│   ├── ScoreService.php    # Score submission & queries
│   ├── RankingService.php  # Ranking computation with tie handling
│   └── WebSocketService.php# PHP → WebSocket server notifications
├── storage/                # Runtime storage (event files)
├── websocket/
│   ├── server.js           # Node.js WebSocket server
│   ├── package.json        # Node.js dependencies
│   └── node_modules/       # Installed packages
├── database.sql            # Full database schema + seed data
├── test_data.sql           # Sample test data
├── index.php               # Login page
├── logout.php              # Session logout
└── README.md               # This file
```

---

## How It Works

### Scoring Flow

1. **Admin** creates judges and bands via the dashboard
2. **Admin** activates a band — all judges are notified in real-time via WebSocket
3. **Judges** see the scoring form appear instantly and enter scores per criteria
4. **Judges** submit scores — admin sees the pending judges panel update live
5. Once all judges submit, the band shows as "Finished"
6. **Admin** activates the next band and repeats
7. **Rankings** are computed automatically with tie-aware ranking

### Real-time Architecture

```
┌─────────┐    WebSocket     ┌──────────────┐    HTTP POST     ┌─────────┐
│  Judge   │◄───────────────►│  Node.js WS  │◄────────────────│   PHP   │
│ Browser  │   (port 8081)   │   Server     │   (port 8082)   │  AJAX   │
└─────────┘                  └──────────────┘                  └─────────┘
                                    ▲
┌─────────┐    WebSocket            │
│  Admin   │◄───────────────────────┘
│ Browser  │
└─────────┘
```

- **Admin activates a band** → PHP notifies WS server → WS broadcasts to all judges
- **Judge submits scores** → PHP notifies WS server → WS broadcasts to all admins
- If WebSocket is unavailable, both admin and judge fall back to HTTP polling

---

## WebSocket Server

The WebSocket server runs as a separate Node.js process alongside Apache/PHP.

### Ports

| Port | Purpose |
|------|---------|
| **8081** | WebSocket — browser clients connect here |
| **8082** | Internal HTTP — PHP sends notifications here (localhost only) |

### Commands

```bash
# Start the server
cd websocket
node server.js

# Health check
curl http://127.0.0.1:8082/health
```

### Running as a Background Service (Production)

Using **pm2** (recommended):
```bash
npm install -g pm2
cd websocket
pm2 start server.js --name botb-ws
pm2 save
pm2 startup    # Auto-start on system boot
```

Using **nssm** on Windows:
```bash
nssm install BotB-WebSocket "C:\Program Files\nodejs\node.exe" "C:\xampp\htdocs\BOB_SYSTEM\websocket\server.js"
nssm start BotB-WebSocket
```

---

## Printing Rankings

1. Go to **Admin → Rankings**
2. Select the round tab (Elimination / Grand Finals)
3. Set the **Top N** value to highlight qualifying bands
4. Click **Print Rankings** — the page uses print-optimized CSS

---

## Troubleshooting

| Issue | Solution |
|-------|----------|
| Database connection failed | Ensure MySQL is running in XAMPP. Check credentials in `config/db.php` |
| WebSocket not connecting | Ensure `node server.js` is running. Check that port 8081 is not blocked |
| Spinner not animating | Clear browser cache (`Ctrl+Shift+R`) |
| Scores not updating in real-time | Check WebSocket server is running; system will fall back to polling |
| Login not working | Run `database.sql` to create the admin account |
| PHP errors | Ensure PHP 8.0+ is installed. Check Apache error log |

---

## Technology Stack

- **Backend:** PHP 8.0+ (vanilla, no framework)
- **Database:** MySQL / MariaDB
- **Frontend:** Bootstrap 5.3, Bootstrap Icons, SweetAlert2
- **Real-time:** Node.js WebSocket (`ws` library)
- **Server:** Apache (XAMPP)

---

## License

This project was built for the NEUST Battle of the Bands 2026 competition.
