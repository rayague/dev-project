<?php
/**
 * ============================================================
 *  CONTRÔLEUR : OrderController  ★ CRUD PRINCIPAL DU PROJET ★
 * ============================================================
 *
 *  RÔLE DANS LE MVC :
 *  Ce contrôleur gère les 5 opérations du CRUD sur les COMMANDES :
 *
 *   ┌────────────┬──────────────┬────────────────────────────┐
 *   │ Opération  │  Méthode     │  Rôle                      │
 *   ├────────────┼──────────────┼────────────────────────────┤
 *   │ Lister     │  index()     │  affiche la liste + filtres │
 *   │ Créer      │  create()    │  affiche le formulaire vierge│
 *   │            │  store()     │  enregistre la commande     │
 *   │ Consulter  │  show()      │  affiche les détails        │
 *   │ Modifier   │  edit()      │  affiche le formulaire rempli│
 *   │            │  update()    │  enregistre les changements │
 *   │ Supprimer  │  destroy()   │  supprime la commande       │
 *   └────────────┴──────────────┴────────────────────────────┘
 *
 *  Il ne contient AUCUNE balise HTML et AUCUNE requête SQL brute :
 *  il reçoit les données → il les valide → il demande au modèle
 *  de travailler → il choisit la vue à afficher.
 * ============================================================
 */

declare(strict_types=1);

class OrderController extends Controller
{
    /**
     * ------------------------------------------------------------
     * LISTER LES COMMANDES  (URL : GET /orders)
     * ------------------------------------------------------------
     * Cette page est le COEUR du CRUD : elle permet de retrouver
     * rapidement une commande (recherche + filtre) pour la consulter,
     * la modifier ou la supprimer.
     */
    public function index(): void
    {
        // 1) ON EXIGE D'ÊTRE CONNECTÉ (sinon → redirection /login).
        Auth::requireLogin();

        // 2) ON LIT LES FILTRES DEMANDÉS DANS L'URL (query string).
        //    Exemple : /orders?search=Marie&statut=validee&page=2
        $search = (string) $this->query('search', ''); // terme recherché
        $statut = (string) $this->query('statut', ''); // statut filtré
        $page   = max(1, (int) $this->query('page', 1)); // page (jamais < 1)

        // 3) ON DEMANDE AU MODÈLE LES DONNÉES DE LA PAGE COURANTE.
        $orderModel = new Order();
        $resultat   = $orderModel->search($search, $statut, $page, 10);

        // 4) ON AFFICHE LA VUE avec la liste + les filtres en cours.
        $this->render('orders/index', [
            'orders' => $resultat['items'], // les commandes de cette page
            'total'  => $resultat['total'], // nombre total (filtres inclus)
            'pages'  => $resultat['pages'], // nombre total de pages
            'page'   => $resultat['page'],  // page courante
            'search' => $search,            // pour réafficher la recherche
            'statut' => $statut,            // pour réafficher le filtre
            'title'  => 'Gestion des commandes',
        ]);
    }

    /**
     * ------------------------------------------------------------
     * AFFICHER LE FORMULAIRE DE CRÉATION  (URL : GET /orders/create)
     * ------------------------------------------------------------
     */
    public function create(): void
    {
        // Contrôle d'accès : connexion obligatoire.
        Auth::requireLogin();

        // On affiche le formulaire vierge.
        // "old" = valeurs déjà saisies (vide ici, un formulaire neuf).
        $this->render('orders/create', [
            'old'     => [],
            'statuts' => Order::statuts(), // liste des statuts pour la liste déroulante
            'title'   => 'Nouvelle commande',
        ]);
    }

    /**
     * ------------------------------------------------------------
     * ENREGISTRER UNE NOUVELLE COMMANDE  (URL : POST /orders)
     * ------------------------------------------------------------
     * Méthode appelée quand on clique sur "Enregistrer".
     */
    public function store(): void
    {
        // 1) Connexion obligatoire.
        Auth::requireLogin();

        // 2) Sécurité : seules les soumissions de formulaire sont acceptées.
        if (!$this->isPost()) {
            $this->redirect('/orders/create');
            return;
        }

        // 3) SÉCURITÉ : vérification du jeton CSRF (anti-falsification).
        //    SI LE JETON EST FAUX, ON ARRÊTE TOUT : la commande ne sera
        //    surtout pas enregistrée. Sans ce "return", un formulaire
        //    envoyé depuis un autre site pourrait créer une commande.
        if (!$this->csrfCheck()) {
            $this->errors['_global'][] = 'Formulaire expiré ou invalide, veuillez réessayer.';

            // On réaffiche le formulaire avec l'erreur.
            $this->render('orders/create', [
                'old' => [
                    'client'        => $this->input('client'),
                    'email'         => $this->input('email'),
                    'date_commande' => $this->input('date_commande'),
                    'montant'       => $this->input('montant'),
                    'statut'        => $this->input('statut'),
                    'description'   => $this->input('description'),
                ],
                'statuts' => Order::statuts(),
                'title'   => 'Nouvelle commande',
            ]);
            return; // ON ARRÊTE ICI : rien n'est enregistré
        }

        // 4) ON RÉCUPÈRE TOUTES LES VALEURS SAISIES.
        //    La référence n'est PAS saisie : elle est générée par le modèle.
        $client        = $this->input('client');         // Nom du client (obligatoire)
        $email         = $this->input('email');          // E-mail client (optionnel)
        $dateCommande  = $this->input('date_commande');  // Date (AAAA-MM-JJ)
        $montant       = $this->input('montant');        // Montant en euros
        $statut        = $this->input('statut');         // État de la commande
        $description   = $this->input('description');    // Remarques libres

        // 5) ON VALIDE CHAQUE CHAMP (Validator).
        $v = new Validator();

        // Le client est obligatoire et doit contenir au moins 2 caractères.
        $v->check('client', $client, ['required', 'min:2', 'max:150'], 'Le client');

        // L'e-mail est facultatif : s'il est rempli, il doit être valide.
        $v->check('email', $email, ['email', 'max:190'], 'L\'e-mail du client');

        // La date est obligatoire et doit être au format AAAA-MM-JJ.
        $v->check('date_commande', $dateCommande, ['required', 'date'], 'La date de commande');

        // Le montant doit être un nombre supérieur à 0.
        $v->check('montant', $montant, ['required', 'positive'], 'Le montant');

        // Le statut doit être impérativement l'une des 4 valeurs autorisées.
        $v->check('statut', $statut, ['required', 'in:en_attente,validee,livree,annulee'], 'Le statut');

        // La description est facultative, 2000 caractères maximum.
        $v->check('description', $description, ['max:2000'], 'La description');

        // 6) EN CAS D'ERREUR : on réaffiche le formulaire avec les messages.
        if ($v->fails()) {
            // On sauvegarde les erreurs du validateur.
            $this->errors = $v->errors();

            // On renvoie les valeurs déjà saisies pour ne pas tout retaper.
            $this->render('orders/create', [
                'old' => [
                    'client'       => $client,
                    'email'        => $email,
                    'date_commande' => $dateCommande,
                    'montant'      => $montant,
                    'statut'       => $statut,
                    'description'  => $description,
                ],
                'statuts' => Order::statuts(),
                'title'   => 'Nouvelle commande',
            ]);
            return; // on arrête : rien n'a été enregistré
        }

        // 7) TOUT EST VALIDE → ON CRÉE LA COMMANDE DANS LA BASE.
        $orderModel = new Order();

        $nouvelId = $orderModel->store([
            // La référence n'est pas fournie : le modèle en génère une
            // automatiquement (séquentielle, ex : CMD-2026-006).
            'client'        => $client,          // Nom du client
            'email'         => $email ?: null,   // "" → null (colonne facultative)
            'date_commande' => $dateCommande,    // Date de la commande
            'montant'       => (float) $montant, // Converti en nombre décimal
            'statut'        => $statut,          // Statut choisi
            'description'   => $description ?: null, // "" → null
            'user_id'       => Auth::id(),       // Auteur = utilisateur connecté
        ]);

        // 8) MESSAGE DE SUCCÈS + REDIRECTION (pattern POST/Redirect/GET).
        //    On redirige vers la page de détails de la commande créée.
        //    On relit la commande pour afficher sa référence auto-générée.
        $nouvelle = $orderModel->findById($nouvelId);
        $ref      = $nouvelle['reference'] ?? ('#' . $nouvelId);
        $this->flash('success', 'La commande ' . $ref . ' a été créée avec succès.');
        $this->redirect('/orders/show?id=' . $nouvelId);
    }

    /**
     * ------------------------------------------------------------
     * AFFICHER LES DÉTAILS D'UNE COMMANDE  (URL : GET /orders/show?id=3)
     * ------------------------------------------------------------
     */
    public function show(): void
    {
        // Connexion obligatoire.
        Auth::requireLogin();

        // 1) ON RÉCUPÈRE L'ID DANS L'URL (?id=3).
        $order = $this->findOr404();

        // 2) On affiche la vue de détail avec la commande trouvée.
        $this->render('orders/show', [
            'order' => $order,      // la commande complète
            'title' => 'Commande ' . $order['reference'],
        ]);
    }

    /**
     * ------------------------------------------------------------
     * AFFICHER LE FORMULAIRE DE MODIFICATION  (URL : GET /orders/edit?id=3)
     * ------------------------------------------------------------
     */
    public function edit(): void
    {
        // Connexion obligatoire.
        Auth::requireLogin();

        // 1) On charge la commande à modifier (ou 404).
        $order = $this->findOr404();

        // 2) On affiche le formulaire PRÉ-REMPLI avec les valeurs actuelles.
        $this->render('orders/edit', [
            'order'   => $order,             // valeurs à mettre dans les champs
            'statuts' => Order::statuts(),   // liste des statuts possibles
            'title'   => 'Modifier ' . $order['reference'],
        ]);
    }

    /**
     * ------------------------------------------------------------
     * ENREGISTRER LES MODIFICATIONS  (URL : POST /orders/update?id=3)
     * ------------------------------------------------------------
     */
    public function update(): void
    {
        // Connexion obligatoire.
        Auth::requireLogin();

        // Sécurité : on doit être sur une vraie soumission de formulaire.
        if (!$this->isPost()) {
            $this->redirect('/orders');
            return;
        }

        // 1) On charge la commande existante (ou 404).
        $order = $this->findOr404();

        // SÉCURITÉ : jeton CSRF. SI INVALIDE, ON ARRÊTE TOUT et on
        // n'exécute AUCUNE mise à jour en base.
        if (!$this->csrfCheck()) {
            $this->flash('error', 'Formulaire expiré ou invalide, modification refusée.');
            $this->redirect('/orders/edit?id=' . (int) ($_POST['id'] ?? $this->query('id', 0)));
            return; // ON ARRÊTE ICI : la commande n'est pas modifiée
        }

        // 2) On relit les valeurs du formulaire.
        //    La référence N'EST PAS modifiable : elle est conservée telle quelle.
        $client       = $this->input('client');
        $email        = $this->input('email');
        $dateCommande = $this->input('date_commande');
        $montant      = $this->input('montant');
        $statut       = $this->input('statut');
        $description  = $this->input('description');

        // 3) Validation identique à la création (mêmes règles).
        $v = new Validator();
        $v->check('client', $client, ['required', 'min:2', 'max:150'], 'Le client');
        $v->check('email', $email, ['email', 'max:190'], 'L\'e-mail du client');
        $v->check('date_commande', $dateCommande, ['required', 'date'], 'La date de commande');
        $v->check('montant', $montant, ['required', 'positive'], 'Le montant');
        $v->check('statut', $statut, ['required', 'in:en_attente,validee,livree,annulee'], 'Le statut');
        $v->check('description', $description, ['max:2000'], 'La description');

        // 4) En cas d'erreur → on réaffiche le formulaire rempli.
        if ($v->fails()) {
            $this->errors = $v->errors();

            // On reconstruit un "faux order" avec les valeurs saisies,
            // comme ça le formulaire reste rempli avec ce que l'utilisateur
            // avait tapé (et non avec les anciennes valeurs en base).
            // La référence reste celle d'origine (non modifiable).
            $order['client']        = $client;
            $order['email']         = $email;
            $order['date_commande'] = $dateCommande;
            $order['montant']       = $montant;
            $order['statut']        = $statut;
            $order['description']   = $description;

            $this->render('orders/edit', [
                'order'   => $order,
                'statuts' => Order::statuts(),
                'title'   => 'Modifier ' . $order['reference'],
            ]);
            return; // on arrête : la modification n'a pas eu lieu
        }

        // 5) TOUT EST VALIDE → MISE À JOUR EN BASE.
        $orderModel = new Order();

        $orderModel->update((int) $order['id'], [
            // La référence n'est pas modifiée (elle reste celle d'origine).
            'client'        => $client,
            'email'         => $email ?: null,
            'date_commande' => $dateCommande,
            'montant'       => (float) $montant,
            'statut'        => $statut,
            'description'   => $description ?: null,
            // "user_id" et "id" ne sont PAS modifiés : l'auteur reste le même.
        ]);

        // 6) Message de succès puis redirection vers les détails.
        $this->flash('success', 'La commande ' . $order['reference'] . ' a été mise à jour.');
        $this->redirect('/orders/show?id=' . $order['id']);
    }

    /**
     * ------------------------------------------------------------
     * SUPPRIMER UNE COMMANDE  (URL : POST /orders/delete?id=3)
     * ------------------------------------------------------------
     * On utilise POST (et non GET) car une suppression est une
     * action DÉSTRUCTIVE : elle doit passer par un formulaire.
     */
    public function destroy(): void
    {
        // Connexion obligatoire.
        Auth::requireLogin();

        // Sécurité : uniquement un vrai formulaire POST.
        if (!$this->isPost()) {
            $this->redirect('/orders');
            return;
        }

        // Sécurité : jeton CSRF (empêche qu'un site tiers supprime nos commandes).
        if (!$this->csrfCheck()) {
            $this->flash('error', 'Formulaire expiré, suppression refusée.');
            $this->redirect('/orders');
            return;
        }

        // 1) On charge la commande (ou 404 si elle n'existe pas).
        $order = $this->findOr404();

        // 2) On demande au modèle de supprimer la ligne de la base.
        $orderModel = new Order();
        $orderModel->delete((int) $order['id']);

        // 3) Message de succès + retour à la liste.
        $this->flash('success', 'La commande ' . $order['reference'] . ' a été supprimée.');
        $this->redirect('/orders');
    }

    /**
     * ------------------------------------------------------------
     * OUTIL PRIVÉ : CHARGER UNE COMMANDE DEPUIS L'URL (ou erreur 404)
     * ------------------------------------------------------------
     * Cette méthode est utilisée par show(), edit(), update() et
     * destroy() : elle évite de répéter le même code 4 fois.
     *
     * @return array La commande complète trouvée en base
     */
    private function findOr404(): array
    {
        // 1) ON LIT L'IDENTIFIANT DE LA COMMANDE.
        //    Priorité au champ caché du formulaire ($_POST['id']),
        //    sinon on retombe sur le paramètre d'URL (?id=3).
        //    Les deux fonctionnent grâce à ce "??".
        $id = (int) ($_POST['id'] ?? $this->query('id', 0));

        // 2) Si l'absent ou nul → l'adresse est invalide.
        if ($id <= 0) {
            // On affiche la page d'erreur 404 via le routeur.
            $router = new Router();
            $router->renderError(404, 'Identifiant de commande manquant ou invalide.');
        }

        // 3) On cherche la commande dans la base.
        $orderModel = new Order();
        $order      = $orderModel->findById($id);

        // 4) Si elle n'existe pas → 404.
        if ($order === null) {
            $router = new Router();
            $router->renderError(404, 'Commande introuvable (id = ' . $id . ').');
        }

        // 5) On retourne la commande (le tableau associatif).
        return $order;
    }
}
