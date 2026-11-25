<?php
require_once __DIR__ . '/config/config.php';
$pageTitle = 'Explore Properties';

// ── Inputs ──────────────────────────────────────────────────────────────
$location    = trim((string)($_GET['location']    ?? ''));
$listingType = (string)($_GET['listing_type']     ?? '');
$propertyType= (string)($_GET['property_type']    ?? '');
$minPrice    = trim((string)($_GET['min_price']   ?? ''));
$maxPrice    = trim((string)($_GET['max_price']   ?? ''));
$bedrooms    = (string)($_GET['bedrooms']         ?? '');
$bathrooms   = (string)($_GET['bathrooms']        ?? '');
$minArea     = trim((string)($_GET['min_area']    ?? ''));
$sort        = (string)($_GET['sort']             ?? 'newest');
$view        = in_array($_GET['view'] ?? '', ['list','grid']) ? $_GET['view'] : 'grid';
$page        = max(1, (int)($_GET['page']         ?? 1));
$perPage     = 12;

// ── Build WHERE ──────────────────────────────────────────────────────────
$where  = ["p.status='approved'"];
$params = [];

if ($location !== '') {
    $where[]           = 'p.location LIKE :location';
    $params['location']= '%' . $location . '%';
}
if (in_array($listingType, ['sale','rent'], true)) {
    $where[]                = 'p.listing_type=:listing_type';
    $params['listing_type'] = $listingType;
}
if (in_array($propertyType, ['house','apartment','land','commercial'], true)) {
    $where[]                 = 'p.property_type=:property_type';
    $params['property_type'] = $propertyType;
}
if ($minPrice !== '' && is_numeric($minPrice) && (float)$minPrice >= 0) {
    $where[]           = 'p.price>=:min_price';
    $params['min_price']= (float)$minPrice;
}
if ($maxPrice !== '' && is_numeric($maxPrice) && (float)$maxPrice >= 0) {
    $where[]           = 'p.price<=:max_price';
    $params['max_price']= (float)$maxPrice;
}
if ($bedrooms !== '' && ctype_digit($bedrooms) && (int)$bedrooms <= 10) {
    $where[]           = 'p.bedrooms>=:bedrooms';
    $params['bedrooms']= (int)$bedrooms;
}
if ($bathrooms !== '' && ctype_digit($bathrooms) && (int)$bathrooms <= 10) {
    $where[]            = 'p.bathrooms>=:bathrooms';
    $params['bathrooms']= (int)$bathrooms;
}
if ($minArea !== '' && is_numeric($minArea) && (float)$minArea >= 0) {
    $where[]           = 'p.area_sqft>=:min_area';
    $params['min_area']= (float)$minArea;
}

$orderBy = match ($sort) {
    'price_asc'  => 'p.price ASC',
    'price_desc' => 'p.price DESC',
    'area_desc'  => 'p.area_sqft DESC',
    'oldest'     => 'p.created_at ASC',
    default      => 'p.created_at DESC',
};

$whereClause = implode(' AND ', $where);

// ── Counts ───────────────────────────────────────────────────────────────
$countStmt = db()->prepare("SELECT COUNT(*) FROM properties p WHERE $whereClause");
$countStmt->execute($params);
$totalCount = (int)$countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($totalCount / $perPage));
$page       = min($page, $totalPages);
$offset     = ($page - 1) * $perPage;

// ── Fetch ────────────────────────────────────────────────────────────────
$sql  = "SELECT p.*, u.name AS owner_name,
          (SELECT pi.image_path FROM property_images pi WHERE pi.property_id=p.id ORDER BY pi.id LIMIT 1) AS cover_image,
          COALESCE(rv.avg_rating, 0) AS avg_rating,
          COALESCE(rv.review_cnt, 0) AS review_cnt
         FROM properties p
         JOIN users u ON u.id=p.owner_id
         LEFT JOIN (
           SELECT property_id, ROUND(AVG(rating),1) AS avg_rating, COUNT(*) AS review_cnt
           FROM reviews WHERE status='approved' GROUP BY property_id
         ) AS rv ON rv.property_id=p.id
         WHERE $whereClause
         ORDER BY $orderBy
         LIMIT $perPage OFFSET $offset";
$stmt = db()->prepare($sql);
$stmt->execute($params);
$properties = $stmt->fetchAll();

// ── Active filter count (for badge) ─────────────────────────────────────
$filterParams  = array_filter(
    compact('location','listingType','propertyType','minPrice','maxPrice','bedrooms','bathrooms','minArea','sort'),
    fn($v) => $v !== ''
);
$activeFilters = count(array_filter(
    compact('location','listingType','propertyType','minPrice','maxPrice','bedrooms','bathrooms','minArea'),
    fn($v) => $v !== ''
));

// ── Sidebar quick-counts ─────────────────────────────────────────────────
$typeCounts = [];
foreach (['house','apartment','land','commercial'] as $t) {
    $s = db()->prepare("SELECT COUNT(*) FROM properties WHERE status='approved' AND property_type=?");
    $s->execute([$t]);
    $typeCounts[$t] = (int)$s->fetchColumn();
}

function paginationUrl(int $p, array $fp): string {
    $fp['page'] = $p;
    unset($fp['listingType'],$fp['propertyType'],$fp['minPrice'],$fp['maxPrice'],$fp['minArea']);
    return url('properties.php?' . http_build_query(array_filter($fp, fn($v)=>$v!=='')));
}
function currentUrl(array $extra=[]): string {
    $q = array_merge($_GET, $extra);
    return url('properties.php?' . http_build_query($q));
}

require __DIR__ . '/includes/header.php';
?>

<!-- ── PAGE HERO ─────────────────────────────────────────────────────── -->
<section class="explore-hero">
  <div class="container explore-hero-inner">
    <div>
      <span class="eyebrow eyebrow-light">Property search</span>
      <h1>Find your perfect property</h1>
      <p>Browse <?= $totalCount ?> approved listings — houses, apartments, land &amp; commercial spaces.</p>
    </div>
    <!-- Quick type tabs -->
    <div class="explore-type-tabs">
      <a class="etype-tab <?= $propertyType===''?'etype-active':'' ?>" href="<?= e(url('properties.php'.($listingType?'?listing_type='.$listingType:''))) ?>">🏘 All</a>
      <a class="etype-tab <?= $propertyType==='house'?'etype-active':'' ?>" href="<?= e(url('properties.php?property_type=house'.($listingType?'&listing_type='.$listingType:''))) ?>">🏠 Houses</a>
      <a class="etype-tab <?= $propertyType==='apartment'?'etype-active':'' ?>" href="<?= e(url('properties.php?property_type=apartment'.($listingType?'&listing_type='.$listingType:''))) ?>">🏢 Apartments</a>
      <a class="etype-tab <?= $propertyType==='land'?'etype-active':'' ?>" href="<?= e(url('properties.php?property_type=land'.($listingType?'&listing_type='.$listingType:''))) ?>">🌿 Land</a>
      <a class="etype-tab <?= $propertyType==='commercial'?'etype-active':'' ?>" href="<?= e(url('properties.php?property_type=commercial'.($listingType?'&listing_type='.$listingType:''))) ?>">🏬 Commercial</a>
    </div>
    <p class="explore-photo-note">Property photos are supplied by owners where available. Representative category photos are illustrative.</p>
  </div>
</section>

<!-- ── MAIN LAYOUT ───────────────────────────────────────────────────── -->
<div class="explore-wrap container">

  <!-- SIDEBAR ──────────────────────────────────────────────────────── -->
  <aside class="explore-sidebar">
    <div class="filter-card-pro">
      <div class="filter-header">
        <h2>Refine your search</h2>
        <?php if ($activeFilters > 0): ?>
          <a class="filter-clear-all" href="<?= e(url('properties.php')) ?>">Clear all <span class="filter-badge"><?= $activeFilters ?></span></a>
        <?php endif; ?>
      </div>

      <form method="get" id="filterForm">
        <?php if ($view !== 'grid'): ?><input type="hidden" name="view" value="<?= e($view) ?>"><?php endif; ?>

        <!-- Buy / Rent toggle -->
        <div class="filter-section">
          <label class="filter-label">Looking to</label>
          <div class="toggle-group">
            <a class="toggle-btn <?= $listingType===''?'toggle-active':'' ?>" href="<?= e(url('properties.php?'.http_build_query(array_merge($_GET,['listing_type'=>'','page'=>1])))) ?>">All</a>
            <a class="toggle-btn <?= $listingType==='sale'?'toggle-active':'' ?>" href="<?= e(url('properties.php?'.http_build_query(array_merge($_GET,['listing_type'=>'sale','page'=>1])))) ?>">Buy</a>
            <a class="toggle-btn <?= $listingType==='rent'?'toggle-active':'' ?>" href="<?= e(url('properties.php?'.http_build_query(array_merge($_GET,['listing_type'=>'rent','page'=>1])))) ?>">Rent</a>
          </div>
        </div>

        <!-- Location -->
        <div class="filter-section">
          <label class="filter-label" for="f-location">Location</label>
          <div class="input-icon-wrap">
            <span class="input-icon">📍</span>
            <input id="f-location" name="location" value="<?= e($location) ?>" placeholder="City, area or landmark" autocomplete="off">
          </div>
          <!-- Quick city chips -->
          <div class="city-chips">
            <?php foreach(['Mumbai','Delhi','Bangalore','Pune','Hyderabad','Chennai'] as $city): ?>
            <a class="city-chip <?= str_contains($location,$city)?'city-chip-active':'' ?>"
               href="<?= e(url('properties.php?'.http_build_query(array_merge($_GET,['location'=>$city,'page'=>1])))) ?>"><?= $city ?></a>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Price range -->
        <div class="filter-section">
          <label class="filter-label">Price range (₹)</label>
          <div class="two-fields">
            <label>Min<input type="number" name="min_price" min="0" step="50000" value="<?= e($minPrice) ?>" placeholder="0"></label>
            <label>Max<input type="number" name="max_price" min="0" step="50000" value="<?= e($maxPrice) ?>" placeholder="Any"></label>
          </div>
          <!-- Quick price chips -->
          <div class="city-chips">
            <?php
            $pChips = $listingType==='rent'
                ? ['Under ₹15K'=>[0,15000],'₹15K–30K'=>[15000,30000],'₹30K–60K'=>[30000,60000],'₹60K+'=>[60000,'']]
                : ['Under ₹50L'=>[0,5000000],'₹50L–1Cr'=>[5000000,10000000],'₹1Cr–3Cr'=>[10000000,30000000],'₹3Cr+'=>[30000000,'']];
            foreach ($pChips as $label=>[$mn,$mx]):
                $isActive = (string)$minPrice===(string)$mn && (string)$maxPrice===(string)$mx;
            ?>
            <a class="city-chip <?= $isActive?'city-chip-active':'' ?>"
               href="<?= e(url('properties.php?'.http_build_query(array_merge($_GET,['min_price'=>$mn,'max_price'=>$mx,'page'=>1])))) ?>"><?= $label ?></a>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Bedrooms -->
        <div class="filter-section">
          <label class="filter-label">Bedrooms</label>
          <div class="bed-chips">
            <a class="bed-chip <?= $bedrooms===''?'bed-chip-active':'' ?>" href="<?= e(url('properties.php?'.http_build_query(array_merge($_GET,['bedrooms'=>'','page'=>1])))) ?>">Any</a>
            <?php for($i=1;$i<=5;$i++): ?>
            <a class="bed-chip <?= $bedrooms===(string)$i?'bed-chip-active':'' ?>"
               href="<?= e(url('properties.php?'.http_build_query(array_merge($_GET,['bedrooms'=>$i,'page'=>1])))) ?>"><?= $i ?>+</a>
            <?php endfor; ?>
          </div>
        </div>

        <!-- Bathrooms -->
        <div class="filter-section">
          <label class="filter-label">Bathrooms</label>
          <div class="bed-chips">
            <a class="bed-chip <?= $bathrooms===''?'bed-chip-active':'' ?>" href="<?= e(url('properties.php?'.http_build_query(array_merge($_GET,['bathrooms'=>'','page'=>1])))) ?>">Any</a>
            <?php for($i=1;$i<=4;$i++): ?>
            <a class="bed-chip <?= $bathrooms===(string)$i?'bed-chip-active':'' ?>"
               href="<?= e(url('properties.php?'.http_build_query(array_merge($_GET,['bathrooms'=>$i,'page'=>1])))) ?>"><?= $i ?>+</a>
            <?php endfor; ?>
          </div>
        </div>

        <!-- Min area -->
        <div class="filter-section">
          <label class="filter-label" for="f-area">Minimum area (sq ft)</label>
          <input id="f-area" type="number" name="min_area" min="0" value="<?= e($minArea) ?>" placeholder="e.g. 500">
        </div>

        <!-- Sort -->
        <div class="filter-section">
          <label class="filter-label" for="f-sort">Sort by</label>
          <select id="f-sort" name="sort" onchange="this.form.submit()">
            <option value="newest"     <?= $sort==='newest'    ?'selected':''?>>Newest first</option>
            <option value="price_asc"  <?= $sort==='price_asc' ?'selected':''?>>Price: low to high</option>
            <option value="price_desc" <?= $sort==='price_desc'?'selected':''?>>Price: high to low</option>
            <option value="area_desc"  <?= $sort==='area_desc' ?'selected':''?>>Largest area</option>
            <option value="oldest"     <?= $sort==='oldest'    ?'selected':''?>>Oldest first</option>
          </select>
        </div>

        <button class="btn btn-full" type="submit">Update results</button>
      </form>

      <!-- Property type quick counts -->
      <div class="sidebar-counts">
        <p class="filter-label" style="margin-bottom:10px">Browse by type</p>
        <?php $typeIcons=['house'=>'🏠','apartment'=>'🏢','land'=>'🌿','commercial'=>'🏬']; ?>
        <?php foreach($typeCounts as $t=>$cnt): ?>
        <a class="sidebar-type-row <?= $propertyType===$t?'sidebar-type-active':'' ?>"
           href="<?= e(url('properties.php?property_type='.$t.($listingType?'&listing_type='.$listingType:''))) ?>">
          <span><?= $typeIcons[$t] ?> <?= ucfirst($t) ?></span>
          <span class="sidebar-count"><?= $cnt ?></span>
        </a>
        <?php endforeach; ?>
      </div>
    </div>
  </aside>

  <!-- RESULTS ──────────────────────────────────────────────────────── -->
  <div class="explore-results">

    <!-- Results toolbar -->
    <div class="results-toolbar">
      <div class="results-info">
        <?php if ($totalCount > 0): ?>
          <h2><?= number_format($totalCount) ?> <?= $totalCount===1?'property':'properties' ?> found</h2>
          <?php $from=min($totalCount,$offset+1); $to=min($totalCount,$offset+$perPage); ?>
          <span class="results-sub">Showing <?= $from ?>–<?= $to ?> of <?= number_format($totalCount) ?></span>
        <?php else: ?>
          <h2>No properties found</h2>
        <?php endif; ?>
      </div>
      <div class="results-controls">
        <!-- View toggle -->
        <div class="view-toggle">
          <a class="view-btn <?= $view==='grid'?'view-btn-active':'' ?>" href="<?= e(currentUrl(['view'=>'grid'])) ?>" title="Grid view">▦</a>
          <a class="view-btn <?= $view==='list'?'view-btn-active':'' ?>" href="<?= e(currentUrl(['view'=>'list'])) ?>" title="List view">☰</a>
        </div>
        <?php if (current_user() && $totalCount > 0): ?>
        <form method="post" action="<?= e(url('saved-searches.php')) ?>" class="inline-form">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="save_search">
          <input type="hidden" name="filters" value="<?= e(json_encode($_GET)) ?>">
          <input type="hidden" name="label" value="<?= e(($location?:'All').', '.($listingType?ucfirst($listingType):'Any type').', '.($propertyType?ucfirst($propertyType):'Any').($minPrice||$maxPrice?' ₹'.($minPrice?number_format((float)$minPrice/100000,0).'L':'0').'–'.($maxPrice?'₹'.number_format((float)$maxPrice/100000,0).'L':'Any'):'')) ?>">
          <button class="btn btn-small btn-outline" type="submit" title="Save this search">🔖 Save search</button>
        </form>
        <?php endif; ?>
      </div>
    </div>

    <!-- Active filter pills -->
    <?php if ($activeFilters > 0): ?>
    <div class="active-filters">
      <?php if ($location): ?><span class="af-pill">📍 <?= e($location) ?> <a href="<?= e(url('properties.php?'.http_build_query(array_merge($_GET,['location'=>'','page'=>1])))) ?>">×</a></span><?php endif; ?>
      <?php if ($listingType): ?><span class="af-pill"><?= $listingType==='sale'?'For sale':'For rent' ?> <a href="<?= e(url('properties.php?'.http_build_query(array_merge($_GET,['listing_type'=>'','page'=>1])))) ?>">×</a></span><?php endif; ?>
      <?php if ($propertyType): ?><span class="af-pill"><?= ucfirst($propertyType) ?> <a href="<?= e(url('properties.php?'.http_build_query(array_merge($_GET,['property_type'=>'','page'=>1])))) ?>">×</a></span><?php endif; ?>
      <?php if ($minPrice||$maxPrice): ?><span class="af-pill">₹<?= $minPrice?number_format((float)$minPrice):'0' ?> – <?= $maxPrice?'₹'.number_format((float)$maxPrice):'Any' ?> <a href="<?= e(url('properties.php?'.http_build_query(array_merge($_GET,['min_price'=>'','max_price'=>'','page'=>1])))) ?>">×</a></span><?php endif; ?>
      <?php if ($bedrooms): ?><span class="af-pill"><?= $bedrooms ?>+ beds <a href="<?= e(url('properties.php?'.http_build_query(array_merge($_GET,['bedrooms'=>'','page'=>1])))) ?>">×</a></span><?php endif; ?>
      <?php if ($bathrooms): ?><span class="af-pill"><?= $bathrooms ?>+ baths <a href="<?= e(url('properties.php?'.http_build_query(array_merge($_GET,['bathrooms'=>'','page'=>1])))) ?>">×</a></span><?php endif; ?>
      <?php if ($minArea): ?><span class="af-pill">Min <?= number_format((float)$minArea) ?> sqft <a href="<?= e(url('properties.php?'.http_build_query(array_merge($_GET,['min_area'=>'','page'=>1])))) ?>">×</a></span><?php endif; ?>
      <a class="af-clear" href="<?= e(url('properties.php')) ?>">Clear all</a>
    </div>
    <?php endif; ?>

    <?php if ($properties): ?>

    <!-- Property grid / list -->
    <div class="prop-results-grid <?= $view==='list'?'prop-results-list':'' ?>">
      <?php foreach($properties as $p): ?>
      <article class="pcard <?= $view==='list'?'pcard-list':'' ?>">

        <a class="pcard-photo" href="<?= e(url('property-details.php?id='.(int)$p['id'])) ?>">
          <?php $listingPhoto = property_photo($p['cover_image'], $p['property_type'], (int)$p['id']); ?>
          <img src="<?= e($listingPhoto['src']) ?>" alt="<?= e($listingPhoto['illustrative'] ? 'Illustrative photo for ' : '') . e($p['title']) ?>" loading="lazy">
          <span class="pcard-tag pcard-tag-<?= $p['listing_type'] ?>"><?= $p['listing_type']==='rent'?'For Rent':'For Sale' ?></span>
          <?php if ($listingPhoto['illustrative']): ?><span class="pcard-photo-note">Illustrative photo</span><?php endif; ?>
          <span class="pcard-type-icon"><?= ['house'=>'🏠','apartment'=>'🏢','land'=>'🌿','commercial'=>'🏬'][$p['property_type']] ?? '🏠' ?></span>
          <?php if(current_user()): ?>
          <form method="post" action="<?= e(url('favourites.php')) ?>" class="pcard-fav-form" onclick="event.stopPropagation()">
            <?= csrf_field() ?>
            <input type="hidden" name="property_id" value="<?= (int)$p['id'] ?>">
            <button class="pcard-fav" type="submit" title="Save to favourites">♡</button>
          </form>
          <?php endif; ?>
        </a>

        <div class="pcard-body">
          <div class="pcard-price">
            <?= money($p['price']) ?>
            <?php if($p['listing_type']==='rent'): ?><small>/ month</small><?php endif; ?>
          </div>
          <?php if ((float)$p['avg_rating'] > 0): ?>
          <div style="font-size:.82rem;color:var(--gold);font-weight:700;margin-bottom:4px">
            <?= str_repeat('★', (int)round((float)$p['avg_rating'])) ?><?= str_repeat('☆', 5-(int)round((float)$p['avg_rating'])) ?>
            <span style="color:var(--muted)"><?= number_format((float)$p['avg_rating'],1) ?> (<?= (int)$p['review_cnt'] ?>)</span>
          </div>
          <?php endif; ?>
          <h3 class="pcard-title">
            <a href="<?= e(url('property-details.php?id='.(int)$p['id'])) ?>"><?= e($p['title']) ?></a>
          </h3>
          <p class="pcard-location">📍 <?= e($p['location']) ?></p>

          <?php if($view==='list'): ?>
          <p class="pcard-desc"><?= e(mb_substr($p['description'],0,120)) ?>…</p>
          <?php endif; ?>

          <div class="pcard-meta">
            <?php if((int)$p['bedrooms']>0): ?><span title="Bedrooms">🛏 <?= (int)$p['bedrooms'] ?> Bed</span><?php endif; ?>
            <?php if((int)$p['bathrooms']>0): ?><span title="Bathrooms">🚿 <?= (int)$p['bathrooms'] ?> Bath</span><?php endif; ?>
            <?php if((int)$p['area_sqft']>0): ?><span title="Area">📐 <?= number_format((int)$p['area_sqft']) ?> sqft</span><?php endif; ?>
          </div>

          <div class="pcard-footer">
            <span class="pcard-owner">👤 <?= e($p['owner_name']) ?></span>
            <a class="pcard-cta" href="<?= e(url('property-details.php?id='.(int)$p['id'])) ?>">View details ↗</a>
            <?php if (current_user()): ?>
            <form method="post" action="<?= e(url('compare-action.php')) ?>" class="inline-form" onclick="event.stopPropagation()">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="add_to_compare">
              <input type="hidden" name="property_id" value="<?= (int)$p['id'] ?>">
              <button class="btn btn-small btn-outline" type="submit" title="Add to compare">Compare ＋</button>
            </form>
            <?php endif; ?>
          </div>
        </div>

      </article>
      <?php endforeach; ?>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <nav class="explore-pagination" aria-label="Pagination">
      <?php if ($page > 1): ?>
        <a class="epg-btn epg-arrow" href="<?= e(paginationUrl($page-1, array_merge(compact('location','listingType','propertyType','minPrice','maxPrice','bedrooms','bathrooms','minArea','sort')))) ?>">← Prev</a>
      <?php endif; ?>
      <?php
      $start = max(1, $page - 2);
      $end   = min($totalPages, $page + 2);
      if ($start > 1): ?><a class="epg-btn" href="<?= e(paginationUrl(1, array_merge(compact('location','listingType','propertyType','minPrice','maxPrice','bedrooms','bathrooms','minArea','sort')))) ?>">1</a><?php if ($start > 2): ?><span class="epg-dots">…</span><?php endif; endif; ?>
      <?php for ($pg = $start; $pg <= $end; $pg++): ?>
        <a class="epg-btn <?= $pg===$page?'epg-active':'' ?>" href="<?= e(paginationUrl($pg, array_merge(compact('location','listingType','propertyType','minPrice','maxPrice','bedrooms','bathrooms','minArea','sort')))) ?>"><?= $pg ?></a>
      <?php endfor; ?>
      <?php if ($end < $totalPages): ?><?php if ($end < $totalPages - 1): ?><span class="epg-dots">…</span><?php endif; ?><a class="epg-btn" href="<?= e(paginationUrl($totalPages, array_merge(compact('location','listingType','propertyType','minPrice','maxPrice','bedrooms','bathrooms','minArea','sort')))) ?>"><?= $totalPages ?></a><?php endif; ?>
      <?php if ($page < $totalPages): ?>
        <a class="epg-btn epg-arrow" href="<?= e(paginationUrl($page+1, array_merge(compact('location','listingType','propertyType','minPrice','maxPrice','bedrooms','bathrooms','minArea','sort')))) ?>">Next →</a>
      <?php endif; ?>
    </nav>
    <?php endif; ?>

    <?php else: ?>

    <!-- ── EMPTY STATE ──────────────────────────────────────────── -->
    <div class="explore-empty">
      <div class="explore-empty-icon">🔍</div>
      <h2>No properties found</h2>
      <p>We couldn't find any properties matching your current filters. Try adjusting your search — widen the price range, change the location, or remove some filters.</p>
      <div class="explore-empty-suggestions">
        <p><strong>Suggestions:</strong></p>
        <ul>
          <?php if ($location): ?><li>Try a broader location like "Mumbai" instead of a specific area.</li><?php endif; ?>
          <?php if ($minPrice || $maxPrice): ?><li>Expand your price range or remove the price filter.</li><?php endif; ?>
          <?php if ($bedrooms): ?><li>Try "Any" bedrooms instead of <?= $bedrooms ?>+.</li><?php endif; ?>
          <li>Browse <a href="<?= e(url('properties.php')) ?>">all available properties</a> without filters.</li>
        </ul>
      </div>
      <div class="explore-empty-actions">
        <a class="btn" href="<?= e(url('properties.php')) ?>">Clear all filters</a>
        <?php if($listingType): ?>
        <a class="btn btn-outline" href="<?= e(url('properties.php?listing_type='.$listingType)) ?>">Keep only "<?= $listingType==='rent'?'Rent':'Buy' ?>" filter</a>
        <?php endif; ?>
      </div>
    </div>
    <?php endif; ?>

  </div><!-- /explore-results -->
</div><!-- /explore-wrap -->

<?php require __DIR__ . '/includes/footer.php'; ?>
