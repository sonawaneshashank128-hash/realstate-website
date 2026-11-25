<?php
require_once __DIR__ . '/config/config.php';
require_login();
$user = current_user();
$pageTitle = 'My Dashboard';

if ($user['role'] === 'admin') redirect('admin/index.php');

if ($user['role'] === 'owner') {
    $stmt = db()->prepare("SELECT p.*, (SELECT pi.image_path FROM property_images pi WHERE pi.property_id=p.id ORDER BY pi.id LIMIT 1) AS cover_image FROM properties p WHERE p.owner_id=? ORDER BY p.created_at DESC");
    $stmt->execute([$user['id']]);
    $items = $stmt->fetchAll();

    $countStmt = db()->prepare('SELECT COUNT(*) FROM inquiries i JOIN properties p ON p.id=i.property_id WHERE p.owner_id=?');
    $countStmt->execute([$user['id']]);
    $inquiryCount = (int)$countStmt->fetchColumn();

    $approvedCount = count(array_filter($items, fn($p) => $p['status'] === 'approved'));
    $pendingCount  = count(array_filter($items, fn($p) => $p['status'] === 'pending'));
} else {
    $stmt = db()->prepare("SELECT p.*, (SELECT pi.image_path FROM property_images pi WHERE pi.property_id=p.id ORDER BY pi.id LIMIT 1) AS cover_image FROM favourites f JOIN properties p ON p.id=f.property_id WHERE f.user_id=? ORDER BY f.created_at DESC");
    $stmt->execute([$user['id']]);
    $items         = $stmt->fetchAll();
    $inquiryCount  = 0;
    $approvedCount = 0;
    $pendingCount  = 0;
}

$memberSince = date('M Y', strtotime(
    db()->prepare('SELECT created_at FROM users WHERE id=?')
        ->execute([$user['id']]) ? '2024-01-01' : '2024-01-01'
));
// Fetch actual join date
$uStmt = db()->prepare('SELECT created_at FROM users WHERE id=?');
$uStmt->execute([$user['id']]);
$memberSince = date('M Y', strtotime($uStmt->fetchColumn() ?: 'now'));

require __DIR__ . '/includes/header.php';
?>

<!-- ══════════════════════════════════════════════════════════════
     DASHBOARD VIDEO HERO
══════════════════════════════════════════════════════════════ -->
<section class="db-hero">

  <!-- Looping background video -->
  <video class="db-hero-video" autoplay muted loop playsinline>
    <source src="https://cdn.coverr.co/videos/coverr-aerial-view-of-a-city-4518/1080p.mp4" type="video/mp4">
    <!-- fallback sources -->
    <source src="https://cdn.coverr.co/videos/coverr-buildings-in-a-city-6756/1080p.mp4" type="video/mp4">
  </video>

  <!-- Animated CSS cityscape fallback (runs always, video plays on top when available) -->
  <canvas class="db-hero-canvas" id="cityCanvas"></canvas>

  <!-- Gradient overlay -->
  <div class="db-hero-overlay"></div>

  <!-- Hero content -->
  <div class="container db-hero-content">
    <div class="db-hero-left">
      <div class="db-greeting-badge">
        <span class="db-role-dot"></span>
        <?= ucfirst(e($user['role'])) ?> account
      </div>
      <h1 class="db-greeting">
        <?php
        $hour = (int)date('G');
        $greet = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
        echo $greet . ',';
        ?>
        <br><span class="db-name"><?= e($user['name']) ?>.</span>
      </h1>
      <p class="db-subtitle">
        <?= $user['role'] === 'owner'
          ? 'Manage your listings, track inquiries and grow your reach.'
          : 'Your saved properties and search history, all in one place.' ?>
      </p>
      <div class="db-hero-actions">
        <?php if ($user['role'] === 'owner'): ?>
          <a class="btn db-btn-primary" href="<?= e(url('add-property.php')) ?>">＋ Add new listing</a>
          <a class="btn db-btn-glass" href="<?= e(url('properties.php')) ?>">Browse market ↗</a>
        <?php else: ?>
          <a class="btn db-btn-primary" href="<?= e(url('properties.php')) ?>">Explore properties ↗</a>
          <a class="btn db-btn-glass" href="<?= e(url('profile.php')) ?>">Edit profile</a>
        <?php endif; ?>
      </div>
    </div>

    <!-- Glassmorphism stat cards -->
    <div class="db-stat-cards">
      <?php if ($user['role'] === 'owner'): ?>
        <div class="db-stat-glass">
          <div class="db-stat-icon">🏘️</div>
          <div class="db-stat-val" data-count="<?= count($items) ?>"><?= count($items) ?></div>
          <div class="db-stat-lbl">Total Listings</div>
        </div>
        <div class="db-stat-glass">
          <div class="db-stat-icon">✅</div>
          <div class="db-stat-val" data-count="<?= $approvedCount ?>"><?= $approvedCount ?></div>
          <div class="db-stat-lbl">Live & Approved</div>
        </div>
        <div class="db-stat-glass">
          <div class="db-stat-icon">⏳</div>
          <div class="db-stat-val" data-count="<?= $pendingCount ?>"><?= $pendingCount ?></div>
          <div class="db-stat-lbl">Pending Review</div>
        </div>
        <div class="db-stat-glass">
          <div class="db-stat-icon">💬</div>
          <div class="db-stat-val" data-count="<?= $inquiryCount ?>"><?= $inquiryCount ?></div>
          <div class="db-stat-lbl">Total Inquiries</div>
        </div>
      <?php else: ?>
        <div class="db-stat-glass">
          <div class="db-stat-icon">❤️</div>
          <div class="db-stat-val" data-count="<?= count($items) ?>"><?= count($items) ?></div>
          <div class="db-stat-lbl">Saved Properties</div>
        </div>
        <div class="db-stat-glass">
          <div class="db-stat-icon">📅</div>
          <div class="db-stat-val" style="font-size:1.1rem"><?= e($memberSince) ?></div>
          <div class="db-stat-lbl">Member Since</div>
        </div>
        <div class="db-stat-glass">
          <div class="db-stat-icon">🔍</div>
          <div class="db-stat-val">∞</div>
          <div class="db-stat-lbl">Properties Available</div>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Scroll indicator -->
  <div class="db-scroll-hint">
    <span>Scroll to manage</span>
    <div class="db-scroll-arrow">↓</div>
  </div>
</section>

<!-- ══════════════════════════════════════════════════════════════
     MAIN DASHBOARD BODY
══════════════════════════════════════════════════════════════ -->
<div class="db-body">
  <div class="container db-body-inner">

    <!-- QUICK ACTIONS BAR -->
    <div class="db-quick-bar">
      <?php if ($user['role'] === 'owner'): ?>
        <a class="db-quick-btn" href="<?= e(url('add-property.php')) ?>"><span>＋</span> Add listing</a>
        <a class="db-quick-btn" href="<?= e(url('properties.php')) ?>"><span>🔍</span> Browse market</a>
        <a class="db-quick-btn" href="<?= e(url('profile.php')) ?>"><span>👤</span> Edit profile</a>
        <a class="db-quick-btn" href="<?= e(url('contact.php')) ?>"><span>📬</span> Contact support</a>
      <?php else: ?>
        <a class="db-quick-btn" href="<?= e(url('properties.php')) ?>"><span>🔍</span> Browse properties</a>
        <a class="db-quick-btn" href="<?= e(url('properties.php?listing_type=rent')) ?>"><span>🏢</span> For rent</a>
        <a class="db-quick-btn" href="<?= e(url('properties.php?listing_type=sale')) ?>"><span>🏠</span> For sale</a>
        <a class="db-quick-btn" href="<?= e(url('profile.php')) ?>"><span>👤</span> Edit profile</a>
      <?php endif; ?>
    </div>

    <?php if (($user['plan'] ?? 'basic') === 'basic' && empty($_SESSION['upgrade_banner_shown'])): ?>
    <?php $_SESSION['upgrade_banner_shown'] = true; ?>
    <div class="db-upgrade-banner">
      <p>⭐ <strong>Upgrade to Premium</strong> for priority placement, featured badges, and 5× more listings.</p>
      <a class="btn" href="<?= e(url('pricing.php')) ?>">See plans ↗</a>
    </div>
    <?php endif; ?>

    <!-- LISTINGS / FAVOURITES SECTION -->
    <div class="db-section">
      <div class="db-section-head">
        <div>
          <h2><?= $user['role'] === 'owner' ? 'Your listings' : 'Saved properties' ?></h2>
          <p class="muted"><?= $user['role'] === 'owner' ? 'All properties you have submitted' : 'Properties you have saved for later' ?></p>
        </div>
        <?php if ($user['role'] === 'owner'): ?>
          <a class="btn btn-small" href="<?= e(url('add-property.php')) ?>">＋ Add new</a>
        <?php else: ?>
          <a class="btn btn-small" href="<?= e(url('properties.php')) ?>">Browse more ↗</a>
        <?php endif; ?>
      </div>

      <?php if ($items): ?>
        <div class="db-property-grid">
          <?php foreach ($items as $p): ?>
            <?php $listingPhoto = property_photo($p['cover_image'], $p['property_type'], (int)$p['id']); ?>
            <article class="db-prop-card">
              <a class="db-prop-photo" href="<?= e(url('property-details.php?id=' . (int)$p['id'])) ?>">
                <img src="<?= e($listingPhoto['src']) ?>" alt="<?= e($listingPhoto['illustrative'] ? 'Illustrative photo for ' : '') . e($p['title']) ?>" loading="lazy">
                <?php if ($listingPhoto['illustrative']): ?><span class="property-image-note">Illustrative photo</span><?php endif; ?>
                <span class="db-status-badge db-status-<?= e($p['status']) ?>"><?= e(ucfirst($p['status'])) ?></span>
                <span class="db-prop-type-tag"><?= ['house'=>'🏠','apartment'=>'🏢','land'=>'🌿','commercial'=>'🏬'][$p['property_type']] ?? '🏠' ?></span>
              </a>
              <div class="db-prop-info">
                <div class="db-prop-price">
                  <?= money($p['price']) ?>
                  <?php if ($p['listing_type'] === 'rent'): ?><small>/ mo</small><?php endif; ?>
                </div>
                <h3><?= e($p['title']) ?></h3>
                <p class="muted">📍 <?= e($p['location']) ?></p>
                <div class="db-prop-meta">
                  <?php if ((int)$p['bedrooms'] > 0): ?><span>🛏 <?= (int)$p['bedrooms'] ?></span><?php endif; ?>
                  <?php if ((int)$p['bathrooms'] > 0): ?><span>🚿 <?= (int)$p['bathrooms'] ?></span><?php endif; ?>
                  <?php if ((int)$p['area_sqft'] > 0): ?><span>📐 <?= number_format((int)$p['area_sqft']) ?> sqft</span><?php endif; ?>
                </div>
                <div class="db-prop-actions">
                  <a class="db-prop-link" href="<?= e(url('property-details.php?id=' . (int)$p['id'])) ?>">View ↗</a>
                  <?php if ($user['role'] === 'owner'): ?>
                    <a class="db-prop-link" href="<?= e(url('edit-property.php?id=' . (int)$p['id'])) ?>">Edit ✎</a>
                    <form method="post" action="<?= e(url('delete-property.php')) ?>" class="delete-form" data-confirm="Permanently delete this listing?">
                      <?= csrf_field() ?>
                      <input type="hidden" name="property_id" value="<?= (int)$p['id'] ?>">
                      <button class="db-prop-delete" type="submit">Delete</button>
                    </form>
                  <?php endif; ?>
                </div>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <div class="db-empty">
          <div class="db-empty-icon"><?= $user['role'] === 'owner' ? '🏗️' : '❤️' ?></div>
          <h3><?= $user['role'] === 'owner' ? 'No listings yet' : 'No saved properties yet' ?></h3>
          <p><?= $user['role'] === 'owner' ? 'Create your first listing — it only takes 2 minutes.' : 'Browse properties and tap the heart to save them here.' ?></p>
          <a class="btn" href="<?= e(url($user['role'] === 'owner' ? 'add-property.php' : 'properties.php')) ?>">
            <?= $user['role'] === 'owner' ? '＋ Add your first listing' : 'Explore properties' ?>
          </a>
        </div>
      <?php endif; ?>
    </div>

    <!-- INQUIRIES SECTION (owners only) -->
    <?php if ($user['role'] === 'owner'): ?>
      <?php
        $inq = db()->prepare('SELECT i.*,p.title AS prop_title,u.name AS sender_name,u.email AS sender_email,u.phone AS sender_phone FROM inquiries i JOIN properties p ON p.id=i.property_id JOIN users u ON u.id=i.user_id WHERE p.owner_id=? ORDER BY i.created_at DESC LIMIT 20');
        $inq->execute([$user['id']]);
        $inquiries = $inq->fetchAll();
      ?>
      <div class="db-section">
        <div class="db-section-head">
          <div>
            <h2>Recent inquiries</h2>
            <p class="muted">People interested in your properties</p>
          </div>
          <?php if ($inquiryCount > 0): ?>
            <span class="db-inq-count"><?= $inquiryCount ?> total</span>
          <?php endif; ?>
        </div>

        <?php if ($inquiries): ?>
          <div class="db-inq-list">
            <?php foreach ($inquiries as $i): ?>
              <div class="db-inq-card">
                <div class="db-inq-avatar"><?= strtoupper(substr($i['sender_name'], 0, 2)) ?></div>
                <div class="db-inq-body">
                  <div class="db-inq-top">
                    <strong><?= e($i['sender_name']) ?></strong>
                    <span class="db-inq-prop">re: <?= e($i['prop_title']) ?></span>
                    <time class="db-inq-time"><?= date('d M Y', strtotime($i['created_at'])) ?></time>
                  </div>
                  <p class="db-inq-msg"><?= e($i['message']) ?></p>
                  <div class="db-inq-contact">
                    <a href="mailto:<?= e($i['sender_email']) ?>">✉ <?= e($i['sender_email']) ?></a>
                    <?php if (!empty($i['sender_phone'])): ?>
                      <a href="tel:<?= e($i['sender_phone']) ?>">📞 <?= e($i['sender_phone']) ?></a>
                    <?php endif; ?>
                  </div>
                </div>
                <span class="status-pill status-<?= e($i['status']) ?>"><?= ucfirst($i['status']) ?></span>
              </div>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <div class="db-empty db-empty-sm">
            <div class="db-empty-icon">📭</div>
            <h3>No inquiries yet</h3>
            <p>When people contact you about your listings, their messages will appear here.</p>
          </div>
        <?php endif; ?>
      </div>
    <?php endif; ?>

  </div><!-- /db-body-inner -->
</div><!-- /db-body -->

<?php
$rvDbProps = [];
$rvDb = array_slice($_SESSION['recently_viewed'] ?? [], 0, 5);
if (!empty($rvDb)):
    $rvDbIds = array_map('intval', $rvDb);
    $inPhDb  = implode(',', array_fill(0, count($rvDbIds), '?'));
    $rvDbStmt = db()->prepare("SELECT p.id, p.title, p.price, p.location, p.listing_type, p.property_type, (SELECT pi.image_path FROM property_images pi WHERE pi.property_id=p.id ORDER BY pi.id LIMIT 1) AS cover_image FROM properties p WHERE p.id IN ($inPhDb) AND p.status='approved'");
    $rvDbStmt->execute($rvDbIds);
    $rvDbProps = $rvDbStmt->fetchAll();
endif;
?>
<div class="db-body">
  <div class="container db-body-inner">
<?php if (!empty($rvDbProps)): ?>
    <div class="db-section">
      <div class="db-section-head"><div><h2>Recently viewed</h2><p class="muted">Properties you explored recently</p></div><a class="btn btn-small btn-outline" href="<?= e(url('properties.php')) ?>">Browse more ↗</a></div>
      <div class="db-property-grid">
        <?php foreach ($rvDbProps as $p): ?>
        <?php $recentPhoto = property_photo($p['cover_image'], $p['property_type'], (int)$p['id']); ?>
        <article class="db-prop-card">
          <a class="db-prop-photo" href="<?= e(url('property-details.php?id='.(int)$p['id'])) ?>"><img src="<?= e($recentPhoto['src']) ?>" alt="<?= e($recentPhoto['illustrative'] ? 'Illustrative photo for ' : '') . e($p['title']) ?>" loading="lazy"><?php if ($recentPhoto['illustrative']): ?><span class="property-image-note">Illustrative photo</span><?php endif; ?></a>
          <div class="db-prop-info">
            <div class="db-prop-price"><?= money($p['price']) ?><?= $p['listing_type']==='rent'?'<small>/mo</small>':'' ?></div>
            <h3><?= e($p['title']) ?></h3>
            <p class="muted">📍 <?= e($p['location']) ?></p>
            <div class="db-prop-actions"><a class="db-prop-link" href="<?= e(url('property-details.php?id='.(int)$p['id'])) ?>">View ↗</a></div>
          </div>
        </article>
        <?php endforeach; ?>
      </div>
    </div>
<?php endif; ?>
  </div><!-- /db-body-inner -->
</div><!-- /recently-viewed db-body -->

<?php require __DIR__ . '/includes/footer.php'; ?>
