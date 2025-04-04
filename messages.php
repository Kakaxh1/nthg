<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

// Ensure user is logged in
if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Get all message threads for the current user
$stmt = $pdo->prepare("
    SELECT 
        mt.id as thread_id,
        mt.last_message_at,
        CASE 
            WHEN mt.user1_id = ? THEN mt.user2_id
            ELSE mt.user1_id
        END as other_user_id,
        u.name as other_user_name,
        (
            SELECT content 
            FROM messages 
            WHERE thread_id = mt.id 
            ORDER BY created_at DESC 
            LIMIT 1
        ) as last_message,
        (
            SELECT created_at 
            FROM messages 
            WHERE thread_id = mt.id 
            ORDER BY created_at DESC 
            LIMIT 1
        ) as last_message_time,
        (
            SELECT COUNT(*) 
            FROM messages 
            WHERE thread_id = mt.id 
            AND receiver_id = ? 
            AND is_read = 0
        ) as unread_count
    FROM message_threads mt
    JOIN users u ON (
        CASE 
            WHEN mt.user1_id = ? THEN mt.user2_id
            ELSE mt.user1_id
        END = u.id
    )
    WHERE mt.user1_id = ? OR mt.user2_id = ?
    ORDER BY mt.last_message_at DESC
");

$stmt->execute([$user_id, $user_id, $user_id, $user_id, $user_id]);
$threads = $stmt->fetchAll();

require_once 'includes/header.php';
?>

<main class="container messages-container">
    <div class="messages-wrapper">
        <!-- Threads List -->
        <div class="threads-list">
            <div class="threads-header">
                <h2>Messages</h2>
            </div>
            
            <div class="threads-content">
                <?php if (empty($threads)): ?>
                    <div class="no-messages">
                        <i class="fas fa-inbox fa-2x"></i>
                        <p>No messages yet</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($threads as $thread): ?>
                        <div class="thread-item <?php echo $thread['unread_count'] > 0 ? 'unread' : ''; ?>" 
                             data-thread-id="<?php echo $thread['thread_id']; ?>"
                             data-other-user-id="<?php echo $thread['other_user_id']; ?>">
                            <div class="thread-avatar">
                                <i class="fas fa-user-circle"></i>
                            </div>
                            <div class="thread-content">
                                <div class="thread-header">
                                    <h3><?php echo htmlspecialchars($thread['other_user_name']); ?></h3>
                                    <span class="thread-time">
                                        <?php echo format_time($thread['last_message_time']); ?>
                                    </span>
                                </div>
                                <p class="thread-preview">
                                    <?php echo htmlspecialchars(substr($thread['last_message'], 0, 50)) . '...'; ?>
                                </p>
                            </div>
                            <?php if ($thread['unread_count'] > 0): ?>
                                <span class="unread-badge"><?php echo $thread['unread_count']; ?></span>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Chat Area -->
        <div class="chat-area" id="chatArea">
            <div class="chat-placeholder">
                <i class="fas fa-comments fa-3x"></i>
                <p>Select a conversation to start messaging</p>
            </div>
        </div>
    </div>
</main>

<!-- Message Template -->
<template id="messageTemplate">
    <div class="message">
        <div class="message-content">
            <p></p>
            <span class="message-time"></span>
        </div>
    </div>
</template>

<script>
function format_time(timestamp) {
    const date = new Date(timestamp);
    const now = new Date();
    const diff = now - date;
    const days = Math.floor(diff / (1000 * 60 * 60 * 24));
    
    if (days === 0) {
        return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    } else if (days === 1) {
        return 'Yesterday';
    } else if (days < 7) {
        return date.toLocaleDateString([], { weekday: 'short' });
    } else {
        return date.toLocaleDateString([], { month: 'short', day: 'numeric' });
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const chatArea = document.getElementById('chatArea');
    const threadItems = document.querySelectorAll('.thread-item');
    let currentThreadId = null;
    let lastMessageId = 0;
    
    // Click handler for thread items
    threadItems.forEach(item => {
        item.addEventListener('click', async function() {
            const threadId = this.dataset.threadId;
            const otherUserId = this.dataset.otherUserId;
            
            // Remove unread styling
            this.classList.remove('unread');
            this.querySelector('.unread-badge')?.remove();
            
            // Load chat interface
            const response = await fetch(`get_messages.php?thread_id=${threadId}`);
            const html = await response.text();
            chatArea.innerHTML = html;
            
            // Set up message form
            const messageForm = chatArea.querySelector('#messageForm');
            messageForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                const messageInput = messageForm.querySelector('input[name="message"]');
                const message = messageInput.value.trim();
                
                if (message) {
                    const formData = new FormData();
                    formData.append('thread_id', threadId);
                    formData.append('receiver_id', otherUserId);
                    formData.append('message', message);
                    
                    const response = await fetch('send_message.php', {
                        method: 'POST',
                        body: formData
                    });
                    
                    if (response.ok) {
                        messageInput.value = '';
                        // Append message to chat
                        appendMessage({
                            content: message,
                            created_at: new Date().toISOString(),
                            is_sender: true
                        });
                        scrollToBottom();
                    }
                }
            });
            
            currentThreadId = threadId;
            startPolling();
            scrollToBottom();
        });
    });
    
    function appendMessage(message) {
        const template = document.getElementById('messageTemplate');
        const messageElement = template.content.cloneNode(true);
        
        const messageDiv = messageElement.querySelector('.message');
        messageDiv.classList.add(message.is_sender ? 'sent' : 'received');
        
        const content = messageElement.querySelector('p');
        content.textContent = message.content;
        
        const time = messageElement.querySelector('.message-time');
        time.textContent = format_time(message.created_at);
        
        const chatMessages = document.querySelector('.chat-messages');
        chatMessages.appendChild(messageElement);
    }
    
    function scrollToBottom() {
        const chatMessages = document.querySelector('.chat-messages');
        if (chatMessages) {
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }
    }
    
    // Poll for new messages
    function startPolling() {
        if (window.pollInterval) {
            clearInterval(window.pollInterval);
        }
        
        window.pollInterval = setInterval(async () => {
            if (!currentThreadId) return;
            
            const response = await fetch(`get_new_messages.php?thread_id=${currentThreadId}&last_id=${lastMessageId}`);
            const messages = await response.json();
            
            if (messages.length > 0) {
                messages.forEach(message => {
                    appendMessage(message);
                    lastMessageId = Math.max(lastMessageId, message.id);
                });
                scrollToBottom();
            }
        }, 3000);
    }
});
</script>

<?php
function format_time($timestamp) {
    if (!$timestamp) return '';
    
    $date = new DateTime($timestamp);
    $now = new DateTime();
    $diff = $now->diff($date);
    
    if ($diff->d === 0) {
        return $date->format('H:i');
    } else if ($diff->d === 1) {
        return 'Yesterday';
    } else if ($diff->d < 7) {
        return $date->format('D');
    } else {
        return $date->format('M j');
    }
}

require_once 'includes/footer.php';
?> 