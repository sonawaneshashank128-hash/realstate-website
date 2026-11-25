<?php
require_once __DIR__ . '/config/config.php';
$pageTitle = 'Pricing Plans';

$user        = current_user();
$currentPlan = $user['plan'] ?? 'basic';

$plans = [
    'basic' => [
        'label'    => 'Basic',
        'price'    => 0,
        'paise'    => 0,
        'features' => [
            '1 property listing',
            'Standard visibility',
            'Email support',
            'Basic search placement',
        ],
    ],
    'premium' => [
        'label'    => 'Premium',
        'price'    => 999,
        'paise'    => 99900,
        'features' => [
            '5 property listings',
            'Priority placement',
            'WhatsApp support',
            'Analytics dashboard',
            'Listing highlights',
        ],
    ],
    'featured' => [
        'label'    => 'Featured',
        'price'    => 2999,
        'paise'    => 299900,
        'features' => [
            'Unlimited listings',
            '⭐ Featured badge on all listings',
            'Homepage spotlight',
            'Dedicated account manager',
            'Premium analytics',
        ],
    ],
];

require __DIR__ . '/includes/header.php';
?>
<style>
.pricing-hero {
    background: linear-gradient(135deg, var(--ink) 0%, #2d3a6b 100%);
    padding: 60px 0 48px;
    text-align: center;
    color: var(--white);
}
.pricing-hero .eyebrow { color: rgba(255,255,255,.65); }
.pricing-hero h1 { font-size: 2.4rem; margin: 10px 0; }
.pricing-hero p  { opacity: .8; max-width: 500px; margin: 0 auto; }

.pricing-section { padding: 56px 0 80px; background: var(--paper); }

.pricing-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 24px;
    align-items: start;
    margin-top: 40px;
}
@media (max-width: 900px) { .pricing-grid { grid-template-columns: 1fr; max-width: 420px; margin-left: auto; margin-right: auto; } }

.pricing-card {
    background: var(--white);
    border-radius: 20px;
    box-shadow: var(--shadow);
    padding: 36px 30px 32px;
    position: relative;
    border: 2px solid transparent;
    transition: transform .2s, box-shadow .2s;
}
.pricing-card:hover { transform: translateY(-4px); box-shadow: 0 12px 40px rgba(0,0,0,.12); }

.pricing-featured {
    border-color: var(--green);
    transform: scale(1.04);
}
.pricing-featured:hover { transform: scale(1.04) translateY(-4px); }

.popular-badge {
    position: absolute;
    top: -14px;
    left: 50%;
    transform: translateX(-50%);
    background: var(--green);
    color: white;
    font-size: .72rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .08em;
    padding: 4px 16px;
    border-radius: 20px;
    white-space: nowrap;
}

.pricing-plan-name {
    font-size: 1.1rem;
    font-weight: 800;
    color: var(--ink);
    margin: 0 0 6px;
}
.pricing-price {
    font-size: 2.6rem;
    font-weight: 900;
    color: var(--ink);
    line-height: 1;
    margin: 16px 0 4px;
}
.pricing-price span { font-size: 1rem; font-weight: 600; color: var(--muted); }
.pricing-price-free { color: var(--green); }

.pricing-divider { border: none; border-top: 1px solid var(--line); margin: 22px 0; }

.pricing-features { list-style: none; padding: 0; margin: 0 0 28px; display: flex; flex-direction: column; gap: 11px; }
.pricing-features li {
    font-size: .88rem;
    color: var(--ink);
    display: flex;
    align-items: flex-start;
    gap: 8px;
}
.pricing-features li::before { content: '✓'; color: var(--green); font-weight: 800; flex-shrink: 0; margin-top: 1px; }

.active-plan-badge {
    display: inline-block;
    background: var(--mint);
    color: var(--green);
    font-size: .73rem;
    font-weight: 700;
    padding: 3px 10px;
    border-radius: 20px;
    margin-bottom: 14px;
    letter-spacing: .04em;
}

/* FAQ */
.pricing-faq { margin-top: 64px; }
.pricing-faq h2 { text-align: center; font-size: 1.6rem; margin-bottom: 28px; }
.faq-list { max-width: 700px; margin: 0 auto; display: flex; flex-direction: column; gap: 12px; }
details.faq-item {
    background: var(--white);
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,.06);
    overflow: hidden;
}
details.faq-item summary {
    padding: 18px 22px;
    font-weight: 700;
    font-size: .95rem;
    cursor: pointer;
    list-style: none;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
}
details.faq-item summary::-webkit-details-marker { display: none; }
details.faq-item summary::after { content: '+'; font-size: 1.3rem; color: var(--green); flex-shrink: 0; }
details.faq-item[open] summary::after { content: '–'; }
details.faq-item .faq-body {
    padding: 0 22px 18px;
    font-size: .88rem;
    color: var(--muted);
    line-height: 1.7;
}
</style>

<!-- HERO -->
<section class="pricing-hero">
    <div class="container">
        <span class="eyebrow">Simple, transparent</span>
        <h1>💎 Pricing Plans</h1>
        <p>Choose the plan that fits your property-selling goals. Upgrade or downgrade anytime.</p>
    </div>
</section>

<!-- PLANS -->
<section class="pricing-section">
    <div class="container">

        <div class="pricing-grid">
            <?php foreach ($plans as $key => $plan): ?>
            <?php $isActive = ($currentPlan === $key); ?>
            <div class="pricing-card <?= $key === 'premium' ? 'pricing-featured' : '' ?>">
                <?php if ($key === 'premium'): ?>
                <div class="popular-badge">⭐ Most Popular</div>
                <?php endif; ?>

                <?php if ($isActive): ?>
                <div class="active-plan-badge">✓ Your current plan</div>
                <?php endif; ?>

                <div class="pricing-plan-name"><?= e($plan['label']) ?></div>

                <div class="pricing-price <?= $key === 'basic' ? 'pricing-price-free' : '' ?>">
                    <?php if ($plan['price'] === 0): ?>
                        Free
                    <?php else: ?>
                        ₹<?= number_format($plan['price']) ?><span>/month</span>
                    <?php endif; ?>
                </div>

                <hr class="pricing-divider">

                <ul class="pricing-features">
                    <?php foreach ($plan['features'] as $feature): ?>
                    <li><?= e($feature) ?></li>
                    <?php endforeach; ?>
                </ul>

                <?php if ($key === 'basic'): ?>
                    <?php if ($isActive): ?>
                        <a class="btn btn-outline btn-full" href="<?= e(url('dashboard.php')) ?>">Go to Dashboard</a>
                    <?php elseif ($user): ?>
                        <a class="btn btn-outline btn-full" href="<?= e(url('payment.php?plan=basic')) ?>">Start for free</a>
                    <?php else: ?>
                        <a class="btn btn-outline btn-full" href="<?= e(url('register.php')) ?>">Join free</a>
                    <?php endif; ?>
                <?php else: ?>
                    <?php if ($isActive): ?>
                        <a class="btn btn-full" href="<?= e(url('dashboard.php')) ?>">✓ Active Plan</a>
                    <?php elseif ($user): ?>
                        <a class="btn btn-full" href="<?= e(url('payment.php?plan=' . $key)) ?>">Get started ↗</a>
                    <?php else: ?>
                        <a class="btn btn-full" href="<?= e(url('login.php?next=' . urlencode('payment.php?plan=' . $key))) ?>">Log in to get started</a>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- FAQ -->
        <div class="pricing-faq">
            <h2>Frequently asked questions</h2>
            <div class="faq-list">
                <details class="faq-item">
                    <summary>Can I change my plan later?</summary>
                    <div class="faq-body">Yes. You can upgrade or downgrade at any time from your account. Changes take effect immediately.</div>
                </details>
                <details class="faq-item">
                    <summary>Is my payment secure?</summary>
                    <div class="faq-body">All payments are processed through Razorpay, a PCI-DSS compliant payment gateway. We never store your card details.</div>
                </details>
                <details class="faq-item">
                    <summary>What happens to my listings if I downgrade?</summary>
                    <div class="faq-body">Your existing listings stay live, but new listing creation will be limited to the allowance of your new plan.</div>
                </details>
                <details class="faq-item">
                    <summary>Do you offer a free trial?</summary>
                    <div class="faq-body">The Basic plan is permanently free with no trial period. Paid plans can be cancelled at any time.</div>
                </details>
                <details class="faq-item">
                    <summary>How do I contact support?</summary>
                    <div class="faq-body">Email or WhatsApp support is available to Premium and Featured plan members. Basic plan members can reach us via the <a href="<?= e(url('contact.php')) ?>">contact form</a>.</div>
                </details>
            </div>
        </div>

    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
