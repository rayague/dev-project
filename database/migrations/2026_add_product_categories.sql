-- ============================================================
--  MIGRATION : catégories produits + clé étrangère
-- ============================================================
--
--  OBJECTIF :
--  - Créer la table "categories" (id, name)
--  - Insérer les 6 catégories de démonstration (ids fixes 1 → 6)
--  - Ajouter products.category_id (nullable → categories.id)
--
--  Encodage : fichier UTF-8 sans BOM. Client MySQL en utf8mb4.
--
--  Exemple d'exécution (UTF-8 obligatoire côté client) :
--    cmd /c "docker exec -i gestcommandes-mysql mysql -uroot --default-character-set=utf8mb4 < database\migrations\2026_add_product_categories.sql"
--  Éviter Get-Content | docker sous PowerShell : ça peut double-encoder les accents (CamÃ©ra).
-- ============================================================

-- Base GestCommandes (Docker / schema.sql).
USE gestion_commandes;

-- ------------------------------------------------------------
-- Table des catégories
-- ------------------------------------------------------------
-- id   : clé primaire (référencée par products.category_id)
-- name : libellé affiché (filtre + formulaires), unique
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS categories (
    -- Identifiant auto-incrémenté (1 = Smartphone, …, 6 = Caméra).
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,

    -- Libellé de la catégorie (accents UTF-8 : é, è…).
    name VARCHAR(100) NOT NULL,

    PRIMARY KEY (id),

    -- Empêche deux catégories homonymes.
    UNIQUE KEY uq_categories_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Jeu de données fourni (ids fixes 1 → 6)
-- ------------------------------------------------------------
-- ON DUPLICATE KEY UPDATE : ré-exécutable sans doublon / sans erreur.
-- ------------------------------------------------------------
INSERT INTO categories (id, name) VALUES
    (1, 'Smartphone'),
    (2, 'Ordinateur portable'),
    (3, 'Casque audio'),
    (4, 'Montre connectée'),
    (5, 'Tablette'),
    (6, 'Caméra')
ON DUPLICATE KEY UPDATE name = VALUES(name);

-- ------------------------------------------------------------
-- Colonne category_id sur products (idempotente si déjà présente)
-- ------------------------------------------------------------
-- Vérifie information_schema avant ALTER pour pouvoir relancer
-- la migration sans planter si la colonne existe déjà.
-- ON DELETE SET NULL : supprimer une catégorie n'efface pas le produit.
-- ------------------------------------------------------------
SET @col_exists := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = 'gestion_commandes'
      AND TABLE_NAME   = 'products'
      AND COLUMN_NAME  = 'category_id'
);

SET @sql := IF(
    @col_exists = 0,
    'ALTER TABLE products
        ADD COLUMN category_id INT UNSIGNED NULL DEFAULT NULL AFTER stock,
        ADD KEY idx_products_category_id (category_id),
        ADD CONSTRAINT fk_products_category
            FOREIGN KEY (category_id) REFERENCES categories(id)
            ON DELETE SET NULL
            ON UPDATE CASCADE',
    'SELECT ''category_id already exists'' AS info'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
