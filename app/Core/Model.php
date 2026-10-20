<?php
/**
 * ============================================================
 *  CLASSE ABSTRAITE : Model (Base commune de tous les modèles)
 * ============================================================
 *
 *  RÔLE DANS LE MVC :
 *  Cette classe fournit les opérations SQL réutilisables :
 *  - findAll()  → LIRE toutes les lignes d'une table
 *  - findById() → LIRE une ligne précise grâce à son identifiant
 *  - create()   → CRÉER une ligne (le "C" du CRUD)
 *  - update()   → MODIFIER une ligne (le "U" du CRUD)
 *  - delete()   → SUPPRIMER une ligne (le "D" du CRUD)
 *
 *  Les classes User et Order héritent de cette classe :
 *  elles n'ont donc pas à réécrire ces requêtes.
 *
 *  SÉCURITÉ : toutes les requêtes utilisent des "requêtes préparées"
 *  (placeholders `:nom`), ce qui bloque efficacement les injections SQL.
 * ============================================================
 */

// On limite l'accès strict aux types (PHP 8 strict).
declare(strict_types=1);

// On ne déclare une classe abstraite que si Model.php est inclus
// directement : on provoque une erreur claire si c'est mal utilisé.
if (!defined('BASE_PATH')) {
    // BASE_PATH est défini dans config/config.php : si on arrive ici,
    // c'est que le fichier a été appelé sans passer par le front controller.
    die('Accès direct interdit : passez par public/index.php');
}

abstract class Model
{
    /**
     * @var string Nom de la table MySQL correspondant à ce modèle.
     *              Chaque classe fille (User, Order) doit définir
     *              cette valeur dans sa propre déclaration.
     */
    protected string $table = '';

    /**
     * @var PDO La connexion à la base, récupérée automatiquement
     *          au moment où on crée un objet modèle (constructeur).
     */
    protected PDO $db;

    /**
     * ------------------------------------------------------------
     * CONSTRUCTEUR : appelé automatiquement avec new Order() / new User()
     * ------------------------------------------------------------
     * On récupère ici la connexion PDO unique (singleton).
     */
    public function __construct()
    {
        // On demande la connexion à la classe Database (singleton).
        // Chaque modèle a ainsi accès à MySQL sans la recréer.
        $this->db = Database::getInstance();
    }

    /**
     * ------------------------------------------------------------
     * LIRE TOUTES LES LIGNES DE LA TABLE  (Read / Read all)
     * ------------------------------------------------------------
     * @param  string $orderBy  Colonne utilisée pour trier (ex: "created_at DESC")
     * @param  int    $limit    Nombre maximum de lignes à retourner (0 = toutes)
     * @return array  Liste de tableaux associatifs : [ ['id'=>1,...], ['id'=>2,...] ]
     */
    public function findAll(string $orderBy = 'id DESC', int $limit = 0): array
    {
        // On construit le début de la requête avec le nom de la table
        // protégé par des quotes (backticks) pour éviter les mots réservés SQL.
        $sql = 'SELECT * FROM `' . $this->table . '`';

        // On ajoute la clause de tri si une colonne de tri est fournie.
        if ($orderBy !== '') {
            $sql .= ' ORDER BY ' . $orderBy; // ex : ORDER BY created_at DESC
        }

        // On ajoute la limite si elle est différente de 0.
        if ($limit > 0) {
            $sql .= ' LIMIT ' . (int) $limit; // (int) force un nombre entier (sécurité)
        }

        // On prépare la requête (le serveur MySQL l'analyse une 1ère fois).
        $stmt = $this->db->prepare($sql);

        // On exécute la requête sans aucun paramètre variable ici.
        $stmt->execute();

        // On récupère TOUS les résultats sous forme de tableau PHP.
        return $stmt->fetchAll();
    }

    /**
     * ------------------------------------------------------------
     * LIRE UNE SEULE LIGNE GRÂCE À SON IDENTIFIANT (Read / Read one)
     * ------------------------------------------------------------
     * @param  int $id  L'identifiant (id) de la ligne recherchée
     * @return array|null  La ligne trouvée, ou null si elle n'existe pas
     */
    public function findById(int $id): ?array
    {
        // Requête préparée : le `:id` sera remplacé par la vraie valeur,
        // ce qui empêche totalement l'injection SQL.
        $sql = 'SELECT * FROM `' . $this->table . '` WHERE id = :id LIMIT 1';

        // Préparation de la requête côté MySQL.
        $stmt = $this->db->prepare($sql);

        // Exécution en fournissant la valeur réelle du paramètre :id.
        $stmt->execute([':id' => $id]);

        // fetch() retourne UNE ligne, ou false si aucune ne correspond.
        $ligne = $stmt->fetch();

        // On convertit "false" en null pour avoir un type de retour propre.
        return $ligne === false ? null : $ligne;
    }

    /**
     * ------------------------------------------------------------
     * CRÉER UNE LIGNE (Create → le "C" du CRUD)
     * ------------------------------------------------------------
     * @param  array $donnees  Les valeurs à insérer, ex :
     *                         ['client' => 'Dupont', 'montant' => 100]
     * @return int  L'identifiant (id) de la ligne fraîchement créée
     */
    public function create(array $donnees): int
    {
        // On extrait les noms de colonnes (les clés du tableau).
        // array_keys(['client'=>.., 'montant'=>..]) → ['client', 'montant']
        $colonnes = array_keys($donnees);

        // On crée un placeholder (:colonne) pour chaque colonne.
        // array_map(fn($c) => ':'.$c, ['client','montant'])
        //   → [':client', ':montant']
        $placeholders = array_map(
            fn (string $c): string => ':' . $c,  // fn() = fonction fléchée (raccourci)
            $colonnes
        );

        // On assemble la requête INSERT :
        // INSERT INTO `orders` (`client`, `montant`) VALUES (:client, :montant)
        $sql = 'INSERT INTO `' . $this->table . '`'
             . ' (`' . implode('`, `', $colonnes) . '`)'
             . ' VALUES (' . implode(', ', $placeholders) . ')';

        // Préparation + exécution avec les données réelles.
        $stmt = $this->db->prepare($sql);
        $stmt->execute($donnees);

        // lastInsertId() = numéro de la ligne créée par MySQL automatiquement.
        return (int) $this->db->lastInsertId();
    }

    /**
     * ------------------------------------------------------------
     * MODIFIER UNE LIGNE (Update → le "U" du CRUD)
     * ------------------------------------------------------------
     * @param int   $id      Identifiant de la ligne à modifier
     * @param array $donnees Nouvelles valeurs  ['montant' => 150]
     * @return int  Nombre de lignes réellement modifiées (0 ou 1)
     */
    public function update(int $id, array $donnees): int
    {
        // Construit : UPDATE `table` SET `montant` = :montant WHERE id = :id
        // 1) On récupère d'abord la liste des noms de colonnes.
        //    array_keys(['montant' => 150]) → ['montant']
        $colonnes = array_keys($donnees);

        // 2) On construit la partie SET de la requête, colonne par colonne.
        $sets = []; // tableau vide qui va recevoir les morceaux de requête

        // Boucle "foreach" : on parcourt chaque nom de colonne un par un.
        foreach ($colonnes as $colonne) {
            // On ajoute " `montant` = :montant " dans le tableau $sets.
            $sets[] = '`' . $colonne . '` = :' . $colonne;
        }

        // On assemble la requête complète.
        $sql = 'UPDATE `' . $this->table . '`'
             . ' SET ' . implode(', ', $sets)  // implode joint les morceaux avec ", "
             . ' WHERE id = :id';

        // On prépare la requête.
        $stmt = $this->db->prepare($sql);

        // On fusionne les données à modifier + l'id pour WHERE.
        $stmt->execute($donnees + [':id' => $id]);

        // rowCount() = combien de lignes ont réellement changé.
        return $stmt->rowCount();
    }

    /**
     * ------------------------------------------------------------
     * SUPPRIMER UNE LIGNE (Delete → le "D" du CRUD)
     * ------------------------------------------------------------
     * @param  int $id  Identifiant de la ligne à supprimer
     * @return int      Nombre de lignes supprimées (0 ou 1)
     */
    public function delete(int $id): int
    {
        // Requête DELETE avec un paramètre protégé (:id).
        $sql = 'DELETE FROM `' . $this->table . '` WHERE id = :id';

        // Préparation de la requête.
        $stmt = $this->db->prepare($sql);

        // Exécution avec l'identifiant fourni.
        $stmt->execute([':id' => $id]);

        // On retourne 1 si une ligne a été supprimée, 0 sinon.
        return $stmt->rowCount();
    }

    /**
     * ------------------------------------------------------------
     * COMPTER LES LIGNES (utilisé pour la pagination et les stats)
     * ------------------------------------------------------------
     * @param  string $where  CONDITION SQL facultative, SANS le mot WHERE.
     *                        Exemple : "statut = :statut"
     *                        (chaîne vide "" = on compte tout)
     * @param  array  $params Paramètres associés à la condition
     * @return int    Le nombre de lignes trouvées
     */
    public function count(string $where = '', array $params = []): int
    {
        // Requête de comptage : SELECT COUNT(*) renvoie un seul nombre.
        $sql = 'SELECT COUNT(*) AS total FROM `' . $this->table . '`';

        // On ajoute la condition seulement si elle est fournie.
        // On ajoute NOUS-MÊMES le mot WHERE : l'appelant fournit
        // uniquement la condition (ex : "statut = :statut").
        if ($where !== '') {
            $sql .= ' WHERE ' . $where; // ex : WHERE statut = :statut
        }

        // Préparation + exécution.
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        // On récupère la valeur du champ "total" en la convertissant en entier.
        return (int) $stmt->fetchColumn();
    }
}
