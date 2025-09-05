# Inventory-Management
A mini Inventory Management System built with **Laravel (Backend)**, **React.js (Frontend)**, **MySQL (Database)**, and optional **Redis caching**.

---

## Features
- **Authentication & Security**
  - JWT-based authentication
  - Two-Factor Authentication (2FA) via Google Authenticator
  - Role-Based Access Control (RBAC):
    - **Admin** → Manage users & inventory
    - **Manager** → Add/Update inventory
    - **Viewer** → Read-only access
- **Inventory Management**
  - CRUD operations (with validation: no negative quantity/price)
  - Tracks `created_by` user
- **Audit Logs**
  - Track user actions (create, update, delete)
- **Caching**
  - Redis caching for inventory list 
- **Docker Setup**
  - Laravel backend, React frontend, MySQL, Redis in containers

---

## 🗄 Database Schema
### `users`
| Field       | Type    |
|-------------|---------|
| id          | int (PK) |
| name        | string  |
| email       | string (unique) |
| password    | string (hashed) |
| two_factor_enabled  | string (nullable) |
| role        | enum(`admin`,`manager`,`viewer`) |
| two_factor_secret  | string (nullable) |

### `inventory`
| Field       | Type    |
|-------------|---------|
| id          | int (PK) |
| name        | string  |
| description | text    |
| quantity    | int     |
| price       | decimal(10,2) |
| created_by  | int (FK → users.id) |

### `audit_logs`
| Field    | Type    |
|----------|---------|
| id       | int (PK) |
| action   | string  |
| user_id  | int (FK → users.id) |
| timestamp| datetime |

---

## Backend Setup (Laravel)

## 1️ Install dependencies
```bash
composer install

2️  Configure .env
Configure .env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=inventory_management
DB_USERNAME=root
DB_PASSWORD=

CACHE_DRIVER=redis
QUEUE_CONNECTION=redis

3️ Run migrations & seeders
Run migrations & seeders
php artisan migrate --seed

4️ Start server
php artisan serve

### Frontend Setup (Recat)
1️ Navigate to frontend
cd ../inventory_management_frontend

2️ Install dependencies
npm install

3️ Run app
npm start
