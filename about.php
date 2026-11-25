<?php
require_once __DIR__ . '/config/config.php';
$pageTitle = 'About Us';

// Live stats from DB
$totalProperties = (int)db()->query("SELECT COUNT(*) FROM properties WHERE status='approved'")->fetchColumn();
$totalUsers      = (int)db()->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalCities     = (int)db()->query("SELECT COUNT(DISTINCT location) FROM properties WHERE status='approved'")->fetchColumn();
$totalInquiries  = (int)db()->query("SELECT COUNT(*) FROM inquiries")->fetchColumn();

require __DIR__ . '/includes/header.php';
?>

<!-- ── HERO ─────────────────────────────────────────────────────────── -->
<section class="about-hero editorial-about">
  <div class="container about-hero-inner">
    <div class="about-hero-copy">
      <span class="eyebrow">A more thoughtful property journey</span>
      <h1>Better connections begin with finding <em>your place.</em></h1>
      <p>Explore homes, discover places to live and connect with listing owners through a clearer, more considered property experience.</p>
      <div class="about-hero-actions">
        <a class="btn" href="<?= e(url('properties.php')) ?>">Browse properties ↗</a>
        <a class="btn btn-outline" href="<?= e(url('register.php')) ?>">Join EstateHub</a>
      </div>
    </div>
    <div class="about-hero-image">
      <img src="<?= e(url('assets/images/about-homes.jpg')) ?>" alt="Contemporary residential architecture and homes" fetchpriority="high">
      <div class="about-photo-caption"><span>ESTATEHUB &nbsp;/&nbsp; YOUR NEXT CHAPTER</span><strong>Spaces to make your own.</strong><small class="about-image-credit">Representative photography · <a href="https://www.pexels.com/license/" target="_blank" rel="noopener noreferrer">Pexels</a></small></div>
    </div>
  </div>
</section>
<!-- ── LIVE STATS ─────────────────────────────────────────────────── -->
<section class="about-stats-band">
  <div class="container about-stats-grid">
    <div class="about-stat"><strong><?= number_format($totalProperties) ?></strong><span>Approved listings</span></div>
    <div class="about-stat"><strong><?= number_format($totalUsers) ?></strong><span>Registered members</span></div>
    <div class="about-stat"><strong><?= number_format($totalCities) ?></strong><span>Listed locations</span></div>
    <div class="about-stat"><strong><?= number_format($totalInquiries) ?></strong><span>Property inquiries</span></div>
  </div>
</section>

<!-- ── WHAT WE OFFER ─────────────────────────────────────────────── -->
<section class="section container editorial-about-offer">
  <div class="section-heading">
    <div><span class="eyebrow">What you can do</span><h2>A considered way to find your next place</h2></div>
  </div>
  <div class="about-offer-grid">

    <div class="offer-card">
      <div class="offer-icon">🔍</div>
      <h3>Search with intention</h3>
      <p>Use the available filters to narrow listings by location, property type, budget and other details that matter to your search.</p>
    </div>

    <div class="offer-card">
      <div class="offer-icon">🏠</div>
      <h3>Houses & Villas</h3>
      <p>Explore house and villa listings with the information and photographs provided by each listing owner.</p>
    </div>

    <div class="offer-card">
      <div class="offer-icon">🏢</div>
      <h3>Apartments & Flats</h3>
      <p>Browse apartment and flat listings, then save the ones you want to revisit.</p>
    </div>

    <div class="offer-card">
      <div class="offer-icon">🌿</div>
      <h3>Land & Plots</h3>
      <p>Discover land and plot listings with the location, area and other details included by the owner.</p>
    </div>

    <div class="offer-card">
      <div class="offer-icon">🏬</div>
      <h3>Commercial Spaces</h3>
      <p>Explore commercial properties offered for sale or rent and review the details available on each listing.</p>
    </div>

    <div class="offer-card">
      <div class="offer-icon">💬</div>
      <h3>Property enquiries</h3>
      <p>Use the inquiry options on a property's details page to ask the listing contact about that home.</p>
    </div>

    <div class="offer-card">
      <div class="offer-icon">❤️</div>
      <h3>Favourites & Shortlisting</h3>
      <p>Save properties to a personal favourites list and return to them when you are ready.</p>
    </div>

    <div class="offer-card">
      <div class="offer-icon">📋</div>
      <h3>Owner Dashboard</h3>
      <p>Owners can use their dashboard to manage submitted listings and review related activity.</p>
    </div>

    <div class="offer-card">
      <div class="offer-icon">✅</div>
      <h3>Reviewed submissions</h3>
      <p>Property submissions are reviewed before they are published in public search results.</p>
    </div>

  </div>
</section>

<!-- ── HOW IT WORKS ───────────────────────────────────────────────── -->
<section class="section bg-white">
  <div class="container">
    <div class="section-heading">
      <div><span class="eyebrow">Simple process</span><h2>How EstateHub works</h2></div>
    </div>
    <div class="how-grid">
      <div class="how-step">
        <div class="how-number">1</div>
        <h3>Create a free account</h3>
        <p>Create a seeker or owner account to save properties or manage your own listings.</p>
      </div>
      <div class="how-step">
        <div class="how-number">2</div>
        <h3>Search or list</h3>
        <p>Search available properties, or submit a property listing with details and photos for review.</p>
      </div>
      <div class="how-step">
        <div class="how-number">3</div>
        <h3>Connect directly</h3>
        <p>Explore the contact options provided on a property page to reach out about a listing.</p>
      </div>
      <div class="how-step">
        <div class="how-number">4</div>
        <h3>Take your next step</h3>
        <p>Arrange a conversation or viewing with the listing contact and continue at your own pace.</p>
      </div>
    </div>
  </div>
</section>

<section class="about-neighborhood-band">
  <div class="container about-neighborhood-inner">
    <div class="about-neighborhood-image">
      <img src="<?= e(url('assets/images/about-neighborhood.jpg')) ?>" alt="A green neighborhood with homes among mature trees" loading="lazy" decoding="async">
    </div>
    <div class="about-neighborhood-copy">
      <span class="eyebrow">More than an address</span>
      <h2>Find a place that feels like yours.</h2>
      <p>Start with the listings, compare the details and explore the neighborhoods that could become part of your story.</p>
      <a class="text-link" href="<?= e(url('properties.php')) ?>">Explore properties ↗</a>
      <small>Representative neighborhood photography · <a href="https://www.pexels.com/license/" target="_blank" rel="noopener noreferrer">Pexels</a></small>
    </div>
  </div>
</section>

<!-- ── WHY CHOOSE US ─────────────────────────────────────────────── -->
<section class="section container editorial-about-why">
  <div class="section-heading">
    <div><span class="eyebrow">A better way to explore</span><h2>Property discovery, made clearer</h2></div>
  </div>
  <div class="why-grid">
    <div class="why-item">
      <span class="why-check">✓</span>
      <div>
        <strong>Explore at your pace</strong>
        <p>Browse available listings and refine your search with filters for location, price, property type and more.</p>
      </div>
    </div>
    <div class="why-item">
      <span class="why-check">✓</span>
      <div>
        <strong>Compare property details</strong>
        <p>Review the prices and details provided with each listing, save favourites, and compare options side by side.</p>
      </div>
    </div>
    <div class="why-item">
      <span class="why-check">✓</span>
      <div>
        <strong>Reviewed submissions</strong>
        <p>New property submissions go through an approval step before being published in public search results.</p>
      </div>
    </div>
    <div class="why-item">
      <span class="why-check">✓</span>
      <div>
        <strong>Mobile-friendly design</strong>
        <p>Browse and revisit listings on mobile, tablet or desktop with a responsive layout.</p>
      </div>
    </div>
    <div class="why-item">
      <span class="why-check">✓</span>
      <div>
        <strong>Property photo galleries</strong>
        <p>Listings can include owner-uploaded photos, with a gallery for viewing the details and spaces that matter to you.</p>
      </div>
    </div>
    <div class="why-item">
      <span class="why-check">✓</span>
      <div>
        <strong>Connect from the listing</strong>
        <p>Property detail pages bring owner contact options and inquiry tools together with the listing information.</p>
      </div>
    </div>
  </div>
</section>

<!-- ── PROPERTY TYPES DEEP DIVE ──────────────────────────────────── -->
<section class="section bg-white editorial-about-types">
  <div class="container">
    <div class="section-heading">
      <div><span class="eyebrow">What you can find</span><h2>Property types on EstateHub</h2></div>
    </div>
    <div class="proptype-grid">
      <div class="proptype-card">
        <img src="<?= e(url('assets/images/estate-house.jpg')) ?>" alt="Illustrative modern detached home">
        <div class="proptype-body">
          <h3>🏠 Houses &amp; Villas</h3>
          <p>Explore house and villa listings and review the price, location and features provided by each listing contact.</p>
          <a class="text-link" href="<?= e(url('properties.php?property_type=house')) ?>">Browse houses ↗</a>
        </div>
      </div>
      <div class="proptype-card">
        <img src="<?= e(url('assets/images/estate-apartment.jpg')) ?>" alt="Illustrative contemporary apartment building">
        <div class="proptype-body">
          <h3>🏢 Apartments &amp; Flats</h3>
          <p>Compare available apartment and flat listings using their descriptions, photos and other provided details.</p>
          <a class="text-link" href="<?= e(url('properties.php?property_type=apartment')) ?>">Browse apartments ↗</a>
        </div>
      </div>
      <div class="proptype-card">
        <img src="<?= e(url('assets/images/estate-land.jpg')) ?>" alt="Illustrative open residential land">
        <div class="proptype-body">
          <h3>🌿 Land &amp; Plots</h3>
          <p>Browse land and plot listings and check the location and area information supplied for each property.</p>
          <a class="text-link" href="<?= e(url('properties.php?property_type=land')) ?>">Browse land ↗</a>
        </div>
      </div>
      <div class="proptype-card">
        <img src="<?= e(url('assets/images/estate-commercial.jpg')) ?>" alt="Illustrative modern commercial building">
        <div class="proptype-body">
          <h3>🏬 Commercial Spaces</h3>
          <p>Explore commercial listings and use property search filters to find options that fit your needs.</p>
          <a class="text-link" href="<?= e(url('properties.php?property_type=commercial')) ?>">Browse commercial ↗</a>
        </div>
      </div>
    </div>
    <p class="about-photo-credit">Representative property photography; images are not specific to a listing. <a href="https://unsplash.com/license" target="_blank" rel="noopener noreferrer">Unsplash</a> and <a href="https://www.pexels.com/license/" target="_blank" rel="noopener noreferrer">Pexels</a>.</p>
  </div>
</section>

<!-- ── TEAM ───────────────────────────────────────────────────────── -->
<section class="section container">
  <div class="section-heading">
    <div><span class="eyebrow">The EstateHub experience</span><h2>A more considered way to search</h2></div>
  </div>
  <div class="team-grid about-principles-grid">
    <div class="team-card">
      <div class="team-avatar" aria-hidden="true">01</div>
      <h3>Explore on your terms</h3>
      <span class="team-role">A clearer shortlist</span>
      <p>Filter active listings by location, property type, budget and the details that matter to your search.</p>
    </div>
    <div class="team-card">
      <div class="team-avatar" aria-hidden="true">02</div>
      <h3>Compare what matters</h3>
      <span class="team-role">Useful property details</span>
      <p>Review pricing, area and property details together, then save the listings you want to revisit.</p>
    </div>
    <div class="team-card">
      <div class="team-avatar" aria-hidden="true">03</div>
      <h3>Connect directly</h3>
      <span class="team-role">A direct conversation</span>
      <p>Contact listing owners through the inquiry options on a property's details page.</p>
    </div>
    <div class="team-card">
      <div class="team-avatar" aria-hidden="true">04</div>
      <h3>Reviewed submissions</h3>
      <span class="team-role">A considered catalogue</span>
      <p>New property submissions go through an approval step before appearing in public search results.</p>
    </div>
  </div>
</section>

<!-- ── CTA ────────────────────────────────────────────────────────── -->
<section class="cta-band">
  <div class="container cta-inner">
    <div>
      <span class="eyebrow">Ready to start?</span>
      <h2>Your next home is one search away.</h2>
      <p>Explore current listings and start building a shortlist that feels right for you.</p>
    </div>
    <div style="display:flex;gap:14px;flex-wrap:wrap;">
      <a class="btn btn-light" href="<?= e(url('properties.php')) ?>">Search properties ↗</a>
      <a class="btn" style="background:rgba(255,255,255,.15);border-color:rgba(255,255,255,.4);" href="<?= e(url('register.php')) ?>">Create free account</a>
    </div>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
