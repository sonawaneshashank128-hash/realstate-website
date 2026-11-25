<?php
require_once __DIR__ . '/config/config.php';
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) { http_response_code(404); exit('Property not found.'); }

// ── POST handlers ────────────────────────────────────────────────────────────
if (is_post() && ($_POST['action'] ?? '') === 'submit_review') {
    require_login();
    verify_csrf();
    $rating = filter_input(INPUT_POST, 'rating', FILTER_VALIDATE_INT);
    $rtext  = trim($_POST['review_text'] ?? '');
    if (!$rating || $rating < 1 || $rating > 5) { flash('error', 'Select a star rating.'); redirect('property-details.php?id='.$id); }
    if (strlen($rtext) < 5 || strlen($rtext) > 1000) { flash('error', 'Review text must be 5–1000 chars.'); redirect('property-details.php?id='.$id); }
    $stmt = db()->prepare('INSERT IGNORE INTO reviews (property_id, user_id, rating, review_text) VALUES (?,?,?,?)');
    $stmt->execute([$id, current_user()['id'], $rating, $rtext]);
    flash('success', 'Review submitted! It will appear after admin approval.');
    redirect('property-details.php?id='.$id);
}

if (is_post() && ($_POST['action'] ?? '') === 'submit_report') {
    require_login();
    verify_csrf();
    $reason  = $_POST['reason'] ?? '';
    $details = trim($_POST['details'] ?? '');
    $allowed = ['fake','wrong_price','duplicate','inappropriate','other'];
    if (!in_array($reason, $allowed, true)) { flash('error', 'Invalid reason.'); redirect('property-details.php?id='.$id); }
    $details = mb_substr($details, 0, 500);
    $stmt = db()->prepare('INSERT INTO property_reports (property_id, user_id, reason, details) VALUES (?,?,?,?)');
    $stmt->execute([$id, current_user()['id'], $reason, $details]);
    flash('success', 'Report submitted. Our team will review it.');
    redirect('property-details.php?id='.$id);
}

$stmt = db()->prepare("SELECT p.*, u.name AS owner_name, u.email AS owner_email, u.phone AS owner_phone FROM properties p JOIN users u ON u.id=p.owner_id WHERE p.id=:id AND (p.status='approved' OR p.owner_id=:viewer OR :is_admin=1)");
$stmt->execute(['id'=>$id,'viewer'=>current_user()['id'] ?? 0,'is_admin'=>(current_user()['role'] ?? '')==='admin'?1:0]);
$property = $stmt->fetch();
if (!$property) { http_response_code(404); exit('Property not found or not available.'); }
$imgStmt = db()->prepare('SELECT image_path FROM property_images WHERE property_id=? ORDER BY id'); $imgStmt->execute([$id]); $storedImages = $imgStmt->fetchAll();
$images = [];
foreach ($storedImages as $image) {
    if (!property_photo($image['image_path'], $property['property_type'], (int)$property['id'])['illustrative']) {
        $images[] = $image;
    }
}
$hasIllustrativePhoto = !$images;
$isFavourite = false;
if (current_user()) {
    $favStmt = db()->prepare('SELECT 1 FROM favourites WHERE user_id=? AND property_id=?');
    $favStmt->execute([current_user()['id'], $id]);
    $isFavourite = (bool)$favStmt->fetchColumn();
}
$cityWord = preg_replace('/[^a-zA-Z].*/', '', trim($property['location']));
$simStmt = db()->prepare("SELECT p.id,p.title,p.price,p.location,p.listing_type,p.property_type,(SELECT pi.image_path FROM property_images pi WHERE pi.property_id=p.id ORDER BY pi.id LIMIT 1) AS cover_image FROM properties p WHERE p.location LIKE :loc AND p.property_type=:pt AND p.id<>:id AND p.status='approved' ORDER BY p.created_at DESC LIMIT 3");
$simStmt->execute(['loc'=>'%'.$cityWord.'%','pt'=>$property['property_type'],'id'=>$id]);
$similar = $simStmt->fetchAll();
$fullUrl = rtrim(BASE_URL,'/').'/property-details.php?id='.(int)$id;
$encodedLocation = urlencode($property['location']);

// ── Fetch reviews ─────────────────────────────────────────────────────────────
$revStmt = db()->prepare("SELECT r.*, u.name AS reviewer_name FROM reviews r JOIN users u ON u.id=r.user_id WHERE r.property_id=? AND r.status='approved' ORDER BY r.created_at DESC LIMIT 20");
$revStmt->execute([$id]);
$reviews   = $revStmt->fetchAll();
$avgRating = $reviews ? round(array_sum(array_column($reviews,'rating')) / count($reviews), 1) : null;

// ── Recently-viewed tracking ──────────────────────────────────────────────────
if (current_user()) {
    $rv = array_values(array_diff($_SESSION['recently_viewed'] ?? [], [$id]));
    array_unshift($rv, $id);
    $_SESSION['recently_viewed'] = array_slice($rv, 0, 10);
}

// ── OG meta tag variables ─────────────────────────────────────────────────────
$ogTitle = $property['title'];
$ogDesc  = mb_substr(strip_tags($property['description']), 0, 160);
$ogImage = property_image($images[0]['image_path'] ?? null, $property['property_type'], (int)$property['id']);
$ogUrl   = $fullUrl;
$ogType  = 'article';

$pageTitle = $property['title'];
require __DIR__ . '/includes/header.php';
?>
<section class="container detail-page">
  <a class="back-link" href="<?= e(url('properties.php')) ?>">← Back to properties</a>
  <div class="detail-heading">
    <div><span class="eyebrow"><?= e(ucfirst($property['property_type'])) ?> · <?= $property['listing_type']==='rent'?'For rent':'For sale' ?></span><h1><?= e($property['title']) ?></h1><p class="muted">⌖ <?= e($property['location']) ?></p></div>
    <div class="detail-price"><?= money($property['price']) ?><?= $property['listing_type']==='rent'?'<small>/ month</small>':'' ?></div>
  </div>

  <?php if ($images): ?>
  <div class="gallery gallery-grid">
    <?php foreach($images as $idx=>$image): ?>
    <?php $detailPhoto = property_photo($image['image_path'], $property['property_type'], (int)$property['id']); ?>
    <img src="<?= e($detailPhoto['src']) ?>" alt="<?= e($detailPhoto['illustrative'] ? 'Illustrative photo for ' : '') . e($property['title']) ?>" class="gallery-thumb<?= $idx===0?' gallery-main':'' ?>" data-gallery-src="<?= e($detailPhoto['src']) ?>" data-gallery-index="<?= $idx ?>">
    <?php endforeach; ?>
  </div>
  <?php else: ?>
  <div class="gallery gallery-grid"><img src="<?= e(property_image(null, $property['property_type'], (int)$property['id'])) ?>" alt="Illustrative property photo" class="gallery-main"></div>
  <?php endif; ?>
  <?php if ($hasIllustrativePhoto): ?><p class="property-photo-disclaimer">Illustrative photo shown where an owner photo is unavailable.</p><?php endif; ?>

  <!-- Lightbox -->
  <div id="lightbox" class="lightbox" aria-hidden="true">
    <button class="lightbox-close" id="lightbox-close" aria-label="Close">✕</button>
    <button class="lightbox-prev" id="lightbox-prev" aria-label="Previous">‹</button>
    <img id="lightbox-img" src="" alt="Full-size image">
    <button class="lightbox-next" id="lightbox-next" aria-label="Next">›</button>
  </div>

  <div class="detail-columns">
    <article class="detail-main">
      <div class="feature-strip">
        <span>🛏 <?= (int)$property['bedrooms'] ?> bedrooms</span>
        <span>🛁 <?= (int)$property['bathrooms'] ?> bathrooms</span>
        <span>▱ <?= number_format((int)$property['area_sqft']) ?> sq ft</span>
      </div>

      <!-- Map embed -->
      <div class="map-embed">
        <iframe loading="lazy" allowfullscreen referrerpolicy="no-referrer-when-downgrade"
          src="https://maps.google.com/maps?q=<?= $encodedLocation ?>&output=embed"
          width="100%" height="320" style="border:0;border-radius:8px;"></iframe>
      </div>

      <h2>About this property</h2>
      <p class="description"><?= nl2br(e($property['description'])) ?></p>

      <div class="emi-widget">
        <h3>🏦 EMI Estimate</h3>
        <div style="display:flex;gap:12px;flex-wrap:wrap">
          <label style="display:flex;flex-direction:column;gap:4px;font-size:.82rem;font-weight:700">Loan Amount (₹)<input id="emi-amount" class="emi-input" type="number" value="<?= (int)$property['price'] ?>" min="0" step="50000" style="border:1px solid var(--line);border-radius:8px;padding:8px 10px;width:180px"></label>
          <label style="display:flex;flex-direction:column;gap:4px;font-size:.82rem;font-weight:700">Interest Rate (%)<input id="emi-rate" class="emi-input" type="number" value="8.5" min="1" max="30" step="0.1" style="border:1px solid var(--line);border-radius:8px;padding:8px 10px;width:120px"></label>
          <label style="display:flex;flex-direction:column;gap:4px;font-size:.82rem;font-weight:700">Tenure (years)<input id="emi-tenure" class="emi-input" type="number" value="20" min="1" max="30" style="border:1px solid var(--line);border-radius:8px;padding:8px 10px;width:120px"></label>
        </div>
        <div class="emi-result-row" style="margin-top:14px">
          <div class="emi-result-card"><strong id="emi-monthly">—</strong><small>Monthly EMI</small></div>
          <div class="emi-result-card"><strong id="emi-total">—</strong><small>Total Payable</small></div>
          <div class="emi-result-card"><strong id="emi-interest">—</strong><small>Total Interest</small></div>
        </div>
        <p class="emi-full-calculator-cta">Want a clearer breakdown? <a class="emi-full-calculator-link" href="<?= e(url('emi-calculator.php?amount='.(int)$property['price'])) ?>">Open the full EMI Calculator <span aria-hidden="true">↗</span></a></p>
      </div>

      <div class="detail-actions">
        <?php if (current_user()): ?>
        <form method="post" action="<?= e(url('favourites.php')) ?>" class="inline-form"><?= csrf_field() ?><input type="hidden" name="property_id" value="<?= (int)$property['id'] ?>"><button class="btn btn-outline" type="submit" name="toggle_favourite"><?= $isFavourite ? '♥ Saved' : '♡ Save to favourites' ?></button></form>
        <?php else: ?><p><a href="<?= e(url('login.php')) ?>">Log in</a> to save this property.</p><?php endif; ?>
        <button class="btn btn-outline" id="share-btn" data-url="<?= e($fullUrl) ?>">Share ↗</button>
      </div>
    </article>

    <aside class="contact-card">
      <span class="eyebrow">Property contact</span>
      <h2><?= e($property['owner_name']) ?></h2>
      <p class="muted">Owner / listing contact</p>
      <?php if (!empty($property['owner_phone'])): ?><p>☎ <?= e($property['owner_phone']) ?></p><?php endif; ?>
      <?php if (!empty($property['owner_phone'])): ?>
      <a class="btn btn-full btn-whatsapp" style="margin-top:8px"
         href="<?= e('https://wa.me/91'.preg_replace('/[^0-9]/','',e($property['owner_phone'])).'?text='.urlencode('Hi, I am interested in '.$property['title'].' listed on EstateHub: '.$fullUrl)) ?>"
         target="_blank" rel="noopener">💬 WhatsApp Owner</a>
      <?php endif; ?>
      <?php if (current_user() && (int)current_user()['id'] !== (int)$property['owner_id']): ?>
      <form method="post" action="<?= e(url('contact.php')) ?>"><?= csrf_field() ?><input type="hidden" name="property_id" value="<?= (int)$property['id'] ?>"><label>Your message<textarea name="message" rows="4" required maxlength="1500" placeholder="Hello, I am interested in this property..."></textarea></label><button class="btn btn-full" type="submit">Send inquiry ↗</button></form>
      <?php elseif (!current_user()): ?><a class="btn btn-full" href="<?= e(url('login.php')) ?>">Log in to contact</a>
      <?php else: ?><p class="muted">This is your listing.</p><?php endif; ?>
    </aside>
  </div>

  <?php if ($similar): ?>
  <div class="similar-properties">
    <h2>Similar properties</h2>
    <div class="property-grid">
      <?php foreach($similar as $p): ?>
      <?php $similarPhoto = property_photo($p['cover_image'], $p['property_type'], (int)$p['id']); ?>
      <article class="property-card">
        <a class="property-photo" href="<?= e(url('property-details.php?id='.(int)$p['id'])) ?>"><img src="<?= e($similarPhoto['src']) ?>" alt="<?= e($similarPhoto['illustrative'] ? 'Illustrative photo for ' : '') . e($p['title']) ?>"><?php if ($similarPhoto['illustrative']): ?><span class="property-image-note">Illustrative photo</span><?php endif; ?><span class="tag"><?= $p['listing_type']==='rent'?'For rent':'For sale' ?></span></a>
        <div class="property-info"><div class="price"><?= money($p['price']) ?><?= $p['listing_type']==='rent'?'<small>/ month</small>':'' ?></div><h3><a href="<?= e(url('property-details.php?id='.(int)$p['id'])) ?>"><?= e($p['title']) ?></a></h3><p class="muted">⌖ <?= e($p['location']) ?></p></div>
      </article>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

  <div class="review-section" style="margin-top:50px">
    <div style="display:flex;align-items:center;gap:16px;margin-bottom:20px;flex-wrap:wrap">
      <h2 style="margin:0">Reviews</h2>
      <?php if ($avgRating): ?>
      <span class="avg-rating">★ <?= $avgRating ?> (<?= count($reviews) ?> review<?= count($reviews)!==1?'s':'' ?>)</span>
      <?php endif; ?>
    </div>
    <?php if ($reviews): ?>
    <div class="review-list">
      <?php foreach ($reviews as $rev): ?>
      <div class="review-card">
        <div class="review-stars"><?= str_repeat('★',(int)$rev['rating']).str_repeat('☆',5-(int)$rev['rating']) ?></div>
        <p class="review-text"><?= e($rev['review_text']) ?></p>
        <div class="review-author"><?= e($rev['reviewer_name']) ?></div>
        <div class="review-date"><?= date('d M Y', strtotime($rev['created_at'])) ?></div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php else: ?>
    <p class="muted">No reviews yet. Be the first to review this property.</p>
    <?php endif; ?>

    <?php if (current_user() && (int)current_user()['id'] !== (int)$property['owner_id']): ?>
    <div style="margin-top:30px;background:white;border:1px solid var(--line);border-radius:14px;padding:24px">
      <h3 style="margin:0 0 14px;font:800 1.1rem Manrope">Write a Review</h3>
      <form method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="submit_review">
        <div class="star-input" style="margin-bottom:14px">
          <?php for($s=5;$s>=1;$s--): ?>
          <input type="radio" id="star<?= $s ?>" name="rating" value="<?= $s ?>">
          <label for="star<?= $s ?>">★</label>
          <?php endfor; ?>
        </div>
        <label style="display:flex;flex-direction:column;gap:6px;font-size:.83rem;font-weight:700;margin-bottom:14px">Your review<textarea name="review_text" rows="4" maxlength="1000" placeholder="Share your experience with this property..." style="border:1px solid var(--line);border-radius:9px;padding:11px 12px;resize:vertical" required></textarea></label>
        <button class="btn" type="submit">Submit Review</button>
      </form>
    </div>
    <?php endif; ?>
  </div>

  <?php if (current_user() && (int)current_user()['id'] !== (int)$property['owner_id']): ?>
  <div style="text-align:center;margin:20px 0">
    <button class="btn btn-outline btn-small" id="report-btn" style="color:var(--danger);border-color:var(--danger)">⚠ Report this listing</button>
  </div>
  <div id="report-modal" class="report-modal" aria-hidden="true">
    <div class="report-modal-inner">
      <button class="report-modal-close" id="report-modal-close" type="button">×</button>
      <h3>Report this listing</h3>
      <form method="post" id="report-form">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="submit_report">
        <label style="display:flex;flex-direction:column;gap:6px;font-size:.83rem;font-weight:700;margin-bottom:14px">Reason<select name="reason" required style="border:1px solid var(--line);border-radius:9px;padding:11px 12px"><option value="">Select a reason</option><option value="fake">Fake listing</option><option value="wrong_price">Wrong price</option><option value="duplicate">Duplicate listing</option><option value="inappropriate">Inappropriate content</option><option value="other">Other</option></select></label>
        <label style="display:flex;flex-direction:column;gap:6px;font-size:.83rem;font-weight:700;margin-bottom:14px">Details (optional)<textarea name="details" rows="3" maxlength="500" placeholder="Any additional details..." style="border:1px solid var(--line);border-radius:9px;padding:11px 12px;resize:vertical"></textarea></label>
        <button class="btn btn-danger btn-full" type="submit">Submit Report</button>
      </form>
    </div>
  </div>
  <?php endif; ?>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
