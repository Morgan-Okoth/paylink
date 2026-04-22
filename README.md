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

## Quick Start

### Local Development

```bash
git clone https://github.com/Morgan-Okoth/paylink.git
cd paylink
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve
```

### M-Pesa Callbacks (Local)

Use cloudflared for local callback testing:

```bash
./cloudflared tunnel run paylink-dev
```

Set `APP_URL` to your cloudflared URL and update the callback URL in Safaricom Developer Portal.

## Deployment to Railway

### 1. Create Railway Project

```bash
railway init
railway project create paylink
```

### 2. Add MySQL Database

```bash
railway add mysql
```

### 3. Configure Environment Variables

In Railway Dashboard → Variables:

```
APP_NAME=PayLink
APP_ENV=production
APP_KEY=base64:YOUR_GENERATED_KEY
APP_DEBUG=false
APP_URL=https://your-app.railway.app

DB_CONNECTION=mysql
DB_HOST=from_railway_mysql_credentials
DB_PORT=3306
DB_DATABASE=railway
DB_USERNAME=root
DB_PASSWORD=YOUR_PASSWORD

SESSION_DRIVER=database
QUEUE_CONNECTION=database
CACHE_DRIVER=database
```

### 4. Deploy

```bash
railway up
```

### 5. Run Migrations

```bash
railway run php artisan migrate
```

### 6. Start Queue Worker

```bash
railway run php artisan queue:work --queue=mpesa-callbacks
```

### 7. M-Pesa Setup

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

## API Endpoints

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/api/mpesa/callback` | POST | M-Pesa payment callback |
| `/api/mpesa/validate` | POST | Transaction validation |

## Database Schema

```
users ───────┬────── customers ─────── invoices
             │                            │
             └────── payments              │
                      │                    │
                      └─── transactions ───┘
```

## Security Features

- Idempotent callback handling (prevents duplicate payments)
- Phone number validation (Kenya format: 254...)
- Database transactions for payment processing
- Full audit trail in transactions table

## Cron Jobs

```bash
# Mark overdue invoices daily at midnight
php artisan invoices:mark-overdue
```

## License

MIT