<?php
/**
 * ============================================================
 *  CONTRÔLEUR : ProductController (CRUD des produits)
 * ============================================================
 *
 *  RÔLE DANS LE MVC :
 *  Fait le lien entre les formulaires / URLs produits et le
 *  modèle Product (table "products").
 *
 *  Opérations :
 *   - index()   : lister (recherche + pagination)
 *   - create()  : afficher le formulaire de création
 *   - store()   : enregistrer un nouveau produit
 *   - show()    : afficher le détail d'un produit
 *   - edit()    : afficher le formulaire de modification
 *   - update()  : enregistrer les modifications
 *   - destroy() : supprimer un produit
 *
 *  Aucune balise HTML, aucune SQL brute ici :
 *  validation (Validator) + modèle Product + vues.
 * ============================================================
 */

declare(strict_types=1);

class ProductController extends Controller
{
    /**
     * ------------------------------------------------------------
     * LISTER LES PRODUITS  (URL : GET /products)
     * ------------------------------------------------------------
     * Affiche la liste paginée, avec une recherche optionnelle
     * sur le nom ou la description (?search=...&page=...).
     */
    public function index(): void
    {
        // Accès réservé aux utilisateurs connectés.
        Auth::requireLogin();

        // Paramètres de l'URL (query string).
        $search = (string) $this->query('search', '');
        $page   = max(1, (int) $this->query('page', 1)); // jamais < 1

        // Données de la page courante via le modèle.
        $productModel = new Product();
        $resultat     = $productModel->search($search, $page, 10);

        // Transmission des variables à la vue products/index.php.
        $this->render('products/index', [
            'products' => $resultat['items'], // lignes de la page
            'total'    => $resultat['total'], // total filtré
            'pages'    => $resultat['pages'], // nombre de pages
            'page'     => $resultat['page'],  // page courante
            'search'   => $search,            // pour réafficher le champ
            'title'    => 'Gestion des produits',
        ]);
    }

    /**
     * ------------------------------------------------------------
     * FORMULAIRE DE CRÉATION  (URL : GET /products/create)
     * ------------------------------------------------------------
     * Affiche un formulaire vide. "old" servira à réinjecter
     * les saisies en cas d'erreur de validation sur store().
     */
    public function create(): void
    {
        Auth::requireLogin();

        $this->render('products/create', [
            'old'   => [], // formulaire neuf : aucune valeur préremplie
            'title' => 'Nouveau produit',
        ]);
    }

    /**
     * ------------------------------------------------------------
     * ENREGISTRER UN PRODUIT  (URL : POST /products)
     * ------------------------------------------------------------
     * Reçoit le formulaire de création, vérifie CSRF + règles,
     * puis appelle Product::store() et redirige vers le détail.
     */
    public function store(): void
    {
        Auth::requireLogin();

        // Sécurité : seuls les vrais POST sont acceptés.
        if (!$this->isPost()) {
            $this->redirect('/products/create');
            return;
        }

        // Jeton CSRF : bloque les soumissions forgées depuis un autre site.
        if (!$this->csrfCheck()) {
            $this->errors['_global'][] = 'Formulaire expiré ou invalide, veuillez réessayer.';
            $this->render('products/create', [
                'old'   => $this->formOld(),
                'title' => 'Nouveau produit',
            ]);
            return; // rien n'est enregistré
        }

        // Lecture des champs du formulaire.
        $name        = $this->input('name');
        $description = $this->input('description');
        $price       = $this->input('price');
        $stock       = $this->input('stock');

        // Validation des règles métier / format.
        $v = new Validator();
        $v->check('name', $name, ['required', 'min:2', 'max:150'], 'Le nom');
        $v->check('description', $description, ['max:2000'], 'La description');
        $v->check('price', $price, ['required', 'positive'], 'Le prix');
        $v->check('stock', $stock, ['required', 'numeric'], 'Le stock');

        // Stock : entier ≥ 0 (la règle "numeric" seule n'exclut pas les négatifs).
        if ($stock !== '' && is_numeric($stock) && (int) $stock < 0) {
            $this->errors['stock'] = ['Le stock ne peut pas être négatif.'];
        }

        // En cas d'erreur : on réaffiche le formulaire avec les messages.
        if ($v->fails() || $this->errors !== []) {
            $this->errors = array_merge_recursive($this->errors, $v->errors());
            $this->render('products/create', [
                'old'   => $this->formOld(),
                'title' => 'Nouveau produit',
            ]);
            return;
        }

        // Insertion en base via le modèle.
        $productModel = new Product();
        $nouvelId     = $productModel->store([
            'name'        => $name,
            'description' => $description ?: null, // chaîne vide → NULL
            'price'       => (float) $price,
            'stock'       => (int) $stock,
        ]);

        // Message flash + redirection (pattern Post/Redirect/Get).
        $this->flash('success', 'Le produit « ' . $name . ' » a été créé.');
        $this->redirect('/products/show?id=' . $nouvelId);
    }

    /**
     * ------------------------------------------------------------
     * DÉTAIL D'UN PRODUIT  (URL : GET /products/show?id=X)
     * ------------------------------------------------------------
     */
    public function show(): void
    {
        Auth::requireLogin();

        // Charge le produit ou affiche une 404.
        $product = $this->findOr404();

        $this->render('products/show', [
            'product' => $product,
            'title'   => $product['name'],
        ]);
    }

    /**
     * ------------------------------------------------------------
     * FORMULAIRE DE MODIFICATION  (URL : GET /products/edit?id=X)
     * ------------------------------------------------------------
     * Pré-remplit les champs avec les valeurs actuelles en base.
     */
    public function edit(): void
    {
        Auth::requireLogin();

        $product = $this->findOr404();

        $this->render('products/edit', [
            'product' => $product,
            'title'   => 'Modifier ' . $product['name'],
        ]);
    }

    /**
     * ------------------------------------------------------------
     * ENREGISTRER LES MODIFICATIONS  (URL : POST /products/update)
     * ------------------------------------------------------------
     */
    public function update(): void
    {
        Auth::requireLogin();

        if (!$this->isPost()) {
            $this->redirect('/products');
            return;
        }

        // Produit ciblé (id dans POST ou query).
        $product = $this->findOr404();

        // CSRF obligatoire avant toute écriture.
        if (!$this->csrfCheck()) {
            $this->flash('error', 'Formulaire expiré ou invalide, modification refusée.');
            $this->redirect('/products/edit?id=' . (int) $product['id']);
            return;
        }

        $name        = $this->input('name');
        $description = $this->input('description');
        $price       = $this->input('price');
        $stock       = $this->input('stock');

        $v = new Validator();
        $v->check('name', $name, ['required', 'min:2', 'max:150'], 'Le nom');
        $v->check('description', $description, ['max:2000'], 'La description');
        $v->check('price', $price, ['required', 'positive'], 'Le prix');
        $v->check('stock', $stock, ['required', 'numeric'], 'Le stock');

        if ($stock !== '' && is_numeric($stock) && (int) $stock < 0) {
            $this->errors['stock'] = ['Le stock ne peut pas être négatif.'];
        }

        // Erreurs → on réaffiche edit avec les valeurs saisies.
        if ($v->fails() || $this->errors !== []) {
            $this->errors = array_merge_recursive($this->errors, $v->errors());
            $product['name']        = $name;
            $product['description'] = $description;
            $product['price']       = $price;
            $product['stock']       = $stock;

            $this->render('products/edit', [
                'product' => $product,
                'title'   => 'Modifier ' . $name,
            ]);
            return;
        }

        // Mise à jour des colonnes autorisées.
        $productModel = new Product();
        $productModel->update((int) $product['id'], [
            'name'        => $name,
            'description' => $description ?: null,
            'price'       => (float) $price,
            'stock'       => max(0, (int) $stock),
        ]);

        $this->flash('success', 'Le produit « ' . $name . ' » a été mis à jour.');
        $this->redirect('/products/show?id=' . (int) $product['id']);
    }

    /**
     * ------------------------------------------------------------
     * SUPPRIMER UN PRODUIT  (URL : POST /products/delete)
     * ------------------------------------------------------------
     * Suppression destructive : uniquement en POST + CSRF.
     */
    public function destroy(): void
    {
        Auth::requireLogin();

        if (!$this->isPost()) {
            $this->redirect('/products');
            return;
        }

        if (!$this->csrfCheck()) {
            $this->flash('error', 'Formulaire expiré, suppression refusée.');
            $this->redirect('/products');
            return;
        }

        $product = $this->findOr404();

        $productModel = new Product();
        $productModel->delete((int) $product['id']);

        $this->flash('success', 'Le produit « ' . $product['name'] . ' » a été supprimé.');
        $this->redirect('/products');
    }

    /**
     * ------------------------------------------------------------
     * VALEURS DU FORMULAIRE (création) APRÈS ERREUR
     * ------------------------------------------------------------
     * Permet de ne pas perdre ce que l'utilisateur avait saisi.
     *
     * @return array{name:string,description:string,price:string,stock:string}
     */
    private function formOld(): array
    {
        return [
            'name'        => $this->input('name'),
            'description' => $this->input('description'),
            'price'       => $this->input('price'),
            'stock'       => $this->input('stock'),
        ];
    }

    /**
     * ------------------------------------------------------------
     * CHARGER UN PRODUIT OU AFFICHER UNE 404
     * ------------------------------------------------------------
     * Lit l'id depuis POST (formulaires) ou GET (?id=).
     *
     * @return array Ligne associative du produit
     */
    private function findOr404(): array
    {
        $id = (int) ($_POST['id'] ?? $this->query('id', 0));

        if ($id <= 0) {
            $router = new Router();
            $router->renderError(404, 'Identifiant de produit manquant ou invalide.');
        }

        $productModel = new Product();
        $product      = $productModel->findById($id);

        if ($product === null) {
            $router = new Router();
            $router->renderError(404, 'Produit introuvable (id = ' . $id . ').');
        }

        return $product;
    }
}
