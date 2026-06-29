# Order & Payment Management API

A Laravel 11 REST API for managing orders and payments, designed with clean architecture and extensibility in mind. The payment gateway system uses the **Strategy Pattern** — adding a new payment gateway requires creating a single class and registering it in a config file.

---

## Tech Stack

- **Framework**: Laravel 13.8 (PHP 8.4)
- **Authentication**: JWT (`tymon/jwt-auth` v2)
- **Database**: SQLite (testing) / MySQL 8.0 (Docker production)
- **Tests**: PHPUnit 12 (38 tests, 115 assertions)
- **Architecture**: Service Repository Pattern, Strategy Pattern, DTOs, Pessimistic Locking

---

## Setup Instructions

You have two ways to run the project.

### Option 1: Docker (Recommended)
This approach requires **zero** local PHP or Composer installation. The included custom entrypoint script automatically handles installing dependencies, generating keys, and running database migrations!

```bash
docker compose up -d --build
```

- **API Base URL**: `http://localhost:8000/api`
- **phpMyAdmin**: `http://localhost:8081` (Login with `sail` / `password`)

---

### Option 2: Local PHP Installation (Manual)

**1. Clone & Install Dependencies**
```bash
composer install
```

**2. Configure Environment**
```bash
cp .env.example .env
php artisan key:generate
php artisan jwt:secret
```

**3. Configure Database**
Edit `.env`. For local development without Docker, SQLite is the easiest:
```env
DB_CONNECTION=sqlite
```
*(You may need to create an empty `database/database.sqlite` file first).*

**4. Run Migrations & Start Server**
```bash
php artisan migrate
php artisan serve
```
The API will be available at `http://localhost:8000/api`

---

## API Endpoints

### Authentication

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/auth/register` | Register a new user |
| POST | `/api/auth/login` | Login and get JWT token |
| POST | `/api/auth/logout` | Invalidate token |
| GET | `/api/auth/me` | Get authenticated user |
| POST | `/api/auth/refresh` | Refresh JWT token |

### Orders

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/orders` | List all orders (filter: `?status=pending\|confirmed\|cancelled`) |
| POST | `/api/orders` | Create an order |
| GET | `/api/orders/{id}` | Get a single order |
| PUT | `/api/orders/{id}` | Update an order |
| DELETE | `/api/orders/{id}` | Delete an order (fails if payments exist) |

### Payments

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/payments` | List all payments (filter: `?order_id=X`) |
| POST | `/api/payments` | Process a payment |
| GET | `/api/payments/{id}` | Get a single payment |

All protected routes require the `Authorization: Bearer {token}` header.

---

## Business Rules & Advanced Features

1. **Idempotency & Double Charges:** Payment endpoints optionally accept an `Idempotency-Key` header. `PaymentService` uses this key alongside pessimistic locking (`lockForUpdate`) to guarantee a payment is only charged once.
2. **Order Constraints:** Orders can only be deleted if they have no associated payments.
3. **Payment Constraints:** Payments can only be processed for orders with `status = confirmed`.
4. **Normalized Items:** Order items are securely stored in a normalized `order_items` relational table.
5. **Auto-Calculation:** Order totals are auto-calculated server-side from `order_items.quantity × order_items.price`.
6. **Clean Exceptions:** Raw stack traces are intercepted. `NotFoundHttpException` and `AccessDeniedHttpException` cleanly return normalized JSON (`404` & `403`).

---

## Payment Gateway Extensibility

The system uses the **Strategy Pattern** to make adding new payment gateways trivial.

### Architecture

```
app/
├── Contracts/
│   └── PaymentGatewayInterface.php    ← The contract every gateway must implement
├── PaymentGateways/
│   ├── CreditCardGateway.php
│   ├── PayPalGateway.php
│   └── StripeGateway.php              ← Example of an added gateway
├── Services/
│   └── PaymentGatewayResolver.php     ← Resolves method string → gateway class
config/
└── payment_gateways.php               ← Gateway registry (method key → class)
```

### How to Add a New Payment Gateway

**Step 1** — Create the gateway class:

```php
// app/PaymentGateways/MpesaGateway.php
namespace App\PaymentGateways;

use App\Contracts\PaymentGatewayInterface;
use App\DTOs\GatewayResultDTO;
use App\Models\Payment;

class MpesaGateway implements PaymentGatewayInterface
{
    public function getName(): string
    {
        return 'mpesa';
    }

    public function process(Payment $payment): GatewayResultDTO
    {
        // Your M-Pesa API integration logic here
        $transactionId = 'MPESA-' . strtoupper(\Str::random(10));
        $success = true;

        return new GatewayResultDTO(
            success:       $success,
            transactionId: $transactionId,
            message:       'M-Pesa payment completed.',
            raw: [
                'gateway'        => 'mpesa',
                'transaction_id' => $transactionId,
                'order_id'       => $payment->order_id,
                'amount'         => $payment->order->total,
                'processed_at'   => now()->toIso8601String(),
            ],
        );
    }
}
```

**Step 2** — Register it in `config/payment_gateways.php`:

```php
'gateways' => [
    'credit_card' => \App\PaymentGateways\CreditCardGateway::class,
    'paypal'      => \App\PaymentGateways\PayPalGateway::class,
    'stripe'      => \App\PaymentGateways\StripeGateway::class,
    'mpesa'       => \App\PaymentGateways\MpesaGateway::class, // ← Add this line
],
```

**Step 3** — Add credentials to `.env` (if needed):

```env
MPESA_API_KEY=your_key
MPESA_API_SECRET=your_secret
```

**That's it.** No controller, service, or route changes needed. The `ProcessPaymentRequest` validation automatically picks up the new method, and the `PaymentGatewayResolver` routes requests to it.

---

## Running Tests

```bash
./vendor/bin/phpunit --testdox
```

### Test Coverage

| Suite | Tests | Description |
|-------|-------|-------------|
| Unit | 6 | Gateway resolution, all 3 gateways |
| Feature | 32 | Auth, Order CRUD, Payment processing |
| **Total** | **38** | **115 assertions** |

---

## Environment Variables

| Variable | Description | Default |
|----------|-------------|---------|
| `JWT_SECRET` | JWT signing secret (auto-generated) | — |
| `CREDIT_CARD_API_KEY` | Credit Card gateway key | `test_key` |
| `CREDIT_CARD_API_SECRET` | Credit Card gateway secret | `test_secret` |
| `PAYPAL_CLIENT_ID` | PayPal client ID | `test_client_id` |
| `PAYPAL_CLIENT_SECRET` | PayPal client secret | `test_client_secret` |
| `PAYPAL_MODE` | `sandbox` or `live` | `sandbox` |
| `STRIPE_PUBLISHABLE_KEY` | Stripe publishable key | `pk_test_demo` |
| `STRIPE_SECRET_KEY` | Stripe secret key | `sk_test_demo` |

---

## Assumptions & Notes

- Payment gateways are **simulated** (no real API calls). Replace the `process()` method body with real SDK calls for production.
- The `total` field is always **auto-calculated** server-side; any client-provided total is ignored.
- JWT tokens expire after **60 minutes** (configurable in `config/jwt.php`).
- All list endpoints support pagination via `?per_page=N` (default: 15, max: 100).
