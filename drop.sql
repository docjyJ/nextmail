DROP TABLE IF EXISTS oc_stalwart_emails;
DROP TABLE IF EXISTS oc_stalwart_accounts;
DROP TABLE IF EXISTS oc_stalwart_configs;
DELETE FROM oc_migrations WHERE app = 'stalwart';
