# EcoSwap - Sustainable Item Exchange Platform

## Local Development Setup

### Prerequisites
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache web server
- Composer (for PHP dependencies)

### Installation Steps

1. **Install XAMPP/WampServer/MAMP**
   - Download and install from their official website
   - Start Apache and MySQL services

2. **Clone/Copy Project**
   - For XAMPP: Copy project to `C:/xampp/htdocs/ecoswap`
   - For WampServer: Copy to `C:/wamp64/www/ecoswap`
   - For MAMP: Copy to `/Applications/MAMP/htdocs/ecoswap`

3. **Database Setup**
   ```sql
   CREATE DATABASE ecoswap;
   USE ecoswap;
   -- Import the database schema from sql/database.sql
   ```

4. **Configure Database Connection**
   - Copy `config/database.example.php` to `config/database.php`
   - Update with your local database credentials:
     ```php
     define('DB_HOST', 'localhost');
     define('DB_USER', 'root');
     define('DB_PASS', ''); // Default XAMPP password is blank
     define('DB_NAME', 'ecoswap');
     ```

5. **File Permissions**
   - Ensure upload directories are writable:
     ```bash
     chmod 777 uploads/
     chmod 777 uploads/items/
     chmod 777 uploads/profiles/
     ```

6. **Access the Website**
   - Open your browser and navigate to:
     - XAMPP: `http://localhost/ecoswap`
     - WampServer: `http://localhost/ecoswap`
     - MAMP: `http://localhost:8888/ecoswap`

### Development

The project structure is organized as follows:
```
ecoswap/
├── config/           # Configuration files
├── includes/         # Reusable PHP components
├── js/              # JavaScript files
├── css/             # CSS stylesheets
├── uploads/         # User uploaded files
├── sql/            # Database schema and migrations
└── index.php       # Entry point
```

### Common Issues

1. **Database Connection Error**
   - Verify MySQL is running
   - Check database credentials in config/database.php
   - Ensure database exists and is properly imported

2. **Upload Issues**
   - Check folder permissions (777 for upload directories)
   - Verify PHP upload settings in php.ini

3. **404 Errors**
   - Ensure Apache mod_rewrite is enabled
   - Check .htaccess file is present and readable

### Testing Accounts

For testing, use these default accounts:
- Admin: admin@ecoswap.com / admin123
- Test User: test@ecoswap.com / test123

### Support
For issues or questions, please create an issue in the repository. 