<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

if (!isset($_GET['id'])) {
    die('No listing ID provided');
}

$listing_id = (int)$_GET['id'];

// Get listing details with user and category info
$query = "SELECT l.*, u.name as owner_name, u.location as owner_location, 
                 u.email as owner_email, c.name as category_name 
          FROM listings l 
          JOIN users u ON l.user_id = u.id 
          JOIN categories c ON l.category_id = c.id 
          WHERE l.id = ? AND l.status = 'active'";

$stmt = $pdo->prepare($query);
$stmt->execute([$listing_id]);
$listing = $stmt->fetch();

if (!$listing) {
    die('Listing not found');
}

// Check if the current user has already made an offer
$has_offer = false;
if (is_logged_in()) {
    $stmt = $pdo->prepare("SELECT id FROM offers WHERE from_user_id = ? AND to_listing_id = ?");
    $stmt->execute([$_SESSION['user_id'], $listing_id]);
    $has_offer = $stmt->fetch() !== false;
}
?>

<div class="listing-details">
    <div class="listing-gallery">
        <img src="<?php echo $listing['image_path'] ?: 'images/placeholder.jpg'; ?>" 
             alt="<?php echo htmlspecialchars($listing['title']); ?>">
    </div>

    <div class="listing-content">
        <div class="listing-header">
            <h2><?php echo htmlspecialchars($listing['title']); ?></h2>
            <span class="listing-category">
                <i class="fas fa-tag"></i>
                <?php echo htmlspecialchars($listing['category_name']); ?>
            </span>
        </div>

        <div class="owner-details">
            <div class="owner-info">
                <i class="fas fa-user"></i>
                <div>
                    <strong><?php echo htmlspecialchars($listing['owner_name']); ?></strong>
                    <span class="owner-location">
                        <i class="fas fa-map-marker-alt"></i>
                        <?php echo htmlspecialchars($listing['owner_location']); ?>
                    </span>
                </div>
            </div>
            <?php if (is_logged_in() && $listing['user_id'] != $_SESSION['user_id']): ?>
                <button class="message-btn" onclick="messageOwner('<?php echo htmlspecialchars($listing['owner_name']); ?>')">
                    <i class="fas fa-envelope"></i> Message
                </button>
            <?php endif; ?>
        </div>

        <div class="listing-description">
            <h3>Description</h3>
            <p><?php echo nl2br(htmlspecialchars($listing['description'])); ?></p>
        </div>

        <?php if (is_logged_in()): ?>
            <?php if ($listing['user_id'] != $_SESSION['user_id']): ?>
                <?php if (!$has_offer): ?>
                    <?php
                    // Get user's active listings
                    $stmt = $pdo->prepare("SELECT id, title, image_path FROM listings WHERE user_id = ? AND status = 'active'");
                    $stmt->execute([$_SESSION['user_id']]);
                    $user_listings = $stmt->fetchAll();
                    ?>
                    <div class="make-offer">
                        <h3>Make an Offer</h3>
                        <form id="offerForm" onsubmit="submitOffer(event, <?php echo $listing_id; ?>)">
                            <div class="form-group">
                                <label>Select Items to Offer</label>
                                <div class="user-listings-grid">
                                    <?php if (empty($user_listings)): ?>
                                        <p class="no-listings">You don't have any active listings to offer. <a href="create_listing.php">Create a listing</a> first.</p>
                                    <?php else: ?>
                                        <?php foreach ($user_listings as $item): ?>
                                            <div class="listing-select-item">
                                                <input type="checkbox" 
                                                       name="offered_items[]" 
                                                       value="<?php echo $item['id']; ?>" 
                                                       id="item_<?php echo $item['id']; ?>">
                                                <label for="item_<?php echo $item['id']; ?>">
                                                    <img src="<?php echo $item['image_path'] ?: 'images/placeholder.jpg'; ?>" 
                                                         alt="<?php echo htmlspecialchars($item['title']); ?>">
                                                    <span><?php echo htmlspecialchars($item['title']); ?></span>
                                                </label>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="offerMessage">Your Message</label>
                                <textarea id="offerMessage" required
                                    placeholder="Introduce yourself and explain why you'd like to make this exchange..."></textarea>
                            </div>
                            <button type="submit" class="submit-offer-btn" <?php echo empty($user_listings) ? 'disabled' : ''; ?>>
                                <i class="fas fa-handshake"></i> Make Offer
                            </button>
                        </form>
                    </div>
                <?php else: ?>
                    <div class="offer-made">
                        <i class="fas fa-check-circle"></i>
                        <p>You have already made an offer for this item</p>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="own-listing">
                    <i class="fas fa-info-circle"></i>
                    <p>This is your listing</p>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="login-prompt">
                <p>Please <a href="login.php">login</a> to make an offer or message the owner</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.listing-details {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 2rem;
}

.listing-gallery img {
    width: 100%;
    border-radius: 10px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.listing-header {
    display: flex;
    justify-content: space-between;
    align-items: start;
    margin-bottom: 1.5rem;
}

.listing-header h2 {
    color: #333;
    margin: 0;
}

.owner-details {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #f8f8f8;
    padding: 1rem;
    border-radius: 10px;
    margin-bottom: 1.5rem;
}

.owner-info {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.owner-info i {
    font-size: 1.5rem;
    color: #55883B;
}

.owner-location {
    display: block;
    font-size: 0.9rem;
    color: #666;
}

.message-btn {
    background: #55883B;
    color: white;
    border: none;
    padding: 0.5rem 1rem;
    border-radius: 5px;
    cursor: pointer;
    transition: background-color 0.3s ease;
}

.message-btn:hover {
    background: #446c2f;
}

.listing-description {
    margin-bottom: 2rem;
}

.listing-description h3 {
    color: #333;
    margin-bottom: 0.5rem;
}

.listing-description p {
    color: #666;
    line-height: 1.6;
}

.make-offer h3 {
    color: #333;
    margin-bottom: 1rem;
}

.make-offer textarea {
    width: 100%;
    height: 100px;
    padding: 0.75rem;
    border: 1px solid #ddd;
    border-radius: 5px;
    margin-bottom: 1rem;
    resize: vertical;
}

.submit-offer-btn {
    background: #55883B;
    color: white;
    border: none;
    padding: 0.75rem 1.5rem;
    border-radius: 5px;
    cursor: pointer;
    transition: background-color 0.3s ease;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.submit-offer-btn:hover {
    background: #446c2f;
}

.offer-made,
.own-listing,
.login-prompt {
    background: #f8f8f8;
    padding: 1rem;
    border-radius: 5px;
    text-align: center;
    color: #666;
}

.offer-made i,
.own-listing i {
    color: #55883B;
    font-size: 1.5rem;
    margin-bottom: 0.5rem;
}

.login-prompt a {
    color: #55883B;
    text-decoration: none;
}

.login-prompt a:hover {
    text-decoration: underline;
}

@media (max-width: 768px) {
    .listing-details {
        grid-template-columns: 1fr;
    }
}

.user-listings-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.listing-select-item {
    position: relative;
}

.listing-select-item input[type="checkbox"] {
    display: none;
}

.listing-select-item label {
    display: block;
    cursor: pointer;
    border: 2px solid transparent;
    border-radius: 8px;
    overflow: hidden;
    transition: all 0.3s ease;
}

.listing-select-item label img {
    width: 100%;
    height: 100px;
    object-fit: cover;
}

.listing-select-item label span {
    display: block;
    padding: 0.5rem;
    font-size: 0.9rem;
    color: #333;
    text-align: center;
    background: #f8f8f8;
}

.listing-select-item input[type="checkbox"]:checked + label {
    border-color: #55883B;
    box-shadow: 0 0 0 2px #55883B;
}

.no-listings {
    grid-column: 1 / -1;
    text-align: center;
    padding: 1rem;
    background: #f8f8f8;
    border-radius: 5px;
}

.no-listings a {
    color: #55883B;
    text-decoration: none;
}

.no-listings a:hover {
    text-decoration: underline;
}

button[disabled] {
    opacity: 0.6;
    cursor: not-allowed;
}
</style>

<script>
function messageOwner(ownerName) {
    // Implement messaging functionality
    console.log('Messaging:', ownerName);
}

function submitOffer(event, listingId) {
    event.preventDefault();
    const message = document.getElementById('offerMessage').value;
    const offeredItems = Array.from(document.querySelectorAll('input[name="offered_items[]"]:checked'))
        .map(checkbox => checkbox.value);
    
    if (offeredItems.length === 0) {
        alert('Please select at least one item to offer in exchange.');
        return;
    }
    
    // Send offer to server
    fetch('submit_offer.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            listing_id: listingId,
            message: message,
            offered_items: offeredItems
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.querySelector('.make-offer').innerHTML = `
                <div class="offer-made">
                    <i class="fas fa-check-circle"></i>
                    <p>Your offer has been sent successfully!</p>
                </div>
            `;
        } else {
            alert(data.error || 'Failed to submit offer. Please try again.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to submit offer. Please try again.');
    });
}
</script> 