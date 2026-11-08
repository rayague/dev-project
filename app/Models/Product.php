<?php
/**
 * ============================================================
 *  MODÈLE : Product (la table "products")
 * ============================================================
 *
 *  RÔLE DANS LE MVC :
 *  Seule couche qui parle à MySQL pour les produits :
 *  - lister / rechercher avec pagination
 *  - créer un produit (store)
 *  - ajuster le stock (updateStock / decrementStock)
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
     * LISTER LES PRODUITS (recherche + pagination)
     * ------------------------------------------------------------
     * Sert la page GET /products (ProductController::index).
     *
     * @param  string $search  Terme recherché dans name OU description
     * @param  int    $page    Page courante (commence à 1)
     * @param  int    $perPage Nombre d'éléments par page
     * @return array{
     *   items: list<array>,
     *   total: int,
     *   pages: int,
     *   page: int
     * }
     */
    public function search(string $search = '', int $page = 1, int $perPage = 10): array
    {
        // Condition SQL optionnelle + paramètres liés (requêtes préparées).
        $condition = '';
        $params    = [];

        if ($search !== '') {
            // Deux noms de paramètres distincts : MySQL refuse de réutiliser
            // le même placeholder deux fois dans certaines configs PDO.
            $condition = '(name LIKE :search_name OR description LIKE :search_desc)';
            $params[':search_name'] = '%' . $search . '%';
            $params[':search_desc'] = '%' . $search . '%';
        }

        // count() attend la condition SANS le mot-clé WHERE.
        $where = $condition !== '' ? ' WHERE ' . $condition : '';
        $total = $this->count($condition, $params);

        // Calcul de pagination (page mini = 1, au moins 1 page affichée).
        $page   = max(1, $page);
        $offset = ($page - 1) * $perPage;
        $pages  = max(1, (int) ceil($total / $perPage));

        // Lecture des lignes de la page courante (plus récentes d'abord).
        $sql = 'SELECT * FROM `products`'
             . $where
             . ' ORDER BY created_at DESC, id DESC'
             . ' LIMIT ' . (int) $perPage
             . ' OFFSET ' . (int) $offset;

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return [
            'items' => $stmt->fetchAll(), // produits de cette page
            'total' => $total,            // total (filtre inclus)
            'pages' => $pages,            // nombre de pages
            'page'  => $page,             // page courante
        ];
    }

    /**
     * ------------------------------------------------------------
     * CRÉER UN PRODUIT (le "C" du CRUD)
     * ------------------------------------------------------------
     * Normalise les champs puis délègue à Model::create().
     *
     * @param  array $donnees Clés attendues : name, description, price, stock
     * @return int            Identifiant auto-incrémenté du produit créé
     */
    public function store(array $donnees): int
    {
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
