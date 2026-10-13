-- Exécuté par MySQL uniquement à la création du volume (première installation).
-- Donne à l'utilisateur de l'application les droits sur la base des tests (penderie_test).
CREATE DATABASE IF NOT EXISTS penderie_test;
GRANT ALL PRIVILEGES ON `penderie_test`.* TO 'penderie'@'%';
FLUSH PRIVILEGES;
