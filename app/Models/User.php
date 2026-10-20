<?php
/**
 * ============================================================
 *  MODÈLE : User (la table "users" de la base de données)
 * ============================================================
 *
 *  RÔLE DANS LE MVC :
 *  Ce fichier est LA SEULE endroit du projet qui sait comment la
 *  table "users" est organisée. Il contient toutes les requêtes
 *  SQL concernant les utilisateurs :
 *  - créer un compte (avec hachage du mot de passe)
 *  - chercher un utilisateur par e-mail (connexion)
 *  - lister / paginer les utilisateurs (CRUD)
 *  - modifier / supprimer un compte
 *
 *  Il HÉRITE de la classe Model : il bénéficie gratuitement des
 *  méthodes génériques findAll(), findById(), create(), update(),
 *  delete() définies dans app/Core/Model.php.
 *
 *  AUCUNE balise HTML ici ! Ce fichier ne parle qu'aux données.
 * ============================================================
 */

declare(strict_types=1);

class User extends Model
{
    /**
     * @var string Nom de la table MySQL gérée par ce modèle.
     *             Obligatoire : c'est lui que les méthodes héritées
             *             insèrent dans les requêtes SQL.
     */
    protected string $table = 'users';

    /**
     * ------------------------------------------------------------
     * RECHERCHER UN UTILISATEUR À PARTIR DE SON E-MAIL
     * ------------------------------------------------------------
     * Utilisé lors de la CONNEXION : on cherche le compte dont
     * l'e-mail correspond, puis on vérifiera le mot de passe.
     *
     * @param  string $email Adresse e-mail saisie
     * @return array|null    La ligne trouvée, ou null si inconnu
     */
    public function findByEmail(string $email): ?array
    {
        // Requête préparée : le ":email" sera remplacé de façon sûre.
        // Cela empêche l'injection SQL via le champ e-mail.
        $sql = 'SELECT * FROM `users` WHERE email = :email LIMIT 1';

        // Préparation de la requête côté serveur MySQL.
        $stmt = $this->db->prepare($sql);

        // Exécution avec la vraie valeur de l'e-mail.
        $stmt->execute([':email' => $email]);

        // Récupération de la ligne (un seul tableau associatif).
        $ligne = $stmt->fetch();

        // Si aucune ligne n'a été trouvée, fetch() renvoie "false".
        // On convertit ce false en "null" pour un code plus propre.
        return $ligne === false ? null : $ligne;
    }

    /**
     * ------------------------------------------------------------
     * VÉRIFIER SI UNE ADRESSE E-MAIL EST DÉJÀ UTILISÉE
     * ------------------------------------------------------------
     * Utilisé à l'INSCRIPTION pour refuser un double compte.
     *
     * @param  string $email     L'e-mail à tester
     * @param  int|null $exclude Id à exclure (utile à la modification :
     *                           l'utilisateur garde son propre e-mail)
     * @return bool              true si l'e-mail est déjà pris
     */
    public function emailExists(string $email, ?int $exclude = null): bool
    {
        // On commence par le comptage : SELECT COUNT(*) → 0 ou 1...
        $sql = 'SELECT COUNT(*) FROM `users` WHERE email = :email';

        // Paramètres de la requête (au moins l'e-mail).
        $params = [':email' => $email];

        // Cas de la modification : "…WHERE email = x AND id <> 7"
        // pour ne pas se bloquer soi-même.
        if ($exclude !== null) {
            $sql .= ' AND id <> :exclude';          // "et l'id différent de l'id exclu"
            $params[':exclude'] = $exclude;          // paramètre associé
        }

        // Préparation + exécution.
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        // fetchColumn() retourne la valeur de la 1ère colonne (le compteur).
        // Si le compteur est > 0, c'est que l'e-mail existe déjà.
        return ((int) $stmt->fetchColumn()) > 0;
    }

    /**
     * ------------------------------------------------------------
     * CRÉER UN COMPTE UTILISATEUR (avec mot de passe sécurisé)
     * ------------------------------------------------------------
     * @param  string $name     Nom complet
     * @param  string $email    Adresse e-mail
     * @param  string $password Mot de passe EN CLAIR saisi par l'utilisateur
     * @param  string $role     Rôle du compte ('admin' ou 'user')
     * @return int              L'id du compte créé
     */
    public function register(string $name, string $email, string $password, string $role = 'user'): int
    {
        // ------------------------------------------------
        // HACHAGE DU MOT DE PASSE (étape obligatoire)
        // ------------------------------------------------
        // password_hash transforme "password123" en une longue chaîne
        // du type "$2y$12$LQv3c1yq..." stockée en base.
        // On ne stocke JAMAIS le mot de passe en clair : si la base
        // est volée, les mots de passe restent inutilisables.
        // PASSWORD_DEFAULT = l'algorithme le plus récent disponible.
        $hash = password_hash($password, PASSWORD_DEFAULT);

        // On prépare le tableau des données à insérer en base.
        $donnees = [
            'name'     => $name,   // Nom affiché
            'email'    => $email,  // E-mail de connexion
            'password' => $hash,   // Mot de passe HACHÉ (jamais en clair)
            // Sécurité : on n'accepte QUE 'admin' ou 'user' (sinon 'user').
            'role'     => $role === 'admin' ? 'admin' : 'user',
        ];

        // On utilise la méthode create() héritée de Model (INSERT SQL).
        // Elle retourne automatiquement le nouvel identifiant.
        return $this->create($donnees);
    }

    /**
     * ------------------------------------------------------------
     * MODIFIER UN COMPTE UTILISATEUR
     * ------------------------------------------------------------
     * Le mot de passe n'est modifié que s'il est fourni (non vide).
     *
     * @param int    $id       Identifiant du compte à modifier
     * @param string $name     Nouveau nom
     * @param string $email    Nouvel e-mail
     * @param string $password Nouveau mot de passe ("" = inchangé)
     * @param string|null $role Nouveau rôle (null = inchangé ; sinon 'admin'/'user')
     * @return int   Nombre de lignes modifiées
     */
    public function updateProfile(int $id, string $name, string $email, string $password = '', ?string $role = null): int
    {
        // Tableau des données à mettre à jour.
        $donnees = [
            'name'  => $name,  // Le nom est toujours mis à jour
            'email' => $email, // L'e-mail est toujours mis à jour
        ];

        // Si un mot de passe a été saisi (pas de chaîne vide)...
        if ($password !== '') {
            // ...on le HACHE avant de l'enregistrer (jamais en clair).
            $donnees['password'] = password_hash($password, PASSWORD_DEFAULT);
        }
        // Sinon, le champ "password" n'est pas dans le tableau,
        // donc la valeur en base reste inchangée.

        // Le rôle n'est modifié que si on nous en fournit un
        // (réservé aux administrateurs — jamais sur soi-même).
        if ($role !== null) {
            $donnees['role'] = $role === 'admin' ? 'admin' : 'user';
        }

        // Méthode héritée de Model : UPDATE `users` SET ... WHERE id = :id
        return $this->update($id, $donnees);
    }

    /**
     * ------------------------------------------------------------
     * LISTER LES UTILISATEURS AVEC RECHERCHE ET PAGINATION
     * ------------------------------------------------------------
     * Cette méthode alimente la page /users du CRUD.
     *
     * @param  string $search Terme recherché ("" = aucun filtre)
     * @param  int    $page   Numéro de page (commence à 1)
     * @param  int    $perPage Nombre d'éléments par page
     * @return array          Tableau ['items' => [...], 'total' => int,
     *                                'pages' => int, 'page' => int]
     */
    public function search(string $search = '', int $page = 1, int $perPage = 10): array
    {
        // Condition SQL de recherche (vide au départ).
        $condition = '';

        // Paramètres SQL associés (remplis seulement si recherche).
        $params = [];

        // Si l'utilisateur a tapé quelque chose dans la recherche...
        if ($search !== '') {
            // On cherche dans le nom OU l'e-mail (LIKE = « contient »).
            // ATTENTION : on n'écrit PAS le mot WHERE ici, car c'est
            // Model::count() et le SELECT qui l'ajoutent eux-mêmes.
            //
            // Deux paramètres distincts (:search_name / :search_email)
            // car MySQL refuse qu'un même nom apparaisse 2 fois.
            $condition = '(name LIKE :search_name OR email LIKE :search_email)';

            // "%" de chaque côté = « le texte peut contenir n'importe quoi ».
            $params[':search_name']  = '%' . $search . '%';
            $params[':search_email'] = '%' . $search . '%';
        }

        // $where = clause complète (avec le mot WHERE) pour le SELECT.
        $where = $condition !== '' ? ' WHERE ' . $condition : '';

        // ------------------------------------------------
        // 1) ON COMPTE LE TOTAL D'ÉLÉMENTS (pour la pagination)
        // ------------------------------------------------
        // On passe la condition SANS le mot WHERE.
        $total = $this->count($condition, $params); // méthode héritée de Model

        // ------------------------------------------------
        // 2) ON CALCULE LA PAGINATION
        // ------------------------------------------------
        // Sécurité : la page doit être au moins 1.
        $page = max(1, $page);

        // Décalage : page 1 → 0, page 2 → 10, page 3 → 20...
        $offset = ($page - 1) * $perPage;

        // Nombre total de pages (arrondi au-dessus), au minimum 1.
        $pages = max(1, (int) ceil($total / $perPage));

        // ------------------------------------------------
        // 3) ON LIT LES LIGNES DE LA PAGE COURANTE
        // ------------------------------------------------
        $sql = 'SELECT * FROM `users`'          // on lit toutes les colonnes
             . $where                             // condition de recherche
             . ' ORDER BY created_at DESC'        // du plus récent au plus ancien
             . ' LIMIT ' . (int) $perPage         // nombre max de lignes
             . ' OFFSET ' . (int) $offset;        // décalage (pagination)

        // Préparation + exécution avec les paramètres éventuels.
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        // On retourne les lignes de CETTE page + le total + la pagination
        // (pages/page servent aux boutons "Précédent / Suivant").
        return [
            'items' => $stmt->fetchAll(), // les utilisateurs de la page
            'total' => $total,            // le nombre total en base
            'pages' => $pages,            // nombre de pages (pour les boutons)
            'page'  => $page,             // page courante (pour la mise en évidence)
        ];
    }
}
