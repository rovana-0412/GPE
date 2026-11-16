-- Initialisation PostgreSQL pour GPE
-- Création des bases de données distinctes pour chaque service
-- Les permissions sont gérées par POSTGRES_USER dans docker-compose.yml

-- Base de données pour auth-service
CREATE DATABASE gpe_auth;

-- Base de données pour patrimoine-service
CREATE DATABASE gpe_patrimoine;

-- Base de données pour mouvements-service
CREATE DATABASE gpe_mouvements;

-- Base de données réservée aux tests
CREATE DATABASE gpe_test;
