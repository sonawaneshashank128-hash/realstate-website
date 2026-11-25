<?php
require_once __DIR__ . '/config/config.php';
require_login();

$pageTitle = 'Compare Properties';
$user      = current_user();
$userId    = (int)$user['id'];

// ── Fetch compare list ───────────────────────────────────────────────────────
$stmt = db()->prepare(
    'SELECT cl.property_id, p.*, u.name AS owner_name,
        (SELECT pi.image_path FROM property_images pi WHERE pi.property_id=p.id ORDER BY pi.id LIMIT 1) AS cover_image
     FROM compare_list cl
     JOIN properties p  ON p.id=cl.property_id
     JOIN users u       ON u.id=p.owner_id
     WHERE cl.user_id=?
     ORDER BY cl.created_at ASC
     LIMIT 4'
);
$stmt->execute([$userId]);
$items = $stmt->fetchAll();

require __DIR__ . '/includes/header.php';
?>
<style>
.cmp-hero {
    background: linear-gradient(135deg, #102d25 0%, #173b30 72%, #24503e 100%);
    padding: 52px 0 40px;
    color: var(--white);
    text-align: center;
}
.cmp-hero .eyebrow { color: rgba(255,255,255,.7); }
.cmp-hero h1 { font-size: 2.2rem; margin: 8px 0 10px; }
.cmp-hero p  { opacity: .8; margin: 0; }

.cmp-wrap { padding: 40px 0 64px; }

.cmp-toolbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
    margin-bottom: 28px;
}
.cmp-toolbar h2 { font-size: 1.2rem; margin: 0; }

/* Comparison table */
.cmp-table-wrap { overflow-x: auto; border-radius: 16px; box-shadow: var(--shadow); }

.cmp-table {
    border-collapse: collapse;
    width: 100%;
    min-width: 640px;
    background: var(--white);
}
.cmp-table th,
.cmp-table td {
    border: 1px solid var(--line);
    padding: 14px 16px;
    text-align: left;
    vertical-align: top;
    font-size: .9rem;
}
.cmp-table thead th {
    background: var(--ink);
    color: var(--white);
    font-weight: 700;
    font-size: .85rem;
    text-transform: uppercase;
    letter-spacing: .04em;
}
.cmp-table thead th:first-child {
    width: 140px;
    background: #1a1a2e;
}
.cmp-table tbody tr:nth-child(even) td { background: var(--paper); }
.cmp-table tbody tr:nth-child(even) td:first-child { background: #f7f8fa; }
.cmp-table tbody td:first-child {
    font-weight: 700;
    color: var(--muted);
    font-size: .8rem;
    text-transform: uppercase;
    letter-spacing: .04em;
    white-space: nowrap;
}

.cmp-prop-img {
    width: 100%;
    max-width: 200px;
    height: 130px;
    object-fit: cover;
    border-radius: 10px;
    display: block;
}

.cmp-remove-form { margin-top: 8px; }
.cmp-remove-form button {
    font-size: .75rem;
    padding: 4px 10px;
    border-radius: 6px;
    border: 1px solid var(--danger);
    background: transparent;
    color: var(--danger);
    cursor: pointer;
}
.cmp-remove-form button:hover { background: var(--danger); color: white; }

.cmp-th-inner { display: flex; flex-direction: column; gap: 6px; }
.cmp-prop-name { font-size: .95rem; font-weight: 700; text-transform: none; letter-spacing: 0; }

/* Empty state */
.cmp-empty {
    text-align: center;
    padding: 64px 20px;
    background: var(--white);
    border-radius: 18px;
    box-shadow: var(--shadow);
}
.cmp-empty .cmp-empty-icon { font-size: 3.5rem; margin-bottom: 16px; }
.cmp-empty h2 { margin: 0 0 10px; }
.cmp-empty p  { color: var(--muted); margin: 0 0 24px; }

.status-pill { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: .75rem; font-weight: 700; }
.status-approved  { background: #d1fae5; color: #065f46; }
.status-pending   { background: #fef3c7; color: #92400e; }
.status-rejected  { background: #fee2e2; color: #991b1b; }
</style>

<!-- ── HERO ───────────────────────────────────────────────────────────────── -->
<section class="cmp-hero">
    <div class="container">
        <span class="eyebrow">Side-by-side comparison</span>
        <h1>⚖️ Compare Properties</h1>
        <p>Compare up to 4 properties at a glance.</p>
    </div>
</section>

<!-- ── BODY ───────────────────────────────────────────────────────────────── -->
<div class="cmp-wrap">
    <div class="container">

        <?php if ($items): ?>

        <div class="cmp-toolbar">
            <h2><?= count($items) ?> propert<?= count($items) === 1 ? 'y' : 'ies' ?> in your compare list</h2>
            <form method="post" action="<?= e(url('compare-action.php')) ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="clear_compare">
                <button class="btn btn-outline btn-small" type="submit"
                        onclick="return confirm('Clear all items from compare list?')">
                    🗑 Clear all
                </button>
            </form>
        </div>

        <div class="cmp-table-wrap">
            <table class="cmp-table" role="table">
                <thead>
                    <tr>
                        <th scope="col">Feature</th>
                        <?php foreach ($items as $p): ?>
                        <th scope="col">
                            <div class="cmp-th-inner">
                                <span class="cmp-prop-name"><?= e($p['title']) ?></span>
                                <form class="cmp-remove-form" method="post" action="<?= e(url('compare-action.php')) ?>">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="remove_from_compare">
                                    <input type="hidden" name="property_id" value="<?= (int)$p['property_id'] ?>">
                                    <button type="submit">✕ Remove</button>
                                </form>
                            </div>
                        </th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <!-- Photo row -->
                    <tr>
                        <td>Photo</td>
                        <?php foreach ($items as $p): ?>
                        <td>
                            <?php $comparePhoto = property_photo($p['cover_image'] ?? null, $p['property_type'] ?? null, (int)$p['id']); ?>
                            <a class="cmp-photo-link" href="<?= e(url('property-details.php?id=' . (int)$p['id'])) ?>">
                                <img class="cmp-prop-img"
                                     src="<?= e($comparePhoto['src']) ?>"
                                     alt="<?= e($comparePhoto['illustrative'] ? 'Illustrative photo for ' : '') . e($p['title']) ?>"
                                     loading="lazy">
                                <?php if ($comparePhoto['illustrative']): ?><span class="property-image-note">Illustrative photo</span><?php endif; ?>
                            </a>
                        </td>
                        <?php endforeach; ?>
                    </tr>
                    <!-- Price row -->
                    <tr>
                        <td>Price</td>
                        <?php foreach ($items as $p): ?>
                        <td><strong><?= money($p['price']) ?><?= $p['listing_type'] === 'rent' ? '<small>/mo</small>' : '' ?></strong></td>
                        <?php endforeach; ?>
                    </tr>
                    <!-- Location -->
                    <tr>
                        <td>Location</td>
                        <?php foreach ($items as $p): ?><td><?= e($p['location']) ?></td><?php endforeach; ?>
                    </tr>
                    <!-- Type -->
                    <tr>
                        <td>Type</td>
                        <?php foreach ($items as $p): ?><td><?= e(ucfirst($p['property_type'])) ?></td><?php endforeach; ?>
                    </tr>
                    <!-- Listing -->
                    <tr>
                        <td>Listing</td>
                        <?php foreach ($items as $p): ?><td><?= $p['listing_type'] === 'rent' ? 'For Rent' : 'For Sale' ?></td><?php endforeach; ?>
                    </tr>
                    <!-- Bedrooms -->
                    <tr>
                        <td>Bedrooms</td>
                        <?php foreach ($items as $p): ?><td><?= (int)$p['bedrooms'] > 0 ? (int)$p['bedrooms'] : '—' ?></td><?php endforeach; ?>
                    </tr>
                    <!-- Bathrooms -->
                    <tr>
                        <td>Bathrooms</td>
                        <?php foreach ($items as $p): ?><td><?= (int)$p['bathrooms'] > 0 ? (int)$p['bathrooms'] : '—' ?></td><?php endforeach; ?>
                    </tr>
                    <!-- Area -->
                    <tr>
                        <td>Area (sq ft)</td>
                        <?php foreach ($items as $p): ?><td><?= (int)$p['area_sqft'] > 0 ? number_format((int)$p['area_sqft']) : '—' ?></td><?php endforeach; ?>
                    </tr>
                    <!-- Furnishing -->
                    <tr>
                        <td>Furnishing</td>
                        <?php foreach ($items as $p): ?><td><?= e(ucfirst(str_replace('-', ' ', $p['furnishing'] ?? 'unfurnished'))) ?></td><?php endforeach; ?>
                    </tr>
                    <!-- Parking -->
                    <tr>
                        <td>Parking</td>
                        <?php foreach ($items as $p): ?><td><?= (isset($p['parking']) && $p['parking']) ? '✅ Yes' : '❌ No' ?></td><?php endforeach; ?>
                    </tr>
                    <!-- Floor -->
                    <tr>
                        <td>Floor</td>
                        <?php foreach ($items as $p): ?><td><?= isset($p['floor_number']) ? (int)$p['floor_number'] : '—' ?></td><?php endforeach; ?>
                    </tr>
                    <!-- Age -->
                    <tr>
                        <td>Age (years)</td>
                        <?php foreach ($items as $p): ?><td><?= isset($p['property_age']) ? (int)$p['property_age'] : '—' ?></td><?php endforeach; ?>
                    </tr>
                    <!-- Status -->
                    <tr>
                        <td>Status</td>
                        <?php foreach ($items as $p): ?>
                        <td><span class="status-pill status-<?= e($p['status']) ?>"><?= e(ucfirst($p['status'])) ?></span></td>
                        <?php endforeach; ?>
                    </tr>
                    <!-- View -->
                    <tr>
                        <td>Details</td>
                        <?php foreach ($items as $p): ?>
                        <td><a class="btn btn-small" href="<?= e(url('property-details.php?id=' . (int)$p['id'])) ?>">View ↗</a></td>
                        <?php endforeach; ?>
                    </tr>
                </tbody>
            </table>
        </div>

        <?php else: ?>

        <div class="cmp-empty">
            <div class="cmp-empty-icon">⚖️</div>
            <h2>Your compare list is empty</h2>
            <p>Browse properties and click <strong>Compare +</strong> to add them here for a side-by-side view.</p>
            <a class="btn" href="<?= e(url('properties.php')) ?>">Browse properties ↗</a>
        </div>

        <?php endif; ?>

    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
