<?php
/**
 * ============================================================
 *  ROUTEUR POUR LE SERVEUR INTÉGRÉ DE PHP (tests locaux)
 * ============================================================
 *
 *  UTILISATION :
 *      php -S 127.0.0.1:8000 -t public server.php
 *
 *  Pourquoi ce fichier ?
 *  Le serveur intégré de PHP ne lit PAS le .htaccess (c'est un
 *  fichier Apache). Ce script joue le même rôle :
 *   - Si la ressource demandée EST un fichier réel (image, css),
 *     on la renvoie telle quelle ("return false").
 *   - SINON, on inclut public/index.php (le front controller),
 *     qui lancera le routeur MVC.
 * ============================================================
 */

// Chemin absolu de la racine du projet.
$racine = __DIR__;

// URL demandée par le navigateur (sans le domaine).
$url = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Chemin physique du fichier correspondant dans le dossier public/.
$fichier = $racine . '/public' . $url;

// Si le fichier demandé existe RÉELLEMENT...
if ($url !== '/' && file_exists($fichier) && is_file($fichier)) {
    // ...on laisse le serveur PHP le servir directement (image, css...).
    return false;
}

// SINON : c'est une route MVC → on délègue au front controller.
require $racine . '/public/index.php';
