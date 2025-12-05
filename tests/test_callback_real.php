<?php
/**
 * TEST CALLBACK ĐÃ SỬA - Tạo transaction trước khi test
 */

class FixedCallbackTest {
    private $key2 = "trMrHtvjo6myautxDUiAcYsVtaeQ8nhf";
    private $callbackUrl = "http://localhost/CNPM/callback.php";
    private $db;
    
    public function __construct() {
        $this->connectDatabase();
    }
    
    private function connectDatabase() {
        try {
            $this->db = new PDO(
                'mysql:host=localhost;dbname=paymentdb;charset=utf8mb4',
                'root',
                '050705',
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
        } catch (PDOException $e) {
            die("❌ Lỗi database: " . $e->getMessage() . "\n");
        }
    }
    
    public function runFixedTests() {
        echo "=== TEST CALLBACK ĐÃ SỬA ===\n\n";
        
        // Test 1: TẠO transaction trước, rồi gửi callback
        echo "🔹 Test 1: Callback hợp lệ (có tạo transaction trước)\n";
        $testTransaction = $this->createTestTransaction();
        
        if ($testTransaction) {
            $testData = [
                "app_trans_id" => $testTransaction['code'],
                "zp_trans_id" => "ZP_FIXED_" . time(),
                "amount" => $testTransaction['amount'],
                "description" => "Test callback với transaction có sẵn"
            ];
            
            $postData = [
                "data" => json_encode($testData),
                "mac" => hash_hmac("sha256", json_encode($testData), $this->key2)
            ];
            
            echo "✓ Đã tạo transaction: {$testTransaction['code']}\n";
            echo "✓ Amount: " . number_format($testTransaction['amount']) . " VND\n";
            
            $response = $this->sendHttpRequest($postData);
            
            echo "Response: " . json_encode($response, JSON_PRETTY_PRINT) . "\n";
            
            if ($response && $response['return_code'] == 1) {
                echo "✅ Callback thành công!\n";
                
                // Kiểm tra database
                $this->verifyDatabase($testTransaction['code']);
            }
        }
        
        echo "\n" . str_repeat("-", 50) . "\n\n";
        
        // Test 2: Vẫn test với transaction có sẵn
        echo "🔹 Test 2: Callback với transaction có thật từ database\n";
        $this->testWithExistingTransaction();
    }
    
    private function createTestTransaction() {
        try {
            // Tạo customer test nếu chưa có
            $stmt = $this->db->prepare("
                INSERT INTO Customers (CustomerName, Email, Phone) 
                VALUES ('Test Customer', 'test@callback.com', '0900000000')
                ON DUPLICATE KEY UPDATE CustomerID=LAST_INSERT_ID(CustomerID)
            ");
            $stmt->execute();
            $customerId = $this->db->lastInsertId();
            
            // Tạo order
            $amount = 125000;
            $stmt = $this->db->prepare("
                INSERT INTO Orders (CustomerID, TotalAmount, OrderDescription, Status) 
                VALUES (?, ?, 'Order for callback test', 'Pending')
            ");
            $stmt->execute([$customerId, $amount]);
            $orderId = $this->db->lastInsertId();
            
            // Tạo payment
            $transactionCode = "TEST_FIXED_" . date("ymd_His");
            $stmt = $this->db->prepare("
                INSERT INTO Payments (OrderID, Amount, MethodID, TransactionCode, IsSuccessful, Currency) 
                VALUES (?, ?, 1, ?, 0, 'VND')
            ");
            $stmt->execute([$orderId, $amount, $transactionCode]);
            
            return ['code' => $transactionCode, 'amount' => $amount, 'orderId' => $orderId];
            
        } catch (Exception $e) {
            echo "❌ Lỗi tạo transaction: " . $e->getMessage() . "\n";
            return null;
        }
    }
    
    private function testWithExistingTransaction() {
        // Tìm transaction chưa thanh toán
        $stmt = $this->db->query("
            SELECT TransactionCode, Amount 
            FROM Payments 
            WHERE IsSuccessful = 0 
            AND TransactionCode NOT LIKE 'TEST_%'
            LIMIT 1
        ");
        $transaction = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($transaction) {
            echo "✓ Tìm thấy transaction: {$transaction['TransactionCode']}\n";
            
            $testData = [
                "app_trans_id" => $transaction['TransactionCode'],
                "zp_trans_id" => "ZP_EXIST_" . time(),
                "amount" => $transaction['Amount']
            ];
            
            $postData = [
                "data" => json_encode($testData),
                "mac" => hash_hmac("sha256", json_encode($testData), $this->key2)
            ];
            
            $response = $this->sendHttpRequest($postData);
            
            echo "Response: " . json_encode($response, JSON_PRETTY_PRINT) . "\n";
            
            if ($response && $response['return_code'] == 1) {
                echo "✅ Database được cập nhật!\n";
                $this->verifyDatabase($transaction['TransactionCode']);
            }
        } else {
            echo "⚠️ Không tìm thấy transaction pending nào\n";
        }
    }
    
    private function verifyDatabase($transactionCode) {
        $stmt = $this->db->prepare("
            SELECT 
                IsSuccessful,
                ZpTransId,
                ReturnCode,
                ResponseMessage,
                PaymentDate,
                UpdatedDate
            FROM Payments 
            WHERE TransactionCode = ?
        ");
        $stmt->execute([$transactionCode]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            echo "\n📊 KẾT QUẢ TRONG DATABASE:\n";
            echo "  IsSuccessful: " . $result['IsSuccessful'] . "\n";
            echo "  ZpTransId: " . ($result['ZpTransId'] ?? 'NULL') . "\n";
            echo "  UpdatedDate: " . ($result['UpdatedDate'] ?? 'NULL') . "\n";
            
            if ($result['IsSuccessful'] == 1 && !empty($result['ZpTransId'])) {
                echo "✅ TRANSACTION ĐÃ ĐƯỢC CẬP NHẬT THÀNH CÔNG!\n";
            }
        }
    }
    
    private function sendHttpRequest($postData) {
        $ch = curl_init($this->callbackUrl);
        
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($postData),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT => 10
        ]);
        
        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            echo "CURL Error: $error\n";
            return null;
        }
        
        return json_decode($response, true);
    }
}

// Chạy test
$test = new FixedCallbackTest();
$test->runFixedTests();