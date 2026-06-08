-- ============================================================
-- ISNM LIBRARY MANAGER DASHBOARD SQL
-- Complete Library Management System
-- ============================================================

USE igangaschoolofl_staffs_db;

-- ============================================================
-- 1. LIBRARY MANAGER USER ACCOUNTS
-- ============================================================

INSERT IGNORE INTO staff (
    staff_id, 
    full_name, 
    email, 
    password, 
    phone, 
    position, 
    department, 
    role_id, 
    status, 
    hire_date,
    created_at
) VALUES
('LIB001', 'School Librarian', 'librarian@igangaschoolofnursingandmidwifery.ac.ug',
 '$2y$10$librarian@isnmHashedPassword', '+256701000013', 'School Librarian', 'Library Services',
 (SELECT id FROM staff_roles WHERE role_name = 'School Librarian' LIMIT 1), 'Active', CURDATE(), NOW()),
('LIB002', 'Assistant Librarian', 'assistant_librarian@igangaschoolofnursingandmidwifery.ac.ug',
 '$2y$10$assistant_librarian@isnmHashedPassword', '+256701000029', 'Assistant Librarian', 'Library Services',
 (SELECT id FROM staff_roles WHERE role_name = 'School Librarian' LIMIT 1), 'Active', CURDATE(), NOW());

-- ============================================================
-- 2. LIBRARY MANAGEMENT TABLES
-- ============================================================

-- Books and Resources Catalog
CREATE TABLE IF NOT EXISTS library_books (
    id INT AUTO_INCREMENT PRIMARY KEY,
    book_id VARCHAR(50) UNIQUE NOT NULL,
    title VARCHAR(255) NOT NULL,
    subtitle VARCHAR(255),
    author VARCHAR(255),
    editor VARCHAR(255),
    edition VARCHAR(50),
    isbn VARCHAR(20),
    issn VARCHAR(20),
    publisher VARCHAR(255),
    publication_year INT,
    publication_place VARCHAR(100),
    category VARCHAR(100),
    subcategory VARCHAR(100),
    call_number VARCHAR(50),
    total_copies INT DEFAULT 1,
    available_copies INT DEFAULT 1,
    shelf_location VARCHAR(100),
    condition_status ENUM('New', 'Good', 'Fair', 'Poor', 'Damaged') DEFAULT 'Good',
    price DECIMAL(10,2),
    currency VARCHAR(3) DEFAULT 'UGX',
    language VARCHAR(50) DEFAULT 'English',
    pages INT,
    description TEXT,
    keywords TEXT,
    cover_image VARCHAR(500),
    digital_copy_path VARCHAR(500),
    status ENUM('Available', 'Borrowed', 'Reserved', 'Lost', 'On Order', 'Archiv') DEFAULT 'Available',
    added_by INT,
    added_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (added_by) REFERENCES staff(id) ON DELETE SET NULL,
    INDEX idx_book_id (book_id),
    INDEX idx_title (title),
    INDEX idx_author (author),
    INDEX idx_category (category),
    INDEX idx_status (status)
);

-- Borrowing Records
CREATE TABLE IF NOT EXISTS library_borrowing (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transaction_id VARCHAR(50) UNIQUE NOT NULL,
    book_id INT NOT NULL,
    borrower_type ENUM('Student', 'Staff', 'External') NOT NULL,
    borrower_id INT,
    borrower_name VARCHAR(255),
    borrow_date DATE NOT NULL,
    due_date DATE NOT NULL,
    return_date DATE,
    return_status ENUM('Borrowed', 'Returned', 'Overdue', 'Lost') DEFAULT 'Borrowed',
    late_fee DECIMAL(10,2) DEFAULT 0,
    fine_paid BOOLEAN DEFAULT FALSE,
    processed_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (book_id) REFERENCES library_books(id) ON DELETE CASCADE,
    FOREIGN KEY (processed_by) REFERENCES staff(id) ON DELETE SET NULL,
    INDEX idx_transaction_id (transaction_id),
    INDEX idx_book_id (book_id),
    INDEX idx_return_status (return_status),
    INDEX idx_due_date (due_date)
);

-- Library Members
CREATE TABLE IF NOT EXISTS library_members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    member_id VARCHAR(50) UNIQUE NOT NULL,
    member_type ENUM('Student', 'Staff', 'External') NOT NULL,
    student_id INT,
    staff_id INT,
    full_name VARCHAR(255) NOT NULL,
    email VARCHAR(255),
    phone VARCHAR(20),
    department VARCHAR(100),
    program VARCHAR(100),
    member_since DATE,
    membership_expiry DATE,
    max_books_allowed INT DEFAULT 3,
    current_books_borrowed INT DEFAULT 0,
    status ENUM('Active', 'Inactive', 'Suspended', 'Expired') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES igangaschoolofl_students_db.students(id) ON DELETE CASCADE,
    FOREIGN KEY (staff_id) REFERENCES staff(id) ON DELETE SET NULL,
    INDEX idx_member_id (member_id),
    INDEX idx_full_name (full_name)
);

-- Digital Resources
CREATE TABLE IF NOT EXISTS library_digital_resources (
    id INT AUTO_INCREMENT PRIMARY KEY,
    resource_id VARCHAR(50) UNIQUE NOT NULL,
    title VARCHAR(255) NOT NULL,
    resource_type ENUM('Ebook', 'Journal', 'Video', 'Audio', 'Database', 'Article') NOT NULL,
    author_creator VARCHAR(255),
    publisher VARCHAR(255),
    publication_year INT,
    url VARCHAR(500),
    file_path VARCHAR(500),
    file_size_mb DECIMAL(10,2),
    access_level ENUM('Public', 'Members Only', 'Restricted') DEFAULT 'Members Only',
    description TEXT,
    subject_keywords TEXT,
    added_by INT,
    added_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (added_by) REFERENCES staff(id) ON DELETE SET NULL,
    INDEX idx_resource_id (resource_id),
    INDEX idx_title (title)
);

-- Library Fines and Fees
CREATE TABLE IF NOT EXISTS library_fines (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fine_id VARCHAR(50) UNIQUE NOT NULL,
    transaction_id INT,
    member_id INT NOT NULL,
    fine_type ENUM('Overdue', 'Damage', 'Lost', 'Reservation') NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'UGX',
    description TEXT,
    waived BOOLEAN DEFAULT FALSE,
    waived_by INT,
    waived_date TIMESTAMP NULL,
    paid BOOLEAN DEFAULT FALSE,
    payment_date TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (transaction_id) REFERENCES library_borrowing(id) ON DELETE SET NULL,
    FOREIGN KEY (member_id) REFERENCES library_members(id) ON DELETE CASCADE,
    INDEX idx_fine_id (fine_id),
    INDEX idx_member_id (member_id),
    INDEX idx_paid (paid)
);

-- ============================================================
-- 3. PROCEDURES FOR LIBRARY MANAGEMENT
-- ============================================================

DELIMITER //

-- Search books in library
CREATE PROCEDURE library_search_books(
    IN p_title VARCHAR(255),
    IN p_author VARCHAR(255),
    IN p_category VARCHAR(100),
    IN p_status VARCHAR(50)
)
BEGIN
    SELECT 
        lb.book_id,
        lb.title,
        lb.author,
        lb.publisher,
        lb.publication_year,
        lb.category,
        lb.total_copies,
        lb.available_copies,
        lb.shelf_location,
        lb.status
    FROM library_books lb
    WHERE (p_title IS NULL OR lb.title LIKE CONCAT('%', p_title, '%'))
      AND (p_author IS NULL OR lb.author LIKE CONCAT('%', p_author, '%'))
      AND (p_category IS NULL OR lb.category = p_category)
      AND (p_status IS NULL OR lb.status = p_status)
    ORDER BY lb.title;
END //

-- Borrow book
CREATE PROCEDURE library_borrow_book(
    IN p_book_id INT,
    IN p_member_id INT,
    IN p_processed_by INT
)
BEGIN
    DECLARE v_transaction_id VARCHAR(50);
    DECLARE v_due_date DATE;
    DECLARE v_current_copies INT;
    DECLARE v_available_copies INT;
    
    SET v_transaction_id = CONCAT('BRW', DATE_FORMAT(NOW(), '%Y%m%d%H%i%s'));
    SET v_due_date = DATE_ADD(CURDATE(), INTERVAL 14 DAY);
    
    -- Check available copies
    SELECT available_copies INTO v_available_copies 
    FROM library_books WHERE id = p_book_id;
    
    IF v_available_copies > 0 THEN
        INSERT INTO library_borrowing (
            transaction_id, book_id, borrower_id, borrower_name, 
            borrow_date, due_date, processed_by
        ) VALUES (
            v_transaction_id, p_book_id, p_member_id, 
            (SELECT full_name FROM library_members WHERE id = p_member_id),
            CURDATE(), v_due_date, p_processed_by
        );
        
        UPDATE library_books 
        SET available_copies = available_copies - 1
        WHERE id = p_book_id;
        
        UPDATE library_members 
        SET current_books_borrowed = current_books_borrowed + 1
        WHERE id = p_member_id;
    END IF;
END //

-- Return book
CREATE PROCEDURE library_return_book(
    IN p_transaction_id INT,
    IN p_processed_by INT
)
BEGIN
    UPDATE library_borrowing 
    SET return_date = CURDATE(),
        return_status = 'Returned'
    WHERE id = p_transaction_id;
    
    UPDATE library_books lb
    JOIN library_borrowing lbw ON lb.id = lbw.book_id
    SET lb.available_copies = lb.available_copies + 1
    WHERE lbw.id = p_transaction_id;
    
    UPDATE library_members lm
    JOIN library_borrowing lbw ON lm.id = lbw.borrower_id
    SET lm.current_books_borrowed = lm.current_books_borrowed - 1
    WHERE lbw.id = p_transaction_id;
END //

DELIMITER ;

COMMIT;