<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

// Ensure user is logged in
if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Handle offer actions (accept/reject)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['offer_id'])) {
    $offer_id = (int)$_POST['offer_id'];
    $action = $_POST['action'];

    try {
        $pdo->beginTransaction();

        // Verify the offer exists and belongs to the user's listing
        $stmt = $pdo->prepare("
            SELECT o.*, l.title as listing_title, l.user_id as listing_owner_id 
            FROM offers o
            JOIN listings l ON o.to_listing_id = l.id
            WHERE o.id = ? AND l.user_id = ? AND o.status = 'pending'
        ");
        $stmt->execute([$offer_id, $user_id]);
        $offer = $stmt->fetch();

        if (!$offer) {
            throw new Exception('Invalid offer or action not allowed');
        }

        if ($action === 'accept') {
            // Update offer status
            $stmt = $pdo->prepare("UPDATE offers SET status = 'accepted' WHERE id = ?");
            $stmt->execute([$offer_id]);

            // Update the status of both listings to 'exchanged'
            $stmt = $pdo->prepare("
                UPDATE listings 
                SET status = 'exchanged' 
                WHERE id = ? OR id IN (
                    SELECT listing_id FROM offer_items WHERE offer_id = ?
                )
            ");
            $stmt->execute([$offer['to_listing_id'], $offer_id]);

            // Reject all other pending offers for this listing
            $stmt = $pdo->prepare("
                UPDATE offers 
                SET status = 'rejected' 
                WHERE to_listing_id = ? AND id != ? AND status = 'pending'
            ");
            $stmt->execute([$offer['to_listing_id'], $offer_id]);

            // Create notifications
            $stmt = $pdo->prepare("
                INSERT INTO notifications (user_id, type, content, related_id, created_at)
                VALUES (?, 'offer_accepted', ?, ?, NOW())
            ");
            $stmt->execute([
                $offer['from_user_id'],
                'Your offer has been accepted!',
                $offer_id
            ]);

        } elseif ($action === 'reject') {
            // Update offer status
            $stmt = $pdo->prepare("UPDATE offers SET status = 'rejected' WHERE id = ?");
            $stmt->execute([$offer_id]);

            // Create notification
            $stmt = $pdo->prepare("
                INSERT INTO notifications (user_id, type, content, related_id, created_at)
                VALUES (?, 'offer_rejected', ?, ?, NOW())
            ");
            $stmt->execute([
                $offer['from_user_id'],
                'Your offer has been declined',
                $offer_id
            ]);
        }

        $pdo->commit();
        $success_message = 'Offer has been ' . ($action === 'accept' ? 'accepted' : 'rejected') . ' successfully.';
    } catch (Exception $e) {
        $pdo->rollBack();
        $error_message = $e->getMessage();
    }
}

// Fetch received offers
$stmt = $pdo->prepare("
    SELECT 
        o.id as offer_id,
        o.message,
        o.status,
        o.created_at,
        l.title as requested_item_title,
        l.image_path as requested_item_image,
        u.name as from_user_name,
        u.location as from_user_location
    FROM offers o
    JOIN listings l ON o.to_listing_id = l.id
    JOIN users u ON o.from_user_id = u.id
    WHERE l.user_id = ?
    ORDER BY o.created_at DESC
");
$stmt->execute([$user_id]);
$received_offers = $stmt->fetchAll();

// Fetch sent offers
$stmt = $pdo->prepare("
    SELECT 
        o.id as offer_id,
        o.message,
        o.status,
        o.created_at,
        l.title as requested_item_title,
        l.image_path as requested_item_image,
        u.name as to_user_name
    FROM offers o
    JOIN listings l ON o.to_listing_id = l.id
    JOIN users u ON l.user_id = u.id
    WHERE o.from_user_id = ?
    ORDER BY o.created_at DESC
");
$stmt->execute([$user_id]);
$sent_offers = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Offers - EcoSwap</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .offers-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .offers-tabs {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .tab-button {
            padding: 0.75rem 1.5rem;
            border: none;
            background: #f5f5f5;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 500;
        }

        .tab-button.active {
            background: #55883B;
            color: white;
        }

        .offers-grid {
            display: grid;
            gap: 1.5rem;
        }

        .offer-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .offer-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 1rem;
        }

        .offer-items {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin: 1rem 0;
            padding: 1rem;
            background: #f8f8f8;
            border-radius: 8px;
        }

        .offer-item {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .offer-item img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 5px;
        }

        .offer-actions {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
        }

        .accept-btn, .reject-btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: background-color 0.3s;
        }

        .accept-btn {
            background: #55883B;
            color: white;
        }

        .accept-btn:hover {
            background: #446c2f;
        }

        .reject-btn {
            background: #dc3545;
            color: white;
        }

        .reject-btn:hover {
            background: #bb2d3b;
        }

        .offer-status {
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .status-pending { background: #ffd700; color: #000; }
        .status-accepted { background: #55883B; color: white; }
        .status-rejected { background: #dc3545; color: white; }

        .offer-message {
            margin: 1rem 0;
            padding: 1rem;
            background: #f8f8f8;
            border-radius: 5px;
            font-style: italic;
        }

        @media (max-width: 768px) {
            .offer-items {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="offers-container">
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success">
                <?php echo $success_message; ?>
            </div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-error">
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <div class="offers-tabs">
            <button class="tab-button active" onclick="showTab('received')">Received Offers</button>
            <button class="tab-button" onclick="showTab('sent')">Sent Offers</button>
        </div>

        <div id="received-offers" class="offers-grid">
            <?php if (empty($received_offers)): ?>
                <p>No offers received yet.</p>
            <?php else: ?>
                <?php foreach ($received_offers as $offer): ?>
                    <div class="offer-card">
                        <div class="offer-header">
                            <div>
                                <h3>Offer from <?php echo htmlspecialchars($offer['from_user_name']); ?></h3>
                                <small><?php echo htmlspecialchars($offer['from_user_location']); ?></small>
                            </div>
                            <span class="offer-status status-<?php echo $offer['status']; ?>">
                                <?php echo ucfirst($offer['status']); ?>
                            </span>
                        </div>

                        <div class="offer-message">
                            "<?php echo htmlspecialchars($offer['message']); ?>"
                        </div>

                        <div class="offer-items">
                            <div class="offer-item">
                                <img src="<?php echo $offer['requested_item_image'] ?: 'images/placeholder.jpg'; ?>" 
                                     alt="Your item">
                                <div>
                                    <strong>Your Item:</strong>
                                    <p><?php echo htmlspecialchars($offer['requested_item_title']); ?></p>
                                </div>
                            </div>
                            <?php
                            // Fetch offered items
                            $stmt = $pdo->prepare("
                                SELECT l.title, l.image_path
                                FROM offer_items oi
                                JOIN listings l ON oi.listing_id = l.id
                                WHERE oi.offer_id = ?
                            ");
                            $stmt->execute([$offer['offer_id']]);
                            $offered_items = $stmt->fetchAll();
                            foreach ($offered_items as $item):
                            ?>
                                <div class="offer-item">
                                    <img src="<?php echo $item['image_path'] ?: 'images/placeholder.jpg'; ?>" 
                                         alt="Offered item">
                                    <div>
                                        <strong>Offered Item:</strong>
                                        <p><?php echo htmlspecialchars($item['title']); ?></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <?php if ($offer['status'] === 'pending'): ?>
                            <div class="offer-actions">
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="offer_id" value="<?php echo $offer['offer_id']; ?>">
                                    <input type="hidden" name="action" value="accept">
                                    <button type="submit" class="accept-btn">
                                        <i class="fas fa-check"></i> Accept Offer
                                    </button>
                                </form>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="offer_id" value="<?php echo $offer['offer_id']; ?>">
                                    <input type="hidden" name="action" value="reject">
                                    <button type="submit" class="reject-btn">
                                        <i class="fas fa-times"></i> Reject Offer
                                    </button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div id="sent-offers" class="offers-grid" style="display: none;">
            <?php if (empty($sent_offers)): ?>
                <p>No offers sent yet.</p>
            <?php else: ?>
                <?php foreach ($sent_offers as $offer): ?>
                    <div class="offer-card">
                        <div class="offer-header">
                            <div>
                                <h3>Offer to <?php echo htmlspecialchars($offer['to_user_name']); ?></h3>
                                <small>Sent on <?php echo date('M j, Y', strtotime($offer['created_at'])); ?></small>
                            </div>
                            <span class="offer-status status-<?php echo $offer['status']; ?>">
                                <?php echo ucfirst($offer['status']); ?>
                            </span>
                        </div>

                        <div class="offer-message">
                            "<?php echo htmlspecialchars($offer['message']); ?>"
                        </div>

                        <div class="offer-items">
                            <div class="offer-item">
                                <img src="<?php echo $offer['requested_item_image'] ?: 'images/placeholder.jpg'; ?>" 
                                     alt="Requested item">
                                <div>
                                    <strong>Requested Item:</strong>
                                    <p><?php echo htmlspecialchars($offer['requested_item_title']); ?></p>
                                </div>
                            </div>
                            <?php
                            // Fetch offered items
                            $stmt = $pdo->prepare("
                                SELECT l.title, l.image_path
                                FROM offer_items oi
                                JOIN listings l ON oi.listing_id = l.id
                                WHERE oi.offer_id = ?
                            ");
                            $stmt->execute([$offer['offer_id']]);
                            $offered_items = $stmt->fetchAll();
                            foreach ($offered_items as $item):
                            ?>
                                <div class="offer-item">
                                    <img src="<?php echo $item['image_path'] ?: 'images/placeholder.jpg'; ?>" 
                                         alt="Your offered item">
                                    <div>
                                        <strong>Your Offered Item:</strong>
                                        <p><?php echo htmlspecialchars($item['title']); ?></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
    function showTab(tabName) {
        // Update tab buttons
        document.querySelectorAll('.tab-button').forEach(button => {
            button.classList.remove('active');
        });
        event.target.classList.add('active');

        // Show selected tab content
        document.getElementById('received-offers').style.display = tabName === 'received' ? 'grid' : 'none';
        document.getElementById('sent-offers').style.display = tabName === 'sent' ? 'grid' : 'none';
    }
    </script>

    <?php include 'includes/footer.php'; ?>
</body>
</html> 