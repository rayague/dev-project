<?php
/**
 * ============================================================
 *  CONTRÔLEUR : UserController (CRUD des utilisateurs)
 * ============================================================
 *
 *  RÔLE DANS LE MVC :
 *  Gère les opérations du CRUD sur les UTILISATEURS :
 *   - index()   : lister les comptes (recherche + pagination)
 *   - create()  : afficher le formulaire de création
 *   - store()   : enregistrer le nouveau compte
 *   - show()    : afficher les détails d'un compte
 *   - edit()    : afficher le formulaire de modification
 *   - update()  : enregistrer la modification
 *   - destroy() : supprimer un compte
 *
 *  Le CRUD des COMMANDES reste le principal du projet ; celui-ci
 *  applique exactement les mêmes principes MVC.
 * ============================================================
 */

declare(strict_types=1);

class UserController extends Controller
{
    /**
     * ------------------------------------------------------------
     * LISTER LES UTILISATEURS  (URL : GET /users)
     * ------------------------------------------------------------
     */
    public function index(): void
    {
        // Gestion des comptes = réservée aux administrateurs (RBAC).
        Auth::requireAdmin();

        // 1) On lit les paramètres de recherche / pagination.
        $search = (string) $this->query('search', ''); // terme cherché
        $page   = max(1, (int) $this->query('page', 1)); // page courante

        // 2) On demande au modèle la page courante d'utilisateurs.
        $userModel = new User();
        $resultat  = $userModel->search($search, $page, 10);

        // 3) On affiche la vue avec la liste.
        $this->render('users/index', [
            'users'  => $resultat['items'], // utilisateurs de la page
            'total'  => $resultat['total'], // total (filtre inclus)
            'pages'  => $resultat['pages'], // nombre de pages
            'page'   => $resultat['page'],  // page courante
            'search' => $search,            // pour réafficher la recherche
            'title'  => 'Gestion des utilisateurs',
        ]);
    }

    /**
     * ------------------------------------------------------------
     * AFFICHER LE FORMULAIRE DE CRÉATION  (URL : GET /users/create)
     * ------------------------------------------------------------
     */
    public function create(): void
    {
        // Création de comptes = réservée aux administrateurs (RBAC).
        Auth::requireAdmin();

        // Formulaire vierge (aucune ancienne valeur).
        $this->render('users/create', [
            'old'   => [],
            'title' => 'Nouvel utilisateur',
        ]);
    }

    /**
     * ------------------------------------------------------------
     * ENREGISTRER UN NOUVEL UTILISATEUR  (URL : POST /users)
     * ------------------------------------------------------------
     */
    public function store(): void
    {
        // Création de comptes = réservée aux administrateurs (RBAC).
        Auth::requireAdmin();

        // Uniquement une soumission de formulaire.
        if (!$this->isPost()) {
            $this->redirect('/users/create');
            return;
        }

        // Sécurité : jeton CSRF.
        if (!$this->csrfCheck()) {
            $this->errors['_global'][] = 'Formulaire expiré, veuillez réessayer.';
        }

        // 1) Lecture des champs.
        $name     = $this->input('name');
        $email    = $this->input('email');
        $password = $this->input('password');
        // Rôle choisi dans le formulaire (réservé aux admins :
        // cette action est déjà protégée par Auth::requireAdmin()).
        $role     = $this->input('role', 'user') === 'admin' ? 'admin' : 'user';

        // 2) Validation des données.
        $v = new Validator();
        $v->check('name', $name, ['required', 'min:2', 'max:100'], 'Le nom');
        $v->check('email', $email, ['required', 'email', 'max:190'], 'L\'adresse e-mail');
        $v->check('password', $password, ['required', 'min:6'], 'Le mot de passe');

        // 3) Vérification que l'e-mail n'est pas déjà pris.
        $userModel = new User();
        if ($email !== '' && $userModel->emailExists($email)) {
            $this->errors['email'] = ['Cette adresse e-mail est déjà utilisée.'];
        }

        // 4) En cas d'erreur → formulaire rempli + messages.
        if ($v->fails() || $this->errors !== []) {
            $this->errors = array_merge_recursive($this->errors, $v->errors());
            $this->render('users/create', [
                'old'   => ['name' => $name, 'email' => $email],
                'title' => 'Nouvel utilisateur',
            ]);
            return;
        }

        // 5) Création du compte (le modèle hache le mot de passe).
        $userModel->register($name, $email, $password, $role);

        // 6) Succès + redirection vers la liste.
        $this->flash('success', 'L\'utilisateur « ' . $name . ' » a été créé.');
        $this->redirect('/users');
    }

    /**
     * ------------------------------------------------------------
     * AFFICHER LES DÉTAILS D'UN UTILISATEUR  (URL : GET /users/show?id=2)
     * ------------------------------------------------------------
     */
    public function show(): void
    {
        Auth::requireLogin();

        // Charge l'utilisateur (ou 404).
        $user = $this->findOr404();

        // RBAC : un utilisateur simple ne voit QUE son propre profil.
        $this->authorizeSelfOrAdmin($user);

        // On compte aussi ses commandes (info utile sur la fiche).
        $orderModel = new Order();
        $nbOrders   = $orderModel->count('user_id = :uid', [':uid' => (int) $user['id']]);

        $this->render('users/show', [
            'user'     => $user,
            'nbOrders' => $nbOrders, // nombre de commandes créées par ce compte
            'title'    => $user['name'],
        ]);
    }

    /**
     * ------------------------------------------------------------
     * AFFICHER LE FORMULAIRE DE MODIFICATION  (URL : GET /users/edit?id=2)
     * ------------------------------------------------------------
     */
    public function edit(): void
    {
        Auth::requireLogin();

        $user = $this->findOr404();

        // RBAC : un utilisateur simple ne modifie QUE son propre profil.
        $this->authorizeSelfOrAdmin($user);

        // Le mot de passe n'est PAS pré-rempli (on ne réaffiche jamais
        // un mot de passe, même haché). Champ vide = « inchangé ».
        $this->render('users/edit', [
            'user'  => $user,
            'title' => 'Modifier ' . $user['name'],
        ]);
    }

    /**
     * ------------------------------------------------------------
     * ENREGISTRER LA MODIFICATION D'UN UTILISATEUR  (URL : POST /users/update?id=2)
     * ------------------------------------------------------------
     */
    public function update(): void
    {
        Auth::requireLogin();

        if (!$this->isPost()) {
            $this->redirect('/users');
            return;
        }

        // On charge l'utilisateur à modifier (ou 404).
        $user = $this->findOr404();

        // RBAC : un utilisateur simple ne modifie QUE son propre profil.
        $this->authorizeSelfOrAdmin($user);

        if (!$this->csrfCheck()) {
            $this->errors['_global'][] = 'Formulaire expiré, veuillez réessayer.';
        }

        // 1) Lecture des champs.
        $name     = $this->input('name');
        $email    = $this->input('email');
        $password = $this->input('password'); // "" = mot de passe inchangé
        // Rôle : modifiable UNIQUEMENT par un administrateur. Pour un
        // utilisateur simple qui modifie son propre profil, $role vaut
        // null → le rôle en base reste inchangé (aucune élévation de privilège).
        // Champ absent/vide → rôle inchangé (sécurité).
        $role = null;
        if (Auth::isAdmin()) {
            $roleInput = $this->input('role', '');
            if ($roleInput !== '') {
                $role = $roleInput === 'admin' ? 'admin' : 'user';
            }
        }

        // 2) Validation.
        $v = new Validator();
        $v->check('name', $name, ['required', 'min:2', 'max:100'], 'Le nom');
        $v->check('email', $email, ['required', 'email', 'max:190'], 'L\'adresse e-mail');

        // Si un mot de passe est saisi, il doit être assez long.
        if ($password !== '') {
            $v->check('password', $password, ['min:6'], 'Le mot de passe');
        }

        // 3) L'e-mail doit rester unique (en excluant SOI-MÊME).
        $userModel = new User();
        if ($email !== '' && $userModel->emailExists($email, (int) $user['id'])) {
            $this->errors['email'] = ['Cette adresse e-mail est déjà utilisée.'];
        }

        // 4) En cas d'erreur → réaffichage du formulaire.
        if ($v->fails() || $this->errors !== []) {
            $this->errors = array_merge_recursive($this->errors, $v->errors());
            $user['name']  = $name;  // on garde les valeurs saisies
            $user['email'] = $email;
            $this->render('users/edit', ['user' => $user, 'title' => 'Modifier ' . $name]);
            return;
        }

        // 5) Mise à jour (le modèle hache le mot de passe seulement s'il est fourni).
        $userModel->updateProfile((int) $user['id'], $name, $email, $password, $role);

        // Si l'on vient de modifier SON PROPRE compte, on rafraîchit les
        // informations en session (le nom et surtout le rôle peuvent changer).
        if (Auth::id() === (int) $user['id']) {
            $fresh = $userModel->findById((int) $user['id']);
            if ($fresh !== null) {
                $_SESSION['user_name']  = $fresh['name'];
                $_SESSION['user_email'] = $fresh['email'];
                $_SESSION['user_role']  = ($fresh['role'] ?? 'user') === 'admin' ? 'admin' : 'user';
            }
        }

        // 6) Succès + redirection.
        $this->flash('success', 'L\'utilisateur « ' . $name . ' » a été modifié.');
        $this->redirect('/users');
    }

    /**
     * ------------------------------------------------------------
     * SUPPRIMER UN UTILISATEUR  (URL : POST /users/delete?id=2)
     * ------------------------------------------------------------
     */
    public function destroy(): void
    {
        // Suppression de comptes = réservée aux administrateurs (RBAC).
        Auth::requireAdmin();

        if (!$this->isPost()) {
            $this->redirect('/users');
            return;
        }

        if (!$this->csrfCheck()) {
            $this->flash('error', 'Formulaire expiré, suppression refusée.');
            $this->redirect('/users');
            return;
        }

        $user = $this->findOr404();

        // ------------------------------------------------
        // RÈGLE MÉTIER : ON NE PEUT PAS SUPPRIMER SON PROPRE COMPTE
        // ------------------------------------------------
        // Sinon, on se supprimerait soi-même en plein milieu du site.
        if ((int) $user['id'] === Auth::id()) {
            $this->flash('error', 'Vous ne pouvez pas supprimer votre propre compte.');
            $this->redirect('/users');
            return;
        }

        // Suppression en base (les commandes liées disparaissent aussi,
        // grâce à "ON DELETE CASCADE" défini dans schema.sql).
        $userModel = new User();
        $userModel->delete((int) $user['id']);

        $this->flash('success', 'L\'utilisateur « ' . $user['name'] . ' » a été supprimé.');
        $this->redirect('/users');
    }

    /**
     * ------------------------------------------------------------
     * OUTIL PRIVÉ : AUTORISER UN ADMIN OU LE PROPRIÉTAIRE DU COMPTE
     * ------------------------------------------------------------
     * Contrôle d'accès (RBAC) pour les actions show/edit/update :
     *  - un administrateur peut agir sur n'importe quel compte ;
     *  - un utilisateur simple ne peut agir que sur SON compte.
     * Sinon → page 403 (Accès interdit).
     *
     * @param array $user La ligne de l'utilisateur visé
     */
    private function authorizeSelfOrAdmin(array $user): void
    {
        // Cas 1 : administrateur → accès total.
        if (Auth::isAdmin()) {
            return;
        }

        // Cas 2 : utilisateur simple → uniquement son propre compte.
        if (Auth::id() !== null && Auth::id() === (int) $user['id']) {
            return;
        }

        // Cas 3 : tout le reste → refusé.
        $router = new Router();
        $router->renderError(403, 'Vous ne pouvez consulter ou modifier que votre propre compte.');
    }

    /**
     * ------------------------------------------------------------
     * OUTIL PRIVÉ : CHARGER UN UTILISATEUR DEPUIS L'URL (ou 404)
     * ------------------------------------------------------------
     * @return array La ligne complète de l'utilisateur
     */
    private function findOr404(): array
    {
        // 1) ON LIT L'IDENTIFIANT.
        //    Priorité au champ caché du formulaire ($_POST['id}),
        //    sinon on retombe sur le paramètre d'URL (?id=2).
        $id = (int) ($_POST['id'] ?? $this->query('id', 0));

        // Identifiant absent/incorrect → page 404.
        if ($id <= 0) {
            $router = new Router();
            $router->renderError(404, 'Identifiant d\'utilisateur manquant ou invalide.');
        }

        // Recherche en base.
        $userModel = new User();
        $user      = $userModel->findById($id);

        // Inexistant → page 404.
        if ($user === null) {
            $router = new Router();
            $router->renderError(404, 'Utilisateur introuvable (id = ' . $id . ').');
        }

        return $user;
    }
}
