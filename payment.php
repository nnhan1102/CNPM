<?php
session_start();
require_once 'db.php';

$config = [
    "appid" => 2554,
    "key1" => "sdngKKJmqEMzvh5QQcdD2A9XBSKUNaYn",
    "key2" => "trMrHtvjo6myautxDUiAcYsVtaeQ8nhf",
    "endpoint" => "https://sb-openapi.zalopay.vn/v2/create"
];

$embeddata = json_encode(["redirecturl" => "https://noninert-muscular-tinley.ngrok-free.dev/CNPM/redirect.php"]);

$items = '[]';
$transID = rand(0,1000000);

try {
    $db = Database::getConnection();

    // Get customer info from form
    $customer_name = $_POST['customer_name'] ?? 'Khách hàng';
    $customer_phone = $_POST['customer_phone'] ?? '';
    $customer_address = $_POST['customer_address'] ?? '';
    
    // Get order data from session
    $order_data = $_SESSION['current_order'] ?? [];
    $amount = $_POST['amount'] ?? 50000;
    $description = $_POST['description'] ?? "Đơn hàng Coffee House #$transID";

    // Insert into Customers table if not exists
    $stmt_customer = $db->prepare("INSERT IGNORE INTO Customers (CustomerName, Email, Phone) VALUES (?, ?, ?)");
    $stmt_customer->execute([$customer_name, 'customer@example.com', $customer_phone]);
    
    // Get customer ID
    $customer_id = 1; // Default for demo, in real app you'd get the actual ID

    // Insert into Orders
    $stmt = $db->prepare("INSERT INTO Orders (CustomerID, TotalAmount, OrderDescription, Status) VALUES (?, ?, ?, 'Pending')");
    $stmt->execute([$customer_id, $amount, $description . " - Địa chỉ: " . $customer_address]);
    $orderId = $db->lastInsertId();
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

$order = [
    "app_id" => $config["appid"],
    "app_time" => round(microtime(true) * 1000),
    "app_trans_id" => date("ymd") . "_" . $transID,
    "app_user" => "user123",
    "item" => $items,
    "embed_data" => $embeddata,
    "amount" => $amount,
    "description" => $description,
    "bank_code" => "",
    "callback_url" => "https://noninert-muscular-tinley.ngrok-free.dev/CNPM/callback.php",
];

try {
    // Insert into Payments
    $stmt2 = $db->prepare("CALL sp_CreatePayment(?, ?, 1, ?)");
    $stmt2->execute([$orderId, $order["amount"], $order["app_trans_id"]]);
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// Clear cart after successful order creation
unset($_SESSION['current_order']);

// Generate MAC for ZaloPay
$data = $order["app_id"] . "|" . $order["app_trans_id"] . "|" . $order["app_user"] . "|" . $order["amount"]
    . "|" . $order["app_time"] . "|" . $order["embed_data"] . "|" . $order["item"];
$order["mac"] = hash_hmac("sha256", $data, $config["key1"]);

$context = stream_context_create([
    "http" => [
        "header" => "Content-type: application/x-www-form-urlencoded\r\n",
        "method" => "POST",
        "content" => http_build_query($order)
    ]
]);

$resp = file_get_contents($config["endpoint"], false, $context);
$result = json_decode($resp, true);

if(isset($result["return_code"]) && $result["return_code"] == 1){
    header("Location:".$result["order_url"]);
    exit;
}

// If payment creation fails, show error
echo "<h2>Lỗi khi tạo đơn hàng</h2>";
foreach ($result as $key => $value) {
    echo "$key: $value<br>";
}
echo '<a href="cart.html">Quay lại giỏ hàng</a>';
