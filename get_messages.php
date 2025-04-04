<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

// Ensure user is logged in
if (!is_logged_in()) {
    http_response_code(401);
    exit('Unauthorized');
}

$thread_id = $_GET['thread_id'] ?? null;
$user_id = $_SESSION['user_id'];

if (!$thread_id) {
    http_response_code(400);
    exit('Thread ID required');
}

// Verify user has access to this thread
$stmt = $pdo->prepare("
    SELECT 
        mt.*,
        CASE 
            WHEN mt.user1_id = ? THEN mt.user2_id
            ELSE mt.user1_id
        END as other_user_id,
        u.name as other_user_name
    FROM message_threads mt
    JOIN users u ON (
        CASE 
            WHEN mt.user1_id = ? THEN mt.user2_id
            ELSE mt.user1_id
        END = u.id
    )
    WHERE mt.id = ? AND (mt.user1_id = ? OR mt.user2_id = ?)
");

$stmt->execute([$user_id, $user_id, $thread_id, $user_id, $user_id]);
$thread = $stmt->fetch();

if (!$thread) {
    http_response_code(403);
    exit('Access denied');
}

// Mark messages as read
$pdo->prepare("
    UPDATE messages 
    SET is_read = 1 
    WHERE thread_id = ? AND receiver_id = ?
")->execute([$thread_id, $user_id]);

// Get messages
$stmt = $pdo->prepare("
    SELECT 
        m.*,
        u.name as sender_name
    FROM messages m
    JOIN users u ON m.sender_id = u.id
    WHERE m.thread_id = ?
    ORDER BY m.created_at ASC
");

$stmt->execute([$thread_id]);
$messages = $stmt->fetchAll();

// Return chat interface HTML
?>
<div class="chat-header">
    <div class="chat-user-info">
        <i class="fas fa-user-circle"></i>
        <h3><?php echo htmlspecialchars($thread['other_user_name']); ?></h3>
    </div>
</div>

<div class="chat-messages" id="chatMessages">
    <?php foreach ($messages as $message): ?>
        <div class="message <?php echo $message['sender_id'] === $user_id ? 'sent' : 'received'; ?>">
            <div class="message-content">
                <p><?php echo htmlspecialchars($message['content']); ?></p>
                <span class="message-time">
                    <?php echo format_time($message['created_at']); ?>
                </span>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<form id="messageForm" class="message-form">
    <input type="text" name="message" placeholder="Type a message..." autocomplete="off">
    <button type="submit">
        <i class="fas fa-paper-plane"></i>
    </button>
</form> 