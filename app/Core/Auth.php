<?php
/**
 * ============================================================
 *  CLASSE : Auth (Gestion de la connexion des utilisateurs)
 * ============================================================
 *
 *  RÔLE DANS LE MVC :
 *  Cette classe centralise TOUT ce qui concerne l'authentification :
 *  - Se connecter (ouvrir une session)
 *  - Se déconnecter (fermer la session)
 *  - Savoir si quelqu'un est connecté (requireLogin)
 *  - Connaître l'utilisateur actuellement connecté
 *
 *  Elle travaille avec les "sessions PHP" : une session, c'est une
 *  boîte mémoire côté serveur qui se souvient de qui est connecté
 *  même quand l'utilisateur change de page.
 * ============================================================
 */

declare(strict_types=1);

class Auth
{
    /**
     * ------------------------------------------------------------
     * DÉMARRER LA SESSION
     * ------------------------------------------------------------
     * session_start() ouvre la boîte mémoire de session.
     * C'est OBLIGATOIRE avant de lire ou écrire dans $_SESSION.
     *
     * On appelle cette méthode depuis public/index.php, avant
     * tout traitement, pour être sûr que la session est disponible.
     */
    public static function start(): void
    {
        // session_status() renvoie "PHP_SESSION_ACTIVE" si déjà ouverte.
        if (session_status() !== PHP_SESSION_ACTIVE) {
            // Paramètres de sécurité de la session :
            // - httponly  : le cookie n'est pas lisible par le JavaScript
            // - samesite  : le cookie n'est pas envoyé depuis un autre site
            // - strict    : le cookie n'est jamais transmis en "cliquant sur un lien externe"
            session_set_cookie_params([
                'httponly' => true,
                'samesite' => 'Lax',
                'path'     => '/',
            ]);

            // Démarrage effectif de la session.
            session_start();
        }
    }

    /**
     * ------------------------------------------------------------
     * TENTATIVE DE CONNEXION
     * ------------------------------------------------------------
     * @param  string $email    Adresse e-mail saisie par l'utilisateur
     * @param  string $password Mot de passe saisi (EN CLAIR, pas encore vérifié)
     * @return bool   true si l'e-mail + mot de passe sont corrects
     */
    public static function attempt(string $email, string $password): bool
    {
        // 1) On crée un objet User pour interroger la table des utilisateurs.
        $userModel = new User();

        // 2) On cherche l'utilisateur dont l'e-mail correspond.
        $user = $userModel->findByEmail($email);

        // 3) Si AUCUN utilisateur n'a cet e-mail → connexion refusée.
        if ($user === null) {
            return false; // false = échec de la connexion
        }

        // 4) On vérifie le mot de passe.
        // password_verify compare le clair saisi avec le HACHAGE stocké
        // en base de données. Si les deux correspondent, ça retourne true.
        if (!password_verify($password, $user['password'])) {
            return false; // Mot de passe incorrect → échec
        }

        // ------------------------------------------------
        // CONNEXION ACCEPTÉE : on ouvre la session
        // ------------------------------------------------
        // On régénère l'identifiant de session (protection contre le
        // "session fixation" : un attaquant ne peut pas réutiliser
        // un identifiant de session volé).
        session_regenerate_id(true);

        // On stocke dans la session les informations UTILES de l'utilisateur.
        // On ne met JAMAIS le mot de passe en session.
        $_SESSION['user_id']    = (int) $user['id'];      // Identifiant
        $_SESSION['user_name']  = $user['name'];          // Nom affiché
        $_SESSION['user_email'] = $user['email'];         // E-mail
        // Rôle (RBAC). Sécurité : si la colonne est absente ou invalide,
        // on retombe sur 'user' (le rôle le MOINS privilégié).
        $_SESSION['user_role']  = ($user['role'] ?? 'user') === 'admin' ? 'admin' : 'user';

        // true = connexion réussie
        return true;
    }

    /**
     * ------------------------------------------------------------
     * L'UTILISATEUR EST-IL CONNECTÉ ?
     * ------------------------------------------------------------
     * @return bool true si une session utilisateur existe
     */
    public static function check(): bool
    {
        // On retourne true seulement si l'identifiant est présent en session.
        return isset($_SESSION['user_id']);
    }

    /**
     * ------------------------------------------------------------
     * OBLIGER D'ÊTRE CONNECTÉ (protection des pages privées)
     * ------------------------------------------------------------
     * Si l'utilisateur n'est pas connecté, on le redirige
     * immédiatement vers la page de connexion.
     *
     * Appelé au début de chaque contrôleur privé (dashboard, CRUD...).
     */
    public static function requireLogin(): void
    {
        // Si l'utilisateur n'est PAS connecté...
        if (!self::check()) {
            // On mémorise l'URL voulue pour y revenir après connexion
            // (confort utilisateur, facultatif).
            $_SESSION['after_login'] = $_SERVER['REQUEST_URI'] ?? '/';

            // On l'envoie sur la page de connexion.
            // BASE_URL ajoute le sous-dossier si besoin (XAMPP) :
            // "/login" → "/projet-web/login".
            header('Location: ' . (defined('BASE_URL') ? BASE_URL . '/login' : '/login'));

            // On arrête le script : la suite (accès aux données) ne doit
            // surtout pas s'exécuter pour un visiteur non connecté.
            exit;
        }
    }

    /**
     * ------------------------------------------------------------
     * RÔLE DE L'UTILISATEUR CONNECTÉ
     * ------------------------------------------------------------
     * @return string 'admin' ou 'user' ('' si personne n'est connecté)
     */
    public static function role(): string
    {
        // Sécurité : toute valeur autre que 'admin' devient 'user'.
        return ($_SESSION['user_role'] ?? '') === 'admin' ? 'admin' : (self::check() ? 'user' : '');
    }

    /**
     * ------------------------------------------------------------
     * L'UTILISATEUR CONNECTÉ EST-IL ADMINISTRATEUR ?
     * ------------------------------------------------------------
     * @return bool true si le rôle en session est 'admin'
     */
    public static function isAdmin(): bool
    {
        return self::role() === 'admin';
    }

    /**
     * ------------------------------------------------------------
     * EXIGER LE RÔLE ADMINISTRATEUR (contrôle d'accès / RBAC)
     * ------------------------------------------------------------
     * À appeler au début des actions réservées aux admins.
     * - Non connecté      → redirection vers /login.
     * - Connecté non-admin → page 403 (Accès interdit).
     */
    public static function requireAdmin(): void
    {
        // D'abord : il faut être connecté.
        self::requireLogin();

        // Ensuite : il faut être administrateur.
        if (!self::isAdmin()) {
            $router = new Router();
            // Message générique : utilisé pour users, produits, etc.
            $router->renderError(403, 'Vous n\'avez pas les droits nécessaires pour accéder à cette page.');
        }
    }

    /**
     * ------------------------------------------------------------
     * RETOURNER L'IDENTIFIANT DE L'UTILISATEUR CONNECTÉ
     * ------------------------------------------------------------
     * @return int|null  L'id, ou null si personne n'est connecté
     */
    public static function id(): ?int
    {
        // On cast (convertit) en entier, ou null si absent.
        return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
    }

    /**
     * ------------------------------------------------------------
     * RETOURNER LE NOM DE L'UTILISATEUR CONNECTÉ
     * ------------------------------------------------------------
     * @return string  Le nom (ex : "Administrateur Demo") ou chaîne vide
     */
    public static function name(): string
    {
        return (string) ($_SESSION['user_name'] ?? '');
    }

    /**
     * ------------------------------------------------------------
     * RETOURNER L'OBJET UTILISATEUR COMPLET DEPUIS LA BASE
     * ------------------------------------------------------------
     * Parfois on a besoin de champs frais (ex : après une modification).
     *
     * @return array|null Les données de l'utilisateur, ou null
     */
    public static function user(): ?array
    {
        // Si personne n'est connecté → null.
        if (self::id() === null) {
            return null;
        }

        // Sinon, on relit la ligne de l'utilisateur dans MySQL.
        $userModel = new User();
        return $userModel->findById(self::id());
    }

    /**
     * ------------------------------------------------------------
     * DÉCONNEXION
     * ------------------------------------------------------------
     * Efface toutes les données de session et détruit la session.
     */
    public static function logout(): void
    {
        // On supprime les variables liées à l'utilisateur.
        unset($_SESSION['user_id']);
        unset($_SESSION['user_name']);
        unset($_SESSION['user_email']);
        unset($_SESSION['user_role']);

        // session_destroy() détruit entièrement la session côté serveur.
        session_destroy();
    }
}
