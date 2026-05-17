# EasyFinder VTU Platform

A complete Virtual Top-Up (VTU) platform for Nigeria — sell mobile data bundles, airtime, cable TV subscriptions, electricity bills, and exam pins. Supports multiple data providers with live API integration.

**Live site:** https://rahausub.com.ng/easyfinder

---

## Table of Contents

- [Features](#features)
- [Stack](#stack)
- [Providers Supported](#providers-supported)
- [Project Structure](#project-structure)
- [Database Setup](#database-setup)
- [Setting Up Bardetech (New Provider)](#setting-up-bardetech-new-provider)
- [Admin Pages Guide (with Screenshots)](#admin-pages-guide-with-screenshots)
- [API Reference](#api-reference)
- [Recent Changes](#recent-changes)
- [Credentials & Secrets](#credentials--secrets)

---

## Features

- Buy data bundles (SME, Gifting, Corporate Gifting) for MTN, Glo, 9mobile, Airtel
- Airtime top-up for all networks
- Cable TV subscription (DSTV, GOtv, Startimes)
- Electricity bill payment (all DISCOs)
- Exam pins (WAEC, NECO)
- Multi-provider switching — change data provider from the admin panel in one click
- Wallet top-up for customers
- Agent management
- Transaction history and reporting

---

## Stack

| Layer | Technology |
|-------|-----------|
| Language | PHP 7+ |
| Database | MySQL (via PDO/CI-style active record) |
| Frontend | Bootstrap 4, jQuery |
| Payments | Monnify, Flutterwave (Rave) |
| APIs | Bardetech, Datastation, Husmodata |
| Auth | Session-based (PHP) |

---

## Providers Supported

| Provider | Services | Status |
|----------|----------|--------|
| **Bardetech** | Data bundles, Airtime, Cable, Electricity | ✅ Integrated |
| **Datastation** | Data bundles, Airtime | ✅ Integrated |
| **Husmodata** | Data bundles | ✅ Integrated |
| **EasyAccess** | Exam pins | ✅ Integrated |

---

## Project Structure

```
easyfinder/
├── app/
│   ├── Controller/
│   │   ├── TopupController.php       ← All provider API logic (data, airtime, cables, etc.)
│   │   ├── AdminController.php       ← Admin actions (update_provider, manage users, etc.)
│   │   └── ...
│   └── Model/
│       └── ...
├── config/
│   └── siteconfig.php                ← DB credentials, site settings
├── dashboard/
│   ├── manage-sme-data.php           ← Edit bundle prices + Bardetech plan IDs
│   ├── manage-bardetech-plans.php    ← View live Bardetech plans + Auto-Match button (NEW)
│   ├── cheap-data.php                ← Customer-facing cheap data purchase page
│   ├── data-topup.php                ← Standard data top-up page
│   ├── topup.php                     ← Airtime top-up page
│   └── layout/                       ← Shared header, sidebar, footer
├── inc/
│   └── user_session.inc.php          ← Session bootstrap (instantiates $TopupController)
└── migration_bardetech.sql           ← DB migration: adds bardetech_plan_id column
```

---

## Database Setup

### Run the Bardetech migration

Import this file once via phpMyAdmin or MySQL CLI:

```
easyfinder/migration_bardetech.sql
```

It does two things:

1. Adds `bardetech_plan_id` column to `sme_data_tbl`
2. Inserts Bardetech into `api_settings`

```sql
ALTER TABLE sme_data_tbl
  ADD COLUMN bardetech_plan_id VARCHAR(20) NULL DEFAULT NULL
  COMMENT 'Bardetech plan ID used when Bardetech is the active provider';

INSERT INTO api_settings (api_name, api_url, api_key, is_active)
VALUES ('bardetech', 'https://www.bardetech.com/api/', 'YOUR_TOKEN_HERE', 0)
ON DUPLICATE KEY UPDATE api_url = VALUES(api_url);
```

Replace `YOUR_TOKEN_HERE` with your real Bardetech API token before running.

---

## Setting Up Bardetech (New Provider)

### Step 1 — Run the DB migration

See [Database Setup](#database-setup) above.

### Step 2 — Add your Bardetech API token

Go to **Admin → Settings → API Settings** and set your token, or run:

```sql
UPDATE api_settings SET api_key = 'd98c2c835ac0579e3fa781b048893a2eafaf463c' WHERE api_name = 'bardetech';
```

### Step 3 — Map Bardetech Plan IDs to your bundles

Navigate to **Admin → Manage SME Data**. You will see a **Bardetech Plan ID** field for each bundle:

```
┌─────────────┬───────────┬─────────────┬──────────────┬───────────────────────────┐
│ Direct Price │ Our Price │ Data Bundle │ Data Duration │ Bardetech Plan ID         │
├─────────────┼───────────┼─────────────┼──────────────┼───────────────────────────┤
│ 850         │ 900       │ 2GB         │ 30 days      │ [  523  ] ← fill this     │
│ 1500        │ 1600      │ 5GB         │ 30 days      │ [       ] ← fill this     │
└─────────────┴───────────┴─────────────┴──────────────┴───────────────────────────┘
```

**OR** use the Auto-Match button (see Step 4).

### Step 4 — Auto-Match Plan IDs (Recommended)

Go to **Admin → Manage SME Data → "View & Sync Bardetech Plans"** button.

This opens the **Bardetech Plans Manager** page which:
- Shows all live Bardetech plans fetched directly from their API
- Lists Plan ID, Network, Type, Bundle Size, Validity, and Price
- Has an **Auto-Match Plans** button that automatically fills in Bardetech Plan IDs by matching bundle size and network

```
┌──────────────────────────────────────────────────────────────────────────────┐
│  Bardetech Plans Manager                                                      │
│                                                                              │
│  [ Auto-Match Plans ]  ← clicks this to auto-fill all Bardetech IDs         │
│                                                                              │
│  All (342) │ MTN (98) │ Glo (87) │ 9mobile (79) │ Airtel (78)              │
│                                                                              │
│  Plan ID │ Network │ Type          │ Bundle  │ Validity │ Price             │
│  ────────┼─────────┼───────────────┼─────────┼──────────┼──────────         │
│  523     │ MTN     │ SME 2         │ 2.0GB   │ 30days   │ ₦850.00           │
│  524     │ MTN     │ SME 2         │ 3.0GB   │ 30days   │ ₦1,200.00         │
│  525     │ MTN     │ SME 2         │ 5.0GB   │ 30days   │ ₦1,700.00         │
│  ...                                                                         │
└──────────────────────────────────────────────────────────────────────────────┘
```

### Step 5 — Switch active provider to Bardetech

Go to **Admin → Settings → Change Provider** and select **Bardetech**.

All new data bundle purchases will now route through Bardetech.

---

## Admin Pages Guide (with Screenshots)

### Login Page
**URL:** `/easyfinder/dashboard/login`

```
┌─────────────────────────────────────┐
│          EasyFinder Admin           │
│                                     │
│  Email:    [____________________]   │
│  Password: [____________________]   │
│                                     │
│         [  Login  ]                 │
└─────────────────────────────────────┘
```

Admin credentials:
- Email: `softwareclone100@gmail.com`
- Password: `123456`

---

### Manage SME Data Page
**URL:** `/easyfinder/dashboard/manage-sme-data`

**What changed:** Added `Bardetech Plan ID` field (last column) for every bundle row. Also added a **"View & Sync Bardetech Plans"** button in the header and info banner.

```
 ┌────────────────────────────────────────────────────────────────────────────────────┐
 │  Manage SME/Cheap DATA Bundle              [ Sync from Bardetech API → ]           │
 │                                                                                    │
 │  ⓘ Bardetech Provider: Fill the Bardetech Plan ID for each bundle, or use         │
 │    Auto-Match to sync IDs from the live Bardetech API automatically.               │
 │                                    [ View & Sync Bardetech Plans ]                 │
 │  ──────────────────────────────────────────────────────────────────────────────    │
 │  Bundle ID: 1 | Network ID: 1 (MTN)                                               │
 │                                                                                    │
 │  Direct Price  Our Price  Data Bundle  Duration   Bardetech Plan ID [Set ✓]       │
 │  [  850  ]    [ 900  ]   [ 2GB  ]     [30days]   [ 523            ]               │
 │                                                                                    │
 │  Bundle ID: 2 | Network ID: 1 (MTN)                                               │
 │  [  1200 ]    [ 1300 ]   [ 5GB  ]     [30days]   [                ] ← Not set     │
 │  ──────────────────────────────────────────────────────────────────────────────    │
 │                        [ Update now ]  [ Auto-Match from Bardetech API ]           │
 └────────────────────────────────────────────────────────────────────────────────────┘
```

**Files changed:** `easyfinder/dashboard/manage-sme-data.php`

---

### Bardetech Plans Manager Page *(New Page)*
**URL:** `/easyfinder/dashboard/manage-bardetech-plans`

**What it does:** Shows all live Bardetech data plans fetched from their API. Click **Auto-Match Plans** to automatically fill Bardetech Plan IDs in your bundle table.

```
 ┌────────────────────────────────────────────────────────────────────────────────────┐
 │  Bardetech Plans Manager                                                           │
 │  Live plans fetched directly from the Bardetech API                               │
 │                                                                                    │
 │  ┌──────────────────────────────────────────────────────────────────────────────┐  │
 │  │  Auto-Match Bardetech Plan IDs                    [ Auto-Match Plans ] [ Manual Edit ] │
 │  │  Automatically matches your bundles to Bardetech plan IDs by size+network   │  │
 │  └──────────────────────────────────────────────────────────────────────────────┘  │
 │                                                                                    │
 │  Bardetech Available Plans (Live)                              342 plans fetched ✓ │
 │                                                                                    │
 │  All (342) │ MTN (98) │ Glo (87) │ 9mobile (79) │ Airtel (78)                   │
 │  ──────────────────────────────────────────────────────────────────────────────    │
 │  Plan ID │ Network │ Type            │ Bundle  │ Validity │ Price (₦)            │
 │  523     │ MTN     │ [SME 2]         │ 2.0GB   │ 30days   │ ₦850.00              │
 │  524     │ MTN     │ [SME 2]         │ 3.0GB   │ 30days   │ ₦1,200.00            │
 │  418     │ MTN     │ [GIFTING]       │ 500MB   │ 30days   │ ₦230.00              │
 │  ...                                                                               │
 │                                                                                    │
 │  ℹ How to use: Copy the Plan ID and paste it into Manage SME Data,               │
 │    or click Auto-Match Plans above.                                               │
 └────────────────────────────────────────────────────────────────────────────────────┘
```

**Files added:** `easyfinder/dashboard/manage-bardetech-plans.php`

---

### Cheap Data Purchase Page (Customer-facing)
**URL:** `/easyfinder/dashboard/cheap-data`

**What changed:** Fixed status check bug — Bardetech returns `"successful"` (lowercase) but old code checked `"Successful"`. Purchases now succeed correctly.

```
 ┌───────────────────────────────────────┐
 │  Buy Cheap Data Bundle                │
 │                                       │
 │  Network:  [ MTN ▼ ]                  │
 │  Plan:     [ 2GB - ₦900 ▼ ]          │
 │  Phone:    [ 08012345678 ]            │
 │                                       │
 │           [ Buy Now ]                 │
 │                                       │
 │  ✓ Transaction successful             │  ← was failing before fix
 └───────────────────────────────────────┘
```

**Files changed:** `easyfinder/dashboard/cheap-data.php`

---

### Change Provider Settings
**URL:** `/easyfinder/dashboard/` → Settings → Change Provider

**What to do:** After setting up Bardetech Plan IDs, come here and switch the active provider to **Bardetech**.

```
 ┌───────────────────────────────────────┐
 │  Change Data Provider                 │
 │                                       │
 │  ○ Datastation                        │
 │  ○ Husmodata                          │
 │  ● Bardetech          ← select this   │
 │                                       │
 │         [ Save Provider ]             │
 └───────────────────────────────────────┘
```

---

## API Reference

### Bardetech Endpoints Used

| Method | Endpoint | Purpose |
|--------|----------|---------|
| `GET` | `/api/user/` | Check balance & account |
| `GET` | `/api/network/?network_id=1` | Fetch available data plans |
| `POST` | `/api/data/` | Purchase a data bundle |

### Buy Data Request (POST `/api/data/`)

```json
{
  "network": 1,
  "mobile_number": "08012345678",
  "plan": 523,
  "Ported_number": true
}
```

### Buy Data Response

```json
{
  "Status": "successful",
  "plan_name": "MTN SME 2 - 2.0GB",
  "plan_amount": "850.00",
  "Mobile_Number": "08012345678",
  "Ported_number": true,
  "plan_network": "MTN",
  "plan_type": "SME 2",
  "plan": "2.0GB"
}
```

> **Note:** The `Status` field comes back as `"successful"` (all lowercase). The code normalizes this with `strtolower()` before comparing.

### Network ID Map

| Network | Bardetech ID |
|---------|-------------|
| MTN | 1 |
| Glo | 2 |
| 9mobile | 3 |
| Airtel | 4 |

---

## Recent Changes

| Commit | File | What Changed |
|--------|------|-------------|
| `f9d6b47e` | `TopupController.php` | Added `FetchBardePlans()` to fetch live Bardetech plans. Made `GetCheapDataPlan()` provider-aware (returns Bardetech plan IDs when Bardetech is active). `BuyCheaperDataBundle()` now normalizes `Status` field with `strtolower()`. `Update_SME_Data()` accepts `bardetech_plan_id`. |
| `e6a19b7d` | `cheap-data.php` | Fixed case-insensitive status check: `strtolower($result->Status) === 'successful'` |
| `d1048d95` | `migration_bardetech.sql` | DB migration: adds `bardetech_plan_id` column, inserts Bardetech into `api_settings` |
| `ffa919dd` | `README.md` | Comprehensive documentation added |
| `77e1b6d1` | `manage-sme-data.php` | Added Bardetech Plan ID field per bundle row; "View & Sync" button |

---

## Credentials & Secrets

> Store these securely. Do not commit real secrets to public repos.

| Service | Where to configure |
|---------|--------------------|
| Bardetech API token | `api_settings` table → `api_key` where `api_name='bardetech'` |
| Database | `easyfinder/config/siteconfig.php` |
| Admin login | `users` table (bcrypt password) |
| Monnify | `easyfinder/config/` |

---

## Gotchas

1. **Run the SQL migration first** — without `bardetech_plan_id` column, saving Bardetech IDs will silently fail.
2. **Switch provider in Settings** — setting Bardetech Plan IDs doesn't activate Bardetech; you must also go to Settings → Change Provider.
3. **Bardetech plan IDs change** — if bundles stop working, visit **Bardetech Plans Manager** → Auto-Match to refresh IDs.
4. **Status is case-sensitive in older code** — the `strtolower()` fix in `cheap-data.php` is required; don't revert it.
5. **Provider switching** — `AdminController::update_provider()` resets all providers to inactive before setting the chosen one active. So switching back is safe.
