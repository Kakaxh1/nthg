<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

// Get categories for filter
$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();

// Build query based on filters
$where_conditions = ['status = "active"'];
$params = [];

if (isset($_GET['category']) && !empty($_GET['category'])) {
    $where_conditions[] = "category_id = ?";
    $params[] = $_GET['category'];
}

if (isset($_GET['search']) && !empty($_GET['search'])) {
    $where_conditions[] = "(title LIKE ? OR description LIKE ?)";
    $search_term = "%" . $_GET['search'] . "%";
    $params[] = $search_term;
    $params[] = $search_term;
}

$where_clause = implode(" AND ", $where_conditions);

// Get listings with user info
$query = "SELECT l.*, u.name as owner_name, u.location as owner_location, c.name as category_name 
          FROM listings l 
          JOIN users u ON l.user_id = u.id 
          JOIN categories c ON l.category_id = c.id 
          WHERE {$where_clause} 
          ORDER BY l.created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$listings = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Items - EcoSwap</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="browse.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="browse-container">
        <aside class="filter-sidebar">
            <div class="search-box">
                <form action="" method="GET" class="search-form">
                    <input type="text" 
                           name="search" 
                           placeholder="Search items..."
                           value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                    <button type="submit"><i class="fas fa-search"></i></button>
                </form>
            </div>

            <div class="filter-section">
                <h3>Categories</h3>
                <form action="" method="GET" class="category-filter">
                    <?php if (isset($_GET['search'])): ?>
                        <input type="hidden" name="search" value="<?php echo htmlspecialchars($_GET['search']); ?>">
                    <?php endif; ?>
                    
                    <div class="category-list">
                        <label class="category-item">
                            <input type="radio" 
                                   name="category" 
                                   value="" 
                                   <?php echo !isset($_GET['category']) ? 'checked' : ''; ?>>
                            <span>All Categories</span>
                        </label>
                        
                        <?php foreach ($categories as $category): ?>
                            <label class="category-item">
                                <input type="radio" 
                                       name="category" 
                                       value="<?php echo $category['id']; ?>"
                                       <?php echo isset($_GET['category']) && $_GET['category'] == $category['id'] ? 'checked' : ''; ?>>
                                <span><?php echo htmlspecialchars($category['name']); ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </form>
            </div>
        </aside>

        <main class="listings-grid">
            <?php if (empty($listings)): ?>
                <div class="no-results">
                    <i class="fas fa-search"></i>
                    <h2>No items found</h2>
                    <p>Try adjusting your search or filter criteria</p>
                </div>
            <?php else: ?>
                <?php foreach ($listings as $listing): ?>
                    <div class="listing-card" data-id="<?php echo $listing['id']; ?>">
                        <div class="listing-image">
                            <img src="<?php echo $listing['image_path'] ?: 'images/placeholder.jpg'; ?>" 
                                 alt="<?php echo htmlspecialchars($listing['title']); ?>">
                            <div class="listing-category">
                                <i class="fas fa-tag"></i>
                                <?php echo htmlspecialchars($listing['category_name']); ?>
                            </div>
                        </div>
                        <div class="listing-info">
                            <h3><?php echo htmlspecialchars($listing['title']); ?></h3>
                            <p class="listing-description">
                                <?php echo htmlspecialchars(substr($listing['description'], 0, 100)) . '...'; ?>
                            </p>
                            <div class="listing-meta">
                                <div class="owner-info">
                                    <i class="fas fa-user"></i>
                                    <span><?php echo htmlspecialchars($listing['owner_name']); ?></span>
                                </div>
                                <div class="location-info">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <span><?php echo htmlspecialchars($listing['owner_location']); ?></span>
                                </div>
                            </div>
                            <button class="view-details-btn" onclick="viewDetails(<?php echo $listing['id']; ?>)">
                                View Details
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </main>
    </div>

    <!-- Item Details Modal -->
    <div id="itemModal" class="modal">
        <div class="modal-content">
            <span class="close-modal">&times;</span>
            <div id="modalContent">
                <!-- Content will be loaded dynamically -->
            </div>
        </div>
    </div>

    <script>
        // Auto-submit category filter when changed
        document.querySelectorAll('.category-filter input[type="radio"]').forEach(radio => {
            radio.addEventListener('change', function() {
                this.closest('form').submit();
            });
        });

        // Modal functionality
        const modal = document.getElementById('itemModal');
        const closeBtn = document.querySelector('.close-modal');

        closeBtn.onclick = function() {
            modal.style.display = "none";
        }

        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }

        function viewDetails(listingId) {
            fetch(`get_listing_details.php?id=${listingId}`)
                .then(response => response.text())
                .then(html => {
                    document.getElementById('modalContent').innerHTML = html;
                    modal.style.display = "flex";
                });
        }
    </script>
</body>
</html> 