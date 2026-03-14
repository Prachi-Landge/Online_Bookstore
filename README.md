# Modern BookStore Web Application

A comprehensive, production-ready PHP + MySQL bookstore web application with modern features, secure authentication, and a complete admin panel. Perfect for a developer portfolio.

## 🚀 Features

### User Features
- ✅ **Secure Authentication** - Password hashing, session management
- ✅ **User Registration & Login** - Complete user account system
- ✅ **Book Browsing** - Browse books with pagination (10 per page)
- ✅ **Category Filtering** - Filter books by category with sidebar
- ✅ **Search Functionality** - Backend search by title or author
- ✅ **Book Details Page** - Full book information with reviews
- ✅ **Shopping Cart** - Add, update quantities, remove items
- ✅ **Wishlist** - Save books for later
- ✅ **Reviews & Ratings** - Users can rate and review books (1-5 stars)
- ✅ **Checkout System** - Complete order processing
- ✅ **Order History** - View past orders with status tracking
- ✅ **Responsive Design** - Mobile-friendly TailwindCSS UI

### Admin Features
- ✅ **Admin Dashboard** - Statistics and overview
- ✅ **Book Management** - Add, edit, delete books
- ✅ **Category Management** - Manage book categories
- ✅ **Order Management** - View and update order statuses
- ✅ **User Management** - View all registered users
- ✅ **Role-Based Access** - Admin-only sections

### Security Features
- ✅ **Password Hashing** - Using PHP `password_hash()` and `password_verify()`
- ✅ **Prepared Statements** - SQL injection prevention
- ✅ **Input Validation** - Server-side validation
- ✅ **Session Management** - Secure session handling
- ✅ **Authentication Guards** - Protected routes

## 📁 Project Structure

```
Online_Bookstore/
├── admin/                    # Admin panel
│   ├── index.php            # Admin dashboard
│   ├── books.php            # Book management
│   ├── categories.php       # Category management
│   ├── orders.php           # Order management
│   └── users.php            # User management
├── auth.php                 # Authentication helpers
├── book.php                 # Book details page
├── cart.php                 # Shopping cart
├── checkout.php             # Checkout process
├── db.php                   # Database connection
├── index.php                # Main bookstore page
├── login.php                # User login
├── logout.php               # Logout handler
├── orders.php               # User order history
├── register.php             # User registration
├── wishlist.php            # User wishlist
├── database_schema.sql      # Complete database schema
└── README.md               # This file
```

## 🗄️ Database Schema

The application uses the following tables:

- **users** - User accounts with roles (user/admin)
- **categories** - Book categories
- **books** - Book inventory with category relationships
- **cart** - Shopping cart items
- **wishlist** - User wishlists
- **reviews** - Book reviews and ratings
- **orders** - Order records
- **order_items** - Order line items

See `database_schema.sql` for complete schema with foreign keys and constraints.

## 🛠️ Setup Instructions

### 1. Prerequisites
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server (XAMPP/WAMP/MAMP)
- Web browser

### 2. Database Setup

1. Open phpMyAdmin or MySQL command line
2. Import `database_schema.sql` file:
   ```sql
   mysql -u root -p < database_schema.sql
   ```
   Or copy and paste the SQL into phpMyAdmin

3. This will create:
   - All necessary tables
   - Sample categories
   - Sample books (14 books)
   - Admin user (username: `admin`, password: `admin123`)

### 3. Configuration

Edit `db.php` and update database credentials if needed:
```php
$host = 'localhost';
$dbname = 'bookstore';
$username = 'root';
$password = '';
```

### 4. Web Server Setup

**For XAMPP:**
1. Place project in `C:\xampp\htdocs\Bookstore\Online_Bookstore\`
2. Start Apache and MySQL from XAMPP Control Panel
3. Access: `http://localhost/Bookstore/Online_Bookstore/login.php`

**For WAMP:**
1. Place project in `C:\wamp64\www\Bookstore\Online_Bookstore\`
2. Start WAMP services
3. Access: `http://localhost/Bookstore/Online_Bookstore/login.php`

### 5. Default Credentials

**Admin Account:**
- Username: `admin`
- Password: `admin123`

**Regular User:**
- Register a new account at `register.php`

## 📖 Usage Guide

### For Users

1. **Registration**: Visit `register.php` to create an account
2. **Login**: Visit `login.php` (default entry point)
3. **Browse Books**: View all books on the main page
4. **Search**: Use the search bar to find books by title/author
5. **Filter**: Use category sidebar to filter by category
6. **View Details**: Click on any book to see full details
7. **Add to Cart**: Click "Add to Cart" on any book
8. **Wishlist**: Add books to wishlist from book details page
9. **Review**: Write reviews and rate books (1-5 stars)
10. **Checkout**: Go to cart and proceed to checkout
11. **Orders**: View order history in "Orders" page

### For Admins

1. **Login**: Use admin credentials
2. **Dashboard**: View statistics and recent orders
3. **Manage Books**: Add, edit, or delete books
4. **Manage Categories**: Create and manage categories
5. **Manage Orders**: Update order statuses
6. **View Users**: See all registered users

## 🎨 UI/UX Features

- **Modern Design**: Clean, professional TailwindCSS styling
- **Responsive Layout**: Works on desktop, tablet, and mobile
- **Hover Effects**: Interactive book cards with smooth transitions
- **Star Ratings**: Visual 5-star rating system
- **Category Sidebar**: Easy navigation by category
- **Pagination**: Navigate through books (10 per page)
- **Search Bar**: Prominent search functionality
- **Status Badges**: Color-coded order status indicators
- **Modal Dialogs**: Clean admin forms with modals

## 🔒 Security Implementation

1. **Password Security**
   - Passwords hashed with `password_hash()` (bcrypt)
   - Verified with `password_verify()`
   - Minimum 6 characters required

2. **SQL Injection Prevention**
   - All queries use prepared statements
   - Parameter binding for user input

3. **Authentication**
   - Session-based authentication
   - Protected routes with `requireAuth()`
   - Admin-only routes with `requireAdmin()`

4. **Input Validation**
   - Server-side validation
   - Input sanitization with `htmlspecialchars()`
   - Type casting for numeric inputs

## 📊 Database Features

- **Normalized Schema**: Proper foreign key relationships
- **Cascade Deletes**: Automatic cleanup on user/book deletion
- **Unique Constraints**: Prevent duplicate entries
- **Indexes**: Optimized for common queries
- **Data Integrity**: Foreign key constraints

## 🚀 Future Enhancements

Potential features to add:
- Email notifications for orders
- Payment gateway integration
- Book recommendations algorithm
- Advanced search filters (price range, rating)
- User profile pages
- Book image upload functionality
- Export orders to CSV/PDF
- Inventory management alerts
- Multi-language support
- Social media sharing

## 🛡️ Best Practices Implemented

- ✅ MVC-like structure (separation of concerns)
- ✅ DRY principle (reusable auth functions)
- ✅ Security-first approach
- ✅ Responsive design
- ✅ User-friendly error messages
- ✅ Clean, readable code
- ✅ Proper database relationships
- ✅ Input validation and sanitization

## 📝 Notes

- The application uses session-based authentication
- All passwords are securely hashed
- Cart and wishlist are user-specific
- Admin panel is accessible only to admin users
- Default admin password should be changed in production
- Book cover images use external URLs (can be changed to local uploads)

## 🐛 Troubleshooting

**Database Connection Error:**
- Check `db.php` credentials
- Ensure MySQL is running
- Verify database exists

**Login Not Working:**
- Check if user exists in database
- Verify password hashing
- Check session configuration

**Admin Panel Access Denied:**
- Ensure user role is set to 'admin' in database
- Check `auth.php` requireAdmin() function

**Images Not Loading:**
- Check internet connection (external URLs)
- Verify image URLs in database
- Check browser console for errors

## 📄 License

This project is open source and available for educational purposes.

## 👨‍💻 Developer

Perfect for showcasing:
- PHP backend development
- MySQL database design
- Frontend UI/UX skills
- Full-stack capabilities
- Security best practices

---

**Built with ❤️ using PHP, MySQL, and TailwindCSS**
