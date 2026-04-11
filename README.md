# Inventory Management System
### Joe's Electronics

Web-based inventory management system built with PHP, MySQL, HTML/CSS and JavaScript. Runs locally via XAMPP.

---

## Project Info

| | |
|---|---|
| **Student** | Nurislam Kurmankul, 11C |
| **Teacher** | Kosemova A.R. |
| **Subject** | Computer Science |
| **Year** | 2025-2026 |
| **Stack** | PHP, MySQL, HTML, CSS, JavaScript |
| **Server** | XAMPP (localhost) |

---

## Installation

### Prerequisites
- XAMPP installed
- Apache and MySQL modules running

### Step 1 — Copy project files
Copy the `inventory` folder into your XAMPP web root:
```
C:\xampp\htdocs\inventory\
```

### Step 2 — Create the database
1. Open `http://localhost/phpmyadmin`
2. Click the **SQL** tab
3. Open `inventory_db.sql` from the project folder, copy its contents
4. Paste into the SQL tab and click **Go**

### Step 3 — Run the application
Open `http://localhost/inventory/`

---

## File Structure

```
inventory/
├── config.php          # Database connection, session start
├── auth.php            # Auth functions: login, register, logout, role checks
├── functions.php       # Sorting and search algorithms (Bubble Sort, Insertion Sort, Binary Search)
├── login.php           # Login page
├── register.php        # Registration page
├── logout.php          # Session destroy and redirect
├── index.php           # Dashboard — summary stats and recent sales
├── products.php        # Product management — view, search, add, edit, delete
├── sales.php           # Record a sale with live revenue preview
├── reports.php         # Daily sales and inventory reports
├── profile.php         # User profile — edit info, change password
├── users.php           # User management — admin only
├── style.css           # Dark theme stylesheet
└── inventory_db.sql    # Database setup script
```

---

## Database Structure

**Database name:** `inventory_db`

### Table: `users`
| Field | Type | Description |
|-------|------|-------------|
| UserID | INT(10) AUTO_INCREMENT PK | Unique user identifier |
| Username | VARCHAR(50) UNIQUE | Login username |
| Password | VARCHAR(255) | Bcrypt-hashed password |
| FullName | VARCHAR(100) | Display name |
| Role | VARCHAR(20) | "admin" or "user" |
| CreatedAt | DATETIME | Registration timestamp |

### Table: `products`
| Field | Type | Description |
|-------|------|-------------|
| ProductID | INT(10) AUTO_INCREMENT PK | Unique product identifier |
| ProductName | VARCHAR(100) | Name of the product |
| Category | VARCHAR(50) | Product category |
| UnitPrice | DECIMAL(10,2) | Price per unit |
| StockQuantity | INT(10) | Current stock quantity |

### Table: `reports`
| Field | Type | Description |
|-------|------|-------------|
| ReportID | INT(10) AUTO_INCREMENT PK | Unique report identifier |
| ReportDate | DATE UNIQUE | Date the report covers |

### Table: `sales`
| Field | Type | Description |
|-------|------|-------------|
| SaleID | INT(10) AUTO_INCREMENT PK | Unique sale identifier |
| SaleDate | DATE | Date of the sale |
| ProductID | INT(10) FK | References products table |
| ReportID | INT(10) FK | References reports table |
| QuantitySold | INT(10) | Number of units sold |

---

## Authentication

All users must log in before accessing any page. Unauthenticated visitors are redirected to the login page.

### Default Accounts

| Username | Password | Role |
|----------|----------|------|
| `admin` | `password` | Administrator |
| `user` | `password` | Regular User |

After registration, new accounts are created with the `user` role by default.

---

## Pages and Functionality

### Login / Register
- Sign in with username and password
- Create a new account with full name, username, and password
- Passwords are hashed with bcrypt
- Session regeneration on login to prevent session fixation

### Dashboard (`index.php`)
- Total products count
- Today's units sold
- Low stock items count (products with less than 5 units)
- Total inventory value (price x stock for all products)
- Table of the 5 most recent sales with revenue
- Warning alert if low stock items exist

### Products (`products.php`)
- Full product list sorted alphabetically using **Bubble Sort**
- **Binary Search** by Product ID (positive integers only)
- Category badges generated from a 1D array
- Color-coded stock indicators (In Stock / Low Stock / Out of Stock)
- Add, edit, and delete products (admin only)
- Edit form pre-fills with existing product data

### Record Sale (`sales.php`)
- Dropdown of available products with live stock count
- Live revenue preview in JavaScript (price x quantity)
- Stock validation before recording — prevents overselling
- Automatic daily report creation if one doesn't exist for the date
- Stock quantity is reduced after each sale
- Sales history table (last 15 entries)

### Reports (`reports.php`)
- Date picker to view reports for any date
- Product summary built from a **2D array** (nested loop in `buildReportMatrix`)
- Table sorted by revenue descending using **Insertion Sort**
- Totals row: units sold, remaining stock, total revenue
- Quick-access links to the 5 most recent report dates
- Print button for hard copy output

### Profile (`profile.php`)
- View account info: username, full name, role, registration date
- Edit full name
- Change password with current password verification

### Manage Users (`users.php`) — Admin Only
- List of all registered users
- Change user role (admin / user)
- Delete users (cannot delete own account)
- Link to register new users

---

## Role Differences

| Feature | Admin | Regular User |
|---------|-------|-------------|
| View Dashboard | Yes | Yes |
| View Products | Yes | Yes |
| Search Products | Yes | Yes |
| Add Products | Yes | No |
| Edit Products | Yes | No |
| Delete Products | Yes | No |
| Record Sales | Yes | Yes |
| View Sales History | Yes | Yes |
| View Reports | Yes | Yes |
| View/Edit Profile | Yes | Yes |
| Manage Users | Yes | No (page hidden, access denied) |
| "Users" link in navbar | Visible | Hidden |

---

## Algorithms

### Bubble Sort
Used in `products.php` to sort the product list alphabetically by name. Compares adjacent elements and swaps them repeatedly.

### Insertion Sort
Used in `reports.php` to sort the report table by revenue in descending order. Inserts each element into its correct position in the sorted portion.

### Binary Search
Used in `products.php` search box. Finds a product by ID in a sorted array by repeatedly halving the search range. Returns the index if found, -1 otherwise.

---

## Security

- Passwords hashed with `password_hash()` / `password_verify()` (bcrypt)
- Session regeneration on login
- SQL escaping with `mysqli_real_escape_string()`
- HTML output escaped with `htmlspecialchars()` to prevent XSS
- Destructive actions (delete) use POST requests, not GET
- Input validation on both client and server side

---

## Tested With
- XAMPP 8.2 (Apache + MySQL)
- PHP 8.2
- MySQL 5.7 / MariaDB 10.4
- Google Chrome / Microsoft Edge
