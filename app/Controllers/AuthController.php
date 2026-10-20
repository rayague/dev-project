<?php
/**
 * ============================================================
 *  CONTRÔLEUR : AuthController (Inscription / Connexion / Déconnexion)
 * ============================================================
 *
 *  RÔLE DANS LE MVC :
 *  Ce contrôleur fait le lien entre les FORMULAIRES d'authentification
 *  (les vues login.php et register.php) et le MODÈLE User (la base).
 *
 *  Il ne contient AUCUNE balise HTML et AUCUNE requête SQL :
 *  - Il reçoit les données du formulaire   ($_POST)
 *  - Il les valide                        (Validator)
 *  - Il demande au modèle de travailler   (User / Auth)
 *  - Il choisit la vue à afficher         (render) ou redirige
 * ============================================================
 */

declare(strict_types=1);

class AuthController extends Controller
{
    /**
     * ------------------------------------------------------------
     * AFFICHER LE FORMULAIRE D'INSCRIPTION  (URL : GET /register)
     * ------------------------------------------------------------
     */
    public function showRegister(): void
    {
        // Si l'utilisateur est DÉJÀ connecté, inutile de s'inscrire :
        // on le renvoie directement vers le tableau de bord.
        if (Auth::check()) {
            $this->redirect('/dashboard');
            return; // on quitte la méthode (le redirect a déjà quitté, c'est par sécurité)
        }

        // On affiche la vue "app/View/auth/register.php".
        // Le 2ème paramètre fournit un tableau vide de données
        // à réinjecter dans le formulaire en cas d'erreur de validation.
        $this->render('auth/register', [
            'old' => [], // "old" = anciennes valeurs saisies (vide ici)
        ]);
    }

    /**
     * ------------------------------------------------------------
     * TRAITEMENT DE L'INSCRIPTION  (URL : POST /register)
     * ------------------------------------------------------------
     * C'est cette méthode qui reçoit le formulaire envoyé.
     */
    public function register(): void
    {
        // Si ce n'est PAS un envoi de formulaire (accès direct en GET),
        // on renvoie vers le formulaire.
        if (!$this->isPost()) {
            $this->redirect('/register');
            return;
        }

        // ------------------------------------------------
        // ÉTAPE 1 : VÉRIFICATION DU JETON CSRF (sécurité)
        // ------------------------------------------------
        // Le jeton prouve que la requête vient bien de NOTRE formulaire.
        if (!$this->csrfCheck()) {
            // Si le jeton est absent/faux : on bloque la demande.
            $this->errors['_global'][] = 'Session expirée ou requête invalide. Veuillez réessayer.';
        }

        // ------------------------------------------------
        // ÉTAPE 2 : RÉCUPÉRATION DES DONNÉES DU FORMULAIRE
        // ------------------------------------------------
        $name     = $this->input('name');      // Nom complet saisi
        $email    = $this->input('email');     // E-mail saisi
        $password = $this->input('password');  // Mot de passe saisi
        $confirm  = $this->input('password_confirmation'); // Confirmation

        // ------------------------------------------------
        // ÉTAPE 3 : VALIDATION DES CHAMPS (Validator)
        // ------------------------------------------------
        $v = new Validator(); // Création du validateur

        // Règles pour le nom : obligatoire, entre 2 et 100 caractères.
        $v->check('name', $name, ['required', 'min:2', 'max:100'], 'Le nom');

        // Règles pour l'e-mail : obligatoire + forme valide + max 190.
        $v->check('email', $email, ['required', 'email', 'max:190'], 'L\'adresse e-mail');

        // Règles pour le mot de passe : obligatoire + 6 caractères minimum.
        $v->check('password', $password, ['required', 'min:6'], 'Le mot de passe');

        // Vérification que les 2 mots de passe sont identiques.
        if ($password !== $confirm) {
            // On ajoute une erreur manuelle (pas de règle intégrée).
            $this->errors['password_confirmation'] = ['Les deux mots de passe ne correspondent pas.'];
        }

        // ------------------------------------------------
        // ÉTAPE 4 : UNICITÉ DE L'E-MAIL
        // ------------------------------------------------
        // On crée le modèle User pour interroger la base.
        $userModel = new User();

        // Est-ce que cet e-mail est déjà utilisé par un autre compte ?
        if ($email !== '' && $userModel->emailExists($email)) {
            $this->errors['email'] = ['Cette adresse e-mail est déjà utilisée par un autre compte.'];
        }

        // ------------------------------------------------
        // ÉTAPE 5 : S'IL Y A DES ERREURS, ON RE-AFFICHE LE FORMULAIRE
        // ------------------------------------------------
        if ($v->fails() || $this->errors !== []) {
            // On fusionne les erreurs du validateur + les erreurs manuelles.
            $this->errors = array_merge_recursive($this->errors, $v->errors());

            // On réaffiche la vue en renvoyant les valeurs déjà saisies
            // ("old") pour que l'utilisateur n'ait pas tout à retaper.
            $this->render('auth/register', [
                'old' => [
                    'name'  => $name,
                    'email' => $email,
                ],
            ]);
            return; // on arrête : l'inscription n'a PAS eu lieu
        }

        // ------------------------------------------------
        // ÉTAPE 6 : CRÉATION RÉELLE DU COMPTE
        // ------------------------------------------------
        // Le modèle hache le mot de passe et fait l'INSERT en base.
        $nouvelId = $userModel->register($name, $email, $password);

        // ------------------------------------------------
        // ÉTAPE 7 : CONNEXION AUTOMATIQUE + REDIRECTION
        // ------------------------------------------------
        // On connecte immédiatement le nouvel inscrit (confort).
        Auth::attempt($email, $password);

        // Message de succès stocké en session (affiché après le redirect).
        $this->flash('success', 'Bienvenue ' . $name . ' ! Votre compte a été créé.');

        // On l'envoie vers le tableau de bord.
        $this->redirect('/dashboard');
    }

    /**
     * ------------------------------------------------------------
     * AFFICHER LE FORMULAIRE DE CONNEXION  (URL : GET /login)
     * ------------------------------------------------------------
     */
    public function showLogin(): void
    {
        // Déjà connecté ? On n'a pas besoin de la page de connexion.
        if (Auth::check()) {
            $this->redirect('/dashboard');
            return;
        }

        // Affichage de la vue app/View/auth/login.php.
        $this->render('auth/login', ['old' => []]);
    }

    /**
     * ------------------------------------------------------------
     * TRAITEMENT DE LA CONNEXION  (URL : POST /login)
     * ------------------------------------------------------------
     */
    public function login(): void
    {
        // Si la requête n'est pas un POST, on retourne au formulaire.
        if (!$this->isPost()) {
            $this->redirect('/login');
            return;
        }

        // ------------------------------------------------
        // ÉTAPE 1 : JETON CSRF (sécurité anti-falsification)
        // ------------------------------------------------
        // SI LE JETON EST FAUX, ON ARRÊTE TOUT : on ne tente même
        // pas de vérifier les identifiants, et on réaffiche le
        // formulaire avec un message d'erreur.
        if (!$this->csrfCheck()) {
            $this->errors['_global'][] = 'Session expirée ou requête invalide. Veuillez réessayer.';
            $this->render('auth/login', ['old' => ['email' => $this->input('email')]]);
            return; // ON ARRÊTE ICI : connexion refusée
        }

        // ------------------------------------------------
        // ÉTAPE 2 : LECTURE DES CHAMPS
        // ------------------------------------------------
        $email    = $this->input('email');
        $password = $this->input('password');

        // ------------------------------------------------
        // ÉTAPE 3 : VALIDATION MINIMALE
        // ------------------------------------------------
        $v = new Validator();
        $v->check('email', $email, ['required', 'email'], 'L\'adresse e-mail');
        $v->check('password', $password, ['required'], 'Le mot de passe');

        if ($v->fails()) {
            // On réaffiche le formulaire avec les erreurs.
            $this->errors = $v->errors();
            $this->render('auth/login', ['old' => ['email' => $email]]);
            return;
        }

        // ------------------------------------------------
        // ÉTAPE 4 : VÉRIFICATION IDENTIFIANTS
        // ------------------------------------------------
        // Auth::attempt compare l'e-mail et le mot de passe haché.
        if (!Auth::attempt($email, $password)) {
            // Échec : on affiche un message volontairement GÉNÉRIQUE
            // (on ne dit pas si l'e-mail existe, pour ne pas aider
            // les attaquants à deviner les comptes).
            $this->errors['_global'][] = 'Adresse e-mail ou mot de passe incorrect.';

            $this->render('auth/login', ['old' => ['email' => $email]]);
            return;
        }

        // ------------------------------------------------
        // ÉTAPE 5 : CONNEXION RÉUSSIE
        // ------------------------------------------------
        $this->flash('success', 'Bonjour ' . Auth::name() . ', content de vous revoir !');

        // Redirection vers la page demandée avant connexion, ou le dashboard.
        $destination = $_SESSION['after_login'] ?? '/dashboard';
        unset($_SESSION['after_login']); // on oublie la destination

        $this->redirect($destination);
    }

    /**
     * ------------------------------------------------------------
     * DÉCONNEXION  (URL : POST /logout)
     * ------------------------------------------------------------
     */
    public function logout(): void
    {
        // On ne déconnecte que si c'est bien un formulaire envoyé.
        if ($this->isPost() && $this->csrfCheck()) {
            Auth::logout(); // destruction de la session
        }

        // On renvoie vers la page de connexion (après déconnexion).
        $this->redirect('/login');
    }
}
