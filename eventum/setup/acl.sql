-- use this schema if you want to grant permissions manually instead of using setup
-- this schema is extracted from setup/index.php.
GRANT SELECT, UPDATE, DELETE, INSERT, ALTER, DROP, CREATE, INDEX ON eventum.* TO 'eventum'@'localhost' IDENTIFIED BY 'password';
