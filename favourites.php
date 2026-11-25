<?php
require_once __DIR__ . '/config/config.php'; require_login();
if (is_post()) {
    verify_csrf(); $id=filter_input(INPUT_POST,'property_id',FILTER_VALIDATE_INT);
    if (!$id) { flash('error','Invalid property.'); redirect('properties.php'); }
    $check=db()->prepare("SELECT id FROM properties WHERE id=? AND status='approved'"); $check->execute([$id]);
    if (!$check->fetch()) { flash('error','That property is no longer available.'); redirect('properties.php'); }
    $exists=db()->prepare('SELECT id FROM favourites WHERE user_id=? AND property_id=?'); $exists->execute([current_user()['id'],$id]);
    if ($exists->fetch()) { $del=db()->prepare('DELETE FROM favourites WHERE user_id=? AND property_id=?'); $del->execute([current_user()['id'],$id]); flash('success','Removed from favourites.'); }
    else { $ins=db()->prepare('INSERT IGNORE INTO favourites (user_id,property_id) VALUES (?,?)'); $ins->execute([current_user()['id'],$id]); flash('success','Added to favourites.'); }
    $back=$_SERVER['HTTP_REFERER'] ?? url('favourites.php'); if (!str_starts_with($back,url(''))) $back=url('favourites.php'); header('Location: '.$back); exit;
}
$pageTitle='Favourites'; $stmt=db()->prepare("SELECT p.*, (SELECT pi.image_path FROM property_images pi WHERE pi.property_id=p.id ORDER BY pi.id LIMIT 1) AS cover_image FROM favourites f JOIN properties p ON p.id=f.property_id WHERE f.user_id=? AND p.status='approved' ORDER BY f.created_at DESC"); $stmt->execute([current_user()['id']]); $items=$stmt->fetchAll(); require __DIR__.'/includes/header.php';
?>
<section class="page-hero"><div class="container"><span class="eyebrow">Your shortlist</span><h1>Favourite properties</h1><p>Keep the places you love close at hand.</p></div></section><section class="container section"><?php if($items): ?><div class="property-grid"><?php foreach($items as $p): ?><?php $favoritePhoto = property_photo($p['cover_image'], $p['property_type'], (int)$p['id']); ?><article class="property-card"><a class="property-photo" href="<?= e(url('property-details.php?id='.(int)$p['id'])) ?>"><img src="<?= e($favoritePhoto['src']) ?>" alt="<?= e($favoritePhoto['illustrative'] ? 'Illustrative photo for ' : '') . e($p['title']) ?>"><?php if($favoritePhoto['illustrative']): ?><span class="property-image-note">Illustrative photo</span><?php endif; ?></a><div class="property-info"><div class="price"><?= money($p['price']) ?></div><h3><a href="<?= e(url('property-details.php?id='.(int)$p['id'])) ?>"><?= e($p['title']) ?></a></h3><p class="muted"><?= e($p['location']) ?></p><form method="post"><?= csrf_field() ?><input type="hidden" name="property_id" value="<?= (int)$p['id'] ?>"><button class="btn btn-outline" type="submit">Remove favourite</button></form></div></article><?php endforeach; ?></div><?php else: ?><div class="empty-state"><h2>Your shortlist is waiting</h2><p>Save properties while browsing to compare them later.</p><a class="btn" href="<?= e(url('properties.php')) ?>">Explore properties</a></div><?php endif; ?></section>
<?php require __DIR__.'/includes/footer.php'; ?>
