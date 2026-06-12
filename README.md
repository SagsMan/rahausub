# Rahausub Web Dashboard — Admin & User Pages

> Web dashboard for the **Rahausub** VTU platform  
> Deployed at: `https://rahausub.com.ng/easyfinder/dashboard/`  
> Framework: EduTech OOP PHP (custom framework)

---

## New Features Added (June 2026)

### 1. Referral System (`referral.php`)
Users can view and share their unique referral link. Shows:
- Referral code + shareable link
- Total users referred
- Total earnings from referrals
- Table of referred users

### 2. My Notifications (`my-notifications.php`)
Users can see all in-app notifications sent to them:
- Mark individual notifications as read
- Mark all as read
- Colour-coded by type (success / info / warning / danger)
- Shows unread badge count

### 3. Admin: Notification List (`admin-notifications.php`)
Full admin management of notifications:
- List all notifications with status (draft / sent / failed)
- Filter by type, priority, status
- Delivery stats (recipients / delivered / read / failed)
- Links to create, detail, delete

### 4. Admin: Create & Send Notification (`admin-notification-create.php`)
Rich form to compose and send notifications:
- **Target**: All users or specific user by email
- **Type**: Important / Update / Promotion / System Alert / General
- **Priority**: Low / Medium / High
- **Channels**: In-app only | + Email (Resend) | + SMS (BulkSMS Nigeria) | + Push (FCM)
- **Schedule**: Send now or pick a future date/time
- Preview before sending

### 5. Admin: Notification Detail (`admin-notification-detail.php?id=N`)
Per-notification delivery report:
- Total sent / delivered / read / failed per channel
- Per-user delivery table (email, status, timestamps)
- Resend failed deliveries

### 6. Admin: API Settings (`admin-notification-settings.php`)
Configure external channels:
- **Resend** — Email API key + sender address
- **BulkSMS Nigeria** — API username + password
- Test connection buttons

### 7. Admin: AJAX Handler (`admin-notification-ajax.php`)
Handles all async actions:
- `delete` — Delete a notification
- `resend` — Retry failed deliveries
- `stats` — Return delivery stats as JSON
- `export` — Export delivery report as CSV

---

## Directory Structure

```
easyfinder/dashboard/
├── referral.php                  ← Referral page (user)
├── my-notifications.php          ← Notification inbox (user)
├── admin-notifications.php       ← Admin: notification list
├── admin-notification-create.php ← Admin: compose & send
├── admin-notification-detail.php ← Admin: delivery report
├── admin-notification-settings.php ← Admin: email/SMS settings
├── admin-notification-ajax.php   ← Admin: AJAX/REST handler
├── topup.php                     ← Wallet top-up
├── data-topup.php                ← Data purchase
├── wallet-transaction.php        ← Transaction history
├── manage-user.php               ← Admin: user management
├── ... (existing pages)
```

---

## Database Tables Added

Run `setup_tables.sql` (in the API repo) on `eduowrav_rahausub`:

| Table | Used By |
|-------|---------|
| `notifications_tbl` | `my-notifications.php`, `admin-notifications.php` |
| `admin_notifications_tbl` | `admin-notifications.php`, `admin-notification-create.php` |
| `admin_notif_delivery_tbl` | `admin-notification-detail.php` |
| `admin_notif_api_settings` | `admin-notification-settings.php` |
| `referal_tbl` | `referral.php` |
| `referal_earn_transaction_tbl` | `referral.php` |
| `device_tokens` | FCM push from API |

---

## Admin Notification Channels

| Channel | Config Required | Provider |
|---------|-----------------|---------|
| In-app | None | Built-in `notifications_tbl` |
| Email | Resend API key | [resend.com](https://resend.com) |
| SMS | BulkSMS username + password | [bulksmsnigeria.com](https://bulksmsnigeria.com) |
| Push | Firebase SA JSON on server | Firebase FCM v1 |

Configure email and SMS credentials at:  
`/easyfinder/dashboard/admin-notification-settings`

---

## Related API Endpoints

All notification and referral data is also accessible via the APK API:

| Action | URL |
|--------|-----|
| Get notifications | `GET /api.php?action=notifications&token=TOKEN` |
| Get unread count | `GET /api.php?action=get_unread_count&token=TOKEN` |
| Mark read | `POST /api.php?action=mark_notification_read&token=TOKEN` |
| Get referral stats | `GET /api.php?action=referral&token=TOKEN` |

See [rahausub-apk-api](https://github.com/SagsMan/rahausub-apk-api) for full API docs.

---

## Deployment

| Item | Value |
|------|-------|
| Server | premium102.web-hosting.com (cPanel) |
| Path | `/home/eduowrav/rahausub.com.ng/easyfinder/dashboard/` |
| Database | `eduowrav_rahausub` |
| API repo | [rahausub-apk-api](https://github.com/SagsMan/rahausub-apk-api) |

