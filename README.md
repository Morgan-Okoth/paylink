# PayLink - M-Pesa Invoice Manager

A production-ready invoice management system for Kenyan small businesses with M-Pesa (Safaricom Daraja API) integration, deployed on Cloudflare Pages with D1 database.

## Features

- **Invoice Management** - Create, edit, delete invoices with auto-generated numbers
- **Customer Management** - Full CRUD for customer records
- **M-Pesa Payments** - STK Push integration for mobile payments
- **Real-time Updates** - Async callback handling with idempotency
- **Dashboard** - Revenue stats, pending/overdue invoices
- **Audit Trail** - Full transaction logging

## Tech Stack

- Laravel 11
- Cloudflare Pages (Workers)
- Cloudflare D1 (SQLite)
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

## Deployment to Cloudflare Pages

### 1. Create D1 Database

```bash
npm install -g wrangler
wrangler login  # Login to Cloudflare
wrangler d1 create paylink-db  # Create D1 database
```

Save the `database_id` from the output.

### 2. Configure wrangler.toml

Update `wrangler.toml` with your database ID:

```toml
[[d1_databases]]
binding = "DB"
database_name = "paylink-db"
database_id = "YOUR_DATABASE_ID_HERE"
```

### 3. Push Schema to D1

```bash
wrangler d1 execute paylink-db --file=./database/migrations/*.sql
```

Or use SQL file:
```bash
wrangler d1 execute paylink-db --local --file=./schema.sql
```

### 4. Deploy to Cloudflare Pages

```bash
npx wrangler pages project create paylink
npx wrangler pages deploy
```

### 5. Set Environment Variables in Cloudflare Dashboard

Go to Workers & Pages → paylink → Settings → Variables:

```
APP_NAME=PayLink
APP_ENV=production
APP_KEY=base64:YOUR_GENERATED_KEY
APP_DEBUG=false
APP_URL=https://paylink.YOUR_SUBDOMAIN.workers.dev

CLOUDFLARE_D1_DATABASE_ID=YOUR_DATABASE_ID
CLOUDFLARE_ACCOUNT_ID=YOUR_ACCOUNT_ID
CLOUDFLARE_API_TOKEN=YOUR_API_TOKEN
```

### 6. Run Migrations

```bash
wrangler d1 execute paylink-db --remote -e production -- --file=./migrations/schema.sql
```

## M-Pesa Setup

1. Register at [Safaricom Developer Portal](https://developer.safaricom.co.ke)
2. Create an app to get Consumer Key/Secret
3. Get your Shortcode and Passkey
4. Set the callback URL: `https://paylink.YOUR_SUBDOMAIN.workers.dev/api/mpesa/callback`

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
             └────── payments             │
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