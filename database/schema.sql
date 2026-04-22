-- PayLink D1 Database Schema

-- Users table (business owners)
CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    email TEXT NOT NULL UNIQUE,
    email_verified_at TEXT,
    password TEXT NOT NULL,
    business_name TEXT NOT NULL,
    phone_number TEXT NOT NULL,
    mpesa_shortcode TEXT NOT NULL,
    mpesa_consumer_key TEXT NOT NULL,
    mpesa_consumer_secret TEXT NOT NULL,
    mpesa_passkey TEXT NOT NULL,
    remember_token TEXT,
    created_at TEXT DEFAULT (datetime('now')),
    updated_at TEXT DEFAULT (datetime('now'))
);

CREATE INDEX IF NOT EXISTS idx_users_email ON users(email);

-- Customers table
CREATE TABLE IF NOT EXISTS customers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    name TEXT NOT NULL,
    email TEXT,
    phone_number TEXT NOT NULL,
    address TEXT,
    created_at TEXT DEFAULT (datetime('now')),
    updated_at TEXT DEFAULT (datetime('now')),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_customers_user_email ON customers(user_id, email);
CREATE INDEX IF NOT EXISTS idx_customers_user_phone ON customers(user_id, phone_number);

-- Invoices table
CREATE TABLE IF NOT EXISTS invoices (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    customer_id INTEGER NOT NULL,
    invoice_number TEXT NOT NULL UNIQUE,
    amount REAL NOT NULL,
    due_date TEXT NOT NULL,
    status TEXT DEFAULT 'pending' CHECK(status IN ('pending', 'paid', 'overdue')),
    paid_at TEXT,
    mpesa_checkout_request_id TEXT,
    notes TEXT,
    created_at TEXT DEFAULT (datetime('now')),
    updated_at TEXT DEFAULT (datetime('now')),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_invoices_user_status ON invoices(user_id, status);
CREATE INDEX IF NOT EXISTS idx_invoices_user_due ON invoices(user_id, due_date);
CREATE INDEX IF NOT EXISTS idx_invoices_number ON invoices(invoice_number);

-- Payments table
CREATE TABLE IF NOT EXISTS payments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    invoice_id INTEGER NOT NULL,
    checkout_request_id TEXT UNIQUE,
    merchant_request_id TEXT,
    amount REAL NOT NULL,
    phone_number TEXT NOT NULL,
    status TEXT DEFAULT 'initiated' CHECK(status IN ('initiated', 'pending', 'completed', 'failed', 'cancelled', 'timeout')),
    mpesa_receipt_number TEXT,
    transaction_date TEXT,
    callback_received_at TEXT,
    result_code INTEGER,
    result_desc TEXT,
    processed_at TEXT,
    created_at TEXT DEFAULT (datetime('now')),
    updated_at TEXT DEFAULT (datetime('now')),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_payments_user_status ON payments(user_id, status);
CREATE INDEX IF NOT EXISTS idx_payments_invoice_status ON payments(invoice_id, status);
CREATE INDEX IF NOT EXISTS idx_payments_checkout ON payments(checkout_request_id);

-- Transactions table (audit trail)
CREATE TABLE IF NOT EXISTS transactions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    invoice_id INTEGER,
    payment_id INTEGER,
    transaction_type TEXT NOT NULL CHECK(transaction_type IN ('stk_push', 'callback', 'invoice_payment')),
    event TEXT NOT NULL CHECK(event IN ('initiated', 'success', 'failed', 'cancelled', 'timeout', 'duplicate', 'validation_failed', 'status_changed')),
    checkout_request_id TEXT,
    merchant_request_id TEXT,
    phone_number TEXT,
    amount REAL,
    mpesa_receipt_number TEXT,
    raw_request TEXT,
    raw_response TEXT,
    result_code INTEGER,
    result_desc TEXT,
    ip_address TEXT,
    processed INTEGER DEFAULT 0,
    created_at TEXT DEFAULT (datetime('now')),
    updated_at TEXT DEFAULT (datetime('now')),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE SET NULL,
    FOREIGN KEY (payment_id) REFERENCES payments(id) ON DELETE SET NULL
);

CREATE INDEX IF NOT EXISTS idx_transactions_user_date ON transactions(user_id, created_at);
CREATE INDEX IF NOT EXISTS idx_transactions_checkout ON transactions(checkout_request_id);
CREATE INDEX IF NOT EXISTS idx_transactions_invoice_event ON transactions(invoice_id, event);
CREATE INDEX IF NOT EXISTS idx_transactions_user_event ON transactions(user_id, event);

-- Sessions table (for Laravel auth)
CREATE TABLE IF NOT EXISTS sessions (
    id TEXT PRIMARY KEY,
    user_id INTEGER,
    ip_address TEXT,
    user_agent TEXT,
    payload TEXT NOT NULL,
    last_activity INTEGER NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_sessions_user ON sessions(user_id);
CREATE INDEX IF NOT EXISTS idx_sessions_last ON sessions(last_activity);

-- Cache table
CREATE TABLE IF NOT EXISTS cache (
    key TEXT PRIMARY KEY,
    value TEXT NOT NULL,
    expiration INTEGER NOT NULL
);

CREATE INDEX IF NOT EXISTS idx_cache_expiration ON cache(expiration);

-- Jobs table
CREATE TABLE IF NOT EXISTS jobs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    queue TEXT NOT NULL,
    payload TEXT NOT NULL,
    attempts INTEGER NOT NULL,
    reserved_at INTEGER,
    available_at INTEGER NOT NULL,
    created_at INTEGER NOT NULL
);

CREATE INDEX IF NOT EXISTS idx_jobs_queue ON jobs(queue);
CREATE INDEX IF NOT EXISTS idx_jobs_available ON jobs(available_at);

-- Failed jobs table
CREATE TABLE IF NOT EXISTS failed_jobs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    uuid TEXT NOT NULL UNIQUE,
    connection TEXT NOT NULL,
    queue TEXT NOT NULL,
    payload TEXT NOT NULL,
    exception TEXT NOT NULL,
    failed_at TEXT DEFAULT (datetime('now'))
);

CREATE INDEX IF NOT EXISTS idx_failed_jobs_uuid ON failed_jobs(uuid);