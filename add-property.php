<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/mailer.php';
require_once __DIR__ . '/config/image.php';
require_login();
if (!in_array(current_user()['role'], ['owner','admin'], true)) { flash('error','Only property owners and administrators can submit listings.'); redirect('dashboard.php'); }
$errors=[]; $data=['title'=>'','description'=>'','price'=>'','location'=>'','property_type'=>'house','listing_type'=>'sale','bedrooms'=>'2','bathrooms'=>'1','area_sqft'=>''];
if (is_post()) {
    verify_csrf();
    foreach ($data as $key=>$v) { if (isset($_POST[$key]) && is_scalar($_POST[$key])) $data[$key]=trim((string)$_POST[$key]); }
    if (mb_strlen($data['title'])<5 || mb_strlen($data['title'])>160) $errors[]='Title must be 5–160 characters.';
    if (mb_strlen($data['description'])<20 || mb_strlen($data['description'])>5000) $errors[]='Description must be 20–5000 characters.';
    if (!is_numeric($data['price']) || (float)$data['price']<=0 || (float)$data['price']>100000000000) $errors[]='Enter a valid price.';
    if (mb_strlen($data['location'])<2 || mb_strlen($data['location'])>200) $errors[]='Enter a valid location.';
    if (!in_array($data['property_type'],['house','apartment','land','commercial'],true)) $errors[]='Invalid property type.';
    if (!in_array($data['listing_type'],['sale','rent'],true)) $errors[]='Invalid listing type.';
    foreach(['bedrooms','bathrooms','area_sqft'] as $field) if (!ctype_digit($data[$field]) || (int)$data[$field] > 1000000) $errors[]='Bedrooms, bathrooms and area must be non-negative whole numbers.';
    $validUploads = [];
    if (!empty($_FILES['photos']['name'][0])) {
        $allowed = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
        $count = min(count($_FILES['photos']['name']), 6);
        for ($i=0; $i<$count; $i++) {
            if ($_FILES['photos']['error'][$i] === UPLOAD_ERR_NO_FILE) continue;
            if ($_FILES['photos']['error'][$i] !== UPLOAD_ERR_OK) { $errors[]='Image '.($i+1).' upload failed (error '.$_FILES['photos']['error'][$i].').'; continue; }
            if ($_FILES['photos']['size'][$i] > MAX_UPLOAD_BYTES) { $errors[]='Image '.($i+1).' must be 5 MB or smaller.'; continue; }
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($_FILES['photos']['tmp_name'][$i]);
            if (!isset($allowed[$mime])) { $errors[]='Image '.($i+1).': only JPG, PNG or WebP allowed.'; continue; }
            $validUploads[] = ['tmp'=>$_FILES['photos']['tmp_name'][$i],'name'=>bin2hex(random_bytes(16)).'.'.$allowed[$mime]];
        }
    }
    if (!$errors) {
        try {
            db()->beginTransaction();
            $status = current_user()['role']==='admin' ? 'approved' : 'pending';
            $stmt=db()->prepare('INSERT INTO properties (owner_id,title,description,price,location,property_type,listing_type,bedrooms,bathrooms,area_sqft,status) VALUES (?,?,?,?,?,?,?,?,?,?,?)');
            $stmt->execute([current_user()['id'],$data['title'],$data['description'],(float)$data['price'],$data['location'],$data['property_type'],$data['listing_type'],(int)$data['bedrooms'],(int)$data['bathrooms'],(int)$data['area_sqft'],$status]);
            $propertyId=(int)db()->lastInsertId();
            if (!empty($validUploads)) {
                if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR,0755,true);
                $imgStmt=db()->prepare('INSERT INTO property_images (property_id,image_path) VALUES (?,?)');
                foreach ($validUploads as $up) {
                    if (!move_uploaded_file($up['tmp'],UPLOAD_DIR.'/'.$up['name'])) throw new RuntimeException('Could not store uploaded image.');
                    optimize_image(UPLOAD_DIR.'/'.$up['name'], UPLOAD_DIR.'/'.$up['name']);
                    $imgStmt->execute([$propertyId,$up['name']]);
                }
            }
            db()->commit();
            flash('success',$status==='approved'?'Property published.':'Property submitted for admin approval.');
            send_mail(current_user()['email'], current_user()['name'], 'Property submitted: '.$data['title'], '<p>Your property listing has been submitted and is pending review.</p>');
            redirect('dashboard.php');
        } catch (Throwable $e) {
            if (db()->inTransaction()) db()->rollBack();
            foreach ($validUploads as $up) { if (is_file(UPLOAD_DIR.'/'.$up['name'])) unlink(UPLOAD_DIR.'/'.$up['name']); }
            error_log($e->getMessage()); $errors[]='Could not save the property. Check your database and uploads permissions.';
        }
    }
}
$pageTitle='List a property'; require __DIR__.'/includes/header.php';
?>
<section class="page-hero"><div class="container"><span class="eyebrow">Share your space</span><h1>List a property</h1><p>Complete the details below. New listings are reviewed before publication.</p></div></section>
<section class="container form-page"><div class="form-card"><?php foreach($errors as $err): ?><div class="alert alert-error"><?= e($err) ?></div><?php endforeach; ?><form method="post" enctype="multipart/form-data" class="form-stack"><?= csrf_field() ?><label>Property title<input name="title" value="<?= e($data['title']) ?>" required maxlength="160" placeholder="e.g. Bright 2-bedroom apartment"></label><label>Description<textarea name="description" rows="5" required minlength="20" maxlength="5000"><?= e($data['description']) ?></textarea></label><div class="two-fields"><label>Price (INR)<input type="number" name="price" min="1" step="1" value="<?= e($data['price']) ?>" required></label><label>Location<input name="location" value="<?= e($data['location']) ?>" required maxlength="200" placeholder="City, area"></label></div><div class="two-fields"><label>Property type<select name="property_type"><?php foreach(['house'=>'House','apartment'=>'Apartment','land'=>'Land','commercial'=>'Commercial'] as $v=>$label): ?><option value="<?= e($v) ?>" <?= $data['property_type']===$v?'selected':'' ?>><?= e($label) ?></option><?php endforeach; ?></select></label><label>Listing type<select name="listing_type"><option value="sale" <?= $data['listing_type']==='sale'?'selected':'' ?>>For sale</option><option value="rent" <?= $data['listing_type']==='rent'?'selected':'' ?>>For rent</option></select></label></div><div class="three-fields"><label>Bedrooms<input type="number" min="0" max="100" name="bedrooms" value="<?= e($data['bedrooms']) ?>"></label><label>Bathrooms<input type="number" min="0" max="100" name="bathrooms" value="<?= e($data['bathrooms']) ?>"></label><label>Area (sq ft)<input type="number" min="0" name="area_sqft" value="<?= e($data['area_sqft']) ?>"></label></div>
<label>Property images (up to 6, JPG/PNG/WebP, max 5 MB each)<input type="file" name="photos[]" id="photos-input" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" multiple></label>
<div id="photo-preview" class="photo-preview-row"></div>
<button class="btn" type="submit">Submit listing ↗</button></form></div></section>
<script>
document.getElementById('photos-input').addEventListener('change', function(){
  var preview = document.getElementById('photo-preview');
  preview.innerHTML = '';
  var files = Array.from(this.files).slice(0,6);
  files.forEach(function(file){
    var reader = new FileReader();
    reader.onload = function(e){
      var img = document.createElement('img');
      img.src = e.target.result;
      img.className = 'preview-thumb';
      preview.appendChild(img);
    };
    reader.readAsDataURL(file);
  });
});
</script>
<?php require __DIR__.'/includes/footer.php'; ?>
