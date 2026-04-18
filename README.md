# Market Management System v2

A complete PHP + MySQL market administration system.
Rebuilt from scratch with all bugs fixed, security hardened,
and running directly on XAMPP without any `.htaccess` tricks.

---

## Quick Start (XAMPP)

### 1. Copy to XAMPP
Place the `mms/` folder inside your XAMPP `htdocs`:
```
C:\xampp\htdocs\mms\
```

### 2. Create the Database
1. Start **Apache** and **MySQL** in XAMPP Control Panel
2. Open **phpMyAdmin**: http://localhost/phpmyadmin
3. Click **SQL** tab and paste the contents of `setup.sql`
4. Click **Go** — this creates `market_db` with all tables + default admin

### 3. Open in Browser
```
http://localhost/mms/
```

### 4. Default Login
| Field    | Value      |
|----------|------------|
| Username | `admin`    |
| Password | `admin123` |

**Change this password immediately after first login.**

---

## Project Structure

```
mms/
├── index.php          ← Central router (single entry point)
├── login.php          ← Login page
├── register.php       ← Registration page
├── logout.php         ← Session destroy
├── config.php         ← DB config + CSRF + Flash helpers
├── auth.php           ← Session guard + role checker
├── setup.sql          ← Run this once to create DB + tables
│
├── pages/             ← All page logic lives here
│   ├── home.php
│   ├── dashboard.php
│   ├── vendor_report.php
│   ├── add_vendor.php
│   ├── edit_vendor.php
│   ├── delete_vendor.php
│   ├── vendor_profile.php
│   ├── vendor_dashboard.php
│   ├── search_vendor.php
│   ├── manage_shop.php
│   ├── add_shop.php
│   ├── edit_shop.php          ← NEW (was missing before)
│   ├── delete_shop.php
│   ├── add_payment.php
│   ├── delete_payment.php     ← NEW
│   ├── rent_record.php
│   ├── report.php
│   ├── pdf_report.php         ← Pure PHP, no FPDF needed
│   └── backup.php             ← XLS export (vendors/shops/payments)
│
├── includes/
│   ├── header.php
│   └── footer.php
│
└── assets/
    ├── css/style.css
    ├── js/main.js
    └── images/
```

---

## URL Pattern
All pages load through `index.php?page=<name>`:

| Page              | URL                                |
|-------------------|------------------------------------|
| Home              | `index.php`                        |
| Dashboard         | `index.php?page=dashboard`         |
| Vendors           | `index.php?page=vendor_report`     |
| Add Vendor        | `index.php?page=add_vendor`        |
| Manage Shops      | `index.php?page=manage_shop`       |
| Rent Records      | `index.php?page=rent_record`       |
| System Report     | `index.php?page=report`            |
| Download PDF      | `index.php?page=pdf_report`        |

No `.htaccess` or mod_rewrite needed. Works on plain XAMPP out of the box.

---

## Security Fixes Applied (vs previous version)

| Issue                        | Fix                                              |
|------------------------------|--------------------------------------------------|
| `BASE_URL` undefined crash   | Removed — no longer needed                       |
| `.htaccess` pointing to missing `.html` files | Removed entirely — query-string routing used |
| Unprotected `add_vendor` form | Auth guard on every page file                  |
| String-based shop FK         | Proper `shop_id` integer FK throughout           |
| N+1 query in rent records    | Single JOIN query with GROUP BY                  |
| XSS in `document.write()`   | Server-side rendering only, no raw URL injection |
| CSRF missing everywhere      | `csrf_field()` + `csrf_verify()` on all forms    |
| No role gate on backup       | `require_role('admin')` enforced                 |
| LIKE wildcard injection      | `%` and `_` escaped before binding               |
| No shop edit page            | `edit_shop.php` now exists                       |
| No payment delete            | `delete_payment.php` now exists                  |
| `vendor_id` never set        | Set in `login.php` for vendor role               |
| PDF silently drops rows      | Full pagination with multi-page support          |
| `edit_vendor` no CSS         | Uses shared `header.php` + `form-box`            |
| No duplicate validation      | Checked on register, add_shop, add_vendor        |

---

## Database Schema

```sql
users    (user_id, username, email, password, role, created_at)
shops    (shop_id, shop_name, owner_name, location, rent, created_at)
vendors  (vendor_id, name, phone, shop_id FK→shops, created_at)
payments (payment_id, vendor_id FK→vendors, amount, payment_method,
          payment_date, note, created_at)
```

---

## Requirements
- PHP 7.4+
- MySQL 5.7+ / MariaDB 10.3+
- XAMPP (Apache + MySQL)
- No external PHP libraries required
