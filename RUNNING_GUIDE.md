# EcoSwap Website - Running Guide

## Table of Contents
1. [Prerequisites](#prerequisites)
2. [Installation Steps](#installation-steps)
3. [Database Setup](#database-setup)
4. [Configuration](#configuration)
5. [Running the Website](#running-the-website)
6. [Testing](#testing)
7. [Troubleshooting](#troubleshooting)

## Prerequisites

Before you start, make sure you have:
- Windows 10/11, macOS, or Linux
- XAMPP (recommended) or similar local server stack
- Web browser (Chrome, Firefox, or Edge)
- Text editor (VS Code, Sublime Text, etc.)

### Download Required Software

1. **XAMPP**:
   - Download from: https://www.apachefriends.org/
   - Choose version with PHP 7.4 or higher
   - Run the installer with default settings

2. **Text Editor**:
   - Download VS Code: https://code.visualstudio.com/
   - Install PHP extensions for better development

## Installation Steps

### 1. Install XAMPP
1. Run the XAMPP installer
2. Select these components:
   - Apache
   - MySQL
   - PHP
   - phpMyAdmin
3. Complete the installation

### 2. Start XAMPP Services
1. Open XAMPP Control Panel
2. Click "Start" for:
   - Apache
   - MySQL
3. Wait for both services to turn green

### 3. Set Up Project Files
1. Navigate to XAMPP's htdocs folder:
   - Windows: `C:\xampp\htdocs`
   - Mac: `/Applications/XAMPP/htdocs`
   - Linux: `/opt/lampp/htdocs`

2. Create project folder:
   ```bash
   mkdir ecoswap
   cd ecoswap
   ```

3. Copy all project files into the `ecoswap` folder

## Database Setup

### 1. Create Database
1. Open your browser
2. Go to: `http://localhost/phpmyadmin`
3. Click "New" in the left sidebar
4. Enter database name: `ecoswap`
5. Click "Create"

### 2. Import Database Schema
1. In phpMyAdmin, select the `ecoswap` database
2. Click "Import" tab
3. Click "Choose File"
4. Select `sql/database.sql` from your project
5. Click "Go" to import

### 3. Verify Database
1. Check if these tables exist:
   - users
   - listings
   - categories
   - messages
   - offers
   - notifications

## Configuration

### 1. Database Configuration
1. Open `includes/config/database.php`
2. Verify these settings:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'root');
   define('DB_PASS', '');  // Default XAMPP password is blank
   define('DB_NAME', 'ecoswap');
   ```

### 2. File Permissions
1. Set these permissions:
   ```bash
   chmod 777 public/uploads/
   chmod 777 public/uploads/items/
   chmod 777 public/uploads/profiles/
   ```

### 3. Apache Configuration
1. Open `C:\xampp\apache\conf\httpd.conf`
2. Find and uncomment (remove #):
   ```apache
   LoadModule rewrite_module modules/mod_rewrite.so
   ```
3. Save and restart Apache

## Running the Website

### 1. Start the Server
1. Open XAMPP Control Panel
2. Start Apache and MySQL
3. Wait for both services to turn green

### 2. Access the Website
1. Open your browser
2. Go to: `http://localhost/ecoswap`
3. You should see the EcoSwap homepage

### 3. Test User Accounts
Use these accounts for testing:
- Admin:
  - Email: admin@ecoswap.com
  - Password: admin123
- Regular User:
  - Email: test@ecoswap.com
  - Password: test123

## Testing

### 1. Basic Functionality
Test these features:
- [ ] User registration
- [ ] User login
- [ ] Creating listings
- [ ] Browsing items
- [ ] Sending messages
- [ ] Making offers

### 2. Admin Features
Test these admin functions:
- [ ] User management
- [ ] Category management
- [ ] Listing moderation
- [ ] System settings

### 3. File Uploads
Test these upload features:
- [ ] Profile picture upload
- [ ] Listing image upload
- [ ] File type validation
- [ ] File size limits

## Troubleshooting

### Common Issues and Solutions

1. **Website Not Loading**
   - Check if Apache is running
   - Verify file permissions
   - Check error logs in XAMPP

2. **Database Connection Error**
   - Verify MySQL is running
   - Check database credentials
   - Ensure database exists

3. **File Upload Issues**
   - Check folder permissions
   - Verify PHP upload settings
   - Check file size limits

4. **404 Errors**
   - Enable mod_rewrite
   - Check .htaccess file
   - Verify file paths

### Error Logs
- Apache logs: `C:\xampp\apache\logs\error.log`
- PHP logs: `C:\xampp\php\logs\php_error_log`

### Support
If you encounter issues:
1. Check the error logs
2. Verify all prerequisites
3. Ensure proper file permissions
4. Contact support with error details

## Additional Resources

### Useful Links
- [XAMPP Documentation](https://www.apachefriends.org/docs/)
- [PHP Documentation](https://www.php.net/docs.php)
- [MySQL Documentation](https://dev.mysql.com/doc/)

### Development Tools
- [VS Code](https://code.visualstudio.com/)
- [phpMyAdmin](https://www.phpmyadmin.net/)
- [Postman](https://www.postman.com/) (for API testing)

### Security Checklist
- [ ] Change default passwords
- [ ] Enable HTTPS
- [ ] Update PHP version
- [ ] Regular backups
- [ ] Security headers enabled

Remember to keep your development environment updated and secure. Happy coding! 