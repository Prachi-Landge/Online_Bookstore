-- Database setup for BookStore Application
-- Run this SQL script to create the necessary tables

CREATE DATABASE IF NOT EXISTS bookstore;
USE bookstore;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    contact VARCHAR(20) NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Books table
CREATE TABLE IF NOT EXISTS books (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    author VARCHAR(255) NOT NULL,
    category VARCHAR(100) NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    cover VARCHAR(500),
    description TEXT,
    stock INT DEFAULT 100,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
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

-- Sample books data
INSERT INTO books (title, author, category, price, cover, description) VALUES
('The Great Gatsby', 'F. Scott Fitzgerald', 'Fiction', 12.99, 'https://images-na.ssl-images-amazon.com/images/I/81QuEGw8VPL.jpg', 'A classic American novel set in the Jazz Age.'),
('To Kill a Mockingbird', 'Harper Lee', 'Fiction', 11.99, 'https://images-na.ssl-images-amazon.com/images/I/81gepf1eMqL.jpg', 'A gripping tale of racial injustice and childhood innocence.'),
('1984', 'George Orwell', 'Dystopian', 13.99, 'https://images-na.ssl-images-amazon.com/images/I/81StSOpmkjL.jpg', 'A dystopian social science fiction novel.'),
('Pride and Prejudice', 'Jane Austen', 'Romance', 10.99, 'https://images-na.ssl-images-amazon.com/images/I/71Q1tPupKjL.jpg', 'A romantic novel of manners.'),
('The Catcher in the Rye', 'J.D. Salinger', 'Fiction', 12.49, 'https://images-na.ssl-images-amazon.com/images/I/91HPG31dTwL.jpg', 'A controversial novel about teenage rebellion.'),
('Lord of the Flies', 'William Golding', 'Fiction', 11.49, 'https://images-na.ssl-images-amazon.com/images/I/81WUAoL-wFL.jpg', 'A story about a group of boys stranded on an island.'),
('The Hobbit', 'J.R.R. Tolkien', 'Fantasy', 14.99, 'https://images-na.ssl-images-amazon.com/images/I/712cDO7d73L.jpg', 'A fantasy novel about Bilbo Baggins.'),
('Harry Potter and the Philosopher\'s Stone', 'J.K. Rowling', 'Fantasy', 15.99, 'https://images-na.ssl-images-amazon.com/images/I/81YOuOGFCJL.jpg', 'The first book in the Harry Potter series.'),
('The Da Vinci Code', 'Dan Brown', 'Mystery', 13.49, 'https://images-na.ssl-images-amazon.com/images/I/81WUAoL-wFL.jpg', 'A mystery thriller novel.'),
('The Alchemist', 'Paulo Coelho', 'Philosophy', 12.99, 'https://images-na.ssl-images-amazon.com/images/I/71aFt4+OTOL.jpg', 'A philosophical novel about following your dreams.');
