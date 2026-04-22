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
- MySQL (Railway)
- Bootstrap 5
- Safaricom Daraja API

## Deployment to Railway

### 1. Create Railway Project

Go to [railway.app](https://railway.app) → **New Project** → **Deploy from GitHub** → select `paylink`

### 2. Add MySQL Database

```bash
railway add mysql
```

### 3. Set Environment Variables

In Railway Dashboard → Variables, add:

```
APP_KEY=base64:GENERATED_KEY
APP_URL=https://paylink.railway.app
```

Railway will auto-fill `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` from your MySQL instance.

### 4. Deploy

Click **Deploy** in Railway dashboard.

### 5. Run Migrations

```bash
railway run php artisan migrate
```

### 6. Start Queue Worker

```bash
railway run php artisan queue:work --queue=mpesa-callbacks
```

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
4. Set the callback URL: `https://your-app.railway.app/api/mpesa/callback`

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

## Security Features

- Idempotent callback handling (prevents duplicate payments)
- Phone number validation (Kenya format: 254...)
- Database transactions for payment processing
- Full audit trail in transactions table

## License

MIT