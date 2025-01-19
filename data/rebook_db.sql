-- Users table
CREATE TABLE users (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(100) NOT NULL UNIQUE,
  username VARCHAR(50),
  postcode VARCHAR(50),
  password VARCHAR(120) NOT NULL,
  latitude DOUBLE,
  longitude DOUBLE,
  preferences JSON DEFAULT '[]',
  liked_books JSON DEFAULT '[]'
);
-- Books table
CREATE TABLE books (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  isbn VARCHAR(13) NOT NULL,
  cover_img VARCHAR(300),
  title VARCHAR(100) NOT NULL,
  author VARCHAR(100) NOT NULL,
  published_date DATE,
  publisher VARCHAR(100),
  category JSON DEFAULT '[]',
  book_condition VARCHAR(50),
  notes TEXT,
  user_id BIGINT UNSIGNED NOT NULL,
  book_latitude DOUBLE,
  book_longitude DOUBLE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
-- Messages table
CREATE TABLE messages (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  sender_id BIGINT UNSIGNED NOT NULL,
  recipient_id BIGINT UNSIGNED NOT NULL,
  book_id BIGINT UNSIGNED NOT NULL,
  message TEXT NOT NULL,
  is_read BOOLEAN NOT NULL DEFAULT FALSE,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (recipient_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE
);
-- Create indexes for better query performance
CREATE INDEX idx_books_user_id ON books(user_id);
CREATE INDEX idx_messages_sender_id ON messages(sender_id);
CREATE INDEX idx_messages_recipient_id ON messages(recipient_id);
CREATE INDEX idx_messages_book_id ON messages(book_id);