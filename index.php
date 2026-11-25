<?php
require_once __DIR__ . '/config/config.php';
$pageTitle = 'Find your next place';
$stmt = db()->query("SELECT p.*, u.name AS owner_name,
    (SELECT pi.image_path FROM property_images pi WHERE pi.property_id=p.id ORDER BY pi.id LIMIT 1) AS cover_image
    FROM properties p JOIN users u ON u.id=p.owner_id
    WHERE p.status='approved' ORDER BY p.featured DESC, p.created_at DESC LIMIT 6");
$featured = $stmt->fetchAll();

$homeStats = db()->query(
    "SELECT COUNT(*) AS listings, COUNT(DISTINCT location) AS locations,
            COUNT(DISTINCT owner_id) AS owners
     FROM properties
     WHERE status='approved'"
)->fetch();

$categoryRows = db()->query(
    "SELECT p.property_type, COUNT(*) AS listing_count
     FROM properties p
     WHERE p.status='approved'
     GROUP BY p.property_type"
)->fetchAll();
$categoryData = [];
foreach ($categoryRows as $row) {
    $categoryData[$row['property_type']] = $row;
}

$popularLocations = db()->query(
    "SELECT p.location, COUNT(*) AS listing_count
     FROM properties p
     WHERE p.status='approved' AND p.location <> ''
     GROUP BY p.location
     ORDER BY listing_count DESC, p.location ASC
     LIMIT 12"
)->fetchAll();

$approvedReviews = db()->query(
    "SELECT r.rating, r.review_text, r.created_at, u.name AS reviewer_name,
            p.title AS property_title, p.location
     FROM reviews r
     JOIN users u ON u.id=r.user_id
     JOIN properties p ON p.id=r.property_id
     WHERE r.status='approved' AND r.review_text <> ''
     ORDER BY r.created_at DESC
     LIMIT 3"
)->fetchAll();
$homeStats['reviews'] = (int)db()->query(
    "SELECT COUNT(*) FROM reviews WHERE status='approved' AND review_text <> ''"
)->fetchColumn();

require __DIR__ . '/includes/header.php';
?>
<div class="editorial-home">
<section class="editorial-hero">
  <div class="container">
    <div class="editorial-intro">
      <span class="eyebrow">A more considered way to find home</span>
      <h1>Find a place<br>to call <em>yours.</em></h1>
      <p>Explore homes and spaces listed by their owners, compare the details, and make your next move at your own pace.</p>
    </div>
    <div class="editorial-hero-photo">
      <img src="<?= e(url('assets/images/estate-hero.jpg')) ?>" alt="Illustrative photograph of a contemporary home">
      <div class="editorial-photo-caption">
        <span>Illustrative property photography</span>
        <strong>Find a place to call yours</strong>
      </div>
      <span class="editorial-photo-index">ESTATEHUB&nbsp; / &nbsp;01</span>
    </div>
    <form class="editorial-search" method="get" action="<?= e(url('properties.php')) ?>">
      <label class="search-field"><span>Location</span><input name="location" placeholder="City or neighbourhood" autocomplete="off"></label>
      <label class="search-field"><span>Looking to</span>
        <select name="listing_type">
          <option value="">Buy or rent</option>
          <option value="sale">Buy</option>
          <option value="rent">Rent</option>
        </select>
      </label>
      <label class="search-field"><span>Property type</span>
        <select name="property_type">
          <option value="">All types</option>
          <option value="house">House</option>
          <option value="apartment">Apartment</option>
          <option value="land">Land</option>
          <option value="commercial">Commercial</option>
        </select>
      </label>
      <button class="btn" type="submit">Explore properties <span aria-hidden="true">↗</span></button>
    </form>
  </div>
</section>
<section id="featured" class="section container">
  <div class="section-heading"><div><span class="eyebrow">A closer look</span><h2>Selected homes</h2></div><a class="text-link" href="<?= e(url('properties.php')) ?>">Explore all properties ↗</a></div>
  <?php if ($featured): ?><div class="property-grid">
    <?php foreach ($featured as $p): ?>
      <?php $homePhoto = property_photo($p['cover_image'], $p['property_type'], (int)$p['id']); ?>
      <article class="property-card">
        <a class="property-photo" href="<?= e(url('property-details.php?id=' . (int)$p['id'])) ?>">
          <img src="<?= e($homePhoto['src']) ?>" alt="<?= $homePhoto['illustrative'] ? 'Illustrative ' : '' ?>property photograph for <?= e($p['title']) ?>">
          <span class="tag"><?= $p['listing_type']==='rent'?'For rent':'For sale' ?></span>
          <?php if ($homePhoto['illustrative']): ?><span class="tag home-photo-note">Illustrative photo</span><?php endif; ?>
          <?php if (!empty($p['featured'])): ?><span class="tag" style="top:44px;background:var(--gold);color:#fff">⭐ Featured</span><?php endif; ?>
        </a>
        <div class="property-info"><div class="price"><?= money($p['price']) ?><?= $p['listing_type']==='rent'?'<small>/ month</small>':'' ?></div><h3><a href="<?= e(url('property-details.php?id='.(int)$p['id'])) ?>"><?= e($p['title']) ?></a></h3><p class="muted">⌖ <?= e($p['location']) ?></p><div class="property-meta"><span><?= (int)$p['bedrooms'] ?> beds</span><span><?= (int)$p['bathrooms'] ?> baths</span><span><?= number_format((int)$p['area_sqft']) ?> sq ft</span></div></div>
      </article>
    <?php endforeach; ?>
  </div><?php else: ?><div class="empty-state"><h3>Your property journey starts here</h3><p>No approved listings yet. Register as an owner to submit the first property.</p><a class="btn" href="<?= e(url('register.php')) ?>">Get started</a></div><?php endif; ?>
</section>
<?php
$categories = [
    'house' => ['label' => 'Houses', 'icon' => '01'],
    'apartment' => ['label' => 'Apartments', 'icon' => '02'],
    'land' => ['label' => 'Land & plots', 'icon' => '03'],
    'commercial' => ['label' => 'Commercial', 'icon' => '04'],
];
$categoryImages = [
    'house' => 'estate-house.jpg',
    'apartment' => 'estate-apartment.jpg',
    'land' => 'estate-land.jpg',
    'commercial' => 'estate-commercial.jpg',
];
?>
<section class="section bg-white">
  <div class="container">
    <div class="section-heading"><div><span class="eyebrow">What are you looking for?</span><h2>Browse by category</h2></div></div>
    <div class="category-grid">
      <?php foreach ($categories as $type => $category):
          $data = $categoryData[$type] ?? null;
          $count = (int)($data['listing_count'] ?? 0);
      ?>
      <a class="category-card" href="<?= e(url('properties.php?property_type=' . $type)) ?>">
        <img class="cat-bg" src="<?= e(url('assets/images/' . $categoryImages[$type])) ?>" alt="" loading="lazy">
        <div class="cat-overlay"></div>
        <div class="cat-content">
          <span class="cat-icon-big" aria-hidden="true"><?= $category['icon'] ?></span>
          <strong><?= e($category['label']) ?></strong>
          <span><?= number_format($count) ?> listing<?= $count !== 1 ? 's' : '' ?></span>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
    <p class="editorial-photo-credit">Representative photos are illustrative, not photos of a specific listing. Photography: <a href="https://www.pexels.com/license/" target="_blank" rel="noopener noreferrer">Pexels</a> and <a href="https://unsplash.com/license" target="_blank" rel="noopener noreferrer">Unsplash</a>.</p>
  </div>
</section>

<?php if ($popularLocations): ?>
<section class="section editorial-locations">
  <div class="container">
    <div class="section-heading"><div><span class="eyebrow">Explore by location</span><h2>Places with homes to explore</h2></div></div>
    <div class="city-grid">
      <?php foreach ($popularLocations as $location): ?>
      <?php $cityPhoto = location_photo($location['location']); ?>
      <a class="city-card" href="<?= e(url('properties.php?location=' . urlencode($location['location']))) ?>">
        <img class="city-bg" src="<?= e($cityPhoto) ?>" alt="Illustrative city view for <?= e($location['location']) ?>" loading="lazy" decoding="async">
        <span class="city-overlay" aria-hidden="true"></span>
        <div class="city-content">
          <span class="city-icon" aria-hidden="true">⌖</span>
          <strong><?= e($location['location']) ?></strong>
          <span><?= number_format((int)$location['listing_count']) ?> listing<?= (int)$location['listing_count'] !== 1 ? 's' : '' ?></span>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
    <p class="editorial-photo-credit">City imagery is representative and may be photographic or illustrated. Photography: <a href="https://www.pexels.com/license/" target="_blank" rel="noopener noreferrer">Pexels</a>.</p>
  </div>
</section>
<?php endif; ?>
<section class="stats-band">
  <div class="container stats-band-inner">
    <div class="stat-item"><strong><?= number_format((int)$homeStats['listings']) ?></strong><span>Available properties</span></div>
    <div class="stat-item"><strong><?= number_format((int)$homeStats['owners']) ?></strong><span>Listing owners</span></div>
    <div class="stat-item"><strong><?= number_format((int)$homeStats['locations']) ?></strong><span>Locations</span></div>
    <div class="stat-item"><strong><?= number_format((int)$homeStats['reviews']) ?></strong><span>Published reviews</span></div>
  </div>
</section>
<!-- ── ABOUT US SECTION ───────────────────────────────────────────── -->
<section class="home-about">
  <div class="container home-about-inner">
    <div class="home-about-images">
      <img class="home-about-img-main" src="<?= e(url('assets/images/about-home.jpg')) ?>" alt="Illustrative photograph of a contemporary home">
      <img class="home-about-img-small" src="<?= e(url('assets/images/about-interior.jpg')) ?>" alt="Illustrative photograph of a contemporary living room">
      <div class="home-about-badge"><strong><?= number_format((int)$homeStats['listings']) ?></strong><span>Available listings</span></div>
    </div>
    <div class="home-about-copy">
      <span class="eyebrow">About EstateHub</span>
      <h2>A clearer way to find your next property.</h2>
      <p>Browse homes for sale and rent, compare the details that matter, and contact listing owners through EstateHub. Property details come from listings published on the platform.</p>
      <p>New listings are reviewed by an administrator before they appear publicly. You can save favourites, compare options and send an inquiry when you are ready.</p>
      <div class="home-about-pillars">
        <div class="pillar"><span class="pillar-icon" aria-hidden="true">⌕</span><div><strong>Search with purpose</strong><small>Filter properties by location, type, budget and more.</small></div></div>
        <div class="pillar"><span class="pillar-icon" aria-hidden="true">✓</span><div><strong>Reviewed listings</strong><small>Submissions go through an approval step before publication.</small></div></div>
        <div class="pillar"><span class="pillar-icon" aria-hidden="true">♡</span><div><strong>Keep your shortlist</strong><small>Save properties and compare your options.</small></div></div>
        <div class="pillar"><span class="pillar-icon" aria-hidden="true">↗</span><div><strong>Contact the owner</strong><small>Send an inquiry from the property details page.</small></div></div>
      </div>
      <a class="btn" href="<?= e(url('about.php')) ?>">Read our full story ↗</a>
    </div>
  </div>
</section>

<?php if ($approvedReviews): ?>
<section class="section bg-white">
  <div class="container">
    <div class="section-heading"><div><span class="eyebrow">From the community</span><h2>Published property reviews</h2></div></div>
    <div class="testimonial-grid">
      <?php foreach ($approvedReviews as $review): ?>
      <article class="testimonial-card">
        <div class="testimonial-stars" aria-label="<?= (int)$review['rating'] ?> out of 5 stars"><?= str_repeat('★', (int)$review['rating']) . str_repeat('☆', 5 - (int)$review['rating']) ?></div>
        <p><?= e($review['review_text']) ?></p>
        <div class="testimonial-author">
          <div class="t-avatar" aria-hidden="true"><?= e(mb_strtoupper(mb_substr($review['reviewer_name'], 0, 1))) ?></div>
          <div>
            <strong><?= e($review['reviewer_name']) ?></strong>
            <small>Review for <?= e($review['property_title']) ?> · <?= e($review['location']) ?></small>
          </div>
        </div>
      </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<section class="cta-band"><div class="container cta-inner"><div><span class="eyebrow">Have a property?</span><h2>Put your place in the spotlight.</h2><p>Create a listing and connect with interested people.</p></div><a class="btn btn-light" href="<?= e(url(current_user()?'add-property.php':'register.php')) ?>">List your property ↗</a></div></section>
<?php
$rvProps = [];
$rv = $_SESSION['recently_viewed'] ?? [];
if (current_user() && !empty($rv)):
    $rvIds = array_map('intval', array_slice($rv, 0, 6));
    $inPh  = implode(',', array_fill(0, count($rvIds), '?'));
    $rvStmt = db()->prepare("SELECT p.*, (SELECT pi.image_path FROM property_images pi WHERE pi.property_id=p.id ORDER BY pi.id LIMIT 1) AS cover_image FROM properties p WHERE p.id IN ($inPh) AND p.status='approved'");
    $rvStmt->execute($rvIds);
    $rvProps = $rvStmt->fetchAll();
endif;
?>
<?php if (!empty($rvProps)): ?>
<section class="section recently-viewed">
  <div class="container">
    <div class="section-heading"><div><span class="eyebrow">Your activity</span><h2>Recently viewed</h2></div><a class="text-link" href="<?= e(url('properties.php')) ?>">Browse all ↗</a></div>
    <div class="rv-scroll">
      <?php foreach ($rvProps as $p): ?>
        <?php $homePhoto = property_photo($p['cover_image'], $p['property_type'], (int)$p['id']); ?>
      <article class="property-card">
        <a class="property-photo" href="<?= e(url('property-details.php?id='.(int)$p['id'])) ?>">
          <img src="<?= e($homePhoto['src']) ?>" alt="<?= $homePhoto['illustrative'] ? 'Illustrative ' : '' ?>property photograph for <?= e($p['title']) ?>" loading="lazy">
          <span class="tag"><?= $p['listing_type']==='rent'?'For rent':'For sale' ?></span>
          <?php if ($homePhoto['illustrative']): ?><span class="tag home-photo-note">Illustrative photo</span><?php endif; ?>
        </a>
        <div class="property-info"><div class="price"><?= money($p['price']) ?></div><h3><a href="<?= e(url('property-details.php?id='.(int)$p['id'])) ?>"><?= e($p['title']) ?></a></h3><p class="muted">⌖ <?= e($p['location']) ?></p></div>
      </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
