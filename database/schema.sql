CREATE DATABASE IF NOT EXISTS library_db;
USE library_db;

CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(150) NULL,
  password VARCHAR(255) NOT NULL,
  role ENUM('admin','student','faculty') NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS books (
  id INT AUTO_INCREMENT PRIMARY KEY,
  date_of_accession DATE NOT NULL,
  accession_no INT NOT NULL UNIQUE,
  category VARCHAR(120) NOT NULL,
  author VARCHAR(180) NOT NULL,
  title VARCHAR(255) NOT NULL,
  publisher VARCHAR(180) NULL,
  year INT NULL,
  price DECIMAL(10,2) DEFAULT 0,
  total_copies INT DEFAULT 0,
  quantity INT DEFAULT 0,
  bill_no VARCHAR(100) NULL,
  bill_date DATE NULL,
  supplier VARCHAR(180) NULL,
  edition VARCHAR(120) NULL,
  remarks TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS issued_books (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  accession_no INT NOT NULL,
  issue_date DATE NOT NULL,
  due_date DATE NOT NULL,
  return_date DATE NULL,
  fine INT DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_issue_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_issue_book FOREIGN KEY (accession_no) REFERENCES books(accession_no) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS holidays (
  id INT AUTO_INCREMENT PRIMARY KEY,
  holiday_date DATE NOT NULL UNIQUE,
  description VARCHAR(255) NOT NULL
);
