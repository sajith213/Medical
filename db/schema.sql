-- Database Schema for Employee Medical Claim System
-- MySQL 8.0+ Syntax

-- Roles Table: Defines user roles within the system
CREATE TABLE `roles` (
    `role_id` INT AUTO_INCREMENT PRIMARY KEY,
    `role_name` VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Users Table: Stores user account information
CREATE TABLE `users` (
    `user_id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(255) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `email` VARCHAR(255) NOT NULL UNIQUE,
    `full_name` VARCHAR(255),
    `role_id` INT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `is_active` BOOLEAN DEFAULT TRUE,
    `password_reset_token` VARCHAR(255) NULL DEFAULT NULL,
    `password_reset_expires` TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (`role_id`) REFERENCES `roles`(`role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Claims Table: Stores medical claim information submitted by employees
CREATE TABLE `claims` (
    `claim_id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `claim_date` DATE NOT NULL,
    `description` TEXT,
    `total_amount` DECIMAL(10, 2) NOT NULL,
    `status` VARCHAR(50) NOT NULL DEFAULT 'Submitted', -- e.g., 'Submitted', 'Under Review', 'Approved', 'Rejected', 'Needs Information'
    `hr_staff_id` INT NULL, -- FK to users table, for HR staff who processes the claim
    `processed_date` TIMESTAMP NULL,
    `comments` TEXT NULL, -- Feedback from HR/Admin
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`),
    FOREIGN KEY (`hr_staff_id`) REFERENCES `users`(`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Documents Table: Stores information about uploaded documents related to claims
CREATE TABLE `documents` (
    `document_id` INT AUTO_INCREMENT PRIMARY KEY,
    `claim_id` INT NOT NULL,
    `file_name` VARCHAR(255) NOT NULL, -- Original file name
    `file_path` VARCHAR(255) NOT NULL, -- Path on server where (potentially encrypted) file is stored
    `file_type` VARCHAR(100),
    `uploaded_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `version` INT DEFAULT 1, -- For simple version control
    `ocr_raw_text` TEXT NULL DEFAULT NULL,
    `ocr_extracted_amount` DECIMAL(10,2) NULL DEFAULT NULL,
    `ocr_extracted_date` DATE NULL DEFAULT NULL,
    `ocr_provider_name` VARCHAR(255) NULL DEFAULT NULL,
    `ocr_status` ENUM('Pending', 'Processed', 'Failed', 'UserConfirmed', 'NotApplicable') NOT NULL DEFAULT 'Pending',
    FOREIGN KEY (`claim_id`) REFERENCES `claims`(`claim_id`) ON DELETE CASCADE -- If a claim is deleted, its documents are also deleted
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Financial Quotas Table: Manages annual medical claim quotas for employees
CREATE TABLE `financial_quotas` (
    `quota_id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `financial_year_start` DATE NOT NULL,
    `financial_year_end` DATE NOT NULL,
    `total_quota` DECIMAL(10, 2) NOT NULL,
    `utilized_quota` DECIMAL(10, 2) DEFAULT 0.00,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`),
    UNIQUE KEY `uq_user_financial_year` (`user_id`, `financial_year_start`) -- Ensures one quota record per user per financial year start
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Indexes for performance
CREATE INDEX idx_users_role_id ON users(role_id);
CREATE INDEX idx_claims_user_id ON claims(user_id);
CREATE INDEX idx_claims_status ON claims(status);
CREATE INDEX idx_claims_hr_staff_id ON claims(hr_staff_id);
CREATE INDEX idx_documents_claim_id ON documents(claim_id);
CREATE INDEX idx_financial_quotas_user_id ON financial_quotas(user_id);

-- Initial Data for Roles
INSERT INTO `roles` (`role_name`) VALUES
('Employee'),
('HR Staff'),
('Admin');

-- Note: Consider adding more specific indexes based on common query patterns as the application evolves.
-- Note: `file_path` in `documents` table might store a relative path if UPLOAD_DIR is consistently used.
-- Note: Encryption of documents at rest is a security consideration not handled by the schema itself but by application logic.
-- Note: For `financial_year_end`, a CHECK constraint could ensure it's after `financial_year_start`, though MySQL versions before 8.0.16 don't enforce CHECK constraints. Application logic should validate this.
-- Make sure password_reset_token and password_reset_expires are indexed if they are frequently queried.
CREATE INDEX idx_users_password_reset_token ON users(password_reset_token);


-- Example of adding a check constraint for newer MySQL versions:
-- ALTER TABLE `financial_quotas` ADD CONSTRAINT `chk_financial_year_order` CHECK (`financial_year_end` > `financial_year_start`);

-- Granting necessary privileges to the web server user for these tables will be done separately in database setup.
-- e.g., GRANT SELECT, INSERT, UPDATE, DELETE ON employee_medical_claims.* TO 'your_web_user'@'localhost'; (This is a database management task, not part of schema.sql)
-- FLUSH PRIVILEGES;
