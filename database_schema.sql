-- Complete Database Schema for Modern BookStore Application
-- Run this SQL script to create all necessary tables

CREATE DATABASE IF NOT EXISTS bookstore;
USE bookstore;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    contact VARCHAR(20) NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('user', 'admin') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Categories table
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) UNIQUE NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Books table (updated with category_id)
CREATE TABLE IF NOT EXISTS books (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    author VARCHAR(255) NOT NULL,
    category_id INT,
    price DECIMAL(10, 2) NOT NULL,
    cover VARCHAR(500),
    description TEXT,
    stock INT DEFAULT 100,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

-- Cart table
CREATE TABLE IF NOT EXISTS cart (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    book_id INT NOT NULL,
    quantity INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE,
    UNIQUE KEY unique_cart_item (user_id, book_id)
);

-- Wishlist table
CREATE TABLE IF NOT EXISTS wishlist (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    book_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE,
    UNIQUE KEY unique_wishlist_item (user_id, book_id)
);

-- Reviews table
CREATE TABLE IF NOT EXISTS reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    book_id INT NOT NULL,
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE,
    UNIQUE KEY unique_review (user_id, book_id)
);

-- Orders table
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    total_amount DECIMAL(10, 2) NOT NULL,
    status ENUM('pending', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
    shipping_address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Order items table
CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    book_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE
);

-- Insert sample categories
INSERT INTO categories (name, description) VALUES
('Fiction', 'Fictional stories and novels'),
('Fantasy', 'Fantasy and magical realism'),
('Mystery', 'Mystery and thriller novels'),
('Romance', 'Romance novels'),
('Dystopian', 'Dystopian and science fiction'),
('Philosophy', 'Philosophical works'),
('Biography', 'Biographies and memoirs'),
('History', 'Historical books');

-- Insert sample books with category_id
INSERT INTO books (title, author, category_id, price, cover, description) VALUES
('The Great Gatsby', 'F. Scott Fitzgerald', 1, 12.99, 'https://images-na.ssl-images-amazon.com/images/I/81QuEGw8VPL.jpg', 'A classic American novel set in the Jazz Age.'),
('To Kill a Mockingbird', 'Harper Lee', 1, 11.99, 'https://images-na.ssl-images-amazon.com/images/I/81gepf1eMqL.jpg', 'A gripping tale of racial injustice and childhood innocence.'),
('1984', 'George Orwell', 5, 13.99, 'https://images-na.ssl-images-amazon.com/images/I/81StSOpmkjL.jpg', 'A dystopian social science fiction novel.'),
('Pride and Prejudice', 'Jane Austen', 4, 10.99, 'https://images-na.ssl-images-amazon.com/images/I/71Q1tPupKjL.jpg', 'A romantic novel of manners.'),
('The Catcher in the Rye', 'J.D. Salinger', 1, 12.49, 'https://images-na.ssl-images-amazon.com/images/I/91HPG31dTwL.jpg', 'A controversial novel about teenage rebellion.'),
('Lord of the Flies', 'William Golding', 1, 11.49, 'https://images-na.ssl-images-amazon.com/images/I/81WUAoL-wFL.jpg', 'A story about a group of boys stranded on an island.'),
('The Hobbit', 'J.R.R. Tolkien', 2, 14.99, 'https://images-na.ssl-images-amazon.com/images/I/712cDO7d73L.jpg', 'A fantasy novel about Bilbo Baggins.'),
('Harry Potter and the Philosopher\'s Stone', 'J.K. Rowling', 2, 15.99, 'https://images-na.ssl-images-amazon.com/images/I/81YOuOGFCJL.jpg', 'The first book in the Harry Potter series.'),
('The Da Vinci Code', 'Dan Brown', 3, 13.49, 'https://images-na.ssl-images-amazon.com/images/I/81WUAoL-wFL.jpg', 'A mystery thriller novel.'),
('The Alchemist', 'Paulo Coelho', 6, 12.99, 'https://images-na.ssl-images-amazon.com/images/I/71aFt4+OTOL.jpg', 'A philosophical novel about following your dreams.'),
('The Lord of the Rings', 'J.R.R. Tolkien', 2, 16.99, 'https://images-na.ssl-images-amazon.com/images/I/71jLBXtWJWL.jpg', 'An epic fantasy trilogy.'),
('Jane Eyre', 'Charlotte Brontë', 4, 11.99, 'https://images-na.ssl-images-amazon.com/images/I/81YOuOGFCJL.jpg', 'A classic romance novel.'),
('Sherlock Holmes', 'Arthur Conan Doyle', 3, 13.99, 'https://images-na.ssl-images-amazon.com/images/I/81QuEGw8VPL.jpg', 'The complete collection of Sherlock Holmes stories.'),
('Brave New World', 'Aldous Huxley', 5, 12.99, 'https://images-na.ssl-images-amazon.com/images/I/81gepf1eMqL.jpg', 'A dystopian novel about a futuristic society.');

-- Create admin user (password: admin123)
INSERT INTO users (username, email, contact, password, role) VALUES
('admin', 'admin@bookstore.com', '1234567890', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');
