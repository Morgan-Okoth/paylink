# PayLink - M-Pesa Invoice Manager

A production-ready invoice management system for Kenyan small businesses with M-Pesa (Safaricom Daraja API) integration.

## Features

- **Invoice Management** - Create, edit, delete invoices with auto-generated numbers
- **Customer Management** - Full CRUD for customer records
- **M-Pesa Payments** - STK Push integration for mobile payments
- **Real-time Updates** - Async callback handling with idempotency
- **Dashboard** - Revenue stats, pending/overdue invoices
- **Audit Trail** - Full transaction logging

## Tech Stack

- Laravel 11
- PostgreSQL/MySQL
- Bootstrap 5
- Safaricom Daraja API

## Deployment to Laravel Cloud

### 1. Sign Up
Go to [cloud.laravel.com](https://cloud.laravel.com/sign-up) - $5 free credit, no credit card required.

### 2. Create Application
1. Click **New Application**
2. Connect GitHub → Select `paylink` repo
3. Set app name: `paylink`
4. Choose **PostgreSQL** database
5. Click **Create**

### 3. Deploy
Click **Deploy** - Laravel Cloud auto-deploys from GitHub.

### 4. Run Migrations
Go to **Commands** → Run:
```
php artisan migrate
```

### 5. Configure M-Pesa Callback
Set M-Pesa callback URL in Safaricom Developer Portal:
```
https://paylink-xxxxx.laravel.cloud/api/mpesa/callback
```

---

## Local Development

```bash
git clone https://github.com/Morgan-Okoth/paylink.git
cd paylink
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve
```

## M-Pesa Setup

1. Register at [Safaricom Developer Portal](https://developer.safaricom.co.ke)
2. Create an app to get Consumer Key/Secret
3. Get your Shortcode and Passkey
4. Set callback URL to your deployed app

## Usage Flow

1. **Register** - Create account with M-Pesa credentials
2. **Add Customer** - Enter customer name and phone number
3. **Create Invoice** - Set amount and due date
4. **Request Payment** - Click "Send STK Push"
5. **Customer Pays** - Customer enters M-Pesa PIN on their phone
6. **Auto-Update** - Invoice automatically marked as paid

## Database Schema

```
users ───────┬────── customers ─────── invoices
             │                            │
             └────── payments             │
                      │                    │
                      └─── transactions ───┘
```

## License

MIT