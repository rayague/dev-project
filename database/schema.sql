-- ============================================================
--  FICHIER SQL : CRÉATION DE LA BASE DE DONNÉES DU PROJET
-- ============================================================
--
--  COMMENT L'UTILISER ?
--  1. Ouvrir phpMyAdmin (http://localhost/phpmyadmin) OU la
--     console MySQL d'XAMPP.
--  2. Exécuter ce fichier entier (bouton "Importer" dans phpMyAdmin).
--
--  Ce fichier crée :
--  - La base de données "gestion_commandes"
--  - La table "users"  (les comptes utilisateurs)
--  - La table "orders" (les commandes = objet du CRUD principal)
--  - Quelques données d'exemple pour pouvoir tester le site.
-- ============================================================

-- ------------------------------------------------------------
-- ÉTAPE 1 : Création de la base de données
-- ------------------------------------------------------------
-- "IF NOT EXISTS" = si la base existe déjà, on ne provoque pas
-- d'erreur, on continue simplement.
-- "utf8mb4_unicode_ci" = classement des caractères compatible
-- avec tous les accents français.
CREATE DATABASE IF NOT EXISTS gestion_commandes
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- ÉTAPE 2 : On indique à MySQL dans quelle base on travaille
-- ------------------------------------------------------------
-- Toutes les instructions suivantes s'appliqueront à cette base.
USE gestion_commandes;

-- ------------------------------------------------------------
-- ÉTAPE 3 : Suppression des anciennes tables (si elles existent)
-- ------------------------------------------------------------
-- C'est pratique pendant le développement : on peut ré-exécuter
-- ce fichier autant de fois qu'on veut sans erreur.
DROP TABLE IF EXISTS orders;   -- on supprime la table des commandes
DROP TABLE IF EXISTS users;    -- puis la table des utilisateurs

-- ------------------------------------------------------------
-- ÉTAPE 4 : Création de la table "users" (utilisateurs)
-- ------------------------------------------------------------
-- Chaque ligne = un champ (une colonne) de la table.
CREATE TABLE users (
    -- Identifiant unique : s'incrémente tout seul (1, 2, 3...).
    -- C'est la « clé primaire », elle sert à retrouver un utilisateur.
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,

    -- Nom complet de l'utilisateur, texte de 100 caractères max.
    name VARCHAR(100) NOT NULL,

    -- Adresse e-mail : unique car deux comptes ne peuvent pas
    -- avoir la même adresse (c'est notre identifiant de connexion).
    email VARCHAR(190) NOT NULL,

    -- Mot de passe : on stocke le HACHAGE (pas le mot de passe en clair).
    -- 255 caractères car password_hash() produit une longue chaîne.
    password VARCHAR(255) NOT NULL,

    -- Rôle du compte (gestion des permissions / RBAC) :
    --   'admin' = accès complet (gestion des autres utilisateurs) ;
    --   'user'  = compte simple (pas de gestion des autres comptes).
    -- Valeur par défaut : 'user' (le plus restrictif = le plus sûr).
    role ENUM('admin', 'user') NOT NULL DEFAULT 'user',

    -- Date/heure de création du compte : la fonction NOW() insère
    -- automatiquement la date et l'heure du moment de l'insertion.
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    -- Date/heure de la dernière modification (NULL = jamais modifié).
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,

    -- Déclaration de la clé primaire (la colonne id).
    PRIMARY KEY (id),

    -- Déclaration de l'unicité de l'e-mail (deux fois le même = refusé).
    UNIQUE KEY uniq_email (email)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- ÉTAPE 5 : Création de la table "orders" (commandes)
-- ------------------------------------------------------------
-- C'est SUR CETTE TABLE qu'on fait le CRUD principal du projet.
CREATE TABLE orders (
    -- Identifiant unique auto-incrémenté de la commande.
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,

    -- Référence de la commande, ex : "CMD-2026-001".
    -- Unique : évite qu'une même référence existe deux fois.
    reference VARCHAR(30) NOT NULL,

    -- Nom du client qui a passé la commande.
    client VARCHAR(150) NOT NULL,

    -- Adresse e-mail du client (facultatif : peut être NULL).
    email VARCHAR(190) NULL,

    -- Date à laquelle la commande a été passée.
    date_commande DATE NOT NULL,

    -- Montant total en euros : DECIMAL(10,2) = 10 chiffres au total,
    -- dont 2 après la virgule (ex : 12345678.99).
    montant DECIMAL(10,2) NOT NULL DEFAULT 0.00,

    -- Statut de la commande : c'est une valeur parmi 4 possibilités.
    -- On utilise un ENUM (énumération) pour que MySQL refuse
    -- toute autre valeur que celles listées ci-dessous.
    statut ENUM('en_attente', 'validee', 'livree', 'annulee')
        NOT NULL DEFAULT 'en_attente',

    -- Description / remarques libres (facultatif).
    description TEXT NULL,

    -- L'utilisateur (de la table users) qui a créé cette commande.
    -- C'est une « clé étrangère » : elle fait le lien entre les 2 tables.
    user_id INT UNSIGNED NOT NULL,

    -- Date/heure de création de la ligne.
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    -- Date/heure de dernière modification.
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,

    -- Clé primaire de la table.
    PRIMARY KEY (id),

    -- Clé unique pour la référence (pas de doublon possible).
    UNIQUE KEY uniq_reference (reference),

    -- CLÉ ÉTRANGÈRE : "user_id" doit toujours correspondre à un id
    -- réellement existant dans la table "users".
    -- Si on supprime un utilisateur, ses commandes sont supprimées
    -- avec lui (ON DELETE CASCADE).
    CONSTRAINT fk_orders_user
        FOREIGN KEY (user_id) REFERENCES users (id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    -- Index sur le statut : accélère les recherches / filtres.
    KEY idx_statut (statut),

    -- Index sur la date : accélère le tri par date.
    KEY idx_date (date_commande)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- ÉTAPE 6 : Données d'exemple (facultatif mais pratique)
-- ------------------------------------------------------------
-- On insère un utilisateur de test :
--  - E-mail    : admin@ascg.fr
--  - Mot de passe en clair : "password123"
--  - Le hachage ci-dessous a été généré avec password_hash() :
--    php -r "echo password_hash('password123', PASSWORD_DEFAULT);"
INSERT INTO users (id, name, email, password, role) VALUES
    (1, 'Administrateur Demo', 'admin@ascg.fr',
     '$2y$10$GanD1bpzEtFgCiIu6U0sm./W.e94S14Zokr9MPA.792k.cT6FsKg2',
     'admin'),
    -- Compte utilisateur simple (rôle user) pour tester le RBAC lecture seule.
    -- Mot de passe en clair : "password123" (même hachage que l'admin).
    (2, 'Utilisateur Demo', 'user@example.com',
     '$2y$10$GanD1bpzEtFgCiIu6U0sm./W.e94S14Zokr9MPA.792k.cT6FsKg2',
     'user');

-- On insère quelques commandes d'exemple pour remplir le dashboard.
INSERT INTO orders (reference, client, email, date_commande, montant, statut, description, user_id) VALUES
    ('CMD-2026-001', 'Marie Corse',   'marie@example.fr',  '2026-01-12', 250.00, 'livree',    'Commande de matériel de bureau',      1),
    ('CMD-2026-002', 'Pierre Rossi',  'pierre@example.fr', '2026-02-03', 1200.50, 'validee',   'Fournitures pour l''atelier',         1),
    ('CMD-2026-003', 'Julie Fancelli', 'julie@example.fr', '2026-02-15', 75.90,  'en_attente','Petit matériel',                      1),
    ('CMD-2026-004', 'Luc Bertoni',   'luc@example.fr',    '2026-03-01', 430.00, 'annulee',   'Commande annulée par le client',      1),
    ('CMD-2026-005', 'Sofia Gnesi',   'sofia@example.fr',  '2026-03-10', 890.00, 'en_attente','Demande de devis transformée',        1);
