<?php
/**
 * ============================================================
 *  MODÈLE : Category (la table "categories")
 * ============================================================
 *
 *  RÔLE DANS LE MVC :
 *  Accès en lecture aux catégories produits.
 *  Sert :
 *   - le filtre « Catégorie » de GET /products (?category=id)
 *   - les listes déroulantes des formulaires create / edit
 *
 *  Hérite de Model : findAll(), findById(), count(), etc.
 *  AUCUNE balise HTML ici — uniquement des données.
 *
 *  Données initiales : migration 2026_add_product_categories.sql
 *  (6 lignes : Smartphone, Ordinateur portable, Casque audio,
 *  Montre connectée, Tablette, Caméra).
 * ============================================================
 */

declare(strict_types=1);

class Category extends Model
{
    /**
     * Nom de la table MySQL associée à ce modèle.
     * Utilisé par les méthodes génériques de Model (findAll, findById…).
     *
     * @var string
     */
    protected string $table = 'categories';

    /**
     * ------------------------------------------------------------
     * LISTER LES CATÉGORIES (ordre alphabétique)
     * ------------------------------------------------------------
     * Renvoie toutes les lignes triées par name ASC, pour alimenter
     * les <select> et le filtre de la liste produits.
     *
     * @return list<array{id:int|string,name:string}>
     */
    public function allOrdered(): array
    {
        // findAll() vient de Model : SELECT * FROM `categories` ORDER BY name ASC
        return $this->findAll('name ASC');
    }
}
