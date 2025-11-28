<?php
require_once 'db.php';

$app_trans_id = $_GET['apptransid'] ?? '';
$zp_trans_id = $_GET['zptransid'] ?? '';

$status = 'Unknown';
$message = '';
$order_details = [];

if ($app_trans_id) {
    try {
        $db = Database::getConnection();

        // Get payment and order details
        $stmt = $db->prepare("
            SELECT p.*,
                   o.TotalAmount, o.OrderDescription, o.Status AS OrderStatus, o.OrderDate,
                   c.CustomerName, c.Email, c.Phone
            FROM Payments p
            JOIN Orders o ON p.OrderID = o.OrderID
            JOIN Customers c ON o.CustomerID = c.CustomerID
            WHERE p.TransactionCode = ?
        ");
        $stmt->execute([$app_trans_id]);
        $payment = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($payment) {
            $order_details = $payment;
            if ($payment['IsSuccessful'] == 1) {
                $status = 'Thành công';
                $message = 'Thanh toán đã được xử lý thành công. Đơn hàng sẽ được giao trong thời gian sớm nhất.';
                
                // Clear cart on successful payment
                echo '<script>localStorage.removeItem("cart");</script>';
            } elseif ($payment['ReturnCode'] == 2) {
                $status = 'Thất bại';
                $message = 'Thanh toán thất bại. Vui lòng thử lại hoặc chọn phương thức thanh toán khác.';
            } else {
                $status = 'Đang chờ';
                $message = 'Thanh toán đang được xử lý. Vui lòng kiểm tra lại sau.';
            }
        } else {
            $status = 'Không tìm thấy';
            $message = 'Không tìm thấy đơn hàng này.';
        }
    } catch (PDOException $e) {
        $status = 'Lỗi';
        $message = 'Có lỗi xảy ra khi kiểm tra đơn hàng.';
        error_log("Redirect error: " . $e->getMessage());
    }
} else {
    $status = 'Thiếu thông tin';
    $message = 'Thiếu thông tin đơn hàng.';
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kết quả thanh toán - Coffee House</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="index.css">
</head>
<body>
    <div class="container">
        <div class="checkout-container" style="margin: 100px auto;">
            <div class="text-center mb-4">
                <img src="https://cdn-icons-png.flaticon.com/512/924/924514.png" alt="Coffee House" width="80" class="mb-3">
                <h1>Kết quả thanh toán</h1>
            </div>
            
            <div class="alert <?php
                if ($status == 'Thành công') echo 'alert-success';
                elseif ($status == 'Thất bại') echo 'alert-danger';
                else echo 'alert-warning';
            ?> text-center">
                <h4>
                    <i class="fas <?php
                        if ($status == 'Thành công') echo 'fa-check-circle';
                        elseif ($status == 'Thất bại') echo 'fa-times-circle';
                        else echo 'fa-clock';
                    ?>"></i>
                    <?php echo $status; ?>
                </h4>
                <p class="mb-0"><?php echo $message; ?></p>
            </div>

            <?php if ($order_details): ?>
            <div class="order-summary">
                <h3 class="text-center"><i class="fas fa-receipt"></i> Chi tiết đơn hàng</h3>
                <div class="row">
                    <div class="col-md-6">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item">
                                <strong>Mã giao dịch:</strong><br>
                                <?php echo htmlspecialchars($order_details['TransactionCode']); ?>
                            </li>
                            <li class="list-group-item">
                                <strong>Zp Trans ID:</strong><br>
                                <?php echo htmlspecialchars($zp_trans_id ?: 'N/A'); ?>
                            </li>
                            <li class="list-group-item">
                                <strong>Số tiền:</strong><br>
                                <?php echo number_format($order_details['Amount']); ?> VND
                            </li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item">
                                <strong>Mô tả:</strong><br>
                                <?php echo htmlspecialchars($order_details['OrderDescription']); ?>
                            </li>
                            <li class="list-group-item">
                                <strong>Ngày đặt hàng:</strong><br>
                                <?php echo $order_details['OrderDate']; ?>
                            </li>
                            <li class="list-group-item">
                                <strong>Trạng thái đơn hàng:</strong><br>
                                <?php echo htmlspecialchars($order_details['OrderStatus']); ?>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <div class="text-center mt-4">
                <a href="index.html" class="btn btn-primary btn-lg">
                    <i class="fas fa-home"></i> Về trang chủ
                </a>
                <a href="cart.html" class="btn btn-outline-primary btn-lg ml-2">
                    <i class="fas fa-shopping-cart"></i> Mua thêm
                </a>
            </div>
        </div>
    </div>
</body>
</html>