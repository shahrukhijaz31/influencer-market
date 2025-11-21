-- Update Campaign ID=1 with 10 Gallery Images
-- Summer Fashion Collection Launch

USE casters_db;

UPDATE campaigns
SET gallery_images = 'https://images.unsplash.com/photo-1483985988355-763728e1935b?w=400&h=300&fit=crop,https://images.unsplash.com/photo-1469334031218-e382a71b716b?w=400&h=300&fit=crop,https://images.unsplash.com/photo-1558769132-cb1aea41f76b?w=400&h=300&fit=crop,https://images.unsplash.com/photo-1515372039744-b8f02a3ae446?w=400&h=300&fit=crop,https://images.unsplash.com/photo-1490481651871-ab68de25d43d?w=400&h=300&fit=crop,https://images.unsplash.com/photo-1539533018447-63fcce2678e3?w=400&h=300&fit=crop,https://images.unsplash.com/photo-1591047139829-d91aecb6caea?w=400&h=300&fit=crop,https://images.unsplash.com/photo-1551488831-00ddcb6c6bd3?w=400&h=300&fit=crop,https://images.unsplash.com/photo-1562157873-818bc0726f68?w=400&h=300&fit=crop,https://images.unsplash.com/photo-1467043237213-65f2da53396f?w=400&h=300&fit=crop'
WHERE id = 1;
