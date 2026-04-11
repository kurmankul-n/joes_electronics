CREATE DATABASE IF NOT EXISTS inventory_db;
USE inventory_db;

CREATE TABLE IF NOT EXISTS users (
    UserID      INT(10)        NOT NULL AUTO_INCREMENT,
    Username    VARCHAR(50)    NOT NULL UNIQUE,
    Password    VARCHAR(255)   NOT NULL,
    FullName    VARCHAR(100)   NOT NULL,
    Role        VARCHAR(20)    NOT NULL DEFAULT 'user',
    CreatedAt   DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (UserID)
);

INSERT INTO users (Username, Password, FullName, Role) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', 'admin'),
('user', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Regular User', 'user');

CREATE TABLE IF NOT EXISTS products (
    ProductID      INT(10)        NOT NULL AUTO_INCREMENT,
    ProductName    VARCHAR(100)   NOT NULL,
    Category       VARCHAR(50)    NOT NULL,
    UnitPrice      DECIMAL(10,2)  NOT NULL,
    StockQuantity  INT(10)        NOT NULL DEFAULT 0,
    PRIMARY KEY (ProductID)
);

CREATE TABLE IF NOT EXISTS reports (
    ReportID    INT(10)  NOT NULL AUTO_INCREMENT,
    ReportDate  DATE     NOT NULL UNIQUE,
    PRIMARY KEY (ReportID)
);

CREATE TABLE IF NOT EXISTS sales (
    SaleID       INT(10)  NOT NULL AUTO_INCREMENT,
    SaleDate     DATE     NOT NULL,
    ProductID    INT(10)  NOT NULL,
    ReportID     INT(10),
    QuantitySold INT(10)  NOT NULL DEFAULT 1,
    PRIMARY KEY (SaleID),
    FOREIGN KEY (ProductID) REFERENCES products(ProductID),
    FOREIGN KEY (ReportID)  REFERENCES reports(ReportID),
    INDEX idx_sales_saledate (SaleDate),
    INDEX idx_sales_productid (ProductID)
);

INSERT INTO products (ProductName, Category, UnitPrice, StockQuantity) VALUES
('Samsung TV 55"',         'Television',  499.99, 15),
('Sony Headphones WH-1000', 'Audio',       299.99, 30),
('iPhone 15 Case',         'Accessories',  19.99, 50),
('Logitech Keyboard K380', 'Peripherals',  49.99, 20),
('USB-C Hub 7-in-1',       'Accessories',  34.99,  4),
('Coca-Cola 2L',           'Soda',          2.49, 100),
('HDMI Cable 2m',          'Accessories',   8.99, 60),
('Wireless Mouse',         'Peripherals',  25.99,  2);