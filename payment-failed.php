<?php
require_once __DIR__ . '/config/config.php';
$pageTitle = 'Payment Failed';
require __DIR__ . '/includes/header.php';
?>
<style>
.pf-wrap {
    min-height: 70vh;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--paper);
    padding: 48px 20px;
}
.pf-card {
    background: var(--white);
    border-radius: 20px;
    box-shadow: var(--shadow);
    padding: 52px 44px;
    max-width: 460px;
    width: 100%;
    text-align: center;
}
@media (max-width: 540px) { .pf-card { padding: 32px 20px; } }

.pf-icon  { font-size: 4rem; margin-bottom: 16px; }
.pf-title { font-size: 1.6rem; font-weight: 800; color: var(--ink); margin: 0 0 12px; }
.pf-desc  { color: var(--muted); font-size: .95rem; line-height: 1.7; margin: 0 0 32px; }

.pf-actions { display: flex; flex-direction: column; gap: 12px; }
</style>

<div class="pf-wrap">
    <div class="pf-card">
        <div class="pf-icon">❌</div>
        <h1 class="pf-title">Payment could not be processed</h1>
        <p class="pf-desc">
            Your transaction was not completed. No amount has been deducted from your account.
            This can happen due to a bank decline, network timeout, or cancelled transaction.
            Please try again or contact our support team if the issue persists.
        </p>
        <div class="pf-actions">
            <a class="btn btn-full" href="<?= e(url('pricing.php')) ?>">← Try again</a>
            <a class="btn btn-outline btn-full" href="<?= e(url('contact.php')) ?>">📬 Contact support</a>
        </div>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
