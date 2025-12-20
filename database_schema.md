# MTP Flex Database Schema

This document outlines the database structure derived from the application codebase.

## Tables

### 1. `users`
Stores registered users and administrators.
- **id** (INT, PK, Auto Increment)
- **name** (VARCHAR)
- **email** (VARCHAR, Unique)
- **password** (VARCHAR) - *Hashed*
- **is_admin** (TINYINT/BOOLEAN) - *0 for User, 1 for Admin*
- **phone_number** (VARCHAR) - *Nullable*
- **address** (TEXT) - *Nullable*

### 2. `categories`
Product categories for filtering.
- **id** (INT, PK, Auto Increment)
- **name** (VARCHAR)

### 3. `products`
Inventory items.
- **id** (INT, PK, Auto Increment)
- **category_id** (INT, FK -> categories.id)
- **name** (VARCHAR)
- **description** (TEXT)
- **price** (DECIMAL)
- **stock** (INT)
- **image_path** (VARCHAR)
- **status** (ENUM/VARCHAR) - *e.g., 'Available'*
- **created_at** (DATETIME)

### 4. `orders`
Main order records.
- **id** (INT, PK, Auto Increment)
- **user_id** (INT, FK -> users.id) - *Nullable for guest checkout*
- **customer_name** (VARCHAR)
- **customer_email** (VARCHAR)
- **shipping_address** (TEXT)
- **total_amount** (DECIMAL)
- **status** (VARCHAR) - *e.g., 'Pending', 'Completed'*
- **created_at** (DATETIME)

### 5. `order_items`
Individual items within an order.
- **id** (INT, PK, Auto Increment)
- **order_id** (INT, FK -> orders.id)
- **product_id** (INT, FK -> products.id)
- **quantity** (INT)
- **price_at_order** (DECIMAL) - *Snapshots price at time of purchase*

## Relationships

- **Users** have many **Orders**.
- **Categories** have many **Products**.
- **Orders** have many **Order Items**.
- **Products** can be in many **Order Items**.

## SQL Setup Snippet (Quick Start)

```sql
CREATE TABLE categories (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(255));
CREATE TABLE users (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(255), email VARCHAR(255), password VARCHAR(255), is_admin TINYINT DEFAULT 0, phone_number VARCHAR(50), address TEXT);
CREATE TABLE products (id INT AUTO_INCREMENT PRIMARY KEY, category_id INT, name VARCHAR(255), description TEXT, price DECIMAL(10,2), stock INT, image_path VARCHAR(255), status VARCHAR(50) DEFAULT 'Available', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP);
CREATE TABLE orders (id INT AUTO_INCREMENT PRIMARY KEY, user_id INT, customer_name VARCHAR(255), customer_email VARCHAR(255), shipping_address TEXT, total_amount DECIMAL(10,2), status VARCHAR(50) DEFAULT 'Pending', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP);
CREATE TABLE order_items (id INT AUTO_INCREMENT PRIMARY KEY, order_id INT, product_id INT, quantity INT, price_at_order DECIMAL(10,2));
```
