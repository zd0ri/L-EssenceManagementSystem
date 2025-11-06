-- Create Database
CREATE DATABASE essence_db;
USE essence_db;

-- 1. USERS TABLE
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'customer', 'inventory_manager', 'finance_manager') NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    status ENUM('active', 'inactive') DEFAULT 'active',
    date_created DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- 2. CUSTOMERS TABLE
CREATE TABLE customers (
    customer_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    fullname VARCHAR(150) NOT NULL,
    contact VARCHAR(20),
    address VARCHAR(255),
    email VARCHAR(150),
    date_registered DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id)
        ON UPDATE CASCADE ON DELETE CASCADE
);

-- 3. PRODUCTS TABLE
CREATE TABLE products (
    product_id INT AUTO_INCREMENT PRIMARY KEY,
    brand_name VARCHAR(100) NOT NULL,
    scent_type VARCHAR(100),
    size VARCHAR(50),
    price DECIMAL(10,2) NOT NULL,
    description TEXT,
    image VARCHAR(255),
    status ENUM('available', 'out_of_stock', 'discontinued') DEFAULT 'available',
    date_added DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- 4. INVENTORY TABLE
CREATE TABLE inventory (
    inventory_id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 0,
    last_updated DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    restock_date DATETIME,
    updated_by INT,
    FOREIGN KEY (product_id) REFERENCES products(product_id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    FOREIGN KEY (updated_by) REFERENCES users(user_id)
        ON UPDATE CASCADE ON DELETE SET NULL
);

-- 5. ORDERS TABLE
CREATE TABLE orders (
    order_id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    order_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending', 'processing', 'shipped', 'completed', 'cancelled') DEFAULT 'pending',
    payment_status ENUM('unpaid', 'paid', 'refunded') DEFAULT 'unpaid',
    delivery_method VARCHAR(100),
    remarks VARCHAR(255),
    FOREIGN KEY (customer_id) REFERENCES customers(customer_id)
        ON UPDATE CASCADE ON DELETE CASCADE
);

-- 6. ORDER_ITEMS TABLE
CREATE TABLE order_items (
    order_item_id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    price_each DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(10,2) GENERATED ALWAYS AS (quantity * price_each) STORED,
    FOREIGN KEY (order_id) REFERENCES orders(order_id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(product_id)
        ON UPDATE CASCADE ON DELETE CASCADE
);

-- 7. PAYMENTS TABLE
CREATE TABLE payments (
    payment_id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    payment_method ENUM('GCash', 'Credit Card', 'Cash on Delivery', 'Bank Transfer') NOT NULL,
    amount_paid DECIMAL(10,2) NOT NULL,
    date_paid DATETIME DEFAULT CURRENT_TIMESTAMP,
    reference_no VARCHAR(100),
    FOREIGN KEY (order_id) REFERENCES orders(order_id)
        ON UPDATE CASCADE ON DELETE CASCADE
);

-- 8. EXPENSES TABLE
CREATE TABLE expenses (
    expense_id INT AUTO_INCREMENT PRIMARY KEY,
    expense_type VARCHAR(100) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    date_recorded DATETIME DEFAULT CURRENT_TIMESTAMP,
    recorded_by INT,
    notes TEXT,
    FOREIGN KEY (recorded_by) REFERENCES users(user_id)
        ON UPDATE CASCADE ON DELETE SET NULL
);

-- 9. SALES_REPORT TABLE
CREATE TABLE sales_report (
    report_id INT AUTO_INCREMENT PRIMARY KEY,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    total_sales DECIMAL(10,2) NOT NULL,
    total_expenses DECIMAL(10,2) NOT NULL,
    net_income DECIMAL(10,2) GENERATED ALWAYS AS (total_sales - total_expenses) STORED,
    generated_by INT,
    date_generated DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (generated_by) REFERENCES users(user_id)
        ON UPDATE CASCADE ON DELETE SET NULL
);

-- 10. ACTIVITY_LOG TABLE
CREATE TABLE activity_log (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    activity TEXT NOT NULL,
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id)
        ON UPDATE CASCADE ON DELETE SET NULL
);