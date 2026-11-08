<?php
/**
 * ============================================================
 *  MODÈLE : Product (la table "products")
 * ============================================================
 *
 *  RÔLE DANS LE MVC :
 *  Seule couche qui parle à MySQL pour les produits :
 *  - lister / rechercher avec pagination (+ filtre catégorie)
 *  - créer un produit (store)
 *  - récupérer le nom de catégorie lié (category_id → categories)
 *  - ajuster le stock (updateStock / decrementStock)
 *
 *  Relation catégorie :
 *   - colonne products.category_id (nullable, FK vers categories.id)
 *   - search() fait un LEFT JOIN pour exposer category_name
 *   - getCategoryName() lit le libellé pour une fiche détail
 *
 *  Hérite de Model : findById(), create(), update(), delete(),
 *  findAll(), count() sont déjà disponibles.
 *
 *  AUCUNE balise HTML ici — uniquement des données.
 * ============================================================
 */

declare(strict_types=1);

class Product extends Model
{
    /**
     * Nom de la table MySQL associée à ce modèle.
     * Utilisé par les méthodes génériques de Model.
     *
     * @var string
     */
    protected string $table = 'products';

    /**
     * ------------------------------------------------------------
     * LISTER LES PRODUITS (recherche + catégorie + pagination)
     * ------------------------------------------------------------
     * Sert la page GET /products (ProductController::index).
     * Joint categories pour exposer category_name dans chaque ligne.
     *
     * @param  string   $search     Terme recherché dans name OU description
     * @param  int      $page       Page courante (commence à 1)
     * @param  int      $perPage    Nombre d'éléments par page
     * @param  int|null $categoryId Filtre optionnel sur category_id
     * @return array{
     *   items: list<array>,
     *   total: int,
     *   pages: int,
     *   page: int
     * }
     */
    public function search(string $search = '', int $page = 1, int $perPage = 10, ?int $categoryId = null): array
    {
        // Conditions SQL optionnelles + paramètres liés (requêtes préparées).
        $conditions = [];
        $params     = [];

        if ($search !== '') {
            // Deux noms de paramètres distincts : MySQL refuse de réutiliser
            // le même placeholder deux fois dans certaines configs PDO.
            $conditions[] = '(p.name LIKE :search_name OR p.description LIKE :search_desc)';
            $params[':search_name'] = '%' . $search . '%';
            $params[':search_desc'] = '%' . $search . '%';
        }

        // Filtre catégorie (?category=2) : uniquement si id > 0.
        if ($categoryId !== null && $categoryId > 0) {
            $conditions[] = 'p.category_id = :category_id';
            $params[':category_id'] = $categoryId;
        }

        // Assemble les conditions avec AND (recherche ± catégorie).
        $condition = implode(' AND ', $conditions);
        $where     = $condition !== '' ? ' WHERE ' . $condition : '';

        // Comptage sur products (alias p) pour rester cohérent avec le filtre.
        $countSql = 'SELECT COUNT(*) AS total FROM `products` p' . $where;
        $countStmt = $this->db->prepare($countSql);
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        // Calcul de pagination (page mini = 1, au moins 1 page affichée).
        $page   = max(1, $page);
        $offset = ($page - 1) * $perPage;
        $pages  = max(1, (int) ceil($total / $perPage));

        // LEFT JOIN categories : category_name même si category_id est NULL.
        // (INNER JOIN exclurait les produits sans catégorie.)
        $sql = 'SELECT p.*, c.name AS category_name'
             . ' FROM `products` p'
             . ' LEFT JOIN `categories` c ON c.id = p.category_id'
             . $where
             . ' ORDER BY p.created_at DESC, p.id DESC'
             . ' LIMIT ' . (int) $perPage
             . ' OFFSET ' . (int) $offset;

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return [
            'items' => $stmt->fetchAll(), // produits de cette page (+ category_name)
            'total' => $total,            // total (filtres inclus)
            'pages' => $pages,            // nombre de pages
            'page'  => $page,             // page courante
        ];
    }

    /**
     * ------------------------------------------------------------
     * NOM DE LA CATÉGORIE LIÉE (relation products → categories)
     * ------------------------------------------------------------
     * Petite requête dédiée pour la fiche détail (show), quand on
     * n'a que category_id et pas encore le libellé joint.
     *
     * @param  int|null $categoryId Identifiant categories.id (ou null)
     * @return string|null          Libellé, ou null si absent / inconnu
     */
    public function getCategoryName(?int $categoryId): ?string
    {
        // Pas de catégorie associée → pas de libellé.
        if ($categoryId === null || $categoryId <= 0) {
            return null;
        }

        // Lecture du name dans categories (requête préparée).
        $sql  = 'SELECT name FROM `categories` WHERE id = :id LIMIT 1';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $categoryId]);
        $name = $stmt->fetchColumn();

        // fetchColumn() renvoie false si aucune ligne.
        return $name === false ? null : (string) $name;
    }

    /**
     * ------------------------------------------------------------
     * CRÉER UN PRODUIT (le "C" du CRUD)
     * ------------------------------------------------------------
     * Normalise les champs puis délègue à Model::create().
     *
     * @param  array $donnees Clés : name, description, price, stock, category_id
     * @return int            Identifiant auto-incrémenté du produit créé
     */
    public function store(array $donnees): int
    {
        // category_id vide / 0 → NULL (colonne nullable).
        $categoryId = isset($donnees['category_id']) && (int) $donnees['category_id'] > 0
            ? (int) $donnees['category_id']
            : null;

        // Payload strict : on n'insère que les colonnes autorisées.
        $payload = [
            'name'        => (string) ($donnees['name'] ?? ''),
            // Description vide → NULL en base (colonne nullable).
            'description' => ($donnees['description'] ?? null) !== ''
                ? ($donnees['description'] ?? null)
                : null,
            'price'       => (float) ($donnees['price'] ?? 0),
            // Stock jamais négatif côté modèle.
            'stock'       => max(0, (int) ($donnees['stock'] ?? 0)),
            // Clé étrangère nullable vers categories.id.
            'category_id' => $categoryId,
        ];

        return $this->create($payload);
    }

    /**
     * ------------------------------------------------------------
     * METTRE À JOUR LE STOCK D'UN PRODUIT
     * ------------------------------------------------------------
     * Utile pour un ajustement manuel sans toucher aux autres champs.
     *
     * @param  int $id    Identifiant du produit
     * @param  int $stock Nouvelle quantité (forcée à ≥ 0)
     * @return int        Nombre de lignes réellement modifiées
     */
    public function updateStock(int $id, int $stock): int
    {
        return $this->update($id, [
            'stock' => max(0, $stock),
        ]);
    }

    /**
     * ------------------------------------------------------------
     * DÉCRÉMENTER LE STOCK (ex. après une vente)
     * ------------------------------------------------------------
     * Opération atomique : ne réussit que si le stock est suffisant
     * (évite un stock négatif en concurrence).
     *
     * @param  int $id       Identifiant du produit
     * @param  int $quantity Quantité à retirer (doit être > 0)
     * @return bool          true si une ligne a été mise à jour
     */
    public function decrementStock(int $id, int $quantity = 1): bool
    {
        if ($quantity <= 0) {
            return false;
        }

        // WHERE stock >= :qty_check empêche de passer sous zéro.
        $sql = 'UPDATE `products`'
             . ' SET stock = stock - :qty'
             . ' WHERE id = :id AND stock >= :qty_check';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':qty'       => $quantity,
            ':id'        => $id,
            ':qty_check' => $quantity,
        ]);

        return $stmt->rowCount() === 1;
    }
}
