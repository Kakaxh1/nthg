<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

// Ensure user is logged in
if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'You must be logged in to make an offer']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['listing_id']) || !isset($input['message']) || !isset($input['offered_items'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit;
}

$listing_id = (int)$input['listing_id'];
$message = trim($input['message']);
$offered_items = array_map('intval', $input['offered_items']);
$from_user_id = $_SESSION['user_id'];

// Validate message length
if (strlen($message) < 10) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Message must be at least 10 characters long']);
    exit;
}

// Validate offered items
if (empty($offered_items)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'You must offer at least one item']);
    exit;
}

try {
    // Check if listing exists and is active
    $stmt = $pdo->prepare("SELECT user_id FROM listings WHERE id = ? AND status = 'active'");
    $stmt->execute([$listing_id]);
    $listing = $stmt->fetch();

    if (!$listing) {
        throw new Exception('Listing not found or is no longer active');
    }

    // Prevent offering on own listing
    if ($listing['user_id'] == $from_user_id) {
        throw new Exception('You cannot make an offer on your own listing');
    }

    // Check if user already has an offer for this listing
    $stmt = $pdo->prepare("SELECT id FROM offers WHERE from_user_id = ? AND to_listing_id = ?");
    $stmt->execute([$from_user_id, $listing_id]);
    if ($stmt->fetch()) {
        throw new Exception('You have already made an offer for this listing');
    }

    // Verify that all offered items belong to the user and are active
    $placeholders = str_repeat('?,', count($offered_items) - 1) . '?';
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM listings 
                          WHERE id IN ($placeholders) 
                          AND user_id = ? 
                          AND status = 'active'");
    $params = array_merge($offered_items, [$from_user_id]);
    $stmt->execute($params);
    
    if ($stmt->fetchColumn() != count($offered_items)) {
        throw new Exception('One or more selected items are not available');
    }

    // Begin transaction
    $pdo->beginTransaction();

    // Create the offer
    $stmt = $pdo->prepare("INSERT INTO offers (from_user_id, to_listing_id, message, status, created_at) 
                          VALUES (?, ?, ?, 'pending', NOW())");
    $stmt->execute([$from_user_id, $listing_id, $message]);
    $offer_id = $pdo->lastInsertId();

    // Link offered items to the offer
    $stmt = $pdo->prepare("INSERT INTO offer_items (offer_id, listing_id) VALUES (?, ?)");
    foreach ($offered_items as $item_id) {
        $stmt->execute([$offer_id, $item_id]);
    }

    // Create notification for listing owner
    $stmt = $pdo->prepare("INSERT INTO notifications (user_id, type, content, related_id, created_at) 
                          VALUES (?, 'new_offer', ?, ?, NOW())");
    $stmt->execute([
        $listing['user_id'],
        'You have received a new offer on your listing',
        $listing_id
    ]);

    $pdo->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
} 