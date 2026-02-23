-- Optional for older MariaDB versions without native JSON type support.
-- Run this only if migration 001 fails on JSON.
ALTER TABLE pool_transactions
  MODIFY metadata_json LONGTEXT NULL;
