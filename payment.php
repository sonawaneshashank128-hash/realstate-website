<?php
require_once __DIR__ . '/config/config.php';
require_login();
require_once __DIR__ . '/config/payment.php';

$pageTitle = 'Upgrade Plan';
$user      = current_user();
$userId    = (int)$user['id'];

$planAmounts = [
    'basic'    => 0,
    'premium'  => 99900,
    'featured' => 299900,
];
$planLabels = [
    'basic'    => 'Basic',
    'premium'  => 'Premium',
    'featured' => 'Featured',
];
$planPriceDisplay = [
    'basic'    => 'Free',
    'premium'  => '₹999/month',
    'featured' => '₹2,999/month',
];

$plan = $_GET['plan'] ?? 'basic';
if (!array_key_exists($plan, $planAmounts)) {
    flash('error', 'Invalid plan selected.');
    redirect('pricing.php');
}

// Basic plan — upgrade immediately at no cost
if ($plan === 'basic') {
    $stmt = db()->prepare('UPDATE users SET plan=? WHERE id=?');
    $stmt->execute(['basic', $userId]);
    $_SESSION['user']['plan'] = 'basic';
    flash('success', 'You are now on the Basic plan.');
    redirect('dashboard.php');
}

// Paid plan — create Razorpay order
$amountPaise = $planAmounts[$plan];
$receiptId   = 'plan_' . $userId . '_' . time();
$order       = create_razorpay_order($amountPaise, $receiptId, ['plan' => $plan]);

if ($order === false) {
    flash('error', 'Could not initiate payment. Please try again.');
    redirect('pricing.php');
}

// Store order in session for verification on callback
$_SESSION['plan_order'] = [
    'order_id' => $order['id'],
    'plan'     => $plan,
    'amount'   => $amountPaise,
];

// Record payment in database
$ins = db()->prepare(
    'INSERT INTO payments (user_id, plan, amount, currency, razorpay_order_id, status) VALUES (?,?,?,?,?,?)'
);
$ins->execute([
    $userId,
    $plan,
    $amountPaise / 100,
    'INR',
    $order['id'],
    'created',
]);

require __DIR__ . '/includes/header.php';
?>
<style>
.pay-wrap {
    min-height: 70vh;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--paper);
    padding: 48px 20px;
}
.pay-card {
    background: var(--white);
    border-radius: 20px;
    box-shadow: var(--shadow);
    padding: 44px 40px;
    max-width: 480px;
    width: 100%;
    text-align: center;
}
@media (max-width: 540px) { .pay-card { padding: 28px 20px; } }

.pay-plan-icon { font-size: 3rem; margin-bottom: 12px; }
.pay-plan-name { font-size: 1.5rem; font-weight: 800; color: var(--ink); margin: 0 0 4px; }
.pay-plan-price { font-size: 2.8rem; font-weight: 900; color: var(--green); margin: 10px 0 4px; }
.pay-plan-price small { font-size: 1rem; font-weight: 600; color: var(--muted); }
.pay-plan-desc { color: var(--muted); font-size: .9rem; margin: 0 0 28px; }

.pay-divider { border: none; border-top: 1px solid var(--line); margin: 24px 0; }

.pay-features { list-style: none; padding: 0; margin: 0 0 28px; text-align: left; display: flex; flex-direction: column; gap: 10px; }
.pay-features li { font-size: .88rem; color: var(--ink); display: flex; align-items: center; gap: 8px; }
.pay-features li::before { content: '✓'; color: var(--green); font-weight: 800; }

.pay-note { font-size: .78rem; color: var(--muted); margin-top: 16px; }
.pay-secure-icons { display: flex; align-items: center; justify-content: center; gap: 8px; font-size: .75rem; color: var(--muted); margin-top: 14px; }
</style>

<?php
$planFeatures = [
    'premium' => ['5 property listings', 'Priority placement', 'WhatsApp support', 'Analytics dashboard'],
    'featured'=> ['Unlimited listings', '⭐ Featured badge', 'Homepage spotlight', 'Dedicated support'],
];
?>

<div class="pay-wrap">
    <div class="pay-card">
        <div class="pay-plan-icon"><?= $plan === 'featured' ? '🌟' : '💎' ?></div>
        <div class="pay-plan-name"><?= e($planLabels[$plan]) ?> Plan</div>
        <div class="pay-plan-price">
            ₹<?= number_format($amountPaise / 100) ?><small>/month</small>
        </div>
        <p class="pay-plan-desc">Billed monthly. Cancel anytime.</p>

        <hr class="pay-divider">

        <ul class="pay-features">
            <?php foreach ($planFeatures[$plan] as $feat): ?>
            <li><?= e($feat) ?></li>
            <?php endforeach; ?>
        </ul>

        <!-- Hidden form POSTed to payment-success.php after Razorpay callback -->
        <form id="paySuccessForm" method="post" action="<?= e(url('payment-success.php')) ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="razorpay_order_id"   id="rzp_order_id"   value="<?= e($order['id']) ?>">
            <input type="hidden" name="razorpay_payment_id" id="rzp_payment_id" value="">
            <input type="hidden" name="razorpay_signature"  id="rzp_signature"  value="">
        </form>

        <button class="btn btn-full" id="rzp-pay-btn" type="button">
            💳 Pay <?= e($planPriceDisplay[$plan]) ?> Securely
        </button>

        <p class="pay-note">By proceeding you agree to our Terms of Use. Payments are processed via Razorpay.</p>
        <div class="pay-secure-icons">🔒 256-bit SSL &nbsp;|&nbsp; Razorpay Secured &nbsp;|&nbsp; PCI DSS</div>

        <div style="margin-top: 20px;">
            <a href="<?= e(url('pricing.php')) ?>" class="btn btn-outline btn-small">← Back to plans</a>
        </div>
    </div>
</div>

<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>
(function () {
    var btn = document.getElementById('rzp-pay-btn');
    var options = {
        key:         <?= json_encode(RAZORPAY_KEY_ID) ?>,
        amount:      <?= (int)$amountPaise ?>,
        currency:    'INR',
        name:        'EstateHub',
        description: <?= json_encode($planLabels[$plan] . ' Plan Subscription') ?>,
        order_id:    <?= json_encode($order['id']) ?>,
        prefill: {
            name:  <?= json_encode($user['name']) ?>,
            email: <?= json_encode($user['email']) ?>,
            contact: <?= json_encode($user['phone'] ?? '') ?>,
        },
        theme: { color: '#2d6a4f' },
        handler: function (response) {
            document.getElementById('rzp_payment_id').value = response.razorpay_payment_id;
            document.getElementById('rzp_signature').value  = response.razorpay_signature;
            document.getElementById('rzp_order_id').value   = response.razorpay_order_id;
            document.getElementById('paySuccessForm').submit();
        },
        modal: {
            ondismiss: function () {
                btn.disabled = false;
                btn.textContent = '💳 Pay <?= e($planPriceDisplay[$plan]) ?> Securely';
            }
        }
    };

    btn.addEventListener('click', function () {
        btn.disabled = true;
        btn.textContent = 'Opening payment window…';
        var rzp = new Razorpay(options);
        rzp.on('payment.failed', function (resp) {
            alert('Payment failed: ' + (resp.error.description || 'Unknown error'));
            btn.disabled = false;
            btn.textContent = '💳 Pay <?= e($planPriceDisplay[$plan]) ?> Securely';
        });
        rzp.open();
    });
}());
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
