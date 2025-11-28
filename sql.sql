-- Xóa các đối tượng cũ
DROP VIEW IF EXISTS vw_PaymentDetails;
DROP TRIGGER IF EXISTS trg_UpdateOrderStatus;
DROP PROCEDURE IF EXISTS sp_CreatePayment;
DROP PROCEDURE IF EXISTS sp_UpdatePaymentStatus;

-- Xóa bảng theo thứ tự (MySQL tự xử lý FK nếu có ON DELETE CASCADE hoặc tạm thời tắt foreign key checks)
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS Payments;
DROP TABLE IF EXISTS PaymentMethods;
DROP TABLE IF EXISTS Orders;
DROP TABLE IF EXISTS Customers;
SET FOREIGN_KEY_CHECKS = 1;

-- 1. Bảng Customers
CREATE TABLE Customers (
    CustomerID INT PRIMARY KEY AUTO_INCREMENT,
    CustomerName VARCHAR(255) NOT NULL,
    Email VARCHAR(255) NOT NULL UNIQUE,
    Phone VARCHAR(20),
    CreatedAt DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 2. Bảng Orders
CREATE TABLE Orders (
    OrderID INT PRIMARY KEY AUTO_INCREMENT,
    CustomerID INT NOT NULL,
    OrderDate DATETIME DEFAULT CURRENT_TIMESTAMP,
    TotalAmount DECIMAL(18,2) NOT NULL,
    Status ENUM('Pending', 'Paid', 'Failed', 'Cancelled') DEFAULT 'Pending',
    OrderDescription VARCHAR(500) NULL,
    FOREIGN KEY (CustomerID) REFERENCES Customers(CustomerID) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 3. Bảng PaymentMethods
CREATE TABLE PaymentMethods (
    MethodID INT PRIMARY KEY AUTO_INCREMENT,
    MethodName VARCHAR(100) NOT NULL
) ENGINE=InnoDB;

-- 4. Bảng Payments
CREATE TABLE Payments (
    PaymentID INT PRIMARY KEY AUTO_INCREMENT,
    OrderID INT NOT NULL,
    Amount DECIMAL(18,2) NOT NULL,
    PaymentDate DATETIME DEFAULT CURRENT_TIMESTAMP,
    MethodID INT NOT NULL,
    TransactionCode VARCHAR(255) UNIQUE,
    IsSuccessful TINYINT(1) DEFAULT 0,  -- 0 = Failed, 1 = Success (MySQL dùng TINYINT cho BOOLEAN)
    UpdatedDate DATETIME NULL,
    Currency VARCHAR(10) DEFAULT 'VND',
    ZpTransId VARCHAR(100) NULL,
    ReturnCode INT NULL,
    ResponseMessage VARCHAR(255) NULL,
    FOREIGN KEY (OrderID) REFERENCES Orders(OrderID) ON DELETE CASCADE,
    FOREIGN KEY (MethodID) REFERENCES PaymentMethods(MethodID)
) ENGINE=InnoDB;

-- Index
CREATE INDEX IX_Payments_TransactionCode ON Payments(TransactionCode);

-- View
CREATE VIEW vw_PaymentDetails AS
SELECT 
    c.CustomerName,
    o.OrderID,
    o.OrderDate,
    o.TotalAmount,
    o.Status AS OrderStatus,
    p.PaymentID,
    p.Amount AS PaymentAmount,
    p.PaymentDate,
    pm.MethodName,
    p.TransactionCode,
    p.UpdatedDate
FROM Customers c
JOIN Orders o ON c.CustomerID = o.CustomerID
LEFT JOIN Payments p ON o.OrderID = p.OrderID
LEFT JOIN PaymentMethods pm ON p.MethodID = pm.MethodID;

-- Trigger:
DELIMITER $$

-- Insert sample data
INSERT INTO Customers (CustomerName, Email, Phone) VALUES ('Test Customer', 'test@example.com', '0123456789');
INSERT INTO PaymentMethods (MethodName) VALUES ('ZaloPay'), ('Credit Card'), ('Bank Transfer');

CREATE TRIGGER trg_UpdateOrderStatus
AFTER INSERT ON Payments
FOR EACH ROW
BEGIN
    -- Chỉ cập nhật nếu thanh toán thành công? (Tùy yêu cầu — hiện tại bạn không kiểm tra IsSuccessful)
    -- Nếu muốn: AND NEW.IsSuccessful = 1
    UPDATE Orders
    SET Status = 'Paid'
    WHERE OrderID = NEW.OrderID;
END $$

DELIMITER ;

-- Stored Procedures

DELIMITER $$

CREATE PROCEDURE sp_CreatePayment(
    IN p_OrderID INT,
    IN p_Amount DECIMAL(18,2),
    IN p_MethodID INT,
    IN p_TransactionCode VARCHAR(255)
)
BEGIN
    INSERT INTO Payments (OrderID, Amount, MethodID, TransactionCode)
    VALUES (p_OrderID, p_Amount, p_MethodID, p_TransactionCode);
END $$

CREATE PROCEDURE sp_UpdatePaymentStatus(
    IN p_PaymentID INT,
    IN p_NewTransactionCode VARCHAR(255)
)
BEGIN
    UPDATE Payments
    SET 
        TransactionCode = p_NewTransactionCode,
        UpdatedDate = NOW()
    WHERE PaymentID = p_PaymentID;
END $$

DELIMITER ;
