<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

// Ensure user is logged in
if (!is_logged_in()) {
    http_response_code(401);
    exit(json_encode(['error' => 'Unauthorized']));
}

// Validate input
$thread_id = $_POST['thread_id'] ?? null;
$receiver_id = $_POST['receiver_id'] ?? null;
$message = trim($_POST['message'] ?? '');

if (!$thread_id || !$receiver_id || !$message) {
    http_response_code(400);
    exit(json_encode(['error' => 'Missing required fields']));
}

$user_id = $_SESSION['user_id'];

// Verify user has access to this thread
$stmt = $pdo->prepare("
    SELECT * FROM message_threads 
    WHERE id = ? AND (user1_id = ? OR user2_id = ?)
");
$stmt->execute([$thread_id, $user_id, $user_id]);
$thread = $stmt->fetch();

if (!$thread) {
    http_response_code(403);
    exit(json_encode(['error' => 'Access denied']));
}

try {
    $pdo->beginTransaction();

    // Insert message
    $stmt = $pdo->prepare("
        INSERT INTO messages (
            thread_id, sender_id, receiver_id, 
            content, created_at
        ) VALUES (?, ?, ?, ?, NOW())
    ");
    $stmt->execute([
        $thread_id,
        $user_id,
        $receiver_id,
        $message
    ]);
    
    // Update thread's last message time
    $stmt = $pdo->prepare("
        UPDATE message_threads 
        SET last_message_at = NOW() 
        WHERE id = ?
    ");
    $stmt->execute([$thread_id]);
    
    $pdo->commit();
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message_id' => $pdo->lastInsertId(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    exit(json_encode(['error' => 'Failed to send message']));
} 