-- Update existing campaigns table with new columns
-- Run this if you already have a campaigns table

USE casters_db;

-- Add new columns to campaigns table
ALTER TABLE campaigns
ADD COLUMN IF NOT EXISTS hero_image VARCHAR(255) AFTER description,
ADD COLUMN IF NOT EXISTS gallery_images TEXT AFTER image,
ADD COLUMN IF NOT EXISTS brand_address VARCHAR(500) AFTER gallery_images,
ADD COLUMN IF NOT EXISTS brand_timing VARCHAR(255) AFTER brand_address,
ADD COLUMN IF NOT EXISTS service_description TEXT AFTER brand_timing,
ADD COLUMN IF NOT EXISTS what_is_expected TEXT AFTER service_description,
ADD COLUMN IF NOT EXISTS what_is_offered TEXT AFTER what_is_expected,
ADD COLUMN IF NOT EXISTS instructions TEXT AFTER what_is_offered,
ADD COLUMN IF NOT EXISTS budget DECIMAL(10,2) AFTER compensation,
ADD COLUMN IF NOT EXISTS category VARCHAR(100) AFTER target_location;

-- Confirm changes
SELECT 'Campaigns table updated successfully!' as status;
