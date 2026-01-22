# Inventory & Sales Management System

## Overview
A comprehensive web-based system to track products, suppliers, stock levels, sales records, and generate reports. Built with pure PHP, HTML, and CSS.

## Features
- **Authentication**: Secure Login/Logout.
- **User Management**: Create and delete users (Admin only).
- **Dashboard**: Quick overview of key metrics.
- **Product Management**: Add, edit, delete, and list products with stock tracking.
- **Supplier Management**: Add, edit, delete, and list suppliers.
- **Customer Management**: Manage customer details.
- **Purchase Orders**: Receive stock from suppliers (increases inventory).
- **Sales Management**: Create sales invoices (decreases inventory) and Void sales (restores inventory).
- **Reporting**: Filter sales and purchase reports by date range.

## Setup Instructions
1.  **Database Setup**:
    - Create a database named `inventory_system` in your MySQL server.
    - Import the `database.sql` file located in the root directory.
    - Default Admin User: `admin`
    - Default Password: `password`

2.  **Configuration**:
    - Check `includes/db.php` to ensure database credentials match your local setup (Default: root/empty).

3.  **Run**:
    - Place the project folder in your web server's root (e.g., `htdocs` for XAMPP).
    - Access via browser: `http://localhost:8080/inventory%20and%20sales/`

## Folder Structure
- `admin/`: Product, Supplier, Customer, and User management.
- `purchases/`: Purchase order management.
- `sales/`: Sales invoice management.
- `reports/`: Reporting tools.
- `includes/`: Database connection and shared files.
- `css/`: Stylesheets.
