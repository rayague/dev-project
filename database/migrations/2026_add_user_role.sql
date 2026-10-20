-- ============================================================
--  MIGRATION : ajout de la colonne "role" à la table "users"
-- ============================================================
--
--  À EXÉCUTER UNE SEULE FOIS sur une base DÉJÀ INSTALLÉE (dont
--  la table "users" n'a pas encore la colonne "role").
--
--  Sur une installation neuve, schema.sql contient déjà la colonne :
--  il n'est donc PAS nécessaire d'exécuter ce fichier.
--
--  Exemple d'exécution :
--    mysql -u root gestion_commandes < database/migrations/2026_add_user_role.sql
-- ============================================================

USE gestion_commandes;

-- Ajout de la colonne "role" (par défaut : 'user' = le plus restrictif).
ALTER TABLE users
    ADD COLUMN role ENUM('admin', 'user') NOT NULL DEFAULT 'user'
    AFTER password;

-- Le compte de démonstration historique devient administrateur.
UPDATE users SET role = 'admin' WHERE email = 'admin@ascg.fr';
