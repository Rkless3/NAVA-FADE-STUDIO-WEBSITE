-- =========================================================
-- NAVA FADE STUDIO
-- DATABASE BACKUP
-- =========================================================

CREATE DATABASE IF NOT EXISTS nava_fade_studio
CHARACTER SET utf8mb4
COLLATE utf8mb4_general_ci;

USE nava_fade_studio;


-- =========================================================
-- DROP TABLES
-- =========================================================

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS order_items;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS reviews;
DROP TABLE IF EXISTS appointments;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS services;
DROP TABLE IF EXISTS customers;
DROP TABLE IF EXISTS users;

SET FOREIGN_KEY_CHECKS = 1;


-- =========================================================
-- USERS
-- Admin / system users
-- =========================================================

CREATE TABLE users (
    id INT(11) NOT NULL AUTO_INCREMENT,
    username VARCHAR(100) NOT NULL,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id)
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_general_ci;


-- =========================================================
-- CUSTOMERS
-- =========================================================

CREATE TABLE customers (
    id INT(11) NOT NULL AUTO_INCREMENT,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    contact_number VARCHAR(11) NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id)
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_general_ci;


-- =========================================================
-- SERVICES
-- =========================================================

CREATE TABLE services (
    id INT(11) NOT NULL AUTO_INCREMENT,
    service_name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    duration VARCHAR(50),
    image VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id)
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_general_ci;


-- =========================================================
-- PRODUCTS
-- =========================================================

CREATE TABLE products (
    id INT(11) NOT NULL AUTO_INCREMENT,
    product_name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    stock INT(11) NOT NULL DEFAULT 0,
    image VARCHAR(255),
    status ENUM('Active','Inactive') NOT NULL DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id)
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_general_ci;


-- =========================================================
-- APPOINTMENTS
-- =========================================================

CREATE TABLE appointments (
    id INT(11) NOT NULL AUTO_INCREMENT,
    customer_id INT(11) NOT NULL,
    service VARCHAR(255) NOT NULL,
    appointment_date DATE NOT NULL,
    appointment_time TIME NOT NULL,
    notes TEXT,
    status ENUM(
        'Pending',
        'Confirmed',
        'Completed',
        'Cancelled'
    ) NOT NULL DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),

    CONSTRAINT fk_appointments_customer
        FOREIGN KEY (customer_id)
        REFERENCES customers(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE

) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_general_ci;


-- =========================================================
-- ORDERS
-- =========================================================

CREATE TABLE orders (
    id INT(11) NOT NULL AUTO_INCREMENT,
    customer_id INT(11) NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    status ENUM(
        'Pending',
        'Confirmed',
        'Processing',
        'Completed',
        'Cancelled'
    ) NOT NULL DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),

    CONSTRAINT fk_orders_customer
        FOREIGN KEY (customer_id)
        REFERENCES customers(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE

) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_general_ci;


-- =========================================================
-- ORDER ITEMS
-- =========================================================

CREATE TABLE order_items (
    id INT(11) NOT NULL AUTO_INCREMENT,
    order_id INT(11) NOT NULL,
    product_id INT(11) NOT NULL,
    quantity INT(11) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,

    PRIMARY KEY (id),

    CONSTRAINT fk_order_items_order
        FOREIGN KEY (order_id)
        REFERENCES orders(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    CONSTRAINT fk_order_items_product
        FOREIGN KEY (product_id)
        REFERENCES products(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE

) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_general_ci;


-- =========================================================
-- REVIEWS
-- =========================================================

CREATE TABLE reviews (
    id INT(11) NOT NULL AUTO_INCREMENT,
    customer_id INT(11) NOT NULL,
    rating TINYINT(1) NOT NULL,
    comment TEXT NOT NULL,
    status ENUM(
        'Pending',
        'Approved',
        'Hidden'
    ) NOT NULL DEFAULT 'Pending',
    is_featured TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),

    CONSTRAINT fk_reviews_customer
        FOREIGN KEY (customer_id)
        REFERENCES customers(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    CONSTRAINT chk_reviews_rating
        CHECK (rating BETWEEN 1 AND 5)

) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_general_ci;


-- =========================================================
-- RE-ENABLE FOREIGN KEY CHECKING
-- =========================================================

SET FOREIGN_KEY_CHECKS = 1;


-- =========================================================
-- END OF BACKUP
-- =========================================================