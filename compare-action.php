<?php
require_once __DIR__ . '/config/config.php';
require_login();

// GET requests go back to properties
if (!is_post()) {
    redirect('properties.php');
}

verify_csrf();

$action = $_POST['action'] ?? '';
$user   = current_user();
$userId = (int)$user['id'];

if ($action === 'add_to_compare') {
    $propId = filter_input(INPUT_POST, 'property_id', FILTER_VALIDATE_INT);
    if (!$propId || $propId <= 0) {
        flash('error', 'Invalid property.');
        redirect('properties.php');
    }

    // Check max 4 items
    $countStmt = db()->prepare('SELECT COUNT(*) FROM compare_list WHERE user_id=?');
    $countStmt->execute([$userId]);
    $count = (int)$countStmt->fetchColumn();

    if ($count >= 4) {
        flash('error', 'Compare list is full (maximum 4 properties). Remove one to add another.');
        redirect('compare.php');
    }

    $ins = db()->prepare('INSERT IGNORE INTO compare_list (user_id, property_id) VALUES (?,?)');
    $ins->execute([$userId, $propId]);

    flash('success', 'Added to compare list.');
    redirect('compare.php');
}

if ($action === 'remove_from_compare') {
    $propId = filter_input(INPUT_POST, 'property_id', FILTER_VALIDATE_INT);
    if ($propId && $propId > 0) {
        $del = db()->prepare('DELETE FROM compare_list WHERE user_id=? AND property_id=?');
        $del->execute([$userId, $propId]);
    }

    $referer = $_SERVER['HTTP_REFERER'] ?? '';
    // Only redirect back to same origin to prevent open redirect
    if ($referer && parse_url($referer, PHP_URL_HOST) === parse_url(BASE_URL, PHP_URL_HOST)) {
        header('Location: ' . $referer);
        exit;
    }
    redirect('compare.php');
}

if ($action === 'clear_compare') {
    $del = db()->prepare('DELETE FROM compare_list WHERE user_id=?');
    $del->execute([$userId]);

    flash('success', 'Compare list cleared.');
    redirect('compare.php');
}

// Unknown action
flash('error', 'Unknown action.');
redirect('properties.php');
