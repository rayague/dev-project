<?php
/**
 * ============================================================
 *  FICHIER DES ROUTES (tableau des adresses du site)
 * ============================================================
 *
 *  Ce fichier liste TOUTES les pages du site sous la forme :
 *      méthode HTTP + URL + contrôleur + méthode
 *
 *  C'est la « carte » du site : pour ajouter une page, on ajoute
 *  une ligne ici, puis on crée la méthode correspondante dans le
 *  contrôleur concerné.
 *
 *  Méthodes HTTP :
 *   - GET  = le navigateur AFFICHE une page (clic sur un lien)
 *   - POST = le navigateur ENVOIE un formulaire (bouton "Valider")
 * ============================================================
 */

declare(strict_types=1);

// On récupère l'objet routeur transmis par public/index.php.
/** @var Router $router */

// ----------------------------------------------------------------
// PAGES PUBLIQUES (accessibles SANS être connecté)
// ----------------------------------------------------------------

// Page d'accueil → redirige vers le tableau de bord ou la connexion.
$router->get('/', 'DashboardController', 'index');

// Page d'inscription : formulaire (GET) + envoi du formulaire (POST).
$router->get('/register', 'AuthController', 'showRegister'); // Affiche le formulaire
$router->post('/register', 'AuthController', 'register');    // Traite l'inscription

// Page de connexion : formulaire (GET) + envoi du formulaire (POST).
$router->get('/login', 'AuthController', 'showLogin'); // Affiche le formulaire
$router->post('/login', 'AuthController', 'login');    // Traite la connexion

// Déconnexion : on utilise POST pour éviter qu'un lien malveillant
// ne déconnecte l'utilisateur sans qu'il le veuille.
$router->post('/logout', 'AuthController', 'logout');

// ----------------------------------------------------------------
// TABLEAU DE BORD (dashboard) — nécessite d'être connecté
// ----------------------------------------------------------------
$router->get('/dashboard', 'DashboardController', 'index');

// ----------------------------------------------------------------
// CRUD DES COMMANDES  ←  PRINCIPAL DU PROJET
// ----------------------------------------------------------------

// LISTER les commandes (avec recherche + filtres + pagination).
$router->get('/orders', 'OrderController', 'index');

// AFFICHER le formulaire de création (page vierge).
$router->get('/orders/create', 'OrderController', 'create');

// TRAITEMENT de la création (envoi du formulaire).
$router->post('/orders', 'OrderController', 'store');

// AFFICHER les détails d'une commande, ex : /orders/3
$router->get('/orders/show', 'OrderController', 'show');

// AFFICHER le formulaire de modification pré-rempli, ex : /orders/edit?id=3
$router->get('/orders/edit', 'OrderController', 'edit');

// TRAITEMENT de la modification (envoi du formulaire modifié).
$router->post('/orders/update', 'OrderController', 'update');

// SUPPRESSION d'une commande, ex : /orders/delete?id=3
$router->post('/orders/delete', 'OrderController', 'destroy');

// ----------------------------------------------------------------
// CRUD DES UTILISATEURS
// ----------------------------------------------------------------

// LISTER les utilisateurs.
$router->get('/users', 'UserController', 'index');

// Formulaire de création d'un utilisateur.
$router->get('/users/create', 'UserController', 'create');
$router->post('/users', 'UserController', 'store');       // Traitement création

// Détails d'un utilisateur, ex : /users/show?id=3
$router->get('/users/show', 'UserController', 'show');

// Formulaire de modification, ex : /users/edit?id=3
$router->get('/users/edit', 'UserController', 'edit');
$router->post('/users/update', 'UserController', 'update'); // Traitement modification

// Suppression d'un utilisateur.
$router->post('/users/delete', 'UserController', 'destroy');
