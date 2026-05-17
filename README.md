# VTU Script — EasyFinder

A full-featured Virtual Top-Up (VTU) platform for Nigeria built with PHP/MySQL. Users can buy data bundles, airtime, pay TV subscriptions, electricity bills, and access exam pin checkers through multiple provider APIs.

## Live Site

🌐 [https://rahausub.com.ng/easyfinder/dashboard/login](https://rahausub.com.ng/easyfinder/dashboard/login)

---

## Features

- **Data Bundle Purchase** — SME/Cheap data via Datastation, Husmodata, or Bardetech
- **Airtime Top-Up** — All major Nigerian networks (MTN, Airtel, Glo, 9Mobile)
- **TV Subscription** — DStv, GOtv, Startimes, Showmax
- **Electricity Bill Payment** — All major DISCOs
- **Exam Pin Checker** — WAEC, JAMB, NECO, NABTEB
- **Wallet System** — Fund wallet, transfer, transaction history
- **Multi-Provider Support** — Switch API provider from admin settings
- **CAC Registration** — Business and company registration support
- **NIN Verification / Modification**
- **Referral & Agent System**

---

## Supported Data Providers

| Provider | Status | Notes |
|----------|--------|-------|
| Datastation | ✅ Working | Default provider |
| Husmodata | ✅ Working | Switchable from Settings |
| Bardetech | ✅ Working | See setup below |

---

## Bardetech Provider Setup

To enable Bardetech as the active data provider:

### 1. Run the database migration
```sql
-- Add Bardetech plan ID column to sme_data_tbl
ALTER TABLE `sme_data_tbl` 
  ADD COLUMN IF NOT EXISTS `bardetech_plan_id` VARCHAR(50) DEFAULT NULL;

-- Add Bardetech to api_settings (if not already there)
INSERT INTO `api_settings` (`api_name`, `api_url`, `api_key`, `is_active`)
SELECT 'bardetech', 'https://www.bardetech.com/api/data/', 'YOUR_BARDETECH_TOKEN', 0
WHERE NOT EXISTS (SELECT 1 FROM `api_settings` WHERE LOWER(`api_name`) = 'bardetech');
```
> The full migration file is at `easyfinder/migration_bardetech.sql`

### 2. Set Bardetech Plan IDs
1. Log in as admin
2. Go to **Manage SME Data** (`/dashboard/manage-sme-data`)
3. Fill in the **Bardetech Plan ID** for each data bundle (e.g. `523` for MTN 2GB)
4. Save

### 3. Activate Bardetech
1. Go to **Settings** → **Change Provider**
2. Select **Bardetech** and save

Once active, the cheap-data page will show only plans that have a Bardetech Plan ID set, and purchases will route through Bardetech's API automatically.

---

## Where Things Are

```
easyfinder/
├── app/
│   └── Controller/
│       ├── TopupController.php       ← Data purchase, plan fetching, Bardetech logic
│       ├── AdminController.php       ← Provider switching (update_provider), plan management
│       ├── WalletController.php      ← Wallet debit/credit/refund
│       └── UserController.php        ← Auth, profile
├── dashboard/
│   ├── cheap-data.php                ← SME/Cheap data purchase page
│   ├── data-topup.php                ← Regular data purchase (VTPass)
│   ├── manage-sme-data.php           ← Admin: edit data bundles + Bardetech Plan IDs
│   ├── manage-plan.php               ← Admin: manage data plans per provider
│   ├── topup.php                     ← Airtime top-up
│   ├── cable-tv.php                  ← TV subscription
│   ├── electricity-bill.php          ← Electricity payments
│   └── exam-pin.php                  ← Exam pin checker
├── inc/
│   ├── config.inc.php                ← App bootstrap, controller instantiation
│   ├── siteconfig.inc.php            ← Site constants (URLs, API keys from DB)
│   └── get-data-ajax.inc.php         ← AJAX handlers (provider switch, plan edit, etc.)
├── migration_bardetech.sql           ← DB migration for Bardetech support
└── utiplus(3).sql                    ← Full database schema + seed data
```

---

## Key Changes (May 2026)

### `easyfinder/app/Controller/TopupController.php`
- **`GetCheapDataPlan()`** — Now provider-aware: when Bardetech is active, it returns plans using `bardetech_plan_id` instead of the default `plan_id`
- **`BuyCheaperDataBundle()`** — Now normalizes the `Status` field across all providers (Bardetech returns `"successful"` lowercase; Datastation returns `"Successful"` capitalized). Also maps Bardetech's `ident` field to `id` for uniform transaction tracking
- **`Update_SME_Data()`** — Accepts optional `$bardetech_plan_id` parameter to save plan IDs per bundle

### `easyfinder/dashboard/cheap-data.php`
- Fixed success detection: changed `$Airtime_result->Status == 'Successful'` to `strtolower($Airtime_result->Status ?? '') === 'successful'` so Bardetech transactions are correctly marked as successful

### `easyfinder/dashboard/manage-sme-data.php`
- Added **Bardetech Plan ID** input field for each bundle row
- Added informational alert explaining how to use the field
- Updated form handler to save `bardetech_plan_id` alongside existing fields

### `easyfinder/migration_bardetech.sql` *(new file)*
- SQL to add `bardetech_plan_id` column to `sme_data_tbl`
- SQL to insert Bardetech into `api_settings` if not already present

---

## Tech Stack

- **Backend**: PHP 7+, MySQL
- **Frontend**: Bootstrap 4, jQuery, SweetAlert2
- **Payment**: Paystack, Monnify, Flutterwave
- **Data APIs**: Datastation, Husmodata, Bardetech, VTPass
- **Airtime Detection**: Reloadly API

---

## Admin Credentials (Demo)

> ⚠️ Change these before going to production!

- **URL**: `/easyfinder/dashboard/login`
- **Email**: `softwareclone100@gmail.com`
- **Password**: `123456`

---

## Running Locally

1. Import `easyfinder/utiplus(3).sql` into your MySQL database
2. Update `edutech_settings` table with your site URL and API keys
3. Run the Bardetech migration: `easyfinder/migration_bardetech.sql`
4. Point your web server root to the project directory
5. Set up `.env` or update the DB credentials in your PHP config
