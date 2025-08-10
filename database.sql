CREATE DATABASE smart_canteen;
USE smart_canteen;

CREATE TABLE admin_users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL
);

INSERT INTO admin_users (username, password) VALUES
('admin', MD5('admin123'));

CREATE TABLE menu_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(6,2) NOT NULL,
    image VARCHAR(255)
);

CREATE TABLE orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    items TEXT NOT NULL,
    total DECIMAL(8,2) NOT NULL,
    order_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
