<?php
// Validate the order_id from the URL. It should be an integer.
$order_id_raw = $_GET['order_id'] ?? null;
$order_id = filter_var($order_id_raw, FILTER_VALIDATE_INT);
if ($order_id === false) {
    $order_id = 'N/A'; // Default to 'N/A' if not a valid integer or not present
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmed - MTP Flex</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f6f9; color: #1f2937; margin: 0; line-height: 1.6; display: flex; justify-content: center; align-items: center; min-height: 100vh;}
        .success-container {
            background-color: white;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            text-align: center;
            max-width: 500px;
        }
        .success-container i {
            font-size: 5rem;
            color: #10b981;
            margin-bottom: 20px;
        }
        h2 {
            color: #10b981;
            margin-bottom: 10px;
            font-size: 2rem;
        }
        p {
            font-size: 1.1rem;
            margin-bottom: 25px;
        }
        .btn-home {
            padding: 10px 20px;
            background-color: #2563eb;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="success-container">
        <i class="fas fa-check-circle"></i>
        <h2>Order Placed Successfully!</h2>
        <p>Thank you for your purchase from MTP Flex. Your order number is **#<?= htmlspecialchars($order_id) ?>**.</p>
        <p>You will receive a confirmation email shortly.</p>
        <a href="index.php" class="btn-home"><i class="fas fa-home"></i> Return to Homepage</a>
    </div>
</body>
</html>