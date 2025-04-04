# EcoSwap Project Structure

```
ecoswap/
├── admin/                    # Admin section
│   ├── css/                 # Admin-specific styles
│   │   ├── admin.css        # Main admin styles
│   │   └── dashboard.css    # Dashboard specific styles
│   ├── js/                  # Admin-specific scripts
│   │   ├── admin.js         # Main admin scripts
│   │   └── dashboard.js     # Dashboard specific scripts
│   ├── includes/            # Admin includes
│   │   ├── header.php       # Admin header
│   │   ├── footer.php       # Admin footer
│   │   └── sidebar.php      # Admin navigation
│   └── pages/               # Admin pages
│       ├── dashboard.php    # Admin dashboard
│       ├── users.php        # User management
│       ├── listings.php     # Listing management
│       ├── categories.php   # Category management
│       └── settings.php     # Admin settings
│
├── user/                    # User section
│   ├── css/                # User-specific styles
│   │   ├── user.css        # Main user styles
│   │   ├── profile.css     # Profile specific styles
│   │   └── messages.css    # Messages specific styles
│   ├── js/                 # User-specific scripts
│   │   ├── user.js         # Main user scripts
│   │   ├── profile.js      # Profile specific scripts
│   │   └── messages.js     # Messages specific scripts
│   ├── includes/           # User includes
│   │   ├── header.php      # User header
│   │   ├── footer.php      # User footer
│   │   └── sidebar.php     # User navigation
│   └── pages/              # User pages
│       ├── dashboard.php   # User dashboard
│       ├── profile.php     # User profile
│       ├── listings.php    # User listings
│       ├── messages.php    # User messages
│       └── offers.php      # User offers
│
├── public/                 # Public assets
│   ├── css/               # Global styles
│   │   ├── styles.css     # Main styles
│   │   ├── animations.css # Animations
│   │   └── responsive.css # Responsive styles
│   ├── js/                # Global scripts
│   │   ├── main.js        # Main scripts
│   │   └── animations.js  # Animation scripts
│   ├── images/            # Global images
│   │   ├── logo.png
│   │   └── favicon.ico
│   └── uploads/           # User uploads
│       ├── items/         # Item images
│       └── profiles/      # Profile images
│
├── includes/              # Global includes
│   ├── config/           # Configuration files
│   │   ├── database.php  # Database config
│   │   └── config.php    # General config
│   ├── auth.php          # Authentication
│   ├── functions.php     # Helper functions
│   └── init.php          # Initialization
│
├── sql/                  # Database files
│   ├── database.sql      # Main database schema
│   ├── migrations/       # Database migrations
│   └── seeds/           # Database seeds
│
├── index.php            # Main entry point
├── login.php           # Login page
├── register.php        # Registration page
├── browse.php         # Browse items page
├── .htaccess          # Apache configuration
└── README.md          # Project documentation
```

## Key Features of This Structure:

1. **Separation of Concerns**:
   - Admin and user sections are completely separate
   - Each section has its own CSS, JS, and includes
   - Clear separation between public and private files

2. **Modular Organization**:
   - Each feature has its own directory
   - Related files are grouped together
   - Easy to maintain and extend

3. **Security**:
   - Sensitive files are outside public directory
   - Uploads are properly organized
   - Configuration files are protected

4. **Scalability**:
   - Easy to add new features
   - Clear structure for new developers
   - Organized for future growth

5. **Performance**:
   - CSS and JS are organized by section
   - Images and uploads are properly structured
   - Easy to implement caching

## Usage Guidelines:

1. **Admin Section**:
   - Use for all administrative functions
   - Keep admin-specific styles and scripts separate
   - Implement proper access control

2. **User Section**:
   - Use for all user-facing features
   - Organize user-specific assets
   - Maintain user privacy

3. **Public Assets**:
   - Keep global styles and scripts here
   - Organize images and uploads properly
   - Implement proper caching

4. **Includes**:
   - Keep configuration files secure
   - Use for shared functionality
   - Maintain proper access control

5. **Database Files**:
   - Keep schema and migrations organized
   - Use seeds for test data
   - Maintain version control

This structure provides a solid foundation for the EcoSwap platform while maintaining flexibility for future growth and development. 