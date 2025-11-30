<?php
/**
 * TEST CALLBACK THẬT - Gửi request trực tiếp đến callback.php
 * Cách sử dụng: php test_callback_real.php
 */

class RealCallbackTest {
    private $key2 = "trMrHtvjo6myautxDUiAcYsVtaeQ8nhf";
    private $callbackUrl = "http://localhost/Test_modulePayment-main/callback.php";
    
    public function runRealTests() {
        echo "=== TEST CALLBACK THẬT ===\n\n";
        
        // Test 1: Gửi callback hợp lệ
        $this->testValidCallback();
        
        // Test 2: Gửi callback với MAC sai
        $this->testInvalidMacCallback();
        
        // Test 3: Gửi callback với transaction có thật trong database
        $this->testWithRealTransaction();
    }
    
    private function testValidCallback() {
        echo "🔹 Test 1: Callback hợp lệ\n";
        
        $testData = [
            "app_trans_id" => "TEST_REAL_" . date("ymd_His"),
            "zp_trans_id" => "ZP_REAL_" . time(),
            "amount" => 125000,
            "description" => "Test callback thật"
        ];
        
        $postData = [
            "data" => json_encode($testData),
            "mac" => hash_hmac("sha256", json_encode($testData), $this->key2)
        ];
        
        $response = $this->sendHttpRequest($postData);
        
        echo "Request: " . json_encode($postData, JSON_PRETTY_PRINT) . "\n";
        echo "Response: " . json_encode($response, JSON_PRETTY_PRINT) . "\n";
        
        if ($response && $response['return_code'] == 1) {
            echo "THÀNH CÔNG: Callback được xử lý\n";
        } else {
            echo "THẤT BẠI: " . ($response['return_message'] ?? 'No response') . "\n";
        }
        echo "----------------------------------------\n\n";
    }
    
    private function testInvalidMacCallback() {
        echo "🔹 Test 2: Callback với MAC sai\n";
        
        $testData = [
            "app_trans_id" => "TEST_INVALID_MAC",
            "zp_trans_id" => "ZP_INVALID",
            "amount" => 50000
        ];
        
        $postData = [
            "data" => json_encode($testData),
            "mac" => "mac_khong_hop_le_1234567890"
        ];
        
        $response = $this->sendHttpRequest($postData);
        
        echo "Request: " . json_encode($postData, JSON_PRETTY_PRINT) . "\n";
        echo "Response: " . json_encode($response, JSON_PRETTY_PRINT) . "\n";
        
        if ($response && $response['return_code'] == -1) {
            echo "THÀNH CÔNG: MAC sai bị từ chối\n";
        } else {
            echo "THẤT BẠI: MAC sai không bị từ chối\n";
        }
        echo "----------------------------------------\n\n";
    }
    
    private function testWithRealTransaction() {
        echo "🔹 Test 3: Callback với transaction thật từ database\n";
        
        try {
            require_once 'db.php';
            $db = Database::getConnection();
            
            // Lấy một transaction chưa thanh toán từ database
            $stmt = $db->query("SELECT TransactionCode FROM Payments WHERE IsSuccessful = 0 LIMIT 1");
            $transaction = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($transaction) {
                $transactionCode = $transaction['TransactionCode'];
                echo "📋 Tìm thấy transaction: $transactionCode\n";
                
                $testData = [
                    "app_trans_id" => $transactionCode,
                    "zp_trans_id" => "ZP_REAL_DB_" . time(),
                    "amount" => 125000
                ];
                
                $postData = [
                    "data" => json_encode($testData),
                    "mac" => hash_hmac("sha256", json_encode($testData), $this->key2)
                ];
                
                $response = $this->sendHttpRequest($postData);
                
                echo "Request: " . json_encode($postData, JSON_PRETTY_PRINT) . "\n";
                echo "Response: " . json_encode($response, JSON_PRETTY_PRINT) . "\n";
                
                // Kiểm tra database sau callback
                $checkStmt = $db->prepare("SELECT IsSuccessful, ZpTransId FROM Payments WHERE TransactionCode = ?");
                $checkStmt->execute([$transactionCode]);
                $updatedPayment = $checkStmt->fetch(PDO::FETCH_ASSOC);
                
                if ($updatedPayment && $updatedPayment['IsSuccessful'] == 1) {
                    echo "THÀNH CÔNG: Database được cập nhật\n";
                } else {
                    echo "THẤT BẠI: Database không được cập nhật\n";
                }
            } else {
                echo "KHÔNG CÓ TRANSACTION nào chưa thanh toán để test\n";
                
                // Tạo transaction test
                $this->createTestTransactionForCallback();
            }
            
        } catch (Exception $e) {
            echo "LỖI DATABASE: " . $e->getMessage() . "\n";
        }
        echo "----------------------------------------\n\n";
    }
    
    private function createTestTransactionForCallback() {
        echo "🔹 Tạo transaction test...\n";
        
        try {
            $db = Database::getConnection();
            
            // Tạo order test
            $stmt = $db->prepare("INSERT INTO Orders (CustomerID, TotalAmount, OrderDescription, Status) VALUES (1, 125000, 'Order for callback test', 'Pending')");
            $stmt->execute();
            $orderId = $db->lastInsertId();
            
            // Tạo payment test
            $transactionCode = "TEST_CALLBACK_" . date("ymd_His");
            $stmt2 = $db->prepare("INSERT INTO Payments (OrderID, Amount, MethodID, TransactionCode, IsSuccessful, Currency) VALUES (?, 125000, 1, ?, 0, 'VND')");
            $stmt2->execute([$orderId, $transactionCode]);
            
            echo "Đã tạo transaction test: $transactionCode\n";
            
            return $transactionCode;
            
        } catch (Exception $e) {
            echo "Lỗi tạo transaction test: " . $e->getMessage() . "\n";
            return null;
        }
    }
    
    private function sendHttpRequest($postData) {
        $ch = curl_init();
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->callbackUrl,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($postData),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'User-Agent: Callback-Tester/1.0'
            ],
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => false
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        curl_close($ch);
        
        if ($error) {
            echo "CURL Error: $error\n";
            return null;
        }
        
        if ($httpCode !== 200) {
            echo "HTTP Code: $httpCode\n";
        }
        
        return json_decode($response, true);
    }
}

// Chạy test
if (php_sapi_name() === 'cli') {
    $test = new RealCallbackTest();
    $test->runRealTests();
} else {
    echo "Vui lòng chạy từ command line: php test_callback_real.php\n";
}