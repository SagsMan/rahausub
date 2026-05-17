-- ============================================================
-- Bardetech Provider Migration
-- Run this SQL on your database to add Bardetech support
-- ============================================================

-- Step 1: Add bardetech_plan_id column to sme_data_tbl
-- (Safe to run multiple times - uses IF NOT EXISTS pattern)
ALTER TABLE `sme_data_tbl` 
  ADD COLUMN IF NOT EXISTS `bardetech_plan_id` VARCHAR(50) DEFAULT NULL 
  COMMENT 'Plan ID used when Bardetech is the active provider';

-- Step 2: Ensure Bardetech exists in api_settings
-- Replace the api_key with your actual Bardetech token
INSERT INTO `api_settings` (`api_name`, `api_url`, `api_key`, `is_active`)
SELECT 'bardetech', 'https://www.bardetech.com/api/data/', 'd98c2c835ac0579e3fa781b048893a2eafaf463c', 0
WHERE NOT EXISTS (
  SELECT 1 FROM `api_settings` WHERE LOWER(`api_name`) = 'bardetech'
);

-- ============================================================
-- NEXT STEPS:
-- 1. Run this SQL on your hosting database (e.g. via phpMyAdmin)
-- 2. Go to Settings > Change Provider > Select Bardetech
-- 3. Go to Manage SME Data > fill in "Bardetech Plan ID" for each bundle
--    (get Plan IDs from https://www.bardetech.com/api/databundle/ or dashboard)
-- 4. Save and test on cheap-data page
-- ============================================================
