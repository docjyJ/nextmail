-- [store."mysql"]
-- type = "mysql"
-- host = "localhost"
-- port = 3306
-- database = "stalwart"
-- user = "root"
-- password = "password"
-- disable = true
-- max-allowed-packet = 1073741824
-- timeout = "15s"
-- pool.max-connections = 10
-- pool.min-connections = 5
-- query.name = "SELECT name, type, secret, description, quota FROM accounts WHERE name = ? AND active = true"
SELECT name, type, secret, description, quota FROM (SELECT uid as name, 'individual' as type, password as secret, '' as description, '' as quota FROM oc_users JOIN oc_stalwart_users USING (uid) WHERE config_id = 1) as accounts WHERE name = ?;
-- query.members = "SELECT member_of FROM group_members WHERE name = ?"
SELECT '' LIMIT 0;
-- query.recipients = "SELECT name FROM emails WHERE address = ? ORDER BY name ASC"
SELECT uid FROM oc_accounts_data JOIN oc_stalwart_users USING (uid) WHERE config_id = 1 AND name = 'email' AND value = ?;
-- query.emails = "SELECT address FROM emails WHERE name = ? AND type != 'list' ORDER BY type DESC, address ASC"
SELECT value FROM oc_accounts_data JOIN oc_stalwart_users USING (uid) WHERE config_id = 1 AND name = 'email' AND uid = ?;
-- query.verify = "SELECT address FROM emails WHERE address LIKE CONCAT('%', ?, '%') AND type = 'primary' ORDER BY address LIMIT 5"
SELECT value FROM oc_accounts_data JOIN oc_stalwart_users USING (uid) WHERE config_id = 1 AND name = 'email' AND value LIKE CONCAT('%', ?, '%') ORDER BY value LIMIT 5;
-- query.expand = "SELECT p.address FROM emails AS p JOIN emails AS l ON p.name = l.name WHERE p.type = 'primary' AND l.address = ? AND l.type = 'list' ORDER BY p.address LIMIT 50"
SELECT '' LIMIT 0;
-- query.domains = "SELECT 1 FROM emails WHERE address LIKE CONCAT('%@', ?) LIMIT 1"
SELECT 1 FROM oc_accounts_data JOIN oc_stalwart_users USING (uid) WHERE config_id = 1 AND name = 'email' AND value LIKE CONCAT('%@', ?) LIMIT 1;

-- init.execute = []
