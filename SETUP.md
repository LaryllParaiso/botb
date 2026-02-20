# BotB Tabulator — Step-by-Step Setup Guide

This guide walks you through setting up the Battle of the Bands Tabulator System from scratch on a Windows machine using XAMPP.

---

## Prerequisites

1. **XAMPP** — Download from [https://www.apachefriends.org/](https://www.apachefriends.org/)
   - Includes Apache, MySQL, PHP 8.0+
2. **Node.js** — Download from [https://nodejs.org/](https://nodejs.org/) (LTS version recommended)
3. **A modern web browser** — Chrome, Firefox, or Edge

---

## Step 1: Install XAMPP

1. Run the XAMPP installer
2. Install to the default location: `C:\xampp`
3. Make sure **Apache** and **MySQL** components are selected

---

## Step 2: Place the Project Files

Copy the entire `BOB_SYSTEM` folder into:

```
C:\xampp\htdocs\BOB_SYSTEM\
```

Your folder structure should look like:
```
C:\xampp\htdocs\BOB_SYSTEM\
├── admin\
├── assets\
├── config\
├── controllers\
├── database.sql
├── ...
```

---

## Step 3: Start XAMPP

1. Open **XAMPP Control Panel**
2. Click **Start** next to **Apache**
3. Click **Start** next to **MySQL**
4. Both should show green "Running" status

---

## Step 4: Create the Database

### Option A: Using phpMyAdmin (GUI)

1. Open your browser and go to: [http://localhost/phpmyadmin](http://localhost/phpmyadmin)
2. Click the **Import** tab at the top
3. Click **Choose File** and select: `C:\xampp\htdocs\BOB_SYSTEM\database.sql`
4. Click **Go** at the bottom
5. You should see a success message

### Option B: Using Command Line

Open a terminal (Command Prompt or PowerShell) and run:

```bash
C:\xampp\mysql\bin\mysql.exe -u root < C:\xampp\htdocs\BOB_SYSTEM\database.sql
```

---

## Step 5: Create Logo Uploads Directory

The system stores uploaded logos in `assets/uploads/logos/`. You must create this folder manually:

```bash
mkdir C:\xampp\htdocs\BOB_SYSTEM\assets\uploads\logos
```

Or manually create the folder structure: `assets/uploads/logos/` inside the project directory.

> **Important:** Apache needs write permissions to this folder. On Windows with XAMPP, this is usually automatic. If logo uploads fail, check the folder permissions.

---

## Step 6: (Optional) Load Test Data

If you want to test with sample bands, judges, and scores:

### Option A: phpMyAdmin

1. In phpMyAdmin, select the `botb_tabulator` database from the left sidebar
2. Click **Import** → **Choose File** → select `test_data.sql`
3. Click **Go**

### Option B: Command Line

```bash
C:\xampp\mysql\bin\mysql.exe -u root botb_tabulator < C:\xampp\htdocs\BOB_SYSTEM\test_data.sql
```

---

## Step 7: Verify Database Connection

The default database config uses:
- **Host:** `127.0.0.1`
- **Database:** `botb_tabulator`
- **User:** `root`
- **Password:** *(empty)*

If your MySQL has a different password, edit `config\db.php`:

```php
private const DB_USER = 'root';
private const DB_PASS = '';  // ← Change this if you set a MySQL password
```

---

## Step 8: Install WebSocket Dependencies

Open a terminal and run:

```bash
cd C:\xampp\htdocs\BOB_SYSTEM\websocket
npm install
```

This installs the `ws` (WebSocket) library.

---

## Step 9: Start the WebSocket Server

In the same terminal:

```bash
node server.js
```

You should see:
```
[WS] WebSocket server running on ws://localhost:8081
[HTTP] Internal notification server on http://127.0.0.1:8082
```

> **Keep this terminal open** while using the system. The WebSocket server must be running for real-time updates. If you close it, the system will still work but will use slower polling instead.

---

## Step 10: Open the Application

Open your browser and go to:

**[http://localhost/BOB_SYSTEM/](http://localhost/BOB_SYSTEM/)**

---

## Step 11: Log In

### Admin Login
- **Email:** `admin@botb.com`
- **Password:** `Admin@2026`

### Judge Login
Judges are created by the admin. After creating a judge in the admin dashboard, use the judge's email and password to log in.

---

## Usage Workflow

### Before the Competition

1. Log in as **Admin**
2. Go to **Judges** → Add all judges with name, email, and password
3. Go to **Bands** → Add all competing bands, assign round and performance order

### During the Competition

1. **Admin:** Click **Activate** on the first band to perform
2. **Judges:** The scoring form appears automatically on their screens
3. **Judges:** Enter scores for each criteria and click **Submit Scores**
4. **Admin:** Watch the pending judges panel — it updates in real-time
5. Once all judges have submitted, **Admin** activates the next band
6. Repeat until all bands have performed

### After the Competition

1. **Admin:** Go to **Rankings** to view results
2. Set the **Top N** value to highlight qualifying bands
3. Click **Print Rankings** for a printable report with watermark, judge signature row, and signatories
4. Click **Excel** to download a styled `.xlsx` file with top-N highlighting

### Configuring Event Settings

1. **Admin:** Go to **Settings**
2. **Event Configuration tab** — Set event title, subtitle, and add/remove signatories (name, title, font size)
3. **Logo Configuration tab** — Upload header left/right logos and watermark (max 5MB, PNG/JPG/SVG)
4. **Admin Credentials tab** — Update admin name, email, or password

---

## Running on a Local Network

To allow judges on other devices (phones, tablets, laptops) to connect:

1. Find your computer's local IP address:
   ```bash
   ipconfig
   ```
   Look for `IPv4 Address` (e.g., `192.168.1.100`)

2. Judges connect to: `http://192.168.1.100/BOB_SYSTEM/`

3. Make sure:
   - Windows Firewall allows Apache (port 80) and WebSocket (port 8081)
   - All devices are on the same Wi-Fi / LAN network

### Firewall Rules (if needed)

Open PowerShell as Administrator:
```powershell
netsh advfirewall firewall add rule name="BotB Apache" dir=in action=allow protocol=TCP localport=80
netsh advfirewall firewall add rule name="BotB WebSocket" dir=in action=allow protocol=TCP localport=8081
```

---

## Stopping the System

1. Press `Ctrl+C` in the terminal running the WebSocket server
2. In XAMPP Control Panel, click **Stop** for Apache and MySQL

---

## Resetting All Data

If you need to start fresh:

1. **Admin → Scores → System Reset** — Type `RESET_ALL` and confirm
2. Or re-import `database.sql` via phpMyAdmin (drops and recreates everything)

---

## Troubleshooting

| Problem | Solution |
|---------|----------|
| "Database connection failed" | Start MySQL in XAMPP Control Panel |
| Page not loading | Start Apache in XAMPP Control Panel |
| "Access denied" on login | Re-import `database.sql` to recreate the admin account |
| WebSocket not connecting | Make sure `node server.js` is running in a terminal |
| Judges can't connect from other devices | Check firewall rules and use your local IP address |
| Port 80 already in use | Stop Skype/IIS or change Apache port in XAMPP config |
| Port 8081 already in use | Edit `websocket/server.js` line `const WS_PORT = 8081;` to another port, and update `WS_URL` in `admin.js` and `judge.js` |
| Logo upload fails | Ensure `assets/uploads/logos/` directory exists and is writable by Apache |
| Excel download empty | Ensure rankings data is loaded first by selecting a round on the Rankings page |
