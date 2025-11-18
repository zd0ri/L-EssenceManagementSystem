-- Migration: Refactor products table to use brand_id FK and product_name column
-- This maintains 3NF: products references brands by ID, not by denormalized name

-- Step 1: Add new columns to products table
ALTER TABLE products 
ADD COLUMN brand_id INT DEFAULT NULL AFTER product_id,
ADD COLUMN product_name VARCHAR(255) DEFAULT NULL AFTER brand_id,
ADD FOREIGN KEY (brand_id) REFERENCES brands(brand_id) ON UPDATE CASCADE ON DELETE SET NULL;

-- Step 2: Populate brand_id by matching brand_name to brands.name
UPDATE products p
SET brand_id = (
  SELECT brand_id FROM brands b 
  WHERE LOWER(b.name) = LOWER(p.brand_name) 
  LIMIT 1
)
WHERE brand_id IS NULL;

-- Step 3: Copy brand_name to product_name (can be edited later)
UPDATE products
SET product_name = brand_name
WHERE product_name IS NULL;

-- Step 4: Ensure product_name is NOT NULL going forward
ALTER TABLE products MODIFY COLUMN product_name VARCHAR(255) NOT NULL;

-- Step 5: Keep brand_name as a denormalized view for backward compatibility during transition
-- (will remove after all code is updated)

-- Done! Now update application code to use brand_id and product_name instead of brand_name
