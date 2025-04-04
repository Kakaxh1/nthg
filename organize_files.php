<?php
// Function to move files
function moveFile($source, $destination) {
    if (file_exists($source)) {
        if (!file_exists(dirname($destination))) {
            mkdir(dirname($destination), 0777, true);
        }
        rename($source, $destination);
        echo "Moved: $source -> $destination\n";
    }
}

// Move CSS files
moveFile('css/styles.css', 'public/css/styles.css');
moveFile('css/animations.css', 'public/css/animations.css');
moveFile('css/about.css', 'public/css/about.css');
moveFile('css/messages.css', 'public/css/messages.css');

// Move JS files
moveFile('js/main.js', 'public/js/main.js');
moveFile('js/animations.js', 'public/js/animations.js');

// Move includes
moveFile('includes/header.php', 'includes/header.php');
moveFile('includes/footer.php', 'includes/footer.php');
moveFile('includes/auth.php', 'includes/auth.php');
moveFile('includes/functions.php', 'includes/functions.php');

// Move config files
moveFile('config/database.php', 'includes/config/database.php');
moveFile('config/config.php', 'includes/config/config.php');

// Move SQL files
moveFile('sql/database.sql', 'sql/database.sql');
moveFile('sql/create_messages_table.sql', 'sql/migrations/create_messages_table.sql');
moveFile('sql/create_offer_items_table.sql', 'sql/migrations/create_offer_items_table.sql');

// Move pages
moveFile('browse.php', 'public/browse.php');
moveFile('login.php', 'public/login.php');
moveFile('register.php', 'public/register.php');
moveFile('index.php', 'public/index.php');

// Move user pages
moveFile('dashboard.php', 'user/pages/dashboard.php');
moveFile('profile.php', 'user/pages/profile.php');
moveFile('messages.php', 'user/pages/messages.php');
moveFile('my-listings.php', 'user/pages/listings.php');
moveFile('manage-offers.php', 'user/pages/offers.php');

// Create user-specific CSS and JS
file_put_contents('user/css/user.css', '/* User-specific styles */');
file_put_contents('user/css/profile.css', '/* Profile page styles */');
file_put_contents('user/css/messages.css', '/* Messages page styles */');
file_put_contents('user/js/user.js', '// User-specific scripts');
file_put_contents('user/js/profile.js', '// Profile page scripts');
file_put_contents('user/js/messages.js', '// Messages page scripts');

// Create admin files
file_put_contents('admin/css/admin.css', '/* Admin-specific styles */');
file_put_contents('admin/css/dashboard.css', '/* Admin dashboard styles */');
file_put_contents('admin/js/admin.js', '// Admin-specific scripts');
file_put_contents('admin/js/dashboard.js', '// Admin dashboard scripts');
file_put_contents('admin/pages/dashboard.php', '<?php require_once "../includes/header.php"; ?>');
file_put_contents('admin/pages/users.php', '<?php require_once "../includes/header.php"; ?>');
file_put_contents('admin/pages/listings.php', '<?php require_once "../includes/header.php"; ?>');
file_put_contents('admin/pages/categories.php', '<?php require_once "../includes/header.php"; ?>');
file_put_contents('admin/pages/settings.php', '<?php require_once "../includes/header.php"; ?>');

// Create .htaccess
$htaccess = <<<EOT
RewriteEngine On
RewriteBase /

# Handle HTTPS redirect
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

# Remove .php extension
RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{REQUEST_FILENAME}.php -f
RewriteRule ^([^\.]+)$ $1.php [NC,L]

# Security headers
Header set X-Content-Type-Options "nosniff"
Header set X-XSS-Protection "1; mode=block"
Header set X-Frame-Options "SAMEORIGIN"
Header set Referrer-Policy "strict-origin-when-cross-origin"

# Prevent directory listing
Options -Indexes

# Protect sensitive files
<FilesMatch "^\.">
    Order allow,deny
    Deny from all
</FilesMatch>

<FilesMatch "(config\.php|database\.php)$">
    Order allow,deny
    Deny from all
</FilesMatch>

# Enable GZIP compression
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/html text/plain text/xml text/css text/javascript application/javascript application/x-javascript application/json
</IfModule>

# Set browser caching
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType image/jpg "access plus 1 year"
    ExpiresByType image/jpeg "access plus 1 year"
    ExpiresByType image/png "access plus 1 year"
    ExpiresByType image/gif "access plus 1 year"
    ExpiresByType image/webp "access plus 1 year"
    ExpiresByType text/css "access plus 1 month"
    ExpiresByType application/javascript "access plus 1 month"
    ExpiresByType text/javascript "access plus 1 month"
</IfModule>
EOT;

file_put_contents('.htaccess', $htaccess);

echo "Files have been organized successfully!\n";
?> 