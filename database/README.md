# Casters.fi Database Setup

## How to Update Your Database

### Step 1: Update Campaign Table Structure
If you already have a campaigns table, you need to add the new columns. Run this SQL in phpMyAdmin:

```sql
USE casters_db;

ALTER TABLE campaigns
ADD COLUMN hero_image VARCHAR(255) AFTER description,
ADD COLUMN gallery_images TEXT AFTER image,
ADD COLUMN brand_address VARCHAR(500) AFTER gallery_images,
ADD COLUMN brand_timing VARCHAR(255) AFTER brand_address,
ADD COLUMN service_description TEXT AFTER brand_timing,
ADD COLUMN what_is_expected TEXT AFTER service_description,
ADD COLUMN what_is_offered TEXT AFTER what_is_expected,
ADD COLUMN instructions TEXT AFTER what_is_offered,
ADD COLUMN budget DECIMAL(10,2) AFTER compensation,
ADD COLUMN category VARCHAR(100) AFTER target_location;
```

### Step 2: Insert Sample Campaigns
Run the sample campaigns SQL to get test data with images:

```bash
# In phpMyAdmin, import the file:
database/sample-campaigns.sql
```

Or copy and paste the contents of `sample-campaigns.sql` into the SQL tab in phpMyAdmin.

### Step 3: Fresh Installation (Alternative)
If you're starting fresh, just run:

1. `database/schema.sql` - Creates all tables with new structure
2. `database/sample-campaigns.sql` - Adds sample campaign data

## Accessing Campaign Detail Page

Once the database is set up, you can access campaign details at:
```
http://localhost/casters/influencer/campaign-detail.php?id=1
```

Replace `1` with any campaign ID from the database.

## Sample Campaigns Included

1. **Summer Fashion Collection Launch** - Fashion category
2. **Healthy Meal Prep Collaboration** - Healthy Lifestyle
3. **Fitness Studio Grand Opening** - Fitness & Yoga
4. **Artisan Coffee Shop Campaign** - Coffee & Tea
5. **Sustainable Beauty Products Launch** - Beauty & Cosmetics

All campaigns include:
- Hero images (from Unsplash)
- Gallery images (3 images each)
- Brand details (address, timing)
- Complete descriptions
- Expectations and offerings
- Step-by-step instructions
- Compensation details
