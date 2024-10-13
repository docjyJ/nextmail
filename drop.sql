DROP TABLE IF EXISTS oc_nextmail_emails;
DROP TABLE IF EXISTS oc_nextmail_accounts;
DROP TABLE IF EXISTS oc_nextmail_configs;
DELETE FROM oc_migrations WHERE app = 'nextmail';
