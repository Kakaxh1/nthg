<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

function runTest($name, $callback) {
    echo "\nTesting $name...\n";
    try {
        $callback();
        echo "✓ Test passed\n";
    } catch (Exception $e) {
        echo "✗ Test failed: " . $e->getMessage() . "\n";
    }
}

// Test Database Connection
runTest('Database Connection', function() {
    global $pdo;
    $pdo->query('SELECT 1');
});

// Test User Registration
runTest('User Registration', function() {
    global $pdo;
    
    // Clean up test user if exists
    $pdo->exec("DELETE FROM users WHERE email = 'test@example.com'");
    
    $stmt = $pdo->prepare("
        INSERT INTO users (name, email, password, location, created_at)
        VALUES (?, ?, ?, ?, NOW())
    ");
    
    $stmt->execute([
        'Test User',
        'test@example.com',
        password_hash('TestPass123!', PASSWORD_DEFAULT),
        'Test Location'
    ]);
    
    if ($pdo->lastInsertId() <= 0) {
        throw new Exception('Failed to create test user');
    }
});

// Test User Login
runTest('User Login', function() {
    $email = 'test@example.com';
    $password = 'TestPass123!';
    
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if (!$user || !password_verify($password, $user['password'])) {
        throw new Exception('Login verification failed');
    }
});

// Test Listing Creation
runTest('Listing Creation', function() {
    global $pdo;
    
    // Get test user
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute(['test@example.com']);
    $user_id = $stmt->fetchColumn();
    
    // Create test category if not exists
    $pdo->exec("
        INSERT IGNORE INTO categories (name, description)
        VALUES ('Test Category', 'Category for testing')
    ");
    $category_id = $pdo->lastInsertId() ?: $pdo->query("SELECT id FROM categories WHERE name = 'Test Category'")->fetchColumn();
    
    // Create test listing
    $stmt = $pdo->prepare("
        INSERT INTO listings (
            user_id, category_id, title, description, 
            image_path, status, created_at
        ) VALUES (?, ?, ?, ?, ?, 'active', NOW())
    ");
    
    $stmt->execute([
        $user_id,
        $category_id,
        'Test Item',
        'This is a test item description',
        'images/placeholder.jpg'
    ]);
    
    if ($pdo->lastInsertId() <= 0) {
        throw new Exception('Failed to create test listing');
    }
});

// Test Offer Creation
runTest('Offer Creation', function() {
    global $pdo;
    
    // Get test user and listing
    $stmt = $pdo->prepare("
        SELECT u.id as user_id, l.id as listing_id
        FROM users u
        JOIN listings l ON l.user_id != u.id
        WHERE u.email = ?
        LIMIT 1
    ");
    $stmt->execute(['test@example.com']);
    $data = $stmt->fetch();
    
    if (!$data) {
        throw new Exception('Test data not found');
    }
    
    // Create offer
    $stmt = $pdo->prepare("
        INSERT INTO offers (
            from_user_id, to_listing_id, message,
            status, created_at
        ) VALUES (?, ?, ?, 'pending', NOW())
    ");
    
    $stmt->execute([
        $data['user_id'],
        $data['listing_id'],
        'Test offer message'
    ]);
    
    $offer_id = $pdo->lastInsertId();
    if ($offer_id <= 0) {
        throw new Exception('Failed to create test offer');
    }
    
    // Add offered items
    $stmt = $pdo->prepare("
        INSERT INTO offer_items (offer_id, listing_id)
        SELECT ?, id FROM listings 
        WHERE user_id = ? 
        LIMIT 1
    ");
    
    $stmt->execute([$offer_id, $data['user_id']]);
});

// Test Message Thread Creation
runTest('Message Thread Creation', function() {
    global $pdo;
    
    // Get test user and another user
    $stmt = $pdo->prepare("
        SELECT u1.id as user1_id, u2.id as user2_id
        FROM users u1
        JOIN users u2 ON u2.id != u1.id
        WHERE u1.email = ?
        LIMIT 1
    ");
    $stmt->execute(['test@example.com']);
    $data = $stmt->fetch();
    
    if (!$data) {
        throw new Exception('Test users not found');
    }
    
    // Create message thread
    $stmt = $pdo->prepare("
        INSERT INTO message_threads (user1_id, user2_id, last_message_at)
        VALUES (?, ?, NOW())
        ON DUPLICATE KEY UPDATE last_message_at = NOW()
    ");
    
    $stmt->execute([$data['user1_id'], $data['user2_id']]);
    $thread_id = $pdo->lastInsertId();
    
    // Create test message
    $stmt = $pdo->prepare("
        INSERT INTO messages (
            thread_id, from_user_id, to_user_id,
            content, created_at
        ) VALUES (?, ?, ?, ?, NOW())
    ");
    
    $stmt->execute([
        $thread_id,
        $data['user1_id'],
        $data['user2_id'],
        'Test message content'
    ]);
    
    if ($pdo->lastInsertId() <= 0) {
        throw new Exception('Failed to create test message');
    }
});

// Test Notifications
runTest('Notifications', function() {
    global $pdo;
    
    // Get test user
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute(['test@example.com']);
    $user_id = $stmt->fetchColumn();
    
    // Create test notification
    $stmt = $pdo->prepare("
        INSERT INTO notifications (
            user_id, type, content, related_id, created_at
        ) VALUES (?, ?, ?, ?, NOW())
    ");
    
    $stmt->execute([
        $user_id,
        'test',
        'Test notification',
        1
    ]);
    
    if ($pdo->lastInsertId() <= 0) {
        throw new Exception('Failed to create test notification');
    }
});

// Clean up test data
runTest('Cleanup', function() {
    global $pdo;
    
    $pdo->beginTransaction();
    
    try {
        // Get test user ID
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute(['test@example.com']);
        $user_id = $stmt->fetchColumn();
        
        // Delete test messages and threads
        $pdo->exec("DELETE FROM messages WHERE from_user_id = $user_id OR to_user_id = $user_id");
        $pdo->exec("DELETE FROM message_threads WHERE user1_id = $user_id OR user2_id = $user_id");
        
        // Delete test notifications
        $pdo->exec("DELETE FROM notifications WHERE user_id = $user_id");
        
        // Delete test offers and offer items
        $pdo->exec("
            DELETE oi FROM offer_items oi
            JOIN offers o ON oi.offer_id = o.id
            WHERE o.from_user_id = $user_id
        ");
        $pdo->exec("DELETE FROM offers WHERE from_user_id = $user_id");
        
        // Delete test listings
        $pdo->exec("DELETE FROM listings WHERE user_id = $user_id");
        
        // Delete test user
        $pdo->exec("DELETE FROM users WHERE id = $user_id");
        
        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
});

echo "\nAll tests completed!\n"; 