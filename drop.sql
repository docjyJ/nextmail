DROP TABLE IF EXISTS oc_stalwart_configs;
DROP TABLE IF EXISTS oc_stalwart_users;
DROP TABLE IF EXISTS oc_stalwart_emails;
DELETE FROM oc_migrations WHERE app = 'stalwart';
