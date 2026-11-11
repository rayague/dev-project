-- ============================================================
--  MIGRATION : création de la table "products"
-- ============================================================
--
--  OBJECTIF :
--  Ajouter la table des produits pour le modèle Product
--  (app/Models/Product.php) et le CRUD ProductController.
--
--  À EXÉCUTER UNE FOIS sur la base gestion_commandes
--  (ex. conteneur Docker MySQL du projet).
--
--  Exemple :
--    Get-Content database/migrations/2026_create_products.sql -Raw |
--      docker exec -i gestcommandes-mysql mysql -uroot
--
--  ou :
--    mysql -u root gestion_commandes < database/migrations/2026_create_products.sql
-- ============================================================

-- Sélectionne la base du projet GestCommandes.
USE gestion_commandes;

-- Crée la table si elle n'existe pas encore (idempotent).
CREATE TABLE IF NOT EXISTS products (
    -- Clé primaire auto-incrémentée.
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,

    -- Nom affiché du produit (obligatoire).
    name VARCHAR(150) NOT NULL,

    -- Description libre (facultative).
    description TEXT NULL,

    -- Prix unitaire en euros (2 décimales).
    price DECIMAL(10,2) NOT NULL DEFAULT 0.00,

    -- Quantité disponible en stock (≥ 0).
    stock INT UNSIGNED NOT NULL DEFAULT 0,

    -- Horodatage de création (rempli automatiquement).
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    -- Horodatage de dernière modification (mis à jour par MySQL).
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),

    -- Index pour accélérer les recherches / tris sur le nom.
    KEY idx_products_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
