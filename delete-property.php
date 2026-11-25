<?php
require_once __DIR__ . '/config/config.php'; require_login();
if (!is_post()) redirect('dashboard.php');
verify_csrf(); $id=filter_input(INPUT_POST,'property_id',FILTER_VALIDATE_INT);
if (!$id) { flash('error','Invalid property.'); redirect('dashboard.php'); }
if (current_user()['role']==='admin') $stmt=db()->prepare('SELECT id FROM properties WHERE id=?');
else $stmt=db()->prepare('SELECT id FROM properties WHERE id=? AND owner_id=?');
$stmt->execute(current_user()['role']==='admin'?[$id]:[$id,current_user()['id']]);
if (!$stmt->fetch()) { http_response_code(403); exit('You cannot delete this listing.'); }
$imgs=db()->prepare('SELECT image_path FROM property_images WHERE property_id=?'); $imgs->execute([$id]); $paths=$imgs->fetchAll();
$del=db()->prepare('DELETE FROM properties WHERE id=?'); $del->execute([$id]);
foreach($paths as $p) if (preg_match('/^[a-zA-Z0-9._-]+$/',$p['image_path']) && is_file(UPLOAD_DIR.'/'.$p['image_path'])) unlink(UPLOAD_DIR.'/'.$p['image_path']);
flash('success','Property listing deleted.'); redirect(current_user()['role']==='admin'?'admin/index.php':'dashboard.php');
