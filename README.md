# Active Picklelabs — Court Booking Web App

A full PHP + MySQL web app built from the "Active Picklelabs" mockup: a public
marketing site plus a client portal (book courts / open play) and an admin
portal (manage courts, services, booking requests, and clients).

## Tech stack
- PHP (procedural, no framework) + PDO/MySQL
- Plain CSS (`assets/css/styles.css`) — no build step
- Plain JavaScript (`assets/js/main.js`)
- MySQL via XAMPP

## File structure
```
activepicklelabs/
├── admin/                  Admin-only pages (requires admin login)
│   ├── dashboard.php
│   ├── requests.php        List + confirm/cancel bookings
│   ├── request-details.php Single booking detail
│   ├── courts.php          Manage courts
│   ├── services.php        Manage homepage "Our Services" cards
│   ├── clients.php         List/disable client accounts
│   └── settings.php        Edit site contact info
├── assets/
│   ├── css/styles.css      All styling
│   └── js/main.js          All client-side logic
├── client/                 Logged-in client pages
│   ├── dashboard.php
│   ├── book-court.php      Booking form
│   ├── my-bookings.php     List + cancel own bookings
│   └── profile.php
├── components/             Shared includes (navbar, footer, sidebars)
├── database/
│   ├── activepicklelabs.sql  Schema + seed courts/services
│   └── create_admin.php      One-time admin account creator
├── includes/
│   ├── db.php                 PDO connection
│   ├── auth.php                Session/login/register functions
│   ├── helpers.php             Small shared utilities
│   └── booking-functions.php   All courts/services/bookings data logic
├── index.php                Public landing page
├── login.php
├── register.php
└── logout.php
```

## Setup (XAMPP)

1. Copy the `activepicklelabs` folder into your XAMPP `htdocs` directory, e.g.
   `C:\xampp\htdocs\activepicklelabs` (Windows) or `/Applications/XAMPP/htdocs/activepicklelabs` (Mac).
2. Start **Apache** and **MySQL** from the XAMPP Control Panel.
3. Open `http://localhost/phpmyadmin`, create nothing manually — instead click
   **Import**, choose `database/activepicklelabs.sql`, and run it. This creates
   the `activepicklelabs` database with all tables plus seed courts and services.
4. Check `includes/db.php` — the defaults (`localhost` / `root` / empty password)
   match a stock XAMPP install. Change them if your MySQL is configured differently.
5. Visit `http://localhost/activepicklelabs/database/create_admin.php` **once**
   to create your first admin login (this hashes the password correctly using
   PHP itself). Then delete `create_admin.php`.
6. Visit `http://localhost/activepicklelabs/` — the site is live.
   - Register a normal account at `register.php` to test the client flow.
   - Log in with your admin account to manage courts, services, and requests.

## How it works
- **Public site** (`index.php`) pulls live services and upcoming open-play
  sessions straight from the database.
- **Clients** register, log in, book a court (private rental or open play),
  view their bookings, and cancel them.
- **Admins** confirm or cancel booking requests, manage the court list and
  hourly rates, edit the homepage services, view/disable client accounts, and
  edit site-wide contact settings.
- Every page checks `isLoggedIn()` / `isAdmin()` from `includes/auth.php`
  before showing protected content, and all SQL goes through PDO prepared
  statements.

## Default seed data
- 4 courts (`Court 1`–`Court 4`)
- 6 services matching the mockup's "Our Services" section
- 1 settings row (site name, tagline, contact info)
