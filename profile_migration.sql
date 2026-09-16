-- SIDEQUEST profile migration for an existing database
USE sidequest;

ALTER TABLE users ADD COLUMN IF NOT EXISTS avatar_path VARCHAR(255) NULL AFTER longitude;
ALTER TABLE users ADD COLUMN IF NOT EXISTS bio VARCHAR(500) NOT NULL DEFAULT '' AFTER avatar_path;
