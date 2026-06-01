-- Drop all foreign key constraints that reference the `staff` table
-- Usage: set @target_db to the database/schema that contains the "staff" table, then source this file.

SET @target_db = 'igangaschoolofl_staffs_db';

-- Generate one DROP statement per foreign-key constraint that references `staff`.
-- Review the output and execute the ALTER statements manually (safer than auto-executing).
SELECT
  CONCAT('ALTER TABLE `', k.TABLE_SCHEMA, '`.`', k.TABLE_NAME, '` DROP FOREIGN KEY `', k.CONSTRAINT_NAME, '`;') AS drop_statement,
  k.CONSTRAINT_NAME, k.TABLE_SCHEMA, k.TABLE_NAME
FROM information_schema.KEY_COLUMN_USAGE k
JOIN information_schema.TABLE_CONSTRAINTS tc
  ON tc.CONSTRAINT_NAME = k.CONSTRAINT_NAME AND tc.TABLE_SCHEMA = k.TABLE_SCHEMA AND tc.TABLE_NAME = k.TABLE_NAME
WHERE tc.CONSTRAINT_TYPE = 'FOREIGN KEY'
  AND k.REFERENCED_TABLE_NAME = 'staff'
  AND (k.REFERENCED_TABLE_SCHEMA = @target_db OR @target_db IS NULL)
ORDER BY k.TABLE_SCHEMA, k.TABLE_NAME;

-- If you prefer a single concatenated SQL payload (may be truncated by GROUP_CONCAT limits), uncomment and run the following:
-- SELECT GROUP_CONCAT(CONCAT('ALTER TABLE `', k.TABLE_SCHEMA, '`.`', k.TABLE_NAME, '` DROP FOREIGN KEY `', k.CONSTRAINT_NAME, '`;') SEPARATOR '\n') AS drops
-- FROM information_schema.KEY_COLUMN_USAGE k
-- WHERE k.REFERENCED_TABLE_NAME = 'staff' AND (k.REFERENCED_TABLE_SCHEMA = @target_db OR @target_db IS NULL);

-- Usage:
-- 1. Run this file in your MySQL client to list DROP statements.
-- 2. Review the generated `drop_statement` rows carefully.
-- 3. Execute the ALTER TABLE ... DROP FOREIGN KEY ... statements one-by-one or copy them into a safe migration script.

