<?php
/**
 * ============================================================
 *  CLASSE DE BASE : Controller
 * ============================================================
 *
 *  RÔLE DANS LE MVC :
 *  Tous les contrôleurs du projet (AuthController, OrderController,
 *  UserController, DashboardController) HÉRITENT de cette classe.
 *
 *  Elle fournit les « outils communs » dont un contrôleur a besoin :
 *  - render()   → afficher une vue (HTML) avec des données
 *  - redirect() → rediriger vers une autre page
 *  - viewData  → transmettre des variables à la vue
 *  - isPost()  → savoir si un formulaire a été envoyé
 *  - csrfToken()→ gérer le jeton de sécurité des formulaires
 *
 *  C'est la traduction concrète du "C" (Controller) du MVC :
 *  elle ne contient AUCUNE requête SQL et AUCUNE balise HTML,
 *  elle se contente d'organiser le flux de l'application.
 * ============================================================
 */

declare(strict_types=1);

abstract class Controller
{
    /**
     * @var array Données à transmettre à la vue.
     *            Exemple : $this->viewData['orders'] = $listeCommandes;
     *            Dans la vue, on récupère avec : $orders
     */
    protected array $viewData = [];

    /**
     * @var array Liste des messages d'erreur de formulaire (erreurs de validation).
     */
    protected array $errors = [];

    /**
     * @var array Messages de succès à afficher (ex : "Commande créée !").
     */
    protected array $success = [];

    /**
     * ------------------------------------------------------------
     * STOCKER UN MESSAGE "FLASH" EN SESSION
     * ------------------------------------------------------------
     * Un message flash est un message temporaires : on le stocke en
     * session AVANT une redirection, il sera affiché sur la page
     * SUIVANTE puis automatiquement oublié.
     *
     * Exemple : "Commande créée avec succès !"
     *
     * @param string $type    "success" (succès) ou "error" (erreur)
     * @param string $message Message à afficher
     */
    protected function flash(string $type, string $message): void
    {
        // On stocke le message dans la session (le layout s'en charge).
        $_SESSION['flash'][$type] = $message;
    }

    /**
     * ------------------------------------------------------------
     * AFFICHER UNE VUE (le contrôleur rend la main à une page HTML)
     * ------------------------------------------------------------
     * @param string $view  Chemin de la vue, ex : "orders/index"
     *                      (le fichier sera app/View/orders/index.php)
     * @param array  $data  Données à rendre disponibles dans la vue
     */
    protected function render(string $view, array $data = []): void
    {
        // 1) On fusionne les données transmises avec celles déjà présentes.
        $this->viewData = array_merge($this->viewData, $data);

        // 2) ON RÉCUPÈRE LES MESSAGES FLASH laissés par la page précédente
        //    (ex : "Commande créée !" envoyé juste avant une redirection).
        if (!empty($_SESSION['flash'])) {
            // On parcourt les messages stockés (['success' => '...', ...]).
            foreach ($_SESSION['flash'] as $type => $message) {
                if ($type === 'success') {
                    $this->success[] = $message; // message vert de succès
                } else {
                    $this->errors['_global'][] = $message; // message rouge
                }
            }
            // On efface les messages : ils ne doivent s'afficher qu'UNE fois.
            unset($_SESSION['flash']);
        }

        // 3) On expose chaque donnée comme VARIABLE LOCALE de la vue.
        //    Exemple : ['title' => 'Mes commandes'] devient $title = 'Mes commandes'
        //    C'est ce qui permet d'utiliser $title directement dans le .php de la vue.
        extract($this->viewData, EXTR_SKIP); // EXTR_SKIP = ne pas écraser les variables existantes

        // 4) On rend aussi les messages d'erreur/succès accessibles à la vue.
        $errors  = $this->errors;
        $success = $this->success;

        // 5) On calcule le chemin ABSOLU du fichier de vue.
        //    "orders/index" → D:/.../app/View/orders/index.php
        $viewFile = VIEWS_PATH . '/' . $view . '.php';

        // 5) On vérifie que le fichier de vue existe vraiment.
        if (!file_exists($viewFile)) {
            // S'il n'existe pas, on affiche une erreur explicite (debug).
            die('Vue introuvable : ' . htmlspecialchars($viewFile, ENT_QUOTES, 'UTF-8'));
        }

        // 7) ON INCLUT LE HEADER (début du HTML, barre de navigation).
        //    Toutes les pages commencent par le même layout.
        include VIEWS_PATH . '/layouts/header.php';

        // 8) ON INCLUT LA VUE DEMANDÉE (le contenu principal de la page).
        include $viewFile;

        // 9) ON INCLUT LE FOOTER (fin du HTML, scripts éventuels).
        include VIEWS_PATH . '/layouts/footer.php';
    }

    /**
     * ------------------------------------------------------------
     * CAPTURER UNE VUE PARTIELLE (sans header ni footer)
     * ------------------------------------------------------------
     * Sert aux réponses AJAX : le fichier de vue est exécuté et son
     * HTML est renvoyé comme texte, sans le layout de la page.
     *
     * @param string $view Chemin de la vue, ex : "products/_rows"
     * @param array  $data Données exposées comme variables dans la vue
     */
    protected function capture(string $view, array $data = []): string
    {
        $this->viewData = array_merge($this->viewData, $data);
        extract($this->viewData, EXTR_SKIP);
        $errors  = $this->errors;
        $success = $this->success;

        $viewFile = VIEWS_PATH . '/' . $view . '.php';
        if (!file_exists($viewFile)) {
            return '';
        }

        ob_start();
        include $viewFile;

        return (string) ob_get_clean();
    }

    /**
     * ------------------------------------------------------------
     * REDIRIGER LE NAVIGATEUR VERS UNE AUTRE PAGE
     * ------------------------------------------------------------
     * Sert après une action (création, suppression...) pour éviter
     * que l'utilisateur ne renvoie le formulaire en actualisant la page.
     *
     * @param string $url Chemin cible, ex : "/orders"
     */
    protected function redirect(string $url): void
    {
        // header() doit être envoyé AVANT tout affichage HTML.
        //
        // On ajoute le préfixe du sous-dossier si le site est installé
        // dans XAMPP (/projet-web). Sans cela, "/dashboard" enverrait
        // le navigateur vers http://localhost/dashboard (404).
        if (defined('BASE_URL') && BASE_URL !== '' && strpos($url, BASE_URL) !== 0) {
            $url = BASE_URL . '/' . ltrim($url, '/');
        }

        // On envoie l'en-tête de redirection HTTP 302 ("page déplacée").
        header('Location: ' . $url);

        // exit arrête immédiatement l'exécution du script PHP.
        exit;
    }

    /**
     * ------------------------------------------------------------
     * EST-CE QUE C'EST UN ENVOI DE FORMULAIRE (méthode HTTP POST) ?
     * ------------------------------------------------------------
     * @return bool true si le formulaire a été soumis
     */
    protected function isPost(): bool
    {
        // $_SERVER['REQUEST_METHOD'] contient "GET" ou "POST".
        return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
    }

    /**
     * ------------------------------------------------------------
     * RÉCUPÉRER UNE VALEUR D'UN FORMULAIRE (champ POST)
     * ------------------------------------------------------------
     * @param  string $key      Nom du champ HTML (name="client")
     * @param  string $default  Valeur par défaut si le champ est absent
     * @return string           Valeur nettoyée (trim = suppression des espaces)
     */
    protected function input(string $key, string $default = ''): string
    {
        // On récupère la valeur POST, on supprime les espaces inutiles
        // en début/fin (trim) et on la convertit en texte (string).
        return trim((string) ($_POST[$key] ?? $default));
    }

    /**
     * ------------------------------------------------------------
     * RÉCUPÉRER UN PARAMÈTRE D'UNE URL (méthode GET)
     * ------------------------------------------------------------
     * Exemple : /orders/edit/3?id=3  →  get('id') retourne "3"
     *
     * @param  string $key     Nom du paramètre
     * @param  mixed  $default Valeur retournée si absent
     * @return mixed
     */
    protected function query(string $key, mixed $default = null): mixed
    {
        return $_GET[$key] ?? $default;
    }

    /**
     * ------------------------------------------------------------
     * JETON DE SÉCURITÉ CSRF (protection anti-falsification)
     * ------------------------------------------------------------
     * Un "jeton CSRF" est une clé secrète stockée dans la session et
     * cachée dans chaque formulaire. Au retour du formulaire, on
     * vérifie que les deux correspondent. Ainsi, un site malveillant
     * ne peut pas envoyer un formulaire à notre insu.
     */
    protected function csrfToken(): string
    {
        // Si aucun jeton n'existe encore dans la session...
        if (empty($_SESSION['csrf_token'])) {
            // bin2hex(random_bytes(32)) = une chaîne aléatoire
            // de 64 caractères quasi impossibles à deviner.
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        // On retourne le jeton (à afficher dans le champ caché du formulaire).
        return $_SESSION['csrf_token'];
    }

    /**
     * ------------------------------------------------------------
     * VÉRIFIER QUE LE JETON CSRF REÇU EST VALIDE
     * ------------------------------------------------------------
     * @return bool true si le jeton est correct
     */
    protected function csrfCheck(): bool
    {
        // On récupère le jeton envoyé par le formulaire ($_POST['csrf_token']).
        $envoye = $_POST['csrf_token'] ?? '';

        // On récupère le jeton stocké dans la session.
        $attendu = $_SESSION['csrf_token'] ?? '';

        // hash_equals compare 2 chaînes sans risque de timing attack,
        // et retourne false si l'une des deux est vide.
        return $attendu !== '' && hash_equals($attendu, $envoye);
    }
}
