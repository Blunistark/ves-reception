-- Create users table for login
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('admin', 'staff', 'viewer') DEFAULT 'staff',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create admission_inquiries table
CREATE TABLE admission_inquiries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    child_name VARCHAR(100) NOT NULL,
    parent_name VARCHAR(100) NOT NULL,
    parent_email VARCHAR(150),
    phone_number VARCHAR(20) NOT NULL,
    desired_class VARCHAR(50) NOT NULL,
    address TEXT,
    specific_requirements TEXT,
    parent_notes TEXT,
    inquiry_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending', 'reviewed', 'approved', 'rejected') DEFAULT 'pending',
    created_by INT,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Create visitors table
CREATE TABLE visitors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    visitor_name VARCHAR(100) NOT NULL,
    email VARCHAR(150),
    phone_number VARCHAR(20) NOT NULL,
    purpose VARCHAR(100) NOT NULL,
    visit_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('scheduled', 'completed', 'cancelled') DEFAULT 'scheduled',
    created_by INT,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Insert default admin user (password: admin123)
INSERT INTO users (username, email, password, full_name, role) VALUES
('admin', 'admin@school.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'admin');

-- Insert sample users
INSERT INTO users (username, email, password, full_name, role) VALUES
('staff1', 'staff1@school.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Staff Member 1', 'staff'),
('viewer1', 'viewer1@school.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Viewer User', 'viewer');

-- Insert sample data for admission inquiries
INSERT INTO admission_inquiries (child_name, parent_name, parent_email, phone_number, desired_class, address, specific_requirements, parent_notes, created_by) VALUES
('Emma Bennett', 'Olivia Bennett', 'olivia.bennett@email.com', '555-987-6543', 'Grade 1', '123 Main St, City', 'None', 'Looking forward to enrollment', 1),
('Liam Davis', 'Ava Davis', 'ava.davis@email.com', '555-369-1470', 'Grade 3', '456 Oak Ave, City', 'Special dietary needs', 'Child has allergies', 1),
('Sophie Foster', 'Sophia Foster', 'sophia.foster@email.com', '555-456-7890', 'Grade 5', '789 Pine Rd, City', 'Advanced math program', 'Gifted student', 1),
('Bella Hayes', 'Isabella Hayes', 'isabella.hayes@email.com', '555-012-3456', 'Grade 2', '321 Elm St, City', 'None', 'New to the area', 1),
('Charlie Jenkins', 'Chloe Jenkins', 'chloe.jenkins@email.com', '555-321-6547', 'Grade 4', '654 Maple Dr, City', 'Sports program', 'Very active child', 1);

-- Insert sample data for visitors
INSERT INTO visitors (visitor_name, email, phone_number, purpose, visit_date, created_by) VALUES
('Ethan Harper', 'ethan.harper@email.com', '555-123-4567', 'Tour', '2024-07-26', 1),
('Noah Carter', 'noah.carter@email.com', '555-246-8013', 'Meeting', '2024-07-24', 1),
('Liam Evans', 'liam.evans@email.com', '555-789-0123', 'Interview', '2024-07-22', 1),
('Jackson Green', 'jackson.green@email.com', '555-654-3210', 'Event', '2024-07-20', 1),
('Aiden Ingram', 'aiden.ingram@email.com', '555-987-1234', 'Consultation', '2024-07-18', 1);