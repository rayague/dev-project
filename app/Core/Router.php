<?php
/**
 * ============================================================
 *  CLASSE : Router (Gestionnaire des adresses URL)
 * ============================================================
 *
 *  RÔLE DANS LE MVC :
 *  Le routeur, c'est le « chef de gare » du site. À chaque visite,
 *  il regarde l'URL demandée par le navigateur et il décide QUEL
 *  CONTRÔLEUR et quelle méthode doivent traiter la demande.
 *
 *  EXEMPLES :
 *  - GET  /orders          → OrderController::index()
 *  - GET  /orders/create   → OrderController::create()  (formulaire vierge)
 *  - POST /orders          → OrderController::store()   (enregistrement)
 *  - GET  /orders/edit?id=3→ OrderController::edit()    (formulaire rempli)
 *  - GET  /login           → AuthController::showLogin()
 *
 *  Les paramètres (comme l'id) passent par la QUERY STRING (?id=3),
 *  c'est l'approche la plus simple et la plus lisible pour un
 *  projet d'école.
 *
 *  Sans routeur, il faudrait un fichier PHP par page : la logique
 *  serait mélangée partout. Le routeur permet un vrai MVC.
 * ============================================================
 */

declare(strict_types=1);

class Router
{
    /**
     * @var array Liste des routes enregistrées, dans l'ordre de déclaration.
     *  Structure : [
     *    ['GET', '/orders', 'OrderController', 'index'],
     *    ['POST','/orders', 'OrderController', 'store'],
     *    ...
     * ]
     */
    private array $routes = [];

    /**
     * ------------------------------------------------------------
     * ENREGISTRER UNE ROUTE
     * ------------------------------------------------------------
     * @param string $method     "GET" (afficher) ou "POST" (formulaire)
     * @param string $path       Chemin URL, ex : "/orders/edit"
     * @param string $controller Nom de la classe contrôleur, ex : "OrderController"
     * @param string $action     Nom de la méthode, ex : "edit"
     */
    public function add(string $method, string $path, string $controller, string $action): void
    {
        // On normalise le chemin : on supprime le "/" final s'il existe,
        // pour éviter que "/orders/" et "/orders" soient 2 routes distinctes.
        // "??" gère le cas particulier du chemin racine "/" qui deviendrait "".
        $path = rtrim($path, '/') ?: '/';

        // On stocke la route dans le tableau des routes.
        $this->routes[] = [
            'method'     => strtoupper($method), // on force "GET" ou "POST"
            'path'       => $path,
            'controller' => $controller,
            'action'     => $action,
        ];
    }

    /**
     * ------------------------------------------------------------
     * RACCOURCIS POUR LES 2 MÉTHODES HTTP UTILISÉES DANS LE SITE
     * ------------------------------------------------------------
     * get('/x', ...)  = enregistrer une page À AFFICHER
     * post('/x', ...) = enregistrer un formulaire À TRAITER
     */
    public function get(string $path, string $controller, string $action): void
    {
        $this->add('GET', $path, $controller, $action);   // Route GET
    }

    public function post(string $path, string $controller, string $action): void
    {
        $this->add('POST', $path, $controller, $action);  // Route POST
    }

    /**
     * ------------------------------------------------------------
     * ANALYSER L'URL DEMANDÉE ET LANCER LE BON CONTRÔLEUR
     * ------------------------------------------------------------
     * Méthode appelée par public/index.php à CHAQUE visite.
     *
     * @param string $url URL reçue, ex : "/orders/edit?id=3"
     */
    public function dispatch(string $url): void
    {
        // 1) On extrait uniquement le CHEMIN de l'URL.
        //    parse_url("/orders/edit?id=3", PHP_URL_PATH) → "/orders/edit"
        //    (la partie "?id=3" est exclue, on s'en occupera via $_GET)
        $path = parse_url($url, PHP_URL_PATH) ?? '/';

        // 2) On nettoie : on enlève les "/" du début et de la fin.
        //    "/orders/edit/" → "orders/edit"
        $path = trim($path, '/');

        // 3) On récupère la méthode HTTP réelle du navigateur :
        //    "GET" quand on clique sur un lien,
        //    "POST" quand on soumet un formulaire.
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

        // 4) ON RECHERCHE LA ROUTE CORRESPONDANTE
        foreach ($this->routes as $route) {

            // 4a) La méthode doit correspondre (GET ≠ POST).
            if ($route['method'] !== $method) {
                continue; // "continue" = on passe à la route suivante
            }

            // 4b) On compare le chemin de la route avec le chemin de l'URL.
            //     ATTENTION : comparaison STRICTE ("==="), donc
            //     "/orders" NE correspond PAS à "/orders/create".
            if (trim($route['path'], '/') === $path) {

                // ------------------------------------------------
                // ROUTE TROUVÉE : on prépare le contrôleur
                // ------------------------------------------------
                $controllerName = $route['controller']; // ex : "OrderController"
                $action         = $route['action'];     // ex : "edit"

                // Sécurité : la classe du contrôleur doit réellement exister.
                if (!class_exists($controllerName)) {
                    $this->renderError(404, 'Contrôleur introuvable : ' . $controllerName);
                    return; // on quitte immédiatement la méthode
                }

                // Sécurité : la méthode doit réellement exister.
                if (!method_exists($controllerName, $action)) {
                    $this->renderError(404, 'Action introuvable : ' . $action);
                    return;
                }

                // ------------------------------------------------
                // ON INSTANCIE LE CONTRÔLEUR (new OrderController())
                // ------------------------------------------------
                $controller = new $controllerName();

                // ------------------------------------------------
                // ON APPELLE LA MÉTHODE = LA PAGE EST PRODUITE ICI
                // ------------------------------------------------
                // Exemple : $controller->edit() va lire la commande n°3
                // (récupérée via $_GET['id']) puis afficher la vue.
                call_user_func([$controller, $action]);

                // La demande est traitée : on arrête la recherche.
                return;
            }
        }

        // ------------------------------------------------
        // AUCUNE ROUTE NE CORRESPOND → ERREUR 404
        // ------------------------------------------------
        $this->renderError(404, 'Page introuvable : /' . $path);
    }

    /**
     * ------------------------------------------------------------
     * AFFICHER UNE PAGE D'ERREUR PROPRE (404, 403, 500...)
     * ------------------------------------------------------------
     * @param int    $code    Code HTTP de l'erreur (404 = page introuvable)
     * @param string $message Message expliqué à l'utilisateur
     */
    public function renderError(int $code, string $message): void
    {
        // On envoie le code HTTP réel au navigateur.
        http_response_code($code);

        // On calcule le chemin de la vue d'erreur (ex : app/View/errors/404.php).
        $viewFile = VIEWS_PATH . '/errors/' . $code . '.php';

        // On prépare le message pour la vue.
        $errorMessage = $message;

        // Si la vue d'erreur existe, on l'utilise ; sinon, page minimaliste.
        if (file_exists($viewFile)) {
            include $viewFile;
        } else {
            echo '<h1>Erreur ' . $code . '</h1>'
               . '<p>' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</p>';
        }

        // On arrête tout : rien d'autre ne doit s'afficher après l'erreur.
        exit;
    }
}
