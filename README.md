# Casters.fi - Influencer Marketing Platform

A comprehensive influencer marketing platform connecting brands with content creators in Finland.

## Features

### For Brands
- Create and manage marketing campaigns
- Browse and search for influencers
- Review campaign applications
- Real-time messaging with influencers (Level 2 subscription required)
- Campaign performance tracking
- Profile management

### For Influencers
- Browse available campaigns
- Apply to campaigns with custom pitches
- View application status
- Real-time messaging with brands
- Profile management with social media integration
- Portfolio showcase

## Tech Stack

- **Backend**: PHP 7.4+
- **Database**: MySQL
- **Frontend**: HTML5, CSS3, JavaScript (ES6+)
- **UI Framework**: Custom CSS with Gradient Design System
- **Icons**: Font Awesome 6.4.0
- **Fonts**: Inter (Google Fonts)

## Installation

1. Clone the repository:
```bash
git clone https://github.com/shahrukhijaz31/influencer-market.git
cd influencer-market
```

2. Set up your web server (Apache/Nginx) to point to the project directory

3. Create a MySQL database:
```sql
CREATE DATABASE casters_db;
```

4. Import the database schema:
```bash
mysql -u your_username -p casters_db < database/schema.sql
```

5. Configure database connection in `includes/config.php`:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'casters_db');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
```

6. Set proper permissions for uploads directory:
```bash
chmod 755 uploads/
```

7. Access the application at `http://localhost/casters`

## Project Structure

```
casters/
├── admin/              # Admin panel
├── api/               # API endpoints
├── assets/            # Static assets (CSS, JS, images)
│   ├── css/
│   ├── js/
│   └── images/
├── brand/             # Brand portal
├── database/          # Database schemas
├── includes/          # Shared PHP files
├── influencer/        # Influencer portal
├── uploads/           # User uploads
└── *.html             # Public pages
```

## Features in Detail

### Campaign Management
- Multi-category support (Fashion, Beauty, Lifestyle, etc.)
- Budget tracking
- Timeline management
- Target audience specifications
- Public/private campaign options

### Messaging System
- Real-time chat functionality
- Emoji support
- File attachments (images, PDFs, documents)
- Read receipts
- Message editing
- Conversation history

### User Profiles
- Brand profiles with company information
- Influencer profiles with social media metrics
- Portfolio management
- Subscription levels

## API Endpoints

- `/api/chat.php` - Messaging functionality
- `/api/campaigns.php` - Campaign data
- `/api/applications.php` - Application management

## Security Features

- Password hashing (bcrypt)
- SQL injection prevention (prepared statements)
- XSS protection
- CSRF token implementation
- Session management
- File upload validation

## Browser Support

- Chrome (latest)
- Firefox (latest)
- Safari (latest)
- Edge (latest)

## License

Proprietary - All rights reserved

## Contact

For support or inquiries, please contact: support@casters.fi
