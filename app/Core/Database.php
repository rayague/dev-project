<?php
/**
 * ============================================================
 *  CLASSE : Database (Connexion à la base de données)
 * ============================================================
 *
 *  RÔLE DANS LE MVC :
 *  Cette classe appartient à la couche "Modèle". Elle est la SEULE
 *  qui parle directement à MySQL. Les autres classes (contrôleurs,
 *  modèles) passent toujours par elle pour exécuter une requête.
 *
 *  PATTERN "SINGLETON" (un seul exemplaire) :
 *  On ne veut PAS recréer une connexion MySQL à chaque page.
 *  On crée donc une connexion unique, stockée dans $instance, et
 *  toutes les demandes réutilisent cette même connexion.
 * ============================================================
 */

// On empêche d'inclure ce fichier 2 fois par accident (sécurité).
// "declare(strict_types=1)" rend PHP plus strict sur les types.
declare(strict_types=1);

class Database
{
    /**
     * @var PDO|null  L'unique connexion à la base de données.
     *                "static" = la variable appartient à la CLASSE,
     *                pas à une instance : tout le monde la partage.
     *                Initialisée à null car il n'y a pas encore de connexion.
     */
    private static ?PDO $instance = null;

    /**
     * ------------------------------------------------------------
     * MÉTHODE : getInstance()
     * ------------------------------------------------------------
     * Retourne la connexion PDO. Si elle n'existe pas encore,
     * elle est créée une première fois, puis réutilisée ensuite.
     *
     * @return PDO  La connexion prête à être utilisée.
     */
    public static function getInstance(): PDO
    {
        // Si $instance est null, c'est qu'on n'a jamais connecté MySQL.
        if (self::$instance === null) {
            // On construit le "DSN" : c'est l'adresse de connexion.
            // Exemple final : mysql:host=localhost;port=3306;dbname=gestion_commandes;charset=utf8mb4
            $dsn = 'mysql:host=' . DB_HOST          // Serveur MySQL (localhost)
                 . ';port=' . DB_PORT               // Port d'écoute (3306)
                 . ';dbname=' . DB_NAME             // Base à utiliser
                 . ';charset=' . DB_CHARSET;        // Encodage des caractères

            // Tableau des options (réglages) de la connexion PDO.
            $options = [
                // MODE_EXCEPTION : en cas d'erreur SQL, PHP lance une
                // exception (une erreur attrapable) au lieu d'afficher
                // un simple message incompréhensible.
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,

                // MODE_FETCH_ASSOC : les résultats des requêtes seront
                // retournés sous forme de tableaux associatifs :
                // $ligne['id'], $ligne['client'], etc.
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,

                // Cette option force MySQL à respecter le vrai type des
                // colonnes (les nombres restent des nombres).
                PDO::ATTR_EMULATE_PREPARES => false,
            ];

            // --------------------------------------------------------
            // CRÉATION DE LA CONNEXION
            // --------------------------------------------------------
            // new PDO($dsn, utilisateur, mot de passe, options)
            // Si ça échoue (mauvais mot de passe, MySQL éteint...),
            // PDO lève une exception attrapée plus bas.
            try {
                self::$instance = new PDO($dsn, DB_USER, DB_PASS, $options);
            } catch (PDOException $e) {
                // ----------------------------------------------------
                // EN CAS D'ERREUR DE CONNEXION
                // ----------------------------------------------------
                // Si le mode debug est activé, on affiche le message
                // réel (très utile pour comprendre le problème).
                if (APP_DEBUG) {
                    die('Erreur de connexion MySQL : ' . $e->getMessage());
                }
                // Sinon, on affiche un message générique (plus sûr :
                // on ne révèle pas les détails techniques au visiteur).
                die('Impossible de se connecter à la base de données.');
            }
        }

        // On retourne la connexion (ancienne ou fraîchement créée).
        return self::$instance;
    }

    /**
     * ------------------------------------------------------------
     * MÉTHODE : reset() (utilisée pour les tests uniquement)
     * ------------------------------------------------------------
     * Ferme la connexion et remet le singleton à null.
     * Sert lors des tests automatisés pour "repartir à zéro".
     */
    public static function reset(): void
    {
        // On met l'instance à null (la prochaine appelée créera une nouvelle connexion).
        self::$instance = null;
    }
}
