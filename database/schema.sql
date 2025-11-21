-- Casters.fi Database Schema
-- Run this in phpMyAdmin or MySQL

CREATE DATABASE IF NOT EXISTS casters_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE casters_db;

-- Ensure proper encoding for all languages (Arabic, Hebrew, Chinese, etc.)
SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

-- Users table (for all user types)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    user_type ENUM('admin', 'manager', 'brand', 'influencer') NOT NULL,
    first_name VARCHAR(100),
    last_name VARCHAR(100),
    phone VARCHAR(50),
    date_of_birth DATE,
    country VARCHAR(100),
    profile_picture VARCHAR(255),
    bio TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    email_verified BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Influencer profiles (extends users)
CREATE TABLE influencer_profiles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    creator_type ENUM('influencer', 'content_creator', 'both') DEFAULT 'influencer',
    instagram_url VARCHAR(255),
    tiktok_url VARCHAR(255),
    youtube_url VARCHAR(255),
    facebook_url VARCHAR(255),
    instagram_followers INT DEFAULT 0,
    tiktok_followers INT DEFAULT 0,
    youtube_followers INT DEFAULT 0,
    cities_available TEXT,
    referral_code VARCHAR(50),
    hear_about_us VARCHAR(100),
    rating DECIMAL(3,2) DEFAULT 0.00,
    total_campaigns INT DEFAULT 0,
    -- Pricing (not visible to brands)
    price_instagram_post DECIMAL(10,2),
    price_instagram_story DECIMAL(10,2),
    price_instagram_reel DECIMAL(10,2),
    price_tiktok_post DECIMAL(10,2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Influencer categories (many-to-many)
CREATE TABLE influencer_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    influencer_id INT NOT NULL,
    category VARCHAR(100) NOT NULL,
    FOREIGN KEY (influencer_id) REFERENCES influencer_profiles(id) ON DELETE CASCADE
);

-- Brand profiles (extends users)
CREATE TABLE brand_profiles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    company_name VARCHAR(255) NOT NULL,
    website_url VARCHAR(255),
    company_description TEXT,
    contact_person_name VARCHAR(255),
    contact_person_phone VARCHAR(50),
    contact_person_email VARCHAR(255),
    instagram_url VARCHAR(255),
    tiktok_url VARCHAR(255),
    facebook_url VARCHAR(255),
    needs TEXT,
    goals TEXT,
    hear_about_us VARCHAR(100),
    newsletter_subscribed BOOLEAN DEFAULT FALSE,
    tax_number VARCHAR(100),
    subscription_level ENUM('level1', 'level2') DEFAULT 'level1',
    rating DECIMAL(3,2) DEFAULT 0.00,
    total_campaigns INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Campaigns
CREATE TABLE campaigns (
    id INT AUTO_INCREMENT PRIMARY KEY,
    brand_id INT NOT NULL,
    created_by INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    hero_image VARCHAR(255),
    image VARCHAR(255),
    gallery_images TEXT,
    brand_address VARCHAR(500),
    brand_timing VARCHAR(255),
    service_description TEXT,
    what_is_expected TEXT,
    what_is_offered TEXT,
    instructions TEXT,
    expectations TEXT,
    compensation TEXT,
    budget DECIMAL(10,2),
    timing_start DATE,
    timing_end DATE,
    target_sex ENUM('any', 'male', 'female') DEFAULT 'any',
    target_age_min INT,
    target_age_max INT,
    target_location VARCHAR(255),
    category VARCHAR(100),
    influencers_needed INT DEFAULT 1,
    influencers_selected INT DEFAULT 0,
    status ENUM('draft', 'active', 'paused', 'completed', 'cancelled') DEFAULT 'draft',
    is_public BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (brand_id) REFERENCES brand_profiles(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Campaign applications
CREATE TABLE campaign_applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    campaign_id INT NOT NULL,
    influencer_id INT NOT NULL,
    message TEXT,
    status ENUM('pending', 'accepted', 'rejected', 'completed') DEFAULT 'pending',
    applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    responded_at TIMESTAMP,
    FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE CASCADE,
    FOREIGN KEY (influencer_id) REFERENCES influencer_profiles(id) ON DELETE CASCADE,
    UNIQUE KEY unique_application (campaign_id, influencer_id)
);

-- Ratings (two-way rating system)
CREATE TABLE ratings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    campaign_id INT NOT NULL,
    rater_id INT NOT NULL,
    rated_id INT NOT NULL,
    rating TINYINT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE CASCADE,
    FOREIGN KEY (rater_id) REFERENCES users(id),
    FOREIGN KEY (rated_id) REFERENCES users(id)
);

-- Messages (for Level 2 brands)
CREATE TABLE messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    campaign_id INT,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    is_edited BOOLEAN DEFAULT FALSE,
    edited_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES users(id),
    FOREIGN KEY (receiver_id) REFERENCES users(id),
    FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE SET NULL
);

-- Categories list
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    icon VARCHAR(50),
    is_active BOOLEAN DEFAULT TRUE
);

-- Insert default categories
INSERT INTO categories (name, icon) VALUES
('Art & Design', 'fa-palette'),
('Beauty & Cosmetics', 'fa-spa'),
('Beer & Wine & Spirits', 'fa-wine-glass'),
('Business & Careers', 'fa-briefcase'),
('Cars & Motorbikes', 'fa-car'),
('Coffee & Tea & Beverages', 'fa-coffee'),
('Electronics & Computers', 'fa-laptop'),
('Fashion', 'fa-tshirt'),
('Fitness & Yoga', 'fa-dumbbell'),
('Friends & Family & Relationships', 'fa-heart'),
('Gaming', 'fa-gamepad'),
('Healthcare & Medicine', 'fa-heartbeat'),
('Healthy Lifestyle', 'fa-leaf'),
('Home Decor & Furniture & Garden', 'fa-couch'),
('Lifestyle', 'fa-star'),
('Luxury Goods', 'fa-gem'),
('Music', 'fa-music'),
('Pets', 'fa-paw'),
('Photography & UGC', 'fa-camera'),
('Restaurant & Foods & Grocery', 'fa-utensils'),
('Sports', 'fa-futbol'),
('Sustainability', 'fa-recycle'),
('TV & Film & Books', 'fa-film'),
('Toys & Children & Baby', 'fa-baby'),
('Travel', 'fa-plane'),
('Wedding', 'fa-ring');

-- Insert default admin user (password: admin123)
INSERT INTO users (email, password, user_type, first_name, last_name, is_active, email_verified) VALUES
('admin@casters.fi', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'Admin', 'User', TRUE, TRUE);

-- Create indexes for better performance
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_type ON users(user_type);
CREATE INDEX idx_campaigns_status ON campaigns(status);
CREATE INDEX idx_campaigns_brand ON campaigns(brand_id);
CREATE INDEX idx_applications_status ON campaign_applications(status);
CREATE INDEX idx_messages_receiver ON messages(receiver_id, is_read);
