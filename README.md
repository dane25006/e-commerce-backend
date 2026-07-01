# 🛒 Laravel E-Commerce Backend

A production-ready, full-featured **e-commerce backend API and admin panel** built with **Laravel 12**. Powers a luxury perfume storefront with authentication, product management, cart/wishlist, orders, Telegram bot integration, AI chat, and Swagger documentation.

[![PHP](https://img.shields.io/badge/PHP-8.2+-777BB4?logo=php)](https://php.net)
[![Laravel](https://img.shields.io/badge/Laravel-12-FF2D20?logo=laravel)](https://laravel.com)
[![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?logo=mysql)](https://mysql.com)
[![License](https://img.shields.io/badge/License-MIT-green)](LICENSE)

---

## 📖 Table of Contents

- [🛒 Project Overview](#-project-overview)
- [✨ Features](#-features)
- [🛠 Tech Stack](#-tech-stack)
- [📁 Project Structure](#-project-structure)
- [🚀 Installation](#-installation)
- [⚙ Environment Variables](#-environment-variables)
- [🤖 Telegram Bot Setup](#-telegram-bot-setup)
- [📚 API Documentation](#-api-documentation)
- [🔐 Authentication](#-authentication)
- [📦 Queue](#-queue)
- [📢 Events & Notifications](#-events--notifications)
- [📂 API Endpoints](#-api-endpoints)
- [🧪 Testing](#-testing)
- [🔍 Troubleshooting](#-troubleshooting)
- [📈 Future Improvements](#-future-improvements)
- [🤝 Contributing](#-contributing)
- [📄 License](#-license)
- [👨‍💻 Author](#-author)

---

## 🛒 Project Overview

**Scentique** is a luxury perfume e-commerce backend that provides a complete REST API and admin panel for managing an online store. It handles everything from user authentication and product catalog management to order processing and real-time Telegram notifications.

### 🎯 Main Purpose

Deliver a secure, scalable, and feature-rich backend that powers both a customer-facing storefront and an admin management dashboard, with seamless Telegram bot integration for order notifications and customer support.

### 👥 Target Users

- **Customers** — Browse products, manage cart/wishlist, place orders, track shipments, and interact via Telegram bot
- **Admins** — Manage products, categories, orders, users, promotions, and receive real-time notifications
- **Developers** — Extend and customize the platform via well-documented REST API with Swagger/OpenAPI

### 🖼 Screenshots

> _Screenshots coming soon. Replace this section with images of Swagger UI, Admin Dashboard, and API responses._

### 🏗 Architecture Overview

```
Client (Vue SPA)                    Admin Browser
      │                                  │
      ▼                                  ▼
┌─────────────────────────────────────────────────┐
│           Laravel 12 Backend (API + Web)         │
│                                                   │
│  ┌──────────┐  ┌──────────┐  ┌────────────────┐ │
│  │  API      │  │  Admin   │  │  Telegram Bot  │ │
│  │  Routes   │  │  Routes  │  │  Webhook       │ │
│  └────┬─────┘  └────┬─────┘  └───────┬────────┘ │
│       │              │                │          │
│  ┌────▼──────────────▼────────────────▼────────┐ │
│  │          Controllers / Services             │ │
│  └────┬──────────────┬────────────────┬────────┘ │
│       │              │                │          │
│  ┌────▼────┐   ┌────▼────┐   ┌──────▼───────┐   │
│  │  Models  │   │  Queue  │   │  Events /    │   │
│  │  (18)    │   │  Jobs   │   │  Listeners   │   │
│  └─────────┘   └─────────┘   └──────────────┘   │
│                                                   │
└─────────────────────────────────────────────────┘
        │              │                │
        ▼              ▼                ▼
    MySQL DB      Redis Cache     Telegram API
```

---

## ✨ Features

### 🔐 Authentication

| Feature | Description |
|---------|-------------|
| Register | Create a new customer account with name, email, and password |
| Login | Authenticate with email/password and receive a Sanctum token |
| Logout | Revoke current API token |
| Email Verification | _(Coming soon)_ Verify email addresses |
| Forgot Password | _(Coming soon)_ Request password reset link |
| Reset Password | _(Coming soon)_ Reset password with secure token |
| Google Login | OAuth 2.0 social login via Google (Laravel Socialite) |
| Facebook Login | _(Configured but not fully implemented)_ |
| Sanctum Authentication | Token-based SPA authentication with CSRF protection |

### 📦 Products

| Feature | Description |
|---------|-------------|
| Product CRUD | Full create, read, update, delete for products |
| Product Images | Multiple images per product with sort order |
| Categories | Products organized by category |
| Brands | Filter products by brand |
| Product Search | Search by name, description, and fields |
| Product Filter | Filter by category, brand, gender, price range, department, type |
| Pagination | Paginated product listings with configurable page size |
| Product Reviews | Authenticated users can rate and review products |
| Ratings | Star ratings (1-5) with average calculation |

### 🛍 Shopping

| Feature | Description |
|---------|-------------|
| Cart | Guest and authenticated cart with localStorage guest tokens |
| Wishlist | Save products for later, merge on login |
| Checkout | Place orders with shipping address and payment method selection |
| Orders | Complete order lifecycle management |
| Order History | View past orders with status tracking |
| Order Tracking | Track order status: pending → processing → shipped → delivered |

### 👑 Admin

| Feature | Description |
|---------|-------------|
| Dashboard | Admin dashboard with key metrics and statistics |
| Product Management | Full CRUD for products with image management |
| Category Management | Manage product categories |
| Brand Management | Manage product brands |
| User Management | View and manage all registered users |
| Customer Management | View customer details and order history |
| Order Management | View, approve, reject, and update order statuses |
| Reports | _(Coming soon)_ Sales reports and analytics |
| Telegram Integration | Admin panel for managing Telegram bot settings, logs, and chats |

### 🤖 Telegram

#### 👤 Customer Bot (`@MyShop_order168_bot`)

| Feature | Description |
|---------|-------------|
| Browse Products | View products by category with inline pagination |
| Cart Management | Add/remove items, view cart, proceed to checkout |
| Place Orders | Complete checkout flow via Telegram with payment selection (COD, Credit Card, PayPal) |
| Order Notifications | Receive real-time order status updates |
| Track Orders | Check order status by order ID |
| Cancel Orders | Cancel pending orders |
| AI Chat Assistant | Ask questions about products using AI (DeepSeek API) |
| Image Generation | Generate product images using AI (DeepSeek API) |
| Account Linking | Link Telegram account to store account via verification code |
| Profile Management | View profile, order history, and notifications settings |

#### 👨‍💼 Admin Bot

| Feature | Description |
|---------|-------------|
| Dashboard | View key metrics: total users, orders, revenue, pending orders, low stock products |
| New Order Notifications | Real-time alerts when new orders are placed |
| Approve Orders | Approve pending orders directly from Telegram |
| Reject Orders | Reject orders with reason |
| Update Order Status | Change order status (processing, shipped, delivered) |
| Payment Notifications | Real-time payment received/approved/rejected alerts |
| Low Stock Alerts | Automatic alerts when product stock drops below threshold |
| Daily Reports | View daily sales summary and top products |
| User Management | View user details and stats |
| Broadcast Messages | Send messages to all customers |
| Broadcast Video/Photo | _(Configured)_ Send media broadcasts |

> **11 Bot Commands:** `/start`, `/menu`, `/chat`, `/image`, `/products`, `/cart`, `/orders`, `/track`, `/cancel`, `/profile`, `/help`

---

## 🛠 Tech Stack

### Backend

| Technology | Version | Purpose |
|------------|---------|---------|
| **Laravel** | ^12.0 | Application framework |
| **PHP** | ^8.2 | Programming language |
| **MySQL** | 8.0+ | Primary database |
| **Composer** | Latest | PHP dependency manager |

### Authentication

| Package | Purpose |
|---------|---------|
| **Laravel Sanctum** | ^4.3 | SPA and mobile API token authentication |
| **Laravel Socialite** | ^5.28 | Social login (Google OAuth) |

### Documentation

| Package | Purpose |
|---------|---------|
| **L5-Swagger** | ^11.1 | OpenAPI/Swagger documentation generation |

### Notifications

| Service | Purpose |
|---------|---------|
| **Telegram Bot API** | Real-time order and payment notifications |

### Queues & Jobs

| Service | Purpose |
|---------|---------|
| **Laravel Queue** | Database-driven queue for async job processing |
| **Queue Driver** | `database` (default, no Redis required) |
| **Telegram Queue** | Dedicated `telegram` queue for bot messages |

### HTTP Client

| Package | Purpose |
|---------|---------|
| **GuzzleHttp** | ^7.12 | HTTP client for Telegram API, AI APIs, and external services |

### Storage

| Driver | Purpose |
|--------|---------|
| **Laravel Storage** | `local` disk for product images, AI-generated images |
| **Public Disk** | Symlinked `storage/app/public` → `public/storage` |

### Testing

| Tool | Purpose |
|------|---------|
| **PHPUnit** | ^11.5 | Testing framework |
| **Mockery** | ^1.6 | Mocking framework |

---

## 📁 Project Structure

```
backend/
├── app/
│   ├── Console/
│   │   └── Commands/           # Artisan commands (7 Telegram commands)
│   ├── Events/                  # 6 events (OrderPlaced, OrderStatusUpdated, etc.)
│   ├── Exceptions/              # Exception handlers
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Admin/           # Admin panel controllers
│   │   │   └── Api/             # API controllers
│   │   ├── Middleware/          # Custom middleware (IsAdmin, Telegram auth)
│   │   ├── Requests/            # Form request validation
│   │   └── Resources/           # API resource transformers
│   ├── Jobs/                    # Queued jobs (Telegram message sending)
│   ├── Listeners/               # Event listeners (Telegram notifications)
│   ├── Models/                  # 18 Eloquent models
│   ├── Providers/               # Service providers (Telegram, etc.)
│   └── Services/                # Business logic layer
│       ├── Auth/                # Authentication service
│       ├── Cart/                # Cart management service
│       ├── Order/               # Order processing service
│       └── Telegram/            # 9 Telegram services (bot, webhook, AI, admin)
├── bootstrap/                   # Framework bootstrap files
├── config/                      # All configuration files
├── database/
│   ├── migrations/              # Database schema (25+ tables)
│   ├── seeders/                 # Database seeders (categories, products, admin)
│   └── factories/               # Model factories
├── public/                      # Web server entry point
├── resources/                   # Blade views (admin panel)
├── routes/
│   ├── api.php                  # API routes (Sanctum-protected)
│   ├── web.php                  # Web/admin routes
│   └── console.php              # Console route bindings
├── storage/                     # Logs, cache, uploaded media
├── tests/                       # PHPUnit tests
├── .env.example                 # Environment template
├── composer.json                # PHP dependencies
└── vite.config.js               # Vite config (admin assets)
```

### 📂 Key Folder Responsibilities

| Folder | Purpose |
|--------|---------|
| `app/Http/Controllers/Api/` | Public REST API endpoints |
| `app/Http/Controllers/Admin/` | Admin panel web controllers |
| `app/Models/` | Eloquent ORM models |
| `app/Services/` | Business logic decoupled from controllers |
| `app/Services/Telegram/` | 9 specialized Telegram bot services |
| `app/Events/` | Application events (order, payment, stock) |
| `app/Listeners/` | Event handlers (Telegram notifications) |
| `app/Jobs/` | Queued async tasks |
| `app/Console/Commands/` | Artisan CLI commands |
| `config/` | Application configuration |
| `database/migrations/` | Database schema versioning |
| `routes/api.php` | All REST API route definitions |

---

## 🚀 Installation

### Prerequisites

| Software | Version |
|----------|---------|
| PHP | ^8.2 |
| Composer | Latest |
| MySQL | 8.0+ |
| Node.js | ^18+ (for admin asset compilation) |
| Git | Latest |

### 1. Clone Repository

```bash
git clone https://github.com/dane25006/e-commerce-backend.git

cd e-commerce-backend
```

### 2. Install Dependencies

```bash
composer install
```

### 3. Create Environment

```bash
cp .env.example .env
```

### 4. Generate Key

```bash
php artisan key:generate
```

### 5. Configure Database

Open `.env` and set your database credentials:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ecommerce_onlineshop
DB_USERNAME=root
DB_PASSWORD=your_password
```

> **💡 Tip:** Ensure your MySQL server is running and create the database first:
> ```sql
> CREATE DATABASE ecommerce_onlineshop CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
> ```

### 6. Run Migration

```bash
php artisan migrate
```

### 7. Seed Database

```bash
php artisan db:seed
```

This creates:
- **Admin user** — email: `admin@example.com`, password: `password`
- **Test customer** — email: `test@example.com`, password: `password`
- **Categories** — Perfume categories (Floral, Oriental, Woody, Fresh, etc.)
- **Products** — Sample luxury perfume products

### 8. Storage Link

```bash
php artisan storage:link
```

Creates a symbolic link from `public/storage` to `storage/app/public` for serving uploaded images.

### 9. Clear Cache

```bash
php artisan optimize:clear
```

### 10. Run Server

```bash
php artisan serve
```

The backend will be available at **http://localhost:8000**.

> **💡 Alternative:** Use the project's `dev` script for concurrent server + queue + logs:
> ```bash
> composer dev
> ```

---

## ⚙ Environment Variables

### Application

| Variable | Description | Default |
|----------|-------------|---------|
| `APP_NAME` | Application name | `Laravel` |
| `APP_ENV` | Application environment (`local`, `production`) | `local` |
| `APP_KEY` | Laravel application key (auto-generated) | — |
| `APP_DEBUG` | Enable/disable debug mode | `true` |
| `APP_URL` | Backend base URL | `http://127.0.0.1:8000` |
| `FRONTEND_URL` | Frontend URL (for CORS) | `http://localhost:5173` |

### Database

| Variable | Description | Default |
|----------|-------------|---------|
| `DB_CONNECTION` | Database driver (`mysql`, `sqlite`, `pgsql`) | `mysql` |
| `DB_HOST` | Database host | `127.0.0.1` |
| `DB_PORT` | Database port | `3306` |
| `DB_DATABASE` | Database name | `ecommerce_onlineshop` |
| `DB_USERNAME` | Database username | `root` |
| `DB_PASSWORD` | Database password | — |

### Authentication & Session

| Variable | Description | Default |
|----------|-------------|---------|
| `SANCTUM_STATEFUL_DOMAINS` | Comma-separated stateful SPA domains | `localhost:5173` |
| `SESSION_DRIVER` | Session storage driver | `file` |
| `SESSION_LIFETIME` | Session lifetime in minutes | `120` |

### Queue & Cache

| Variable | Description | Default |
|----------|-------------|---------|
| `QUEUE_CONNECTION` | Queue driver (`database`, `sync`) | `database` |
| `CACHE_STORE` | Cache driver (`database`, `file`, `redis`) | `database` |

### Mail

| Variable | Description | Default |
|----------|-------------|---------|
| `MAIL_MAILER` | Mail driver | `log` |
| `MAIL_HOST` | SMTP host | `127.0.0.1` |
| `MAIL_PORT` | SMTP port | `2525` |
| `MAIL_USERNAME` | SMTP username | — |
| `MAIL_PASSWORD` | SMTP password | — |
| `MAIL_FROM_ADDRESS` | Default from address | — |
| `MAIL_FROM_NAME` | Default from name | — |

> **⚠️ Warning:** Mail is configured with `log` driver by default. For production, switch to `smtp` and configure your SMTP credentials.

### Social Login (Google)

| Variable | Description | Default |
|----------|-------------|---------|
| `GOOGLE_CLIENT_ID` | Google OAuth 2.0 client ID | — |
| `GOOGLE_CLIENT_SECRET` | Google OAuth 2.0 client secret | — |
| `GOOGLE_REDIRECT_URI` | OAuth callback URL | `http://127.0.0.1:8000/api/auth/google/callback` |

> **🔑 How to get these:** Go to [Google Cloud Console](https://console.cloud.google.com/) → Create project → Enable OAuth consent screen → Create OAuth 2.0 credentials (Web application).

### Telegram

| Variable | Description | Default |
|----------|-------------|---------|
| `TELEGRAM_BOT_TOKEN` | Bot token from BotFather | — |
| `TELEGRAM_BOT_USERNAME` | Bot username (e.g., `MyShop_order168_bot`) | — |
| `TELEGRAM_ADMIN_CHAT_ID` | Admin Telegram chat ID for notifications | — |
| `TELEGRAM_WEBHOOK_SECRET` | Secret token for webhook validation | — |

### Swagger

| Variable | Description | Default |
|----------|-------------|---------|
| `L5_SWAGGER_GENERATE_ALWAYS` | Auto-regenerate docs on every request | `true` |
| `L5_SWAGGER_CONST_HOST` | API host URL for Swagger UI | `http://localhost:8000` |

---

## 🤖 Telegram Bot Setup

The project includes **two Telegram bots** in one: a **Customer Bot** and an **Admin Bot**, both powered by the same bot token but differentiated by user role.

### Step 1: Create a Bot with BotFather

1. Open Telegram and search for **@BotFather**
2. Send `/newbot` and follow the prompts
3. Choose a display name (e.g., `My Store Bot`) and username (e.g., `MyShop_order_bot`)
4. BotFather will give you an **API token** — save it

### Step 2: Configure Environment

Add the token to your `.env`:

```env
TELEGRAM_BOT_TOKEN=your-bot-token-here
TELEGRAM_BOT_USERNAME=your-bot-username
TELEGRAM_ADMIN_CHAT_ID=your-chat-id
TELEGRAM_WEBHOOK_SECRET=your-secret-token-here
```

### Step 3: Set Webhook

```bash
php artisan telegram:bot set-webhook https://your-domain.com/api/telegram/webhook
```

Or for local development with polling:

```bash
php artisan telegram:poll
```

> **⚠️ Important:** Webhooks require HTTPS. For local development, use [ngrok](https://ngrok.com/) to create a secure tunnel.

### Step 4: Register Bot Commands

```bash
php artisan telegram:set-commands
```

This registers 11 bot commands: `/start`, `/menu`, `/chat`, `/image`, `/products`, `/cart`, `/orders`, `/track`, `/cancel`, `/profile`, `/help`.

### Step 5: Register Admin Chat

```bash
php artisan telegram:admin 123456789
```

> Replace with your actual Telegram chat ID (get it from @userinfobot).

### Step 6: Test the Bot

Open Telegram, search for your bot, and send `/start`. The bot should respond with a welcome message and main menu.

### 🔔 Notification Flow

```
Order Placed (Customer)
    │
    ▼
┌─────────────────────────────┐
│ OrderPlaced Event Fired     │
└──────────┬──────────────────┘
           │
    ┌──────▼──────┐
    │  Listeners  │
    └──┬──────┬───┘
       │      │
       ▼      ▼
 Customer    Admin
   Bot       Bot
 (status    (new order
 update)    alert)
```

---

## 📚 API Documentation

This project uses **L5-Swagger** for OpenAPI 3.0 documentation with annotations.

### Generate Documentation

```bash
php artisan l5-swagger:generate
```

### Access Swagger UI

```
http://localhost:8000/api/documentation
```

The Swagger UI includes:
- All public and protected API endpoints
- Request/response schemas with example payloads
- Bearer token authentication (click **Authorize** and enter your token)
- Interactive "Try it out" functionality

### Auto-Generation

When `L5_SWAGGER_GENERATE_ALWAYS=true`, the documentation is automatically regenerated on every request (development mode). Disable in production.

---

## 🔐 Authentication

The API uses **Laravel Sanctum** for token-based authentication.

### Register

```http
POST /api/register
Content-Type: application/json

{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "password123",
  "password_confirmation": "password123"
}
```

**Response:**
```json
{
  "user": { "id": 1, "name": "John Doe", "email": "john@example.com", "role": "customer" },
  "token": "1|abc123def456..."
}
```

### Login

```http
POST /api/login
Content-Type: application/json

{
  "email": "john@example.com",
  "password": "password123"
}
```

**Response:** Same as register — user object + Sanctum token.

### Logout

```http
POST /api/logout
Authorization: Bearer 1|abc123def456...
```

Revokes the current API token.

### Sanctum Tokens

- Tokens are **plain-text Bearer tokens** (format: `{token_id}|{plain_text_token}`)
- Store securely on the client (localStorage, sessionStorage, or HTTP-only cookie)
- Pass in every protected request via `Authorization: Bearer <token>` header
- No token expiration by default (configurable in `config/sanctum.php`)

### Protected Routes

All routes under `auth:sanctum` middleware require a valid token:
- `POST /api/logout`
- `GET|PUT /api/profile`
- `PUT /api/password`
- `POST /api/cart/merge`
- `POST /api/wishlist/merge`
- `POST /api/checkout`
- `GET /api/orders`
- `GET /api/orders/{order}`
- `PUT /api/orders/{order}/cancel`
- `POST|PUT|DELETE /api/products/{product}/reviews/{review}`

### Social Login (Google)

1. Frontend redirects user to:
   ```
   GET /api/auth/google/redirect
   ```
2. User authenticates with Google
3. Google redirects to:
   ```
   GET /api/auth/google/callback?code=...
   ```
4. Backend creates/retrieves user and returns a Sanctum token

> **🔄 Flow:** The callback URL should be configured in Google Cloud Console to point to your backend, which then redirects the frontend SPA with the token as a query parameter.

### Password Reset

> _(Coming soon)_ Password reset endpoints will use Laravel's built-in password reset system with email notifications.

---

## 📦 Queue

The project uses **database-driven queues** for async processing.

### Queue Connection

Configured in `.env`:

```env
QUEUE_CONNECTION=database
```

A dedicated queue named `telegram` is used for Telegram bot messages.

### Run Queue Worker

```bash
php artisan queue:work
```

For the dedicated Telegram queue:

```bash
php artisan queue:work --queue=telegram
```

### Run Queue in Background (Production)

```bash
# Using nohup (Linux/Mac)
nohup php artisan queue:work --queue=telegram --sleep=3 --tries=3 > storage/logs/queue.log &

# Using Supervisor (recommended for production)
# Add to /etc/supervisor/conf.d/laravel-queue.conf
```

### Retry Failed Jobs

```bash
# Retry all failed jobs
php artisan queue:retry all

# Retry a specific failed job
php artisan queue:retry <job-id>
```

### View Failed Jobs

```bash
# Show failed jobs table
php artisan queue:failed
```

> **💡 Tip:** Run `php artisan queue:work` alongside `php artisan serve` during development. The project's `composer dev` script handles this automatically.

---

## 📢 Events & Notifications

### Events

| Event | Description | Fired When |
|-------|-------------|------------|
| `OrderPlaced` | New order is created | Customer completes checkout |
| `OrderStatusUpdated` | Order status changes | Admin updates order status |
| `PaymentReceived` | Payment is received | Payment confirmed |
| `PaymentApproved` | Payment is approved | Admin approves payment |
| `PaymentRejected` | Payment is rejected | Admin rejects payment |
| `LowStockAlert` | Product stock is low | Stock drops below threshold |

### Listeners

| Listener | Handles | Action |
|----------|---------|--------|
| `SendTelegramOrderNotification` | `OrderPlaced`, `OrderStatusUpdated` | Sends order notification to customer and admin via Telegram |
| `SendTelegramPaymentNotification` | `PaymentReceived`, `PaymentApproved`, `PaymentRejected` | Sends payment status notifications |
| `SendTelegramLowStockAlert` | `LowStockAlert` | Alerts admin about low stock products |

### How It Works

```
[Event] OrderPlaced
    │
    ▼
[EventServiceProvider] maps to listeners
    │
    ▼
[Listener] SendTelegramOrderNotification
    │
    ├─► [Service] TelegramNotificationService
    │       ├─► Customer: "Your order #123 has been placed ✅"
    │       └─► Admin: "New order #123 - $89.99 - John Doe"
    │
    └─► [Job] SendTelegramMessageJob (queued)
            └─► Telegram Bot API
```

### Notification Types

| Type | Description |
|------|-------------|
| **Telegram** | Real-time bot notifications to customer and admin chats |
| **Email** | _(Coming soon)_ Email notifications with Laravel Mail |
| **Database** | _(Coming soon)_ In-app notification center |

---

## 📂 API Endpoints

### Authentication

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| POST | `/api/register` | Register new user | ❌ |
| POST | `/api/login` | Login and get token | ❌ |
| POST | `/api/logout` | Revoke current token | ✅ |
| GET | `/api/profile` | Get authenticated user profile | ✅ |
| PUT | `/api/profile` | Update user profile | ✅ |
| PUT | `/api/password` | Change password | ✅ |
| GET | `/api/auth/google/redirect` | Redirect to Google OAuth | ❌ |
| GET | `/api/auth/google/callback` | Google OAuth callback | ❌ |

### Products

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| GET | `/api/products` | List products (paginated, filterable) | ❌ |
| GET | `/api/products/{product}` | Get single product with images | ❌ |
| GET | `/api/products/{product}/reviews` | Get product reviews | ❌ |
| POST | `/api/products/{product}/reviews` | Create a review | ✅ |
| PUT | `/api/products/{product}/reviews/{review}` | Update your review | ✅ |
| DELETE | `/api/products/{product}/reviews/{review}` | Delete your review | ✅ |
| GET | `/api/categories` | List all categories | ❌ |
| GET | `/api/filters` | Get available filter options | ❌ |

### Cart

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| GET | `/api/cart` | Get current cart items | ❌* |
| POST | `/api/cart` | Add item to cart | ❌* |
| PUT | `/api/cart/{cart}` | Update cart item quantity | ❌* |
| DELETE | `/api/cart/{cart}` | Remove item from cart | ❌* |
| DELETE | `/api/cart` | Clear entire cart | ❌* |
| POST | `/api/cart/merge` | Merge guest cart on login | ✅ |

> *Works with guest token (`X-Guest-Token` header) or authenticated user.

### Wishlist

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| GET | `/api/wishlist` | Get wishlist items | ❌* |
| POST | `/api/wishlist` | Add item to wishlist | ❌* |
| POST | `/api/wishlist/toggle` | Toggle item in wishlist | ❌* |
| DELETE | `/api/wishlist/{wishlist}` | Remove from wishlist | ❌* |
| POST | `/api/wishlist/merge` | Merge guest wishlist on login | ✅ |

### Orders

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| POST | `/api/checkout` | Place a new order | ✅ |
| GET | `/api/orders` | List user orders | ✅ |
| GET | `/api/orders/{order}` | Get order details | ✅ |
| PUT | `/api/orders/{order}/cancel` | Cancel pending order | ✅ |

### Promotions

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| GET | `/api/promotions` | List active promotions | ❌ |
| GET | `/api/promotions/{promotion}` | Get promotion details | ❌ |
| POST | `/api/promotions/validate` | Validate a promo code | ❌ |

### Telegram (User)

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| GET | `/api/telegram/status` | Get Telegram connection status | ✅ |
| POST | `/api/telegram/connect` | Generate verification code | ✅ |
| POST | `/api/telegram/toggle-notifications` | Toggle notification preferences | ✅ |
| POST | `/api/telegram/unlink` | Unlink Telegram account | ✅ |
| POST | `/api/telegram/send-test` | Send test notification | ✅ |

### Telegram (Webhook)

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| POST | `/api/telegram/webhook` | Telegram webhook entry point | ❌* |

> *Validated via `X-Telegram-Bot-Api-Secret-Token` header or `secret` query parameter.

### Admin (Web Routes)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/admin/login` | Admin login page |
| POST | `/admin/login` | Admin login action |
| POST | `/admin/logout` | Admin logout |
| GET | `/admin/dashboard` | Admin dashboard |
| GET/POST/PUT/DELETE | `/admin/categories` | Category management |
| GET/POST/PUT/DELETE | `/admin/products` | Product management |
| GET | `/admin/orders` | Order listing |
| GET | `/admin/orders/{order}` | Order detail |
| GET | `/admin/users` | User management |
| GET/POST/PUT/DELETE | `/admin/promotions` | Promotion management |
| GET | `/admin/telegram/dashboard` | Telegram admin dashboard |
| GET | `/admin/telegram/logs` | Telegram bot logs |
| POST | `/admin/telegram/retry-failed` | Retry failed messages |

---

## 🧪 Testing

### Run Tests

```bash
php artisan test
```

### Test Suites

| Suite | Description |
|-------|-------------|
| **Unit Tests** | Test individual classes in isolation |
| **Feature Tests** | Test HTTP endpoints and application flow |

### Testing Configuration

Tests use an **in-memory SQLite database** with:
- `CACHE_DRIVER=array`
- `QUEUE_CONNECTION=sync`
- `SESSION_DRIVER=array`
- `MAIL_MAILER=array`

Configured in `phpunit.xml`.

### Run Specific Tests

```bash
# Run all tests in a directory
php artisan test tests/Feature

# Run a specific test file
php artisan test tests/Feature/ExampleTest.php

# Run with coverage (requires Xdebug or PCOV)
php artisan test --coverage
```

> **⚠️ Note:** Tests are currently minimal (skeleton/placeholder files). Contributions welcome!

---

## 🔍 Troubleshooting

### Composer install fails

**Issue:** Memory limit or dependency conflicts.

**Solutions:**
```bash
# Increase memory limit
php -d memory_limit=-1 composer install

# Update dependencies
composer update

# Clear Composer cache
composer clear-cache
```

### Migration errors

**Issue:** `php artisan migrate` throws SQL errors.

**Solutions:**
- Ensure MySQL 8.0+ is running: `mysql --version`
- Verify database credentials in `.env`
- Create the database first: `mysql -u root -p -e "CREATE DATABASE ecommerce_onlineshop CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"`
- Drop all tables and retry: `php artisan migrate:fresh`

### Permission denied

**Issue:** Storage or bootstrap cache not writable.

**Solutions:**
```bash
# Windows: Give write permissions to the storage folder
icacls storage /grant "Everyone:(OI)(CI)W"
icacls bootstrap/cache /grant "Everyone:(OI)(CI)W"

# Linux/Mac:
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

### Storage link issues

**Issue:** `php artisan storage:link` fails or images not showing.

**Solutions:**
```bash
# Remove existing link and recreate
rm public/storage
php artisan storage:link

# Or create manually (Windows)
mklink /D public\storage ..\storage\app\public
```

### Queue not running

**Issue:** Async jobs (Telegram notifications) are not being processed.

**Solutions:**
```bash
# Start queue worker
php artisan queue:work

# Check failed jobs
php artisan queue:failed

# Retry all failed
php artisan queue:retry all

# Ensure QUEUE_CONNECTION=database in .env
```

### Telegram webhook not working

**Issue:** Bot not receiving updates.

**Solutions:**
```bash
# Check current webhook status
php artisan telegram:bot check-webhook

# Delete and recreate webhook
php artisan telegram:bot delete-webhook
php artisan telegram:bot set-webhook https://your-domain.com/api/telegram/webhook

# Use polling instead (for local dev)
php artisan telegram:poll
```

> **⚠️ Note:** Webhooks require HTTPS. Use [ngrok](https://ngrok.com/) for local development.

### Telegram bot not responding

**Issue:** Bot sends no response or "bot not found".

**Solutions:**
- Verify `TELEGRAM_BOT_TOKEN` is correct
- Check `php artisan telegram:check-health`
- Ensure `php artisan queue:work` is running (messages are queued)
- Check Telegram bot logs in admin panel: `/admin/telegram/logs`
- Verify webhook is set correctly

### Swagger not loading

**Issue:** Swagger UI shows blank page or 404.

**Solutions:**
```bash
# Regenerate documentation
php artisan l5-swagger:generate

# Clear route cache
php artisan route:clear

# Check URL: http://localhost:8000/api/documentation

# Verify L5_SWAGGER_CONST_HOST in .env matches APP_URL
```

### Database connection failed

**Issue:** "SQLSTATE[HY000] [2002] Connection refused" or similar.

**Solutions:**
- Ensure MySQL service is running
- Check `.env` database credentials
- Verify `DB_HOST` — use `127.0.0.1` instead of `localhost` on Windows
- Check MySQL port: `netstat -an | findstr :3306`

### Sanctum unauthorized

**Issue:** Protected routes return 401 even with token.

**Solutions:**
- Ensure token is passed as `Authorization: Bearer <token>`
- Verify CORS: `SANCTUM_STATEFUL_DOMAINS` must include your frontend domain
- Check token format: `{id}|{plain_text_token}` (no extra quotes)
- Generate a fresh token by re-logging in

### Social login callback error

**Issue:** Google login returns error or redirects incorrectly.

**Solutions:**
- Verify `GOOGLE_CLIENT_ID` and `GOOGLE_CLIENT_SECRET` in `.env`
- Ensure `GOOGLE_REDIRECT_URI` matches exactly in Google Cloud Console
- Check the redirect URI is added to Authorized Redirect URIs in Google Console
- Verify `FRONTEND_URL` is correct for the final redirect

---

## 📈 Future Improvements

| Feature | Status | Priority |
|---------|--------|---------|
| **Payment Gateway** (Stripe, PayPal) | Planned | 🔴 High |
| **Coupons & Discounts** | Planned | 🔴 High |
| **Multi-language Support** | Planned | 🟡 Medium |
| **Inventory Management** | Planned | 🟡 Medium |
| **Advanced Analytics & Reports** | Planned | 🟡 Medium |
| **SMS Notifications** | Planned | 🟢 Low |
| **Docker Setup** | Planned | 🟢 Low |
| **CI/CD Pipeline** | Planned | 🟢 Low |
| **WebSockets** (real-time updates) | Planned | 🟢 Low |
| **AI Product Recommendations** | Planned | 🟢 Low |
| **Email Verification** | Planned | 🟢 Low |
| **Password Reset** | Planned | 🟢 Low |
| **Email Notifications** | Planned | 🟢 Low |
| **Database Notifications** | Planned | 🟢 Low |

---

## 🤝 Contributing

Contributions are welcome! Please follow this workflow:

1. **Fork** the repository
2. **Create** a feature branch:
   ```bash
   git checkout -b feature/amazing-feature
   ```
3. **Commit** your changes using [Conventional Commits](https://www.conventionalcommits.org/):
   ```bash
   git commit -m "feat: add amazing feature"
   ```
4. **Push** to your fork:
   ```bash
   git push origin feature/amazing-feature
   ```
5. **Open** a Pull Request

### Commit Convention

| Prefix | Usage |
|--------|-------|
| `feat:` | New feature |
| `fix:` | Bug fix |
| `refactor:` | Code refactoring |
| `style:` | Formatting, styling |
| `docs:` | Documentation |
| `test:` | Adding or updating tests |
| `chore:` | Maintenance, dependencies |

### Guidelines

- Follow existing code style (Laravel Pint will auto-format)
- Write tests for new features
- Update Swagger annotations for new/changed endpoints
- Keep pull requests focused on a single feature/fix

---

## 📄 License

This project is licensed under the **MIT License** — see the [LICENSE](LICENSE) file for details.

---

## 👨‍💻 Author

**Dane**

[![GitHub](https://img.shields.io/badge/GitHub-dane25006-181717?logo=github)](https://github.com/dane25006)

📧 **Email** — _your.email@example.com_

🔗 **LinkedIn** — _linkedin.com/in/yourusername_

🌐 **Portfolio** — _your-portfolio.com_

---

<p align="center">
  <sub>Built with ❤️ using Laravel 12</sub>
</p>
