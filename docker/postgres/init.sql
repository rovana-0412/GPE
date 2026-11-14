-- Initialisation PostgreSQL pour GPE
-- Création des bases de données distinctes pour chaque service

-- Base de données pour auth-service
CREATE DATABASE gpe_auth;

-- Base de données pour patrimoine-service
CREATE DATABASE gpe_patrimoine;

-- Base de données pour mouvements-service
CREATE DATABASE gpe_mouvements;

-- Base de données pour web (optionnel, si besoin)
CREATE DATABASE gpe_web;

-- Permissions
GRANT ALL PRIVILEGES ON DATABASE gpe_auth TO gpe_user;
GRANT ALL PRIVILEGES ON DATABASE gpe_patrimoine TO gpe_user;
GRANT ALL PRIVILEGES ON DATABASE gpe_mouvements TO gpe_user;
GRANT ALL PRIVILEGES ON DATABASE gpe_web TO gpe_user;
