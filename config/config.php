<?php
/**
 * ============================================================
 *  FICHIER DE CONFIGURATION DU PROJET
 * ============================================================
 *
 *  Ce fichier contient toutes les « réglages » globaux du site :
 *  - Les informations de connexion à la base de données MySQL
 *  - Le nom du site
 *  - Le chemin des dossiers importants
 *
 *  IMPORTANT : aucune page HTML n'inclut ce fichier directement.
 *  C'est le fichier public/index.php (le point d'entrée) qui
 *  le charge au tout début, avant de lancer le routeur.
 * ============================================================
 */

// ----------------------------------------------------------------
// On déclare que c'est du PHP (obligatoire pour un fichier .php)
// ----------------------------------------------------------------

// ----------------------------------------------------------------
// RÉGLAGES DE LA BASE DE DONNÉES (MySQL / XAMPP)
// ----------------------------------------------------------------
// Nom du serveur MySQL : "localhost" car MySQL tourne sur la
// même machine que le site (c'est le cas avec XAMPP).
define('DB_HOST', 'localhost');

// Nom du port MySQL : 3306 est la valeur par défaut de MySQL.
define('DB_PORT', '3306');

// Nom de la base de données : c'est la base qu'on a créée
// avec le fichier database/schema.sql.
define('DB_NAME', 'gestion_commandes');

// Utilisateur MySQL : "root" est l'utilisateur par défaut de
// XAMPP, il n'a aucun mot de passe (champ vide ci-dessous).
define('DB_USER', 'root');

// Mot de passe de l'utilisateur MySQL : vide sur XAMPP.
define('DB_PASS', '');

// Nom du jeu de caractères : "utf8mb4" permet d'afficher
// accents et emojis sans problème.
define('DB_CHARSET', 'utf8mb4');

// ----------------------------------------------------------------
// RÉGLAGES GÉNÉRAUX DU SITE
// ----------------------------------------------------------------

// Nom affiché dans le titre des pages et dans la barre de navigation.
define('APP_NAME', 'GestCommandes');

// ----------------------------------------------------------------
// MODE DÉVELOPPEMENT (utile pendant les tests)
// ----------------------------------------------------------------

// APP_DEBUG à "true" : affiche les erreurs PHP détaillées à l'écran.
// Mettre "false" avant un vrai déploiement (pour la sécurité).
define('APP_DEBUG', true);

// ----------------------------------------------------------------
// CONVERSION DES CHEMINS DE FICHIERS (détection du dossier projet)
// ----------------------------------------------------------------

// __DIR__ = le dossier dans lequel on se trouve actuellement (config/)
// dirname(__DIR__) = le dossier parent, c'est-à-dire la racine du projet.
define('BASE_PATH', dirname(__DIR__));

// Chemin absolu du dossier contenant les vues (les pages HTML).
define('VIEWS_PATH', BASE_PATH . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'View');
