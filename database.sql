CREATE DATABASE IF NOT EXISTS barakah_grove CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE barakah_grove;

CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    phone VARCHAR(30) NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('customer','admin') NOT NULL DEFAULT 'customer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE products (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(180) NOT NULL,
    description TEXT NULL,
    price DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    image VARCHAR(255) NULL,
    stock INT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE orders (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    invoice_no VARCHAR(50) NOT NULL UNIQUE,
    subtotal DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    total DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    status ENUM('pending','paid','completed','cancelled') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_orders_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE order_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NULL,
    product_name VARCHAR(180) NOT NULL,
    price DECIMAL(12,2) NOT NULL,
    quantity INT UNSIGNED NOT NULL,
    total DECIMAL(12,2) NOT NULL,
    CONSTRAINT fk_items_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    CONSTRAINT fk_items_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Demo admin. Password: Admin@12345
INSERT INTO users (full_name, email, phone, password, role)
VALUES (
    'Barakah Grove Admin',
    'admin@barakahgrove.com',
    '08000000000',
    '$2y$12$Nhk8wxMgsniWkVbKyKydp.HD4ndcgLWyvVDbobinPjx2naG1PXjAC',
    'admin'
);

INSERT INTO products (name, description, price, stock, image) VALUES
('Wireless Headphones', 'Comfortable wireless headphones with rich sound.', 35000, 15, NULL),
('Smart Watch', 'Modern smart watch for fitness and everyday use.', 28000, 20, NULL),
('Bluetooth Speaker', 'Portable Bluetooth speaker with powerful audio.', 22000, 12, NULL);
