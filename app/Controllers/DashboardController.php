<?php
/**
 * ============================================================
 *  CONTRÔLEUR : DashboardController (Tableau de bord)
 * ============================================================
 *
 *  RÔLE DANS LE MVC :
 *  Affiche la page d'accueil APRÈS connexion (le "dashboard").
 *  Il calcule les statistiques via le modèle Order, puis transmet
 *  les chiffres à la vue qui se charge de les dessiner en HTML/Tailwind.
 *
 *  Ce contrôleur ne fait AUCUNE requête SQL directement : il fait
 *  confiance au modèle pour lui fournir les données.
 * ============================================================
 */

declare(strict_types=1);

class DashboardController extends Controller
{
    /**
     * ------------------------------------------------------------
     * AFFICHER LE TABLEAU DE BORD  (URL : GET /  ou  GET /dashboard)
     * ------------------------------------------------------------
     */
    public function index(): void
    {
        // ------------------------------------------------
        // ÉTAPE 1 : CONTRÔLE D'ACCÈS (page privée)
        // ------------------------------------------------
        // Si personne n'est connecté, Auth::requireLogin()
        // redirige immédiatement vers /login.
        Auth::requireLogin();

        // ------------------------------------------------
        // ÉTAPE 2 : RÉCUPÉRATION DES DONNÉES AUPRÈS DU MODÈLE
        // ------------------------------------------------
        // On crée une instance du modèle "Order" (la table des commandes).
        $orderModel = new Order();

        // $stats contient : nombre de commandes, montant total,
        // répartition par statut, et les 4 dernières commandes.
        $stats = $orderModel->stats();

        // On compte aussi les utilisateurs inscrits (affiché en carte).
        $nbUsers = $orderModel->countAllUsers();

        // ------------------------------------------------
        // ÉTAPE 3 : AFFICHAGE DE LA VUE
        // ------------------------------------------------
        // On transmet les chiffres à la vue dashboard/index.php
        // qui les affichera dans des « cartes » Tailwind CSS.
        $this->render('dashboard/index', [
            'stats'   => $stats,   // statistiques des commandes
            'nbUsers' => $nbUsers, // nombre d'utilisateurs
            'title'   => 'Tableau de bord',
        ]);
    }
}
