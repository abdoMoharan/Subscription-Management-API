# Subscription Lifecycle Engine

A robust Subscription Management API built with Laravel, designed to handle dynamic pricing, multi-currency support, and complex subscription lifecycle management.

---

## Table of Contents

- [Overview](#overview)
- [Architecture Decisions](#architecture-decisions)
- [Database Design](#database-design)
- [Subscription Lifecycle](#subscription-lifecycle)
- [Grace Period Logic](#grace-period-logic)
- [Project Structure](#project-structure)
- [Setup & Installation](#setup--installation)
- [Running the Scheduler](#running-the-scheduler)
- [API Endpoints](#api-endpoints)
- [Authentication](#authentication)

---

## Overview

This API manages the full lifecycle of user subscriptions, including:

- Dynamic plan management with multiple billing cycles (Monthly, Yearly)
- Multi-currency pricing (AED, USD, EGP)
- Trial period support per plan
- Automated grace period handling on payment failure
- Daily scheduled tasks to process expired trials and grace periods

---

## Architecture Decisions

### 1. Service Layer Pattern

All business logic lives in `App\Services\SubscriptionService` rather than in controllers.

**Why?**
Controllers are responsible for handling HTTP requests and returning responses — nothing more. Putting business logic inside controllers makes the code harder to test and reuse.

With a Service Layer:
- Controllers stay thin and readable
- Business logic can be tested independently
- The same service method can be called from a controller, a command, or a job without duplication

```
Request → Controller → Service → Model → Database
```

---

### 2. Separated Plan Pricing Table

Instead of storing `price`, `currency`, and `billing_cycle` directly on the `plans` table, a separate `plan_prices` table holds all pricing combinations.

**Why?**

A single plan can have multiple prices depending on currency and billing cycle. For example, the Pro plan has 6 different prices:

| Billing Cycle | Currency | Price  |
|---------------|----------|--------|
| monthly       | AED      | 50.00  |
| monthly       | USD      | 14.00  |
| monthly       | EGP      | 450.00 |
| yearly        | AED      | 500.00 |
| yearly        | USD      | 130.00 |
| yearly        | EGP      | 4500.00|

Storing these on the plans table would require duplicating the plan row 6 times. The separated table keeps data normalized and avoids redundancy.

A unique constraint on `(plan_id, billing_cycle, currency)` ensures no duplicate pricing entries.

---

### 3. Storing `plan_price_id` on Subscriptions

When a user subscribes, we store a reference to the exact `plan_price` they chose — not just the plan.

**Why?**

This preserves the exact terms the user agreed to at subscription time. If prices change in the future, existing subscriptions are not affected. It also gives us direct access to the currency and billing cycle without extra joins.

---

### 4. Soft Deletes

Plans and subscriptions use Laravel's `SoftDeletes` trait.

**Why?**

Hard-deleting a plan that has active subscribers would break foreign key references and lose historical data. Soft deletes keep the record in the database while hiding it from normal queries. Deleted plans can be restored if needed.

---

### 5. Scheduled Command over Queue for Grace Period

The grace period check runs as a daily scheduled Artisan command rather than a queued job per subscription.

**Why?**

For this use case, a single daily sweep is simpler, predictable, and easier to monitor. A queue-based approach (scheduling a job per subscription) adds infrastructure complexity without meaningful benefit at this scale.

---

### 6. Database Transactions in Service Methods

All service methods that involve multiple write operations are wrapped in `DB::transaction()`.

**Why?**

If any step fails (e.g., payment record creation fails after updating the subscription status), the entire operation is rolled back. This prevents the database from ending up in an inconsistent state.

---

## Database Design

```
users
  └── subscriptions (user_id, plan_id, plan_price_id, status, ...)
        └── payments (subscription_id, amount, currency, status)

plans
  └── plan_prices (plan_id, billing_cycle, currency, price)
```

### Table: `plans`

| Column       | Type    | Description                        |
|--------------|---------|------------------------------------|
| id           | bigint  | Primary key                        |
| name         | string  | Plan name (Basic, Pro, Enterprise) |
| description  | text    | Optional description               |
| trial_days   | integer | Number of free trial days (0 = no trial) |
| is_active    | boolean | Whether the plan is publicly available |

### Table: `plan_prices`

| Column        | Type    | Description                            |
|---------------|---------|----------------------------------------|
| id            | bigint  | Primary key                            |
| plan_id       | bigint  | Foreign key → plans                    |
| billing_cycle | enum    | `monthly` or `yearly`                  |
| currency      | enum    | `AED`, `USD`, or `EGP`                 |
| price         | decimal | Price amount                           |

Unique constraint on: `(plan_id, billing_cycle, currency)`

### Table: `subscriptions`

| Column                  | Type     | Description                                      |
|-------------------------|----------|--------------------------------------------------|
| id                      | bigint   | Primary key                                      |
| user_id                 | bigint   | Foreign key → users                              |
| plan_id                 | bigint   | Foreign key → plans                              |
| plan_price_id           | bigint   | Foreign key → plan_prices (locked-in pricing)    |
| status                  | enum     | `trialing`, `active`, `past_due`, `canceled`     |
| trial_ends_at           | datetime | When the trial period ends                       |
| current_period_ends_at  | datetime | When the current billing period ends             |
| grace_period_ends_at    | datetime | When the 3-day grace period expires              |
| canceled_at             | datetime | When the subscription was canceled               |

### Table: `payments`

| Column           | Type     | Description                          |
|------------------|----------|--------------------------------------|
| id               | bigint   | Primary key                          |
| subscription_id  | bigint   | Foreign key → subscriptions          |
| amount           | decimal  | Amount charged                       |
| currency         | enum     | `AED`, `USD`, or `EGP`               |
| status           | enum     | `success` or `failed`                |
| failure_reason   | string   | Optional reason for failure          |

---

## Subscription Lifecycle

```
User subscribes
      │
      ▼
 Has trial days?
  │          │
 Yes         No
  │          │
  ▼          ▼
Trialing   Active
  │          │
  │ Trial    │ Payment
  │ expires  │ fails
  ▼          ▼
Active     Past Due ──── paid within 3 days ──→ Active
               │
           3 days pass
               │
               ▼
           Canceled
```

### Status Definitions

| Status    | Description                                              | Has Access |
|-----------|----------------------------------------------------------|------------|
| trialing  | User is in their free trial period                       | Yes        |
| active    | Subscription is paid and current                         | Yes        |
| past_due  | Payment failed — grace period is running                 | Yes        |
| canceled  | Subscription has ended                                   | No         |

---

## Grace Period Logic

When a payment fails:

1. Subscription status changes to `past_due`
2. `grace_period_ends_at` is set to 3 days from now
3. The user **retains full access** during this period
4. If a successful payment is recorded within 3 days → status returns to `active`
5. If no payment is made within 3 days → the daily Cron job cancels the subscription automatically

---

## Project Structure

app/
├── Console/
│   └── Commands/
│       └── ProcessSubscriptions.php   # Daily cron job
├── Http/
│   └── Controllers/
│       └── Api/
│           ├── Auth/
│           │   └── AuthController.php
│           ├── Plan/
│           │   └── PlanController.php
│           ├── Subscription/
│           │   └── SubscriptionController.php
│           └── Payment/
│               └── PaymentController.php
├── Models/
│   ├── Plan.php
│   ├── PlanPrice.php
│   ├── Subscription.php
│   └── Payment.php
├── Repositories/
│   └── PlanRepository.php
    └── SubscriptionRepository.php           # Database queries for plans
└── Services/
    └── SubscriptionService.php

database/
└── migrations/
    ├── create_plans_table.php
    ├── create_plan_prices_table.php
    ├── create_subscriptions_table.php
    └── create_payments_table.php

routes/
├── api.php
└── console.php

database/
└── migrations/
    ├── create_plans_table.php
    ├── create_plan_prices_table.php
    ├── create_subscriptions_table.php
    └── create_payments_table.php

routes/
├── api.php
└── console.php

## Setup & Installation

### Requirements

- PHP 8.2+
- Composer
- MySQL 8+ or PostgreSQL
- Laravel 11+

### Steps

```bash
# 1. Clone the repository
git clone https://github.com/abdoMoharan/Subscription-Management-API
cd subscription-engine

# 2. Install dependencies
composer install

# 3. Copy environment file
cp .env.example .env

# 4. Generate application key
php artisan key:generate

# 5. Configure your database in .env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=subscription_engine
DB_USERNAME=root
DB_PASSWORD=

# 6. Run migrations
php artisan migrate:refresh --seed

# 7. Start the development server
php artisan serve
```

---

## Running the Scheduler

### Manually (for testing)

```bash
php artisan subscriptions:process
```

### Local development (runs continuously)

```bash
php artisan schedule:work
```

### Production (add to server crontab)

```bash
crontab -e
```

Add this line:


### Verify the schedule is registered

```bash
php artisan schedule:list
```

---

## API Endpoints

### Authentication

| Method | Endpoint       | Description       | Auth Required |
|--------|----------------|-------------------|---------------|
| POST   | /api/login     | Login user        | No            |
| POST   | /api/logout    | Logout user       | No            |

### Plans

| Method | Endpoint                     | Description                  | Auth Required |
|--------|------------------------------|------------------------------|---------------|
| GET    | /api/plans                   | List all active plans        | No            |
| POST   | /api/plans                   | Create a new plan            | No            |
| GET    | /api/plans/show/{id}         | Get plan details             | No            |
| POST   | /api/plans/update/{id}       | Update a plan                | No            |
| DELETE | /api/plans/delete/{id}       | Soft delete a plan           | No            |
| GET    | /api/plans/deleted           | List soft-deleted plans      | No            |
| POST   | /api/plans/restore/{id}      | Restore a deleted plan       | No            |
| GET    | /api/plans/force-delete/{id} | Permanently delete a plan    | No            |

### Subscriptions

| Method | Endpoint                              | Description                    | Auth Required |
|--------|---------------------------------------|--------------------------------|---------------|
| GET    | /api/subscriptions                    | List user subscriptions        | Yes           |
| POST   | /api/subscriptions                    | Create a new subscription      | Yes           |
| GET    | /api/subscriptions/show/{id}          | Get subscription details       | Yes           |
| DELETE | /api/subscriptions/delete/{id}        | Soft delete subscription       | Yes           |
| GET    | /api/subscriptions/deleted            | List deleted subscriptions     | Yes           |
| POST   | /api/subscriptions/restore/{id}       | Restore a subscription         | Yes           |
| GET    | /api/subscriptions/force-delete/{id}  | Permanently delete             | Yes           |
| POST   | /api/subscriptions/cancel/{id}        | Cancel a subscription          | Yes           |

### Payments

| Method | Endpoint              | Description                        | Auth Required |
|--------|-----------------------|------------------------------------|---------------|
| POST   | /api/payments/success | Record a successful payment        | Yes           |
| POST   | /api/payments/fail    | Record a failed payment            | Yes           |

---

## Authentication

This API uses **Laravel Sanctum** for token-based authentication.

Include the token in every protected request:

```
Authorization: Bearer {your-token}
```

To get a token, call the login endpoint:

```json
POST /api/login
{
    "email": "user@example.com",
    "password": "password"
}
```
