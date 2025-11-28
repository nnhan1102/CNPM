<?php
require_once 'db.php';

$result = [];

try {
  $key2 = "trMrHtvjo6myautxDUiAcYsVtaeQ8nhf";
  $postdata = file_get_contents('php://input');
  $postdatajson = json_decode($postdata, true);
  $mac = hash_hmac("sha256", $postdatajson["data"], $key2);

  $requestmac = $postdatajson["mac"];

  // kiểm tra callback hợp lệ (đến từ ZaloPay server)
  if (strcmp($mac, $requestmac) != 0) {
    // callback không hợp lệ
    $result["return_code"] = -1;
    $result["return_message"] = "mac not equal";
  } else {
    // thanh toán thành công
    // merchant cập nhật trạng thái cho đơn hàng
    $datajson = json_decode($postdatajson["data"], true);

    try {
      $db = Database::getConnection();
      // Update Payments table
      $stmt = $db->prepare("UPDATE Payments SET IsSuccessful=1, UpdatedDate=NOW(), ZpTransId=?, ReturnCode=?, ResponseMessage=? WHERE TransactionCode=?");
      $stmt->execute([$datajson["zp_trans_id"], 1, "success", $datajson["app_trans_id"]]);

      // Optionally update Orders status
      $stmt2 = $db->query("UPDATE Orders o JOIN Payments p ON o.OrderID = p.OrderID SET o.Status='Paid' WHERE p.TransactionCode='" . $datajson["app_trans_id"] . "'");

    } catch (PDOException $e) {
      error_log("Database update error: " . $e->getMessage() . "\n", 3, __DIR__ . '/logFile.log');
    }

	error_log("Thanh toan thanh cong\n", 3, __DIR__ . '/logFile.log');

    $result["return_code"] = 1;
    $result["return_message"] = "success";
  }
} catch (Exception $e) {
  $result["return_code"] = 0; // ZaloPay server sẽ callback lại (tối đa 3 lần)
  $result["return_message"] = $e->getMessage();
}

// thông báo kết quả cho ZaloPay server
echo json_encode($result);
