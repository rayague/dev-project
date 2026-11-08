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
 *   - index()   : lister (recherche + filtre catégorie + pagination)
 *   - create()  : afficher le formulaire de création
 *   - store()   : enregistrer un nouveau produit
 *   - show()    : afficher le détail d'un produit
 *   - edit()    : afficher le formulaire de modification
 *   - update()  : enregistrer les modifications
 *   - destroy() : supprimer un produit
 *
 *  Catégories (feature associée) :
 *   - filtre index via ?category=id
 *   - select category_id sur create / edit / store / update
 *   - libellé category_name sur show
 *
 *  Aucune balise HTML, aucune SQL brute ici :
 *  validation (Validator) + modèle Product / Category + vues.
 * ============================================================
 */

declare(strict_types=1);

class ProductController extends Controller
{
    /**
     * ------------------------------------------------------------
     * LISTER LES PRODUITS  (URL : GET /products)
     * ------------------------------------------------------------
     * Affiche la liste paginée, avec recherche optionnelle
     * (?search=...), filtre catégorie (?category=2) et tri
     * (?sort=category → regroupement alphabétique par catégorie).
     */
    public function index(): void
    {
        // Accès réservé aux utilisateurs connectés.
        Auth::requireLogin();

        // Paramètres de l'URL (query string).
        $search = (string) $this->query('search', '');
        $page   = max(1, (int) $this->query('page', 1)); // jamais < 1

        // Filtre catégorie : ?category=2 → int, 0 / absent → null (toutes).
        $categoryId = (int) $this->query('category', 0);
        $categoryId = $categoryId > 0 ? $categoryId : null;

        // Tri : seul 'category' est reconnu (sinon ordre chronologique).
        $sort = (string) $this->query('sort', '');
        if ($sort !== 'category') {
            $sort = '';
        }

        // Données de la page courante via le modèle (JOIN category_name).
        $productModel = new Product();
        $resultat     = $productModel->search($search, $page, 10, $categoryId, $sort);

        // Liste des catégories pour peupler le <select> du filtre.
        $categoryModel = new Category();

        // Transmission des variables à la vue products/index.php.
        $this->render('products/index', [
            'products'         => $resultat['items'], // lignes (+ category_name)
            'total'            => $resultat['total'], // total filtré
            'pages'            => $resultat['pages'], // nombre de pages
            'page'             => $resultat['page'],  // page courante
            'search'           => $search,            // pour réafficher le champ
            'categories'       => $categoryModel->allOrdered(), // options du filtre
            'activeCategoryId' => $categoryId,        // filtre courant (ou null)
            'sort'             => $sort,              // '' ou 'category'
            'title'            => 'Gestion des produits',
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
            'old'        => [], // formulaire neuf : aucune valeur préremplie
            'categories' => (new Category())->allOrdered(), // select category_id
            'title'      => 'Nouveau produit',
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
                'old'        => $this->formOld(),
                'categories' => (new Category())->allOrdered(),
                'title'      => 'Nouveau produit',
            ]);
            return; // rien n'est enregistré
        }

        // Lecture des champs du formulaire (dont category_id facultatif).
        $name        = $this->input('name');
        $description = $this->input('description');
        $price       = $this->input('price');
        $stock       = $this->input('stock');
        // "" ou "0" → null ; sinon entier positif.
        $categoryId  = $this->normalizeCategoryId($this->input('category_id'));

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

        // Catégorie facultative : si fournie, elle doit exister en base.
        if ($categoryId !== null && !(new Category())->findById($categoryId)) {
            $this->errors['category_id'] = ['La catégorie sélectionnée est invalide.'];
        }

        // En cas d'erreur : on réaffiche le formulaire avec les messages.
        if ($v->fails() || $this->errors !== []) {
            $this->errors = array_merge_recursive($this->errors, $v->errors());
            $this->render('products/create', [
                'old'        => $this->formOld(), // conserve aussi category_id
                'categories' => (new Category())->allOrdered(),
                'title'      => 'Nouveau produit',
            ]);
            return;
        }

        // Insertion en base via le modèle (category_id peut être null).
        $productModel = new Product();
        $nouvelId     = $productModel->store([
            'name'        => $name,
            'description' => $description ?: null, // chaîne vide → NULL
            'price'       => (float) $price,
            'stock'       => (int) $stock,
            'category_id' => $categoryId,
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

        // Enrichit la ligne avec le libellé catégorie (relation FK).
        $product['category_name'] = (new Product())->getCategoryName(
            isset($product['category_id']) ? (int) $product['category_id'] : null
        );

        $this->render('products/show', [
            'product' => $product, // contient category_id + category_name
            'title'   => $product['name'],
        ]);
    }

    /**
     * ------------------------------------------------------------
     * FORMULAIRE DE MODIFICATION  (URL : GET /products/edit?id=X)
     * ------------------------------------------------------------
     * Pré-remplit les champs avec les valeurs actuelles en base
     * (y compris category_id pour le <select>).
     */
    public function edit(): void
    {
        Auth::requireLogin();

        $product = $this->findOr404();

        $this->render('products/edit', [
            'product'    => $product,
            'categories' => (new Category())->allOrdered(), // options du select
            'title'      => 'Modifier ' . $product['name'],
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
        // Même normalisation que store() : vide → null.
        $categoryId  = $this->normalizeCategoryId($this->input('category_id'));

        $v = new Validator();
        $v->check('name', $name, ['required', 'min:2', 'max:150'], 'Le nom');
        $v->check('description', $description, ['max:2000'], 'La description');
        $v->check('price', $price, ['required', 'positive'], 'Le prix');
        $v->check('stock', $stock, ['required', 'numeric'], 'Le stock');

        if ($stock !== '' && is_numeric($stock) && (int) $stock < 0) {
            $this->errors['stock'] = ['Le stock ne peut pas être négatif.'];
        }

        // Vérifie que l'id catégorie (s'il est fourni) existe bien.
        if ($categoryId !== null && !(new Category())->findById($categoryId)) {
            $this->errors['category_id'] = ['La catégorie sélectionnée est invalide.'];
        }

        // Erreurs → on réaffiche edit avec les valeurs saisies.
        if ($v->fails() || $this->errors !== []) {
            $this->errors = array_merge_recursive($this->errors, $v->errors());
            $product['name']        = $name;
            $product['description'] = $description;
            $product['price']       = $price;
            $product['stock']       = $stock;
            $product['category_id'] = $categoryId; // pour resélectionner le <select>

            $this->render('products/edit', [
                'product'    => $product,
                'categories' => (new Category())->allOrdered(),
                'title'      => 'Modifier ' . $name,
            ]);
            return;
        }

        // Mise à jour des colonnes autorisées (category_id inclus).
        $productModel = new Product();
        $productModel->update((int) $product['id'], [
            'name'        => $name,
            'description' => $description ?: null,
            'price'       => (float) $price,
            'stock'       => max(0, (int) $stock),
            'category_id' => $categoryId,
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
     * @return array{name:string,description:string,price:string,stock:string,category_id:string}
     */
    private function formOld(): array
    {
        return [
            'name'        => $this->input('name'),
            'description' => $this->input('description'),
            'price'       => $this->input('price'),
            'stock'       => $this->input('stock'),
            'category_id' => $this->input('category_id'),
        ];
    }

    /**
     * ------------------------------------------------------------
     * NORMALISER category_id (vide → null)
     * ------------------------------------------------------------
     * Le <select> envoie "" pour « Aucune » : on convertit en null
     * pour respecter la colonne nullable en base.
     */
    private function normalizeCategoryId(string $raw): ?int
    {
        $raw = trim($raw);
        // Chaîne vide, non numérique ou ≤ 0 → pas de catégorie.
        if ($raw === '' || !is_numeric($raw) || (int) $raw <= 0) {
            return null;
        }

        return (int) $raw;
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
