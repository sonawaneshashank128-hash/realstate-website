<?php
require_once __DIR__ . '/config/config.php';
$pageTitle = 'EMI Calculator';

// ── POST handler ──────────────────────────────────────────────────────────────
$emiResult = null;
$errors    = [];
$formAmount  = '';
$formRate    = '8.5';
$formTenure  = '20';

if (is_post()) {
    verify_csrf();

    $formAmount = trim($_POST['amount'] ?? '');
    $formRate   = trim($_POST['rate']   ?? '');
    $formTenure = trim($_POST['tenure'] ?? '');

    $amount = (float)$formAmount;
    $rate   = (float)$formRate;
    $tenure = (int)$formTenure;

    if ($amount < 100000) {
        $errors[] = 'Loan amount must be at least ₹1,00,000.';
    }
    if ($rate < 1 || $rate > 30) {
        $errors[] = 'Interest rate must be between 1% and 30%.';
    }
    if ($tenure < 1 || $tenure > 30) {
        $errors[] = 'Loan tenure must be between 1 and 30 years.';
    }

    if (!$errors) {
        $r = $rate / 12 / 100;
        $n = $tenure * 12;
        $emiMonthly   = $amount * $r * pow(1 + $r, $n) / (pow(1 + $r, $n) - 1);
        $totalPayable = $emiMonthly * $n;
        $totalInterest= $totalPayable - $amount;

        $emiResult = [
            'monthly'  => $emiMonthly,
            'payable'  => $totalPayable,
            'interest' => $totalInterest,
            'principal' => $amount,
        ];
    }
}

require __DIR__ . '/includes/header.php';
?>
<style>
.emi-hero {
    overflow: hidden;
    background: radial-gradient(circle at 75% 30%, rgba(180,147,80,.2), transparent 28%), linear-gradient(120deg, #102d25, #214b3a);
    padding: 54px 0;
    color: var(--white);
}
.emi-hero-inner { display: grid; grid-template-columns: 1.1fr .9fr; align-items: center; gap: 48px; }
.emi-hero .eyebrow { color: #d3ddce; }
.emi-hero h1 { max-width: 600px; font: 800 clamp(2.4rem,5vw,4rem)/1.08 Manrope,sans-serif; letter-spacing: -2px; margin: 13px 0; }
.emi-hero p { color: #dbe5dc; max-width: 520px; margin: 0; font-size: 1.04rem; }
.emi-hero-visual { position: relative; min-height: 235px; }
.emi-hero-visual img { width: 100%; height: 245px; object-fit: cover; border: 1px solid rgba(255,255,255,.2); border-radius: 20px; box-shadow: 0 24px 55px rgba(0,0,0,.2); }
.emi-hero-note { position: absolute; left: -22px; bottom: 17px; display: flex; align-items: center; gap: 11px; background: white; color: var(--ink); padding: 12px 16px; border-radius: 12px; box-shadow: var(--shadow); }
.emi-hero-note svg { flex: 0 0 auto; color: var(--green2); }
.emi-hero-note strong,.emi-hero-note small { display: block; }
.emi-hero-note small { color: var(--muted); font-size: .75rem; }
.emi-page { max-width: 1100px; margin: 0 auto; padding: 38px 20px 70px; }
.emi-card,.emi-video-card { background: var(--white); border: 1px solid var(--line); border-radius: 20px; box-shadow: 0 12px 38px rgba(24,43,33,.07); }
.emi-card { padding: 34px; }
.emi-card-heading { display: flex; align-items: flex-start; justify-content: space-between; gap: 18px; margin-bottom: 25px; }
.emi-card-heading h2 { font: 800 1.45rem Manrope,sans-serif; letter-spacing: -.5px; margin: 3px 0 4px; color: var(--ink); }
.emi-card-heading p { color: var(--muted); font-size: .9rem; margin: 0; }
.emi-secure-note { display: flex; align-items: center; gap: 7px; color: var(--muted); font-size: .78rem; white-space: nowrap; padding-top: 5px; }
.emi-secure-note svg { color: var(--green2); }
.emi-fields { display: grid; grid-template-columns: repeat(3,minmax(0,1fr)); gap: 20px; }
.emi-field label { display: flex; flex-direction: column; gap: 8px; font-size: .84rem; font-weight: 700; color: var(--ink); }
.emi-field-top { display: flex; justify-content: space-between; gap: 8px; align-items: center; }
.emi-field input[type=number] { width: 100%; border: 1px solid var(--line); border-radius: 9px; padding: 11px 13px; font-size: 1rem; background: #fbfbf8; color: var(--ink); }
.emi-field input[type=number]:focus { outline: 2px solid rgba(46,97,74,.18); border-color: var(--green2); }
.emi-field input[type=range] { width: 100%; accent-color: var(--green2); margin: 8px 0 0; cursor: pointer; }
.emi-range-labels { display: flex; justify-content: space-between; color: var(--muted); font-size: .71rem; font-weight: 500; margin-top: -6px; }
.emi-calculate { margin-top: 24px; }
.emi-result-row { display: grid; grid-template-columns: repeat(3,minmax(0,1fr)); gap: 14px; margin-top: 26px; }
.emi-result-row[hidden],.emi-insights[hidden] { display: none; }
.emi-result-card { position: relative; background: #f7f8f4; border: 1px solid #ecefe8; border-radius: 14px; padding: 18px; display: flex; align-items: center; gap: 13px; min-width: 0; }
.emi-result-card.highlight { background: #f0f5ef; border-color: #dce8dc; }
.emi-result-icon { display: grid; place-items: center; flex: 0 0 42px; height: 42px; background: white; color: var(--green2); border-radius: 12px; }
.emi-result-card strong { display: block; font: 800 clamp(1.05rem,2vw,1.35rem) Manrope,sans-serif; color: var(--ink); overflow-wrap: anywhere; }
.emi-result-card small { display: block; color: var(--muted); font-size: .72rem; font-weight: 700; text-transform: uppercase; letter-spacing: .045em; }
.emi-insights { display: grid; grid-template-columns: .85fr 1.15fr; gap: 18px; margin-top: 18px; }
.emi-insight-card { min-width: 0; background: #fbfbf9; border: 1px solid var(--line); border-radius: 15px; padding: 20px; }
.emi-insight-card h3 { font: 800 1rem Manrope,sans-serif; margin: 0 0 4px; }
.emi-insight-card > p { font-size: .8rem; color: var(--muted); margin: 0; }
.emi-breakdown-content { display: flex; align-items: center; justify-content: center; gap: 24px; padding-top: 10px; }
.emi-donut { width: 150px; height: 150px; flex: 0 0 150px; }
.emi-donut-track,.emi-donut-principal,.emi-donut-interest { fill: none; stroke-width: 15; }
.emi-donut-track { stroke: #e9eee7; }
.emi-donut-principal,.emi-donut-interest { transform: rotate(-90deg); transform-origin: 80px 80px; transition: stroke-dasharray .35s ease,stroke-dashoffset .35s ease; }
.emi-donut-principal { stroke: var(--green2); }
.emi-donut-interest { stroke: #c5a45f; }
.emi-donut-label { fill: var(--ink); font: 800 17px Manrope,sans-serif; text-anchor: middle; }
.emi-donut-caption { fill: var(--muted); font: 600 9px 'DM Sans',sans-serif; text-anchor: middle; }
.emi-legend { display: grid; gap: 12px; min-width: 120px; }
.emi-legend-item { display: grid; grid-template-columns: 10px 1fr; gap: 0 8px; align-items: center; font-size: .78rem; color: var(--muted); }
.emi-legend-item strong { grid-column: 2; color: var(--ink); font-size: .91rem; }
.emi-legend-dot { width: 9px; height: 9px; border-radius: 50%; background: var(--green2); }
.emi-legend-dot.interest { background: #c5a45f; }
.emi-chart-wrap { margin-top: 10px; }
.emi-payoff-chart { display: block; width: 100%; height: 160px; overflow: visible; }
.emi-gridline { stroke: #e8ebe5; stroke-width: 1; }
.emi-chart-area { fill: rgba(46,97,74,.1); }
.emi-chart-line { fill: none; stroke: var(--green2); stroke-width: 3; stroke-linecap: round; stroke-linejoin: round; }
.emi-chart-dot { fill: white; stroke: var(--green2); stroke-width: 2; }
.emi-chart-labels { display: flex; justify-content: space-between; color: var(--muted); font-size: .7rem; }
.emi-chart-summary { margin-top: 12px !important; }
.emi-info-box { display: flex; gap: 12px; background: #f3f6f1; border: 1px solid #e4ebe1; border-radius: 12px; padding: 15px 17px; margin-top: 20px; font-size: .83rem; color: var(--muted); line-height: 1.65; }
.emi-info-icon { flex: 0 0 auto; color: var(--green2); margin-top: 2px; }
.emi-info-box strong { color: var(--ink); }
.emi-errors { background: #fff0f0; border: 1px solid var(--danger); border-radius: 10px; padding: 14px 18px; margin-bottom: 20px; font-size: .88rem; color: var(--danger); }
.emi-errors ul { margin: 6px 0 0; padding-left: 18px; }
.emi-video-card { display: grid; grid-template-columns: 1.05fr .95fr; overflow: hidden; margin-top: 22px; }
.emi-video-poster { position: relative; display: block; min-height: 255px; overflow: hidden; background: var(--green); }
.emi-video-poster img { width: 100%; height: 100%; min-height: 255px; object-fit: cover; transition: transform .35s; }
.emi-video-poster:hover img { transform: scale(1.035); }
.emi-video-overlay { position: absolute; inset: 0; display: grid; place-items: center; background: linear-gradient(0deg,rgba(13,34,26,.55),rgba(13,34,26,.05)); }
.emi-play-button { display: grid; place-items: center; width: 62px; height: 62px; border: 1px solid rgba(255,255,255,.7); border-radius: 50%; color: white; background: rgba(255,255,255,.2); backdrop-filter: blur(7px); transition: transform .2s,background .2s; }
.emi-video-poster:hover .emi-play-button { transform: scale(1.08); background: rgba(255,255,255,.32); }
.emi-video-tag { position: absolute; left: 16px; bottom: 15px; border-radius: 6px; background: rgba(16,45,37,.82); color: white; padding: 5px 9px; font-size: .7rem; font-weight: 700; }
.emi-video-copy { padding: 28px; align-self: center; }
.emi-video-copy h2 { font: 800 1.35rem/1.3 Manrope,sans-serif; margin: 7px 0 9px; }
.emi-video-copy p { color: var(--muted); font-size: .88rem; margin: 0 0 18px; }
.emi-video-link { color: var(--green); font-size: .86rem; font-weight: 800; }
.emi-video-link:hover { color: var(--green2); text-decoration: underline; }
@media (max-width: 760px) {
    .emi-hero-inner { grid-template-columns: 1fr; gap: 25px; }
    .emi-hero-visual { display: none; }
    .emi-fields { grid-template-columns: 1fr; gap: 16px; }
    .emi-insights { grid-template-columns: 1fr; }
    .emi-video-card { grid-template-columns: 1fr; }
}
@media (max-width: 560px) {
    .emi-hero { padding: 42px 0; }
    .emi-page { padding: 24px 14px 48px; }
    .emi-card { padding: 21px 16px; }
    .emi-card-heading { flex-direction: column; gap: 5px; }
    .emi-result-row { grid-template-columns: 1fr; gap: 9px; margin-top: 20px; }
    .emi-result-card { padding: 14px; }
    .emi-breakdown-content { gap: 15px; }
    .emi-donut { width: 125px; height: 125px; flex-basis: 125px; }
    .emi-video-copy { padding: 22px; }
}
</style>

<section class="emi-hero">
    <div class="container emi-hero-inner">
        <div>
            <span class="eyebrow">Plan with confidence</span>
            <h1>Know your numbers before you find your home.</h1>
            <p>Explore your monthly payment, total interest and payoff journey with a clear, flexible home-loan estimate.</p>
        </div>
        <div class="emi-hero-visual" aria-hidden="true">
            <img src="<?= e(url('assets/images/estate-hero.jpg')) ?>" alt="">
            <div class="emi-hero-note">
                <svg width="23" height="23" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m3 10 9-7 9 7v10a1 1 0 0 1-1 1h-6v-7h-4v7H4a1 1 0 0 1-1-1z"/></svg>
                <span><strong>Plan your next move</strong><small>Make the numbers feel simple</small></span>
            </div>
        </div>
    </div>
</section>

<div class="emi-page">
    <div class="emi-card">
        <div class="emi-card-heading">
            <div>
                <span class="eyebrow">Your estimate</span>
                <h2>Set your loan details</h2>
                <p>Adjust any value to see how it changes your monthly plan.</p>
            </div>
            <span class="emi-secure-note">
                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M12 22s8-4 8-11V5l-8-3-8 3v6c0 7 8 11 8 11Z"/><path d="m9 12 2 2 4-4"/></svg>
                No personal details required
            </span>
        </div>

        <?php if ($errors): ?>
        <div class="emi-errors">
            <strong>Please fix the following:</strong>
            <ul><?php foreach ($errors as $e): ?><li><?= e($e) ?></li><?php endforeach; ?></ul>
        </div>
        <?php endif; ?>

        <form method="post" id="emiForm">
            <?= csrf_field() ?>

            <div class="emi-fields">
                <div class="emi-field">
                    <label for="emi-amount">
                        <span class="emi-field-top"><span>Loan amount</span><span>INR</span></span>
                        <input
                            id="emi-amount"
                            type="number"
                            name="amount"
                            value="<?= e($formAmount ?: '5000000') ?>"
                            min="100000"
                            step="50000"
                            required
                            data-emi="amount"
                        >
                        <input id="emi-amount-range" type="range" min="100000" max="100000000" step="50000" value="<?= e($formAmount ?: '5000000') ?>" aria-label="Adjust loan amount">
                        <span class="emi-range-labels"><span>₹1 lakh</span><span>₹10 crore</span></span>
                    </label>
                </div>
                <div class="emi-field">
                    <label for="emi-rate">
                        <span class="emi-field-top"><span>Interest rate</span><span>% per year</span></span>
                        <input
                            id="emi-rate"
                            type="number"
                            name="rate"
                            value="<?= e($formRate) ?>"
                            min="1"
                            max="30"
                            step="0.1"
                            required
                            data-emi="rate"
                        >
                        <input id="emi-rate-range" type="range" min="1" max="30" step="0.1" value="<?= e($formRate) ?>" aria-label="Adjust annual interest rate">
                        <span class="emi-range-labels"><span>1%</span><span>30%</span></span>
                    </label>
                </div>
                <div class="emi-field">
                    <label for="emi-tenure">
                        <span class="emi-field-top"><span>Loan tenure</span><span>years</span></span>
                        <input
                            id="emi-tenure"
                            type="number"
                            name="tenure"
                            value="<?= e($formTenure) ?>"
                            min="1"
                            max="30"
                            step="1"
                            required
                            data-emi="tenure"
                        >
                        <input id="emi-tenure-range" type="range" min="1" max="30" step="1" value="<?= e($formTenure) ?>" aria-label="Adjust loan tenure">
                        <span class="emi-range-labels"><span>1 year</span><span>30 years</span></span>
                    </label>
                </div>
            </div>

            <div class="emi-calculate">
                <button class="btn btn-full" type="submit">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 4v6h6M20 20v-6h-6"/><path d="M5.5 15a7 7 0 0 0 12 2l2.5-3M18.5 9a7 7 0 0 0-12-2L4 10"/></svg>
                    Calculate EMI
                </button>
            </div>
        </form>

        <?php
        $chartCircumference = 2 * pi() * 62;
        $serverPrincipalShare = $emiResult ? $emiResult['principal'] / $emiResult['payable'] : 1;
        $serverPrincipalLength = $chartCircumference * $serverPrincipalShare;
        $serverInterestLength = $chartCircumference - $serverPrincipalLength;
        ?>
        <div class="emi-result-row" id="emiResultRow"<?= $emiResult ? '' : ' hidden' ?>>
            <div class="emi-result-card highlight">
                <span class="emi-result-icon" aria-hidden="true"><svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M7 9h.01M7 15h.01M11 9h6M11 15h6"/></svg></span>
                <span><strong id="js-emi-monthly"><?= $emiResult ? money(round($emiResult['monthly'])) : '—' ?></strong><small>Monthly EMI</small></span>
            </div>
            <div class="emi-result-card">
                <span class="emi-result-icon" aria-hidden="true"><svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 10h18M5 10V6l7-3 7 3v4M5 20v-7m4 7v-7m6 7v-7m4 7v-7M3 21h18"/></svg></span>
                <span><strong id="js-emi-total"><?= $emiResult ? money(round($emiResult['payable'])) : '—' ?></strong><small>Total payable</small></span>
            </div>
            <div class="emi-result-card">
                <span class="emi-result-icon" aria-hidden="true"><svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></span>
                <span><strong id="js-emi-interest"><?= $emiResult ? money(round($emiResult['interest'])) : '—' ?></strong><small>Total interest</small></span>
            </div>
        </div>

        <div class="emi-insights" id="emiInsights"<?= $emiResult ? '' : ' hidden' ?>>
            <section class="emi-insight-card" aria-labelledby="emiBreakdownTitle">
                <h3 id="emiBreakdownTitle">Payment breakdown</h3>
                <p>See how principal and interest compare.</p>
                <div class="emi-breakdown-content">
                    <svg class="emi-donut" viewBox="0 0 160 160" role="img" aria-labelledby="emiDonutTitle emiDonutDesc">
                        <title id="emiDonutTitle">Loan principal and interest breakdown</title>
                        <desc id="emiDonutDesc">Principal and interest portions of the total payable.</desc>
                        <circle class="emi-donut-track" cx="80" cy="80" r="62"/>
                        <circle id="emiPrincipalArc" class="emi-donut-principal" cx="80" cy="80" r="62" stroke-dasharray="<?= e(number_format($serverPrincipalLength, 2, '.', '')) ?> <?= e(number_format($chartCircumference - $serverPrincipalLength, 2, '.', '')) ?>"/>
                        <circle id="emiInterestArc" class="emi-donut-interest" cx="80" cy="80" r="62" stroke-dasharray="<?= e(number_format($serverInterestLength, 2, '.', '')) ?> <?= e(number_format($chartCircumference - $serverInterestLength, 2, '.', '')) ?>" stroke-dashoffset="<?= e(number_format(-$serverPrincipalLength, 2, '.', '')) ?>"/>
                        <text id="emiPrincipalPercent" class="emi-donut-label" x="80" y="77">—</text>
                        <text class="emi-donut-caption" x="80" y="94">PRINCIPAL</text>
                    </svg>
                    <div class="emi-legend">
                        <div class="emi-legend-item"><span class="emi-legend-dot"></span><span>Principal</span><strong id="emiPrincipalValue"><?= $emiResult ? money(round($emiResult['principal'])) : '—' ?></strong></div>
                        <div class="emi-legend-item"><span class="emi-legend-dot interest"></span><span>Interest</span><strong id="emiInterestValue"><?= $emiResult ? money(round($emiResult['interest'])) : '—' ?></strong></div>
                    </div>
                </div>
            </section>
            <section class="emi-insight-card" aria-labelledby="emiPayoffTitle">
                <h3 id="emiPayoffTitle">Your payoff journey</h3>
                <p>Estimated outstanding balance over the loan term.</p>
                <div class="emi-chart-wrap">
                    <svg class="emi-payoff-chart" viewBox="0 0 520 160" role="img" aria-labelledby="emiPayoffTitle emiPayoffDesc">
                        <desc id="emiPayoffDesc">A line chart showing the estimated loan balance decreasing over the selected tenure.</desc>
                        <line class="emi-gridline" x1="20" y1="20" x2="510" y2="20"/>
                        <line class="emi-gridline" x1="20" y1="75" x2="510" y2="75"/>
                        <line class="emi-gridline" x1="20" y1="130" x2="510" y2="130"/>
                        <path id="emiChartArea" class="emi-chart-area" d=""/>
                        <polyline id="emiChartLine" class="emi-chart-line" points=""/>
                        <g id="emiChartDots"></g>
                    </svg>
                    <div class="emi-chart-labels"><span>Today</span><span id="emiChartMidLabel">In 10 years</span><span>Loan paid off</span></div>
                </div>
                <p class="emi-chart-summary" id="emiChartSummary"></p>
            </section>
        </div>

        <div class="emi-info-box">
            <svg class="emi-info-icon" width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 11v5m0-8h.01"/></svg>
            <div><strong>Indicative estimate.</strong> EMI = P × r × (1 + r)<sup>n</sup> / ((1 + r)<sup>n</sup> – 1), where <em>P</em> is principal, <em>r</em> is monthly interest rate, and <em>n</em> is the number of monthly payments. Actual terms may vary by lender, fees and loan structure.</div>
        </div>
    </div>

    <section class="emi-video-card" aria-labelledby="emiVideoTitle">
        <a class="emi-video-poster" href="https://www.youtube.com/results?search_query=India+home+buying+guide+property+tour" target="_blank" rel="noopener noreferrer" aria-label="Browse home-buying guides and property tours on YouTube">
            <img src="<?= e(url('assets/images/estate-house.jpg')) ?>" alt="A home exterior representing the property tours and home-buying guides">
            <span class="emi-video-overlay" aria-hidden="true">
                <span class="emi-play-button"><svg width="23" height="23" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5.8c0-.8.9-1.3 1.6-.9l9.1 5.3a1 1 0 0 1 0 1.7l-9.1 5.3c-.7.4-1.6-.1-1.6-.9z"/></svg></span>
            </span>
            <span class="emi-video-tag">HOME-BUYING VIDEOS</span>
        </a>
        <div class="emi-video-copy">
            <span class="eyebrow">See the possibilities</span>
            <h2 id="emiVideoTitle">A home is more than a number.</h2>
            <p>Explore home tours and practical buying guides as you plan your next move. Videos open on YouTube only when you choose to watch.</p>
            <a class="emi-video-link" href="https://www.youtube.com/results?search_query=India+home+buying+guide+property+tour" target="_blank" rel="noopener noreferrer">Explore home tours and guides <span aria-hidden="true">→</span></a>
        </div>
    </section>
</div>

<script>
(function () {
    var amountInput = document.getElementById('emi-amount');
    var rateInput   = document.getElementById('emi-rate');
    var tenureInput = document.getElementById('emi-tenure');
    var amountRange = document.getElementById('emi-amount-range');
    var rateRange   = document.getElementById('emi-rate-range');
    var tenureRange = document.getElementById('emi-tenure-range');
    var row         = document.getElementById('emiResultRow');
    var insights    = document.getElementById('emiInsights');
    var principalArc = document.getElementById('emiPrincipalArc');
    var interestArc  = document.getElementById('emiInterestArc');
    var circumference = 2 * Math.PI * 62;

    function fmt(n) {
        return '₹' + Math.round(n).toLocaleString('en-IN');
    }

    function syncRange(input, range) {
        var value = Number(input.value);
        if (Number.isFinite(value)) {
            if (input === amountInput && value > Number(range.max)) {
                range.max = String(Math.ceil(value / 50000) * 50000);
            }
            range.value = String(Math.min(Number(range.max), Math.max(Number(range.min), value)));
        }
    }

    function drawPayoffChart(amount, annualRate, years) {
        var monthlyRate = annualRate / 12 / 100;
        var monthlyPayment = amount * monthlyRate * Math.pow(1 + monthlyRate, years * 12) /
            (Math.pow(1 + monthlyRate, years * 12) - 1);
        var points = [];
        var sampleCount = 6;

        for (var i = 0; i <= sampleCount; i++) {
            var monthsPaid = Math.round(years * 12 * i / sampleCount);
            var balance = i === sampleCount ? 0 :
                amount * Math.pow(1 + monthlyRate, monthsPaid) -
                monthlyPayment * (Math.pow(1 + monthlyRate, monthsPaid) - 1) / monthlyRate;
            var x = 20 + 490 * i / sampleCount;
            var y = 20 + 110 * (1 - Math.max(0, balance) / amount);
            points.push([x, y]);
        }

        var pointString = points.map(function (point) {
            return point[0].toFixed(1) + ',' + point[1].toFixed(1);
        }).join(' ');
        document.getElementById('emiChartLine').setAttribute('points', pointString);
        document.getElementById('emiChartArea').setAttribute('d',
            'M ' + points[0][0] + ' 130 L ' + pointString.replace(/ /g, ' L ') + ' L ' + points[points.length - 1][0] + ' 130 Z');
        document.getElementById('emiChartDots').innerHTML = points.map(function (point) {
            return '<circle class="emi-chart-dot" cx="' + point[0].toFixed(1) + '" cy="' + point[1].toFixed(1) + '" r="3.5"/>';
        }).join('');
        document.getElementById('emiChartMidLabel').textContent = 'In ' + Math.max(1, Math.round(years / 2)) + ' years';
        document.getElementById('emiChartSummary').textContent =
            'Estimated balance: ' + fmt(amount) + ' today, reducing to ₹0 by year ' + years + '.';
    }

    function calc() {
        var amount = parseFloat(amountInput.value);
        var rate   = parseFloat(rateInput.value);
        var tenure = parseInt(tenureInput.value, 10);

        if (!Number.isFinite(amount) || !Number.isFinite(rate) || !Number.isFinite(tenure) ||
            amount < 100000 || rate < 1 || rate > 30 || tenure < 1 || tenure > 30) {
            row.hidden = true;
            insights.hidden = true;
            return;
        }

        var r   = rate / 12 / 100;
        var n   = tenure * 12;
        var emi = amount * r * Math.pow(1 + r, n) / (Math.pow(1 + r, n) - 1);
        var tot = emi * n;
        var int = tot - amount;

        document.getElementById('js-emi-monthly').textContent  = fmt(emi);
        document.getElementById('js-emi-total').textContent    = fmt(tot);
        document.getElementById('js-emi-interest').textContent = fmt(int);
        document.getElementById('emiPrincipalValue').textContent = fmt(amount);
        document.getElementById('emiInterestValue').textContent = fmt(int);
        document.getElementById('emiPrincipalPercent').textContent = Math.round(amount / tot * 100) + '%';

        var principalLength = circumference * amount / tot;
        var interestLength = circumference - principalLength;
        principalArc.setAttribute('stroke-dasharray', principalLength + ' ' + (circumference - principalLength));
        principalArc.setAttribute('stroke-dashoffset', '0');
        interestArc.setAttribute('stroke-dasharray', interestLength + ' ' + (circumference - interestLength));
        interestArc.setAttribute('stroke-dashoffset', String(-principalLength));

        drawPayoffChart(amount, rate, tenure);
        row.hidden = false;
        insights.hidden = false;
    }

    [[amountInput, amountRange], [rateInput, rateRange], [tenureInput, tenureRange]].forEach(function (pair) {
        pair[0].addEventListener('input', function () {
            syncRange(pair[0], pair[1]);
            calc();
        });
        pair[1].addEventListener('input', function () {
            pair[0].value = pair[1].value;
            calc();
        });
        syncRange(pair[0], pair[1]);
    });

    calc();
}());
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
