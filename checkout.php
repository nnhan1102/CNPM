<?php
session_start();

// Get order data from URL parameters
$items = isset($_GET['items']) ? json_decode(urldecode($_GET['items']), true) : [];
$total = isset($_GET['total']) ? intval($_GET['total']) : 0;
$description = isset($_GET['description']) ? urldecode($_GET['description']) : '';

// If no items, redirect back to cart
if (empty($items)) {
    header('Location: cart.html');
    exit;
}

// Store order data in session for payment processing
$_SESSION['current_order'] = [
    'items' => $items,
    'total' => $total,
    'description' => $description
];
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thanh toán - Coffee House</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="index.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .checkout-container {
            background: rgba(255, 255, 255, 0.95);
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            margin: 100px auto;
            max-width: 1000px;
        }
        
        .order-summary {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .payment-form {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body>
    <!-- Header -->
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top">
        <div class="container">
            <a class="navbar-brand" href="index.html">
                <img src="https://cdn-icons-png.flaticon.com/512/924/924514.png" alt="Coffee House" width="40">
                Coffee House
            </a>
        </div>
    </nav>

    <div class="container">
        <div class="checkout-container">
            <h1 class="text-center mb-4"><i class="fas fa-credit-card"></i> Thanh toán đơn hàng</h1>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="order-summary">
                        <h3><i class="fas fa-receipt"></i> Đơn hàng của bạn</h3>
                        
                        <div class="order-items">
                            <?php foreach ($items as $item): ?>
                            <div class="order-item">
                                <div class="item-info">
                                    <h6><?php echo htmlspecialchars($item['name']); ?></h6>
                                    <small>Số lượng: <?php echo $item['quantity']; ?></small>
                                </div>
                                <div class="item-price">
                                    <?php echo number_format($item['price'] * $item['quantity']); ?> VND
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="order-total mt-3">
                            <div class="total-line">
                                <span>Tạm tính:</span>
                                <span><?php echo number_format($total - 15000); ?> VND</span>
                            </div>
                            <div class="total-line">
                                <span>Phí vận chuyển:</span>
                                <span>15,000 VND</span>
                            </div>
                            <div class="total-line final">
                                <span><strong>Tổng cộng:</strong></span>
                                <span><strong><?php echo number_format($total); ?> VND</strong></span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="payment-form">
                        <h3><i class="fas fa-lock"></i> Thông tin thanh toán</h3>
                        
                        <form action="payment.php" method="post">
                            <input type="hidden" name="amount" value="<?php echo $total; ?>">
                            <input type="hidden" name="description" value="<?php echo htmlspecialchars($description); ?>">
                            
                            <div class="form-group">
                                <label for="customer_name">Họ tên:</label>
                                <input type="text" class="form-control" id="customer_name" name="customer_name" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="customer_phone">Số điện thoại:</label>
                                <input type="tel" class="form-control" id="customer_phone" name="customer_phone" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="customer_address">Địa chỉ giao hàng:</label>
                                <textarea class="form-control" id="customer_address" name="customer_address" rows="3" required></textarea>
                            </div>
                            
                            <div class="payment-methods">
                                <h6>Chọn phương thức thanh toán:</h6>
                                <div class="method-option active" data-method="zalopay">
                                    <img src="https://cdn.haitrieu.com/wp-content/uploads/2022/10/Logo-ZaloPay.png" alt="ZaloPay" width="60">
                                    <span>ZaloPay</span>
                                    <i class="fas fa-check"></i>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary btn-block mt-4">
                                <i class="fas fa-lock"></i> Thanh toán ngay - <?php echo number_format($total); ?> VND
                            </button>
                        </form>
                        
                        <div class="text-center mt-3">
                            <a href="cart.html" class="text-muted">
                                <i class="fas fa-arrow-left"></i> Quay lại giỏ hàng
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Method selection
        document.querySelectorAll('.method-option').forEach((option) => {
            option.addEventListener('click', function () {
                document.querySelectorAll('.method-option').forEach((opt) => {
                    opt.classList.remove('active');
                });
                this.classList.add('active');
            });
        });
    </script>
</body>
</html>