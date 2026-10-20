<?php
/**
 * ============================================================
 *  POINT D'ENTRÉE UNIQUE DU SITE (Front Controller)
 * ============================================================
 *
 *  CE FICHIER EST LE CERVEAU DE DÉMARRAGE.
 *  TOUTES les URLs du site atterrissent ici (grâce au .htaccess).
 *
 *  SON ORDRE D'EXÉCUTION :
 *   1. Charger la configuration (config/config.php)
 *   2. Charger les fonctions utilitaires (helpers)
 *   3. Déclarer l'auto-chargement des classes (autoload)
 *   4. Démarrer la session utilisateur
 *   5. Enregistrer toutes les routes (routes.php)
 *   6. Demander au routeur de traiter l'URL courante
 *
 *  AUCUNE logique métier ici : ce fichier ne fait qu'ORGANISER
 *  le démarrage. C'est la première règle du MVC.
 * ============================================================
 */

// ----------------------------------------------------------------
// ÉTAPE 1 : CHARGEMENT DE LA CONFIGURATION
// ----------------------------------------------------------------
// Ce fichier définit les constantes DB_HOST, DB_NAME, BASE_PATH...
require dirname(__DIR__) . '/config/config.php';

// ----------------------------------------------------------------
// ÉTAPE 2 : GESTION DES ERREURS PHP
// ----------------------------------------------------------------
// Si APP_DEBUG est true, on affiche toutes les erreurs (utile
// pendant le développement pour comprendre les bugs).
// Si APP_DEBUG est false, les erreurs sont cachées (production).
error_reporting(APP_DEBUG ? E_ALL : E_NONE); // E_ALL = toutes les erreurs
ini_set('display_errors', APP_DEBUG ? '1' : '0');

// On déclare que les pages produites sont du UTF-8 (accents français).
header('Content-Type: text/html; charset=utf-8');

// ----------------------------------------------------------------
// ÉTAPE 3 : FONCTIONS UTILITAIRES (e(), money(), statut_badge()...)
// ----------------------------------------------------------------
require BASE_PATH . '/app/Core/helpers.php';

// ----------------------------------------------------------------
// ÉTAPE 4 : AUTO-CHARGEMENT DES CLASSES (autoload)
// ----------------------------------------------------------------
// Plutôt que d'écrire des "require" partout, on déclare à PHP :
// « quand tu vois une classe inconnue, va chercher le fichier
//   correspondant dans ce dossier ».
spl_autoload_register(function (string $classe): void {

    // Liste des dossiers où chercher un fichier de classe.
    $dossiers = [
        BASE_PATH . '/app/Core',        // Database, Router, Controller, Auth...
        BASE_PATH . '/app/Controllers', // AuthController, OrderController...
        BASE_PATH . '/app/Models',      // User, Order
    ];

    // On teste chaque dossier jusqu'à trouver le bon fichier.
    foreach ($dossiers as $dossier) {
        // On construit le chemin complet : app/Models/User.php
        $fichier = $dossier . '/' . $classe . '.php';

        // Si le fichier existe réellement...
        if (file_exists($fichier)) {
            require $fichier; // ...on le charge (PHP découvre la classe)
            return;           // et on arrête la recherche
        }
    }
    // Si aucun fichier n'est trouvé, PHP affichera une erreur claire
    // du type "Class not found" (utile pour déboguer).
});

// ----------------------------------------------------------------
// ÉTAPE 5 : DÉMARRAGE DE LA SESSION UTILISATEUR
// ----------------------------------------------------------------
// La session permet de savoir QUI est connecté d'une page à l'autre.
Auth::start();

// ----------------------------------------------------------------
// ÉTAPE 6 : CRÉATION DU ROUTEUR ET CHARGEMENT DES ROUTES
// ----------------------------------------------------------------
// On crée une instance du routeur (la « chef de gare »).
$router = new Router();

// On inclut routes.php : c'est lui qui déclare toutes les pages du site.
// Il a besoin de la variable $router, d'où le "require" maintenant.
require BASE_PATH . '/routes.php';

// ----------------------------------------------------------------
// ÉTAPE 7 : DÉTERMINATION DE L'URL À TRAITER
// ----------------------------------------------------------------
// Deux cas de figure selon la façon dont on lance le site :
//
//   A) AVEC APACHE (.htaccess) : le fichier .htaccess a transmis
//      l'URL demandée dans le paramètre PHP __path.
//      Ex : /projet-web/orders/edit?id=3  →  __path = "orders/edit"
//      (c'est le paramètre $_GET['__path'] qui nous indique la route,
//      car Apache perd le sous-dossier lors de ses réécritures).
//
//   B) AVEC LE SERVEUR INTÉGRÉ DE PHP (php -S) : aucun .htaccess,
//      on lit directement $_SERVER['REQUEST_URI'].
if (isset($_GET['__path']) && is_string($_GET['__path'])) {
    // --- Cas A : on reconstitue le chemin ("/" . "orders/edit")
    $urlDemandee = '/' . trim($_GET['__path'], '/');

    // On garde aussi l'URL ORIGINALE (avec le sous-dossier Apache) :
    // "/projet-web/orders/edit?id=3" → "/projet-web/orders/edit".
    // Elle nous sert à déduire le préfixe "/projet-web" (voir plus bas).
    $urlOrigine = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
} else {
    // --- Cas B : URL complète demandée, ex "/orders/edit?id=3"
    $urlDemandee = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

    // Si le projet est dans un sous-dossier XAMPP
    // (http://localhost/projet-web/orders/edit), il faut retirer
    // le préfixe "/projet-web" sinon le routeur ne trouvera aucune
    // route. On le retrouve grâce à l'URL de index.php (SCRIPT_NAME).
    //   "/projet-web/public/index.php" → préfixe "/projet-web"
    //   "/index.php"                   → préfixe ""
    $dossierIndex  = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
    $prefixeBase   = rtrim(preg_replace('#/public$#', '', $dossierIndex), '/');

    if ($prefixeBase !== '') {
        if (strncmp($urlDemandee, $prefixeBase . '/', strlen($prefixeBase) + 1) === 0) {
            $urlDemandee = substr($urlDemandee, strlen($prefixeBase));
        } elseif ($urlDemandee === $prefixeBase) {
            $urlDemandee = '/';
        }
    }
}

// ----------------------------------------------------------------
// ÉTAPE 8 : CALCUL DU PRÉFIXE D'URL DU SITE (BASE_URL)
// ----------------------------------------------------------------
// BASE_URL sert à construire tous les liens et toutes les
// redirections du site :
//   - installé à la racine (php -S)      → BASE_URL = ""   (vidé)
//   - installé dans XAMPP                → BASE_URL = "/projet-web"
//
// Formule : préfixe = URL d'origine MINUS la route demandée.
//   "/projet-web/orders/edit" − "/orders/edit" = "/projet-web"
$prefixeURL = '';

if (isset($urlOrigine)) {
    // --- Cas A (Apache) : on soustrait la route de l'URL d'origine.
    if ($urlDemandee === '/') {
        // La racine du projet = tout le chemin (ex : "/projet-web")
        $prefixeURL = rtrim($urlOrigine, '/');
    } else {
        // ex : "/projet-web/orders" se termine par "/orders"
        $routePropre = rtrim($urlDemandee, '/');
        if (substr($urlOrigine, -strlen($routePropre)) === $routePropre) {
            // On garde uniquement la partie avant la route.
            $prefixeURL = rtrim(
                substr($urlOrigine, 0, strlen($urlOrigine) - strlen($routePropre)),
                '/'
            );
        }
    }
} else {
    // --- Cas B (php -S) : aucun sous-dossier, préfixe vide.
    $prefixeURL = '';
}

// On expose ce préfixe à tout le site (vues, contrôleurs...).
define('BASE_URL', $prefixeURL);

// Le routeur analyse l'URL, trouve le contrôleur, et lance la page.
$router->dispatch($urlDemandee);
