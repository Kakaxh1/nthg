<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

// Ensure user is logged in
if (!is_logged_in()) {
    http_response_code(401);
    exit(json_encode(['error' => 'Unauthorized']));
}

$thread_id = $_GET['thread_id'] ?? null;
$last_id = $_GET['last_id'] ?? 0;
$user_id = $_SESSION['user_id'];

if (!$thread_id) {
    http_response_code(400);
    exit(json_encode(['error' => 'Thread ID required']));
}

// Verify user has access to this thread
$stmt = $pdo->prepare("
    SELECT * FROM message_threads 
    WHERE id = ? AND (user1_id = ? OR user2_id = ?)
");
$stmt->execute([$thread_id, $user_id, $user_id]);

if (!$stmt->fetch()) {
    http_response_code(403);
    exit(json_encode(['error' => 'Access denied']));
}

// Get new messages
$stmt = $pdo->prepare("
    SELECT 
        m.*,
        u.name as sender_name
    FROM messages m
    JOIN users u ON m.sender_id = u.id
    WHERE m.thread_id = ? 
    AND m.id > ?
    ORDER BY m.created_at ASC
");

$stmt->execute([$thread_id, $last_id]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Mark messages as read
if (!empty($messages)) {
    $pdo->prepare("
        UPDATE messages 
        SET is_read = 1 
        WHERE thread_id = ? 
        AND receiver_id = ? 
        AND is_read = 0
    ")->execute([$thread_id, $user_id]);
}

// Format messages for response
$formatted_messages = array_map(function($message) use ($user_id) {
    return [
        'id' => $message['id'],
        'content' => $message['content'],
        'created_at' => $message['created_at'],
        'is_sender' => $message['sender_id'] == $user_id,
        'sender_name' => $message['sender_name']
    ];
}, $messages);

echo json_encode($formatted_messages); 