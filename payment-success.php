<?php
require_once __DIR__ . '/config/config.php';
require_login();
require_once __DIR__ . '/config/payment.php';

// GET requests go back to pricing
if (!is_post()) {
    redirect('pricing.php');
}

verify_csrf();

$userId = (int)current_user()['id'];

$orderId   = trim($_POST['razorpay_order_id']   ?? '');
$paymentId = trim($_POST['razorpay_payment_id'] ?? '');
$signature = trim($_POST['razorpay_signature']  ?? '');

// Validate session order
$sessionOrder = $_SESSION['plan_order'] ?? null;
if (!$sessionOrder || ($sessionOrder['order_id'] ?? '') !== $orderId) {
    flash('error', 'Session mismatch. Please try again.');
    redirect('pricing.php');
}

$plan = $sessionOrder['plan'];

// Verify signature
if (verify_razorpay_payment($orderId, $paymentId, $signature)) {
    // Mark payment as paid
    $upd = db()->prepare(
        'UPDATE payments SET razorpay_payment_id=?, razorpay_signature=?, status=? WHERE razorpay_order_id=?'
    );
    $upd->execute([$paymentId, $signature, 'paid', $orderId]);

    // Upgrade user plan
    $upgStmt = db()->prepare('UPDATE users SET plan=? WHERE id=?');
    $upgStmt->execute([$plan, $userId]);

    // Refresh session
    $_SESSION['user']['plan'] = $plan;

    unset($_SESSION['plan_order']);
    flash('success', '🎉 Plan upgraded to ' . ucfirst($plan) . '! Enjoy your new features.');
    redirect('dashboard.php');
} else {
    // Mark payment as failed
    $fail = db()->prepare('UPDATE payments SET status=? WHERE razorpay_order_id=?');
    $fail->execute(['failed', $orderId]);

    flash('error', 'Payment verification failed. Please contact support if you were charged.');
    redirect('payment-failed.php');
}
