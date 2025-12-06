<?php
require_once 'db.php';

$config = [
  "app_id" => 2554,
  "key1" => "sdngKKJmqEMzvh5QQcdD2A9XBSKUNaYn",
  "key2" => "trMrHtvjo6myautxDUiAcYsVtaeQ8nhf",
  "endpoint" => "https://sb-openapi.zalopay.vn/v2/query"
];

if(isset($_GET["app_trans_id"]))
{
	$app_trans_id = $_GET["app_trans_id"];  // Input your app_trans_id

	try {
		$db = Database::getConnection();
		$stmt = $db->prepare("SELECT * FROM Payments WHERE TransactionCode = ?");
        $stmt->execute([$app_trans_id]);
        $payment = $stmt->fetch(PDO::FETCH_ASSOC);
		if ($payment) {
			echo "<h3>Thông tin từ database:</h3>";
			foreach ($payment as $key => $value) {
				echo "$key: $value<br>";
			}
			echo "<hr>";
		} else {
			echo "Không tìm thấy thanh toán trong database<br><hr>";
		}
	} catch (PDOException $e) {
		echo "Database error: " . $e->getMessage() . "<br><hr>";
	}
}

$data = $config["app_id"]."|".$app_trans_id."|".$config["key1"]; // app_id|app_trans_id|key1
$params = [
  "app_id" => $config["app_id"],
  "app_trans_id" => $app_trans_id,
  "mac" => hash_hmac("sha256", $data, $config["key1"])
];

$context = stream_context_create([
    "http" => [
        "header" => "Content-type: application/x-www-form-urlencoded\r\n",
        "method" => "POST",
        "content" => http_build_query($params)
    ]
]);

$resp = file_get_contents($config["endpoint"], false, $context);
$result = json_decode($resp, true);

echo "<h3>Query từ ZaloPay API:</h3>";
foreach ($result as $key => $value) {
  echo "$key: $value<br>";
  
}
