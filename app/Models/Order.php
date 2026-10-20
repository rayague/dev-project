<?php
/**
 * ============================================================
 *  MODÈLE : Order (la table "orders" — CRUD PRINCIPAL DU PROJET)
 * ============================================================
 *
 *  RÔLE DANS LE MVC :
 *  Ce fichier contient TOUTES les requêtes SQL liées aux commandes :
 *  - Lister les commandes avec recherche, filtre par statut, pagination
 *  - Créer une commande (référence, client, montant, statut...)
 *  - Modifier une commande
 *  - Supprimer une commande
 *  - Calculer les statistiques affichées sur le dashboard
 *
 *  Il HÉRITE de la classe Model (create, update, delete, findById
 *  sont donc déjà disponibles gratuitement).
 *
 *  AUCUNE balise HTML ici : ce fichier ne parle qu'aux données.
 * ============================================================
 */

declare(strict_types=1);

class Order extends Model
{
    /**
     * @var string Nom de la table MySQL gérée par ce modèle.
     */
    protected string $table = 'orders';

    /**
     * @var array Liste des statuts autorisés pour une commande.
     *            Doit rester en cohérence avec le ENUM de la table SQL.
     */
    public const STATUTS = ['en_attente', 'validee', 'livree', 'annulee'];

    /**
     * ------------------------------------------------------------
     * LISTE COMPLÈTE POUR LA PAGE /orders (Cœur du CRUD)
     * ------------------------------------------------------------
     * Combine 3 critères :
     *  1. La recherche textuelle (n° de référence OU nom du client)
     *  2. Le filtre par statut (ex : seulement "en attente")
     *  3. La pagination (10 commandes par page)
     *
     * @param  string $search Terme recherché ("" = aucun)
     * @param  string $statut Statut filtré ("" = tous)
     * @param  int    $page   Page courante (commence à 1)
     * @param  int    $perPage Éléments par page
     * @return array  ['items' => [...], 'total' => int, 'pages' => int]
     */
    public function search(string $search = '', string $statut = '', int $page = 1, int $perPage = 10): array
    {
        // ------------------------------------------------
        // 1) CONSTRUCTION DE LA CONDITION SQL ("WHERE")
        // ------------------------------------------------
        $conditions = []; // va contenir les morceaux de condition
        $params     = []; // va contenir les valeurs associées

        // --- Critère n°1 : la recherche textuelle ---
        if ($search !== '') {
            // "(reference LIKE ... OR client LIKE ...)" = le texte peut
            // correspondre au N° de commande OU au nom du client.
            //
            // ATTENTION : on utilise DEUX paramètres différents
            // (:search_ref et :search_client) même pour la même valeur.
            // MySQL n'accepte PAS de réutiliser un même nom de paramètre
            // deux fois dans une requête réellement préparée.
            $conditions[] = '(reference LIKE :search_ref OR client LIKE :search_client)';

            // On entoure le texte de "%" pour le mode « contient ».
            $params[':search_ref']     = '%' . $search . '%';
            $params[':search_client']  = '%' . $search . '%';
        }

        // --- Critère n°2 : le filtre par statut ---
        if ($statut !== '') {
            // On n'accepte que les statuts officiels (liste STATUTS) :
            // cela évite qu'un paramètre d'URL piégé traverse la requête.
            if (in_array($statut, self::STATUTS, true)) {
                $conditions[] = 'statut = :statut';
                $params[':statut'] = $statut;
            }
        }

        // On assemble les conditions avec "AND" (les 2 doivent être vraies).
        // $condition ne contient PAS le mot WHERE (c'est Model::count()
        // et la requête SELECT qui l'ajouteront).
        $condition = $conditions !== []
            ? implode(' AND ', $conditions) // implode joint avec " AND "
            : '';                          // aucune condition

        // $where = la clause SQL complète avec le mot WHERE (pour le SELECT).
        $where = $condition !== '' ? ' WHERE ' . $condition : '';

        // ------------------------------------------------
        // 2) COMPTAGE DU TOTAL (indispensable pour la pagination)
        // ------------------------------------------------
        // On passe la CONDITION SANS le mot WHERE (voir Model::count).
        $total = $this->count($condition, $params);

        // ------------------------------------------------
        // 3) CALCUL DE LA PAGINATION
        // ------------------------------------------------
        $page    = max(1, $page);                            // jamais en dessous de 1
        $offset  = ($page - 1) * $perPage;                   // décalage SQL
        $pages   = max(1, (int) ceil($total / $perPage));    // nombre total de pages

        // ------------------------------------------------
        // 4) LECTURE DES LIGNES DE LA PAGE COURANTE
        // ------------------------------------------------
        $sql = 'SELECT * FROM `orders`'   // toutes les colonnes
             . $where                      // conditions (recherche/statut)
             . ' ORDER BY date_commande DESC, id DESC' // tri : plus récent d'abord
             . ' LIMIT ' . (int) $perPage  // nombre de lignes max
             . ' OFFSET ' . (int) $offset; // décalage

        // Préparation + exécution de la requête.
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        // ------------------------------------------------
        // 5) RÉSULTAT RETOURNÉ AU CONTRÔLEUR
        // ------------------------------------------------
        return [
            'items' => $stmt->fetchAll(), // les commandes de cette page
            'total' => $total,            // nombre total (filtres inclus)
            'pages' => $pages,            // nombre de pages (pour les boutons)
            'page'  => $page,             // page courante (pour la mise en évidence)
        ];
    }

    /**
     * ------------------------------------------------------------
     * CRÉER UNE COMMANDE COMPLÈTE (le "C" du CRUD)
     * ------------------------------------------------------------
     * @param  array $donnees Champs du formulaire :
     *                        ['client'=>..., 'montant'=>..., 'user_id'=>...]
     * @return int            L'id de la commande créée
     */
    public function store(array $donnees): int
    {
        // ------------------------------------------------
        // GÉNÉRATION AUTOMATIQUE DE LA RÉFÉRENCE
        // ------------------------------------------------
        // Si aucune référence n'a été saisie dans le formulaire,
        // on en fabrique une automatiquement (ex : CMD-2026-047).
        if (empty($donnees['reference'])) {
            $donnees['reference'] = $this->referenceUnique();
        }

        // ------------------------------------------------
        // VALEURS PAR DÉFAUT (si oubliées dans le formulaire)
        // ------------------------------------------------
        $donnees['statut']        = $donnees['statut'] ?? 'en_attente'; // par défaut : en attente
        $donnees['date_commande'] = $donnees['date_commande'] ?? date('Y-m-d'); // aujourd'hui
        $donnees['montant']       = $donnees['montant'] ?? 0;           // montant nul

        // On appelle la méthode create() héritée de Model (INSERT SQL).
        return $this->create($donnees);
    }

    /**
     * ------------------------------------------------------------
     * GÉNÉRER UNE RÉFÉRENCE UNIQUE ET SÉQUENTIELLE
     * ------------------------------------------------------------
     * Format : CMD-<année>-<numéro>  (ex : CMD-2026-006).
     * Le numéro reprend le plus grand existant pour l'année + 1,
     * ainsi les références se suivent (001, 002, 003...).
     *
     * @return string Ex : "CMD-2026-006"
     */
    public function referenceUnique(): string
    {
        // Préfixe de l'année en cours (ex : "CMD-2026-").
        $prefix = 'CMD-' . date('Y') . '-';

        // Position (1-indexée) du début de la partie NUMÉRIQUE dans la
        // référence : juste après le préfixe. Ex : "CMD-2026-" = 9
        // caractères → la partie numérique commence au 10e.
        $start = strlen($prefix) + 1;

        // On cherche le plus grand numéro déjà utilisé POUR CETTE ANNÉE.
        //   SUBSTRING(reference, $start)  → extrait "006" de "CMD-2026-006"
        //   CAST(... AS UNSIGNED)         → le convertit en entier 6
        //   MAX(...)                      → le plus grand (0 si aucun)
        // $start vient de strlen() : c'est un entier sûr (pas d'injection).
        $sql = 'SELECT MAX(CAST(SUBSTRING(reference, ' . (int) $start . ') AS UNSIGNED)) AS maxnum'
             . ' FROM `orders` WHERE reference LIKE :prefix';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':prefix' => $prefix . '%']);

        // Numéro suivant = max + 1.
        $next = (int) $stmt->fetchColumn() + 1;

        // On fabrique la référence et on s'assure qu'elle est bien libre
        // (sécurité si des références manuelles ont perturbé la séquence).
        do {
            $reference = $prefix . str_pad((string) $next, 3, '0', STR_PAD_LEFT);

            $check = $this->db->prepare('SELECT COUNT(*) FROM `orders` WHERE reference = :ref');
            $check->execute([':ref' => $reference]);
            $exists = (int) $check->fetchColumn() > 0;

            $next++;
        } while ($exists && $next < 100000);

        // On retourne la première référence libre trouvée.
        return $reference;
    }

    /**
     * ------------------------------------------------------------
     * STATISTIQUES POUR LE TABLEAU DE BORD (dashboard)
     * ------------------------------------------------------------
     * @return array [
     *    'total_commandes' => int,
     *    'montant_total'   => float,
     *    'par_statut'      => ['en_attente'=>3, 'validee'=>1...],
     *    'dernieres'       => [4 dernières commandes],
     * ]
     */
    public function stats(): array
    {
        // --- Nombre total de commandes ---
        $totalCommandes = $this->count();

        // --- Montant total (somme de toutes les colonnes "montant") ---
        $sqlMontant = 'SELECT COALESCE(SUM(montant), 0) AS total FROM `orders`';
        // COALESCE(x, 0) : si la table est vide, SUM renvoie NULL → on met 0.
        $stmtMontant = $this->db->query($sqlMontant);
        $montantTotal = (float) $stmtMontant->fetchColumn();

        // --- Répartition par statut (un seul GROUP BY = efficace) ---
        $sqlGroupes = 'SELECT statut, COUNT(*) AS nb FROM `orders` GROUP BY statut';
        $stmtGroupes = $this->db->query($sqlGroupes);

        // On initialise le tableau avec 0 partout (affichage propre).
        $parStatut = array_fill_keys(self::STATUTS, 0);

        // On parcourt les lignes : ['statut'=>'validee', 'nb'=>4] ...
        foreach ($stmtGroupes->fetchAll() as $ligne) {
            $parStatut[$ligne['statut']] = (int) $ligne['nb'];
        }

        // --- Les 4 dernières commandes créées ---
        // Tri par "id DESC" = la plus grosse valeur d'id en premier,
        // donc la commande la plus RÉCEMMENT enregistrée arrive en tête.
        $dernieres = $this->findAll('id DESC', 4);

        // On regroupe tout dans un seul tableau de résultats.
        return [
            'total_commandes' => $totalCommandes, // ex : 5
            'montant_total'   => $montantTotal,   // ex : 2846.40
            'par_statut'      => $parStatut,      // ex : ['en_attente'=>2, ...]
            'dernieres'       => $dernieres,      // ex : [les 4 dernières lignes]
        ];
    }

    /**
     * ------------------------------------------------------------
     * COMPTER LES UTILISATEURS (affiché sur le dashboard)
     * ------------------------------------------------------------
     * @return int Nombre d'inscrits
     */
    public function countAllUsers(): int
    {
        // Requête SQL directe sur la table users.
        $stmt = $this->db->query('SELECT COUNT(*) FROM `users`');

        // fetchColumn() retourne le nombre retourné par COUNT(*).
        return (int) $stmt->fetchColumn();
    }

    /**
     * ------------------------------------------------------------
     * FILTRER LES STATUTS AUTORISÉS (utilisé par le formulaire)
     * ------------------------------------------------------------
     * @return array Les 4 codes de statut possibles
     */
    public static function statuts(): array
    {
        // On retourne la constante de classe (liste officielle).
        return self::STATUTS;
    }
}
