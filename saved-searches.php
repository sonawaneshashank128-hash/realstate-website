<?php
require_once __DIR__ . '/config/config.php';
require_login();

$pageTitle = 'Saved Searches';
$user      = current_user();
$userId    = (int)$user['id'];

// ── POST handlers ─────────────────────────────────────────────────────────────
if (is_post()) {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'save_search') {
        $label   = mb_substr(trim($_POST['label']   ?? ''), 0, 100);
        $filters = $_POST['filters'] ?? '{}';

        if ($label === '') {
            flash('error', 'Search label cannot be empty.');
            redirect('saved-searches.php');
        }

        // Validate that filters is valid JSON
        $decodedFilters = json_decode($filters, true);
        if (!is_array($decodedFilters)) {
            $decodedFilters = [];
        }

        // Cap at 20 saved searches per user
        $countStmt = db()->prepare('SELECT COUNT(*) FROM saved_searches WHERE user_id=?');
        $countStmt->execute([$userId]);
        if ((int)$countStmt->fetchColumn() >= 20) {
            flash('error', 'You can save up to 20 searches. Please delete some to add new ones.');
            redirect('saved-searches.php');
        }

        $ins = db()->prepare(
            'INSERT INTO saved_searches (user_id, label, filters) VALUES (?,?,?)'
        );
        $ins->execute([$userId, $label, json_encode($decodedFilters)]);
        flash('success', 'Search saved successfully.');
        redirect('saved-searches.php');
    }

    if ($action === 'delete_search') {
        $searchId = filter_input(INPUT_POST, 'search_id', FILTER_VALIDATE_INT);
        if ($searchId && $searchId > 0) {
            $del = db()->prepare('DELETE FROM saved_searches WHERE id=? AND user_id=?');
            $del->execute([$searchId, $userId]);
        }
        redirect('saved-searches.php');
    }

    redirect('saved-searches.php');
}

// ── GET — fetch all saved searches ───────────────────────────────────────────
$stmt = db()->prepare(
    'SELECT id, label, filters, created_at FROM saved_searches WHERE user_id=? ORDER BY created_at DESC'
);
$stmt->execute([$userId]);
$searches = $stmt->fetchAll();

/**
 * Build a human-readable summary from saved filter params.
 */
function filter_summary(array $filters): string
{
    $parts = [];
    if (!empty($filters['location'])) {
        $parts[] = $filters['location'];
    }
    if (!empty($filters['listing_type'])) {
        $parts[] = $filters['listing_type'] === 'sale' ? 'For Sale' : 'For Rent';
    }
    if (!empty($filters['property_type'])) {
        $parts[] = ucfirst($filters['property_type']);
    }
    if (!empty($filters['bedrooms'])) {
        $parts[] = $filters['bedrooms'] . '+ Beds';
    }
    if (!empty($filters['min_price']) || !empty($filters['max_price'])) {
        $min = !empty($filters['min_price']) ? '₹' . number_format((float)$filters['min_price'] / 100000, 0) . 'L' : '₹0';
        $max = !empty($filters['max_price']) ? '₹' . number_format((float)$filters['max_price'] / 100000, 0) . 'L' : 'Any';
        $parts[] = $min . ' – ' . $max;
    }
    return $parts ? implode(', ', $parts) : 'All properties';
}

require __DIR__ . '/includes/header.php';
?>
<style>
.ss-hero {
    background: linear-gradient(135deg, #1a1a2e 0%, var(--ink) 100%);
    padding: 52px 0 40px;
    color: var(--white);
    text-align: center;
}
.ss-hero .eyebrow { color: rgba(255,255,255,.65); }
.ss-hero h1 { font-size: 2.2rem; margin: 8px 0 10px; }
.ss-hero p  { opacity: .8; margin: 0; }

.ss-section { padding: 44px 0 72px; background: var(--paper); }

.ss-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
    margin-top: 28px;
}

.ss-card {
    background: var(--white);
    border-radius: 16px;
    box-shadow: var(--shadow);
    padding: 24px 22px;
    display: flex;
    flex-direction: column;
    gap: 10px;
}
.ss-card-label {
    font-size: 1rem;
    font-weight: 800;
    color: var(--ink);
    display: flex;
    align-items: center;
    gap: 8px;
}
.ss-card-label::before { content: '🔖'; }

.ss-card-summary {
    font-size: .84rem;
    color: var(--muted);
    line-height: 1.5;
}
.ss-card-date {
    font-size: .75rem;
    color: var(--muted);
    margin-top: 2px;
}

.ss-card-actions {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-top: 6px;
    flex-wrap: wrap;
}

.ss-delete-form button {
    font-size: .78rem;
    padding: 5px 12px;
    border-radius: 8px;
    border: 1px solid var(--danger);
    background: transparent;
    color: var(--danger);
    cursor: pointer;
}
.ss-delete-form button:hover { background: var(--danger); color: white; }

/* Empty state */
.ss-empty {
    text-align: center;
    padding: 64px 20px;
    background: var(--white);
    border-radius: 18px;
    box-shadow: var(--shadow);
    margin-top: 28px;
}
.ss-empty-icon { font-size: 3.5rem; margin-bottom: 14px; }
.ss-empty h2   { margin: 0 0 8px; }
.ss-empty p    { color: var(--muted); margin: 0 0 22px; }
</style>

<!-- HERO -->
<section class="ss-hero">
    <div class="container">
        <span class="eyebrow">Your search history</span>
        <h1>🔖 Saved Searches</h1>
        <p>Quickly re-run your favourite property searches.</p>
    </div>
</section>

<!-- BODY -->
<section class="ss-section">
    <div class="container">

        <?php if ($searches): ?>

        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">
            <p style="margin:0;color:var(--muted);font-size:.9rem">
                <?= count($searches) ?> saved search<?= count($searches) !== 1 ? 'es' : '' ?>
                (max 20)
            </p>
            <a class="btn btn-small btn-outline" href="<?= e(url('properties.php')) ?>">Browse properties ↗</a>
        </div>

        <div class="ss-grid">
            <?php foreach ($searches as $row): ?>
            <?php
                $filters = json_decode($row['filters'], true);
                if (!is_array($filters)) $filters = [];
                $summary    = filter_summary($filters);
                $searchUrl  = url('properties.php?' . http_build_query(array_filter($filters, fn($v) => $v !== '')));
                $createdAt  = date('d M Y', strtotime($row['created_at']));
            ?>
            <div class="ss-card">
                <div class="ss-card-label"><?= e($row['label']) ?></div>
                <div class="ss-card-summary"><?= e($summary) ?></div>
                <div class="ss-card-date">Saved on <?= $createdAt ?></div>
                <div class="ss-card-actions">
                    <a class="btn btn-small" href="<?= e($searchUrl) ?>">🔍 Search again</a>
                    <form class="ss-delete-form" method="post"
                          onsubmit="return confirm('Delete this saved search?')">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action"    value="delete_search">
                        <input type="hidden" name="search_id" value="<?= (int)$row['id'] ?>">
                        <button type="submit">🗑 Delete</button>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <?php else: ?>

        <div class="ss-empty">
            <div class="ss-empty-icon">🔍</div>
            <h2>No saved searches yet</h2>
            <p>Use the <strong>🔖 Save search</strong> button on the properties page to save your current filters here.</p>
            <a class="btn" href="<?= e(url('properties.php')) ?>">Start exploring ↗</a>
        </div>

        <?php endif; ?>

    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
