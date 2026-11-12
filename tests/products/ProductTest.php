<?php

/**
 * ============================================================
 *  TESTS SELENIUM : CRUD DES PRODUITS
 * ============================================================
 *
 *  Scénarios :
 *   - liste /products atteignable
 *   - formulaire /products/create atteignable
 *   - création via le formulaire
 *   - fiche détail /products/show
 *   - modification via le formulaire
 *   - suppression et disparition de la liste
 *   - création avec catégorie (Smartphone)
 *   - filtre ?category=N sur la liste
 *   - dropdown filtre : 6 catégories
 *   - remise à zéro du filtre (toutes les catégories)
 *   - RBAC : utilisateur simple bloqué sur /products/create (403)
 *   - RBAC : admin peut ouvrir /products/create
 *   - Catalogue /products-user accessible à un utilisateur simple
 *
 *  Chaque test se connecte d'abord (Auth::requireLogin côté app).
 * ============================================================
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/SeleniumTest.php';

use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Facebook\WebDriver\WebDriverKeys;
use Facebook\WebDriver\WebDriverSelect;
use Facebook\WebDriver\WebDriverWait;

/**
 * Tests Selenium du CRUD produits GestCommandes
 * (création, lecture, modification, suppression + catégories).
 */
final class ProductTest extends SeleniumTest
{
    /** Racine HTTP de l'application locale (php -S :8000). */
    private const BASE_URL = 'http://localhost:8000';

    /** Page de connexion (obligatoire avant /products). */
    private const LOGIN_URL = self::BASE_URL . '/login';

    /** Liste des produits (filtre catégorie : ?category=id). */
    private const PRODUCTS_URL = self::BASE_URL . '/products';

    /** Catalogue vitrine (lecture seule) — GET /products-user. */
    private const CATALOG_URL = self::BASE_URL . '/products-user';

    /** Compte admin de démonstration (schema.sql). */
    private const DEMO_EMAIL = 'admin@ascg.fr';

    /** Compte utilisateur simple de démonstration (rôle user). */
    private const USER_EMAIL = 'user@example.com';

    /** Mot de passe en clair des comptes de démonstration. */
    private const DEMO_PASSWORD = 'password123';

    /**
     * ------------------------------------------------------------
     * testProductListPageIsReachable
     * ------------------------------------------------------------
     * Vérifie que /products s'affiche après connexion.
     */
    public function testProductListPageIsReachable(): void
    {
        $this->loginAsDemo();

        $this->driver->get(self::PRODUCTS_URL);

        (new WebDriverWait($this->driver, 8))->until(
            WebDriverExpectedCondition::urlContains('/products')
        );

        $this->assertStringContainsString('/products', strtolower($this->driver->getCurrentURL()));
        $this->assertStringContainsString('Produits', $this->driver->getPageSource());
    }

    /**
     * ------------------------------------------------------------
     * testCreateProductFormIsReachable
     * ------------------------------------------------------------
     * Vérifie la présence des champs name, description, price, stock
     * et du bouton submit du formulaire produit (pas le logout).
     */
    public function testCreateProductFormIsReachable(): void
    {
        $this->loginAsDemo();

        $this->driver->get(self::PRODUCTS_URL . '/create');

        (new WebDriverWait($this->driver, 8))->until(
            WebDriverExpectedCondition::presenceOfElementLocated(
                WebDriverBy::cssSelector('input[name="name"]')
            )
        );

        $this->assertStringContainsString('/products/create', strtolower($this->driver->getCurrentURL()));
        $this->assertNotEmpty($this->driver->findElements(WebDriverBy::cssSelector('input[name="name"]')));
        $this->assertNotEmpty($this->driver->findElements(WebDriverBy::cssSelector('textarea[name="description"]')));
        $this->assertNotEmpty($this->driver->findElements(WebDriverBy::cssSelector('input[name="price"]')));
        $this->assertNotEmpty($this->driver->findElements(WebDriverBy::cssSelector('input[name="stock"]')));
        // Bouton du formulaire produit (pas celui du logout dans le header).
        $this->assertNotEmpty(
            $this->driver->findElements(
                WebDriverBy::cssSelector('form[method="POST"][action="/products"] button[type="submit"]')
            )
        );
    }

    /**
     * ------------------------------------------------------------
     * testCreateProductViaForm
     * ------------------------------------------------------------
     * Crée un produit, attend la redirection vers show, puis
     * vérifie qu'il apparaît dans la liste.
     */
    public function testCreateProductViaForm(): void
    {
        $this->loginAsDemo();

        $name = 'Selenium Produit ' . uniqid('', false);
        $id   = $this->createProductViaUi($name, 'Description auto', '19.90', '7');

        $this->assertGreaterThan(0, $id);
        $this->assertStringContainsString('products/show', strtolower($this->driver->getCurrentURL()));
        $this->assertStringContainsString($name, $this->driver->getPageSource());

        // Vérifie la présence dans la liste.
        $this->driver->get(self::PRODUCTS_URL);
        (new WebDriverWait($this->driver, 8))->until(
            WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::cssSelector('table.tbl'))
        );
        $this->assertStringContainsString($name, $this->driver->getPageSource());
    }

    /**
     * ------------------------------------------------------------
     * testShowProductDetailPage
     * ------------------------------------------------------------
     * Crée un produit puis ouvre sa fiche détail.
     */
    public function testShowProductDetailPage(): void
    {
        $this->loginAsDemo();

        $name = 'Selenium Detail ' . uniqid('', false);
        $id   = $this->createProductViaUi($name, 'Fiche détail', '8.50', '3');

        $this->driver->get(self::PRODUCTS_URL . '/show?id=' . $id);

        (new WebDriverWait($this->driver, 8))->until(
            WebDriverExpectedCondition::urlContains('products/show')
        );

        $page = $this->driver->getPageSource();
        $this->assertStringContainsString($name, $page);
        $this->assertStringContainsString('Fiche détail', $page);
        $this->assertStringContainsString('#' . $id, $page);
    }

    /**
     * ------------------------------------------------------------
     * testEditProductViaForm
     * ------------------------------------------------------------
     * Crée un produit, le modifie, puis vérifie les nouvelles valeurs.
     */
    public function testEditProductViaForm(): void
    {
        $this->loginAsDemo();

        $original = 'Selenium Edit ' . uniqid('', false);
        $id       = $this->createProductViaUi($original, 'Avant modif', '10.00', '4');

        $updated = $original . ' modifié';
        $this->driver->get(self::PRODUCTS_URL . '/edit?id=' . $id);

        (new WebDriverWait($this->driver, 8))->until(
            WebDriverExpectedCondition::presenceOfElementLocated(
                WebDriverBy::cssSelector('input[name="name"]')
            )
        );

        $this->fillProductForm($updated, 'Après modif', '22.00', '9');
        // Soumet le formulaire d'édition (évite le bouton logout du header).
        $this->clickProductSubmit('form[action*="products/update"] button[type="submit"]');

        (new WebDriverWait($this->driver, 8))->until(
            WebDriverExpectedCondition::urlContains('products/show')
        );

        $page = $this->driver->getPageSource();
        $this->assertStringContainsString($updated, $page);
        $this->assertStringContainsString('Après modif', $page);
        $this->assertStringContainsString('22,00', $page); // money() FR
    }

    /**
     * ------------------------------------------------------------
     * testDeleteProduct
     * ------------------------------------------------------------
     * Crée un produit, le supprime depuis la fiche, puis vérifie
     * qu'il n'apparaît plus dans la liste.
     */
    public function testDeleteProduct(): void
    {
        $this->loginAsDemo();

        $name = 'Selenium Delete ' . uniqid('', false);
        $id   = $this->createProductViaUi($name, 'À supprimer', '5.00', '1');

        $this->driver->get(self::PRODUCTS_URL . '/show?id=' . $id);
        (new WebDriverWait($this->driver, 8))->until(
            WebDriverExpectedCondition::presenceOfElementLocated(
                WebDriverBy::cssSelector('form[action*="products/delete"]')
            )
        );

        // Accepte automatiquement la boîte confirm() du formulaire.
        $this->driver->executeScript('window.confirm = function () { return true; };');
        $this->driver->findElement(
            WebDriverBy::cssSelector('form[action*="products/delete"] button[type="submit"]')
        )->click();

        (new WebDriverWait($this->driver, 8))->until(
            function () {
                $url = strtolower($this->driver->getCurrentURL());

                return str_contains($url, '/products') && !str_contains($url, 'show');
            }
        );

        $this->driver->get(self::PRODUCTS_URL);
        (new WebDriverWait($this->driver, 8))->until(
            WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::cssSelector('table.tbl'))
        );

        $this->assertStringNotContainsString($name, $this->driver->getPageSource());
    }

    /**
     * ------------------------------------------------------------
     * testCreateProductWithCategory
     * ------------------------------------------------------------
     * Crée un produit lié à la catégorie Smartphone (id = 1)
     * et vérifie que le libellé apparaît sur show puis sur la liste.
     */
    public function testCreateProductWithCategory(): void
    {
        $this->loginAsDemo();

        $name = 'Selenium Cat Smartphone ' . uniqid('', false);
        // category_id = 1 → « Smartphone » (seed migration).
        $id   = $this->createProductViaUi($name, 'Avec catégorie', '99.00', '2', 1);

        $this->assertGreaterThan(0, $id);
        $this->assertStringContainsString('Smartphone', $this->driver->getPageSource());

        // Vérifie aussi la colonne catégorie de la liste.
        $this->driver->get(self::PRODUCTS_URL);
        (new WebDriverWait($this->driver, 8))->until(
            WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::cssSelector('table.tbl'))
        );
        $liste = $this->driver->getPageSource();
        $this->assertStringContainsString($name, $liste);
        $this->assertStringContainsString('Smartphone', $liste);
    }

    /**
     * ------------------------------------------------------------
     * testCategoryFilterShowsOnlyMatchingProducts
     * ------------------------------------------------------------
     * Crée un produit Smartphone et un produit Tablette, puis
     * vérifie que ?category=1 n'affiche que le Smartphone.
     */
    public function testCategoryFilterShowsOnlyMatchingProducts(): void
    {
        $this->loginAsDemo();

        $suffix     = uniqid('', false);
        $smartphone = 'Selenium Filtre Phone ' . $suffix;
        $tablette   = 'Selenium Filtre Tab ' . $suffix;

        $this->createProductViaUi($smartphone, 'Cat 1', '10.00', '1', 1); // Smartphone
        $this->createProductViaUi($tablette, 'Cat 5', '20.00', '1', 5);   // Tablette

        // Filtre sur category=1 (Smartphone).
        $this->driver->get(self::PRODUCTS_URL . '?category=1');
        (new WebDriverWait($this->driver, 8))->until(
            WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::cssSelector('table.tbl'))
        );

        $page = $this->driver->getPageSource();
        $this->assertStringContainsString($smartphone, $page);
        $this->assertStringNotContainsString($tablette, $page);
        $this->assertStringContainsString('category=1', strtolower($this->driver->getCurrentURL()));
    }

    /**
     * ------------------------------------------------------------
     * testCategoryFilterDropdownListsAllCategories
     * ------------------------------------------------------------
     * Vérifie que le <select name="category"> de l'index propose
     * les 6 catégories seedées.
     */
    public function testCategoryFilterDropdownListsAllCategories(): void
    {
        $this->loginAsDemo();

        $this->driver->get(self::PRODUCTS_URL);
        (new WebDriverWait($this->driver, 8))->until(
            WebDriverExpectedCondition::presenceOfElementLocated(
                WebDriverBy::cssSelector('select[name="category"]')
            )
        );

        $options = $this->driver->findElements(
            WebDriverBy::cssSelector('select[name="category"] option')
        );
        $labels = [];
        foreach ($options as $option) {
            $labels[] = trim($option->getText());
        }

        // Option vide « Toutes… » + 6 catégories.
        $this->assertGreaterThanOrEqual(7, count($options));
        $this->assertContains('Smartphone', $labels);
        $this->assertContains('Ordinateur portable', $labels);
        $this->assertContains('Casque audio', $labels);
        $this->assertContains('Montre connectée', $labels);
        $this->assertContains('Tablette', $labels);
        $this->assertContains('Caméra', $labels);
    }

    /**
     * ------------------------------------------------------------
     * testClearingCategoryFilterShowsAllProducts
     * ------------------------------------------------------------
     * Après un filtre category=1, revenir à /products (sans param)
     * doit réafficher les produits des autres catégories.
     */
    public function testClearingCategoryFilterShowsAllProducts(): void
    {
        $this->loginAsDemo();

        $suffix     = uniqid('', false);
        $smartphone = 'Selenium Clear Phone ' . $suffix;
        $tablette   = 'Selenium Clear Tab ' . $suffix;

        $this->createProductViaUi($smartphone, 'Clear cat 1', '11.00', '1', 1);
        $this->createProductViaUi($tablette, 'Clear cat 5', '22.00', '1', 5);

        // D'abord filtré : la tablette disparaît.
        $this->driver->get(self::PRODUCTS_URL . '?category=1');
        (new WebDriverWait($this->driver, 8))->until(
            WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::cssSelector('table.tbl'))
        );
        $this->assertStringNotContainsString($tablette, $this->driver->getPageSource());

        // Remise à zéro : aucune query category → les deux réapparaissent.
        $this->driver->get(self::PRODUCTS_URL);
        (new WebDriverWait($this->driver, 8))->until(
            WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::cssSelector('table.tbl'))
        );
        $page = $this->driver->getPageSource();
        $this->assertStringContainsString($smartphone, $page);
        $this->assertStringContainsString($tablette, $page);
        $this->assertStringNotContainsString('category=', strtolower($this->driver->getCurrentURL()));
    }

    /**
     * ------------------------------------------------------------
     * testRegularUserCannotAccessProductCreate
     * ------------------------------------------------------------
     * RBAC : un compte rôle « user » qui ouvre /products/create
     * doit recevoir une page 403 (Accès interdit), pas le formulaire.
     */
    public function testRegularUserCannotAccessProductCreate(): void
    {
        // Compte démo user@example.com (rôle user) — ou inscription de secours.
        $this->loginAsRegularUser();

        $this->driver->get(self::PRODUCTS_URL . '/create');

        (new WebDriverWait($this->driver, 8))->until(
            function () {
                $src = $this->driver->getPageSource();

                return str_contains($src, '403') || str_contains($src, 'Accès interdit');
            }
        );

        $page = $this->driver->getPageSource();
        $this->assertTrue(
            str_contains($page, '403') || str_contains($page, 'Accès interdit'),
            'Un utilisateur non-admin devait être refusé (403) sur /products/create.'
        );
        // Le formulaire de création ne doit pas être présent.
        $this->assertEmpty(
            $this->driver->findElements(WebDriverBy::cssSelector('form[method="POST"][action="/products"]'))
        );
    }

    /**
     * ------------------------------------------------------------
     * testRegularUserCanAccessProductCatalog
     * ------------------------------------------------------------
     * Un utilisateur simple connecté doit voir le catalogue
     * GET /products-user (titre + contenu, sans formulaire create).
     */
    public function testRegularUserCanAccessProductCatalog(): void
    {
        $this->loginAsRegularUser();

        $this->driver->get(self::CATALOG_URL);

        (new WebDriverWait($this->driver, 8))->until(
            WebDriverExpectedCondition::urlContains('/products-user')
        );

        $page = $this->driver->getPageSource();
        $this->assertStringContainsString('/products-user', strtolower($this->driver->getCurrentURL()));
        $this->assertStringContainsString('Catalogue', $page);
        // Pas de formulaire d'écriture produits sur la vitrine.
        $this->assertEmpty(
            $this->driver->findElements(WebDriverBy::cssSelector('form[method="POST"][action="/products"]'))
        );
    }

    /**
     * ------------------------------------------------------------
     * testAdminCanAccessProductCreate
     * ------------------------------------------------------------
     * RBAC : un administrateur atteint bien le formulaire de création.
     */
    public function testAdminCanAccessProductCreate(): void
    {
        $this->loginAsDemo();

        $this->driver->get(self::PRODUCTS_URL . '/create');

        (new WebDriverWait($this->driver, 8))->until(
            WebDriverExpectedCondition::presenceOfElementLocated(
                WebDriverBy::cssSelector('input[name="name"]')
            )
        );

        $this->assertStringContainsString('/products/create', strtolower($this->driver->getCurrentURL()));
        $this->assertNotEmpty(
            $this->driver->findElements(
                WebDriverBy::cssSelector('form[method="POST"][action="/products"]')
            )
        );
    }

    /**
     * ------------------------------------------------------------
     * loginAsRegularUser : connexion compte user@example.com
     * ------------------------------------------------------------
     * Utilise le compte démo rôle « user » (schema / migration).
     * Si la connexion échoue, crée un compte via /register en secours.
     */
    private function loginAsRegularUser(): void
    {
        $this->driver->get(self::LOGIN_URL);

        (new WebDriverWait($this->driver, 8))->until(
            WebDriverExpectedCondition::presenceOfElementLocated(
                WebDriverBy::cssSelector('input[name="email"]')
            )
        );

        $email = $this->driver->findElement(WebDriverBy::cssSelector('input[name="email"]'));
        $email->clear();
        $email->sendKeys(self::USER_EMAIL);

        $password = $this->driver->findElement(WebDriverBy::cssSelector('input[name="password"]'));
        $password->clear();
        $password->sendKeys(self::DEMO_PASSWORD);

        $this->driver->findElement(WebDriverBy::cssSelector('button[type="submit"]'))->click();

        try {
            (new WebDriverWait($this->driver, 5))->until(
                WebDriverExpectedCondition::not(
                    WebDriverExpectedCondition::urlContains('/login')
                )
            );
        } catch (\Throwable $e) {
            // Secours : inscription publique (rôle user forcé).
            $this->registerAsRegularUser();
        }
    }

    /**
     * ------------------------------------------------------------
     * registerAsRegularUser : crée un compte « user » via /register
     * ------------------------------------------------------------
     * L'inscription publique force le rôle user (pas d'élévation).
     */
    private function registerAsRegularUser(): void
    {
        $suffix = uniqid('', false);
        $email  = 'user.selenium.' . $suffix . '@example.test';
        $pass   = 'testpass123';

        $this->driver->get(self::BASE_URL . '/register');

        (new WebDriverWait($this->driver, 8))->until(
            WebDriverExpectedCondition::presenceOfElementLocated(
                WebDriverBy::cssSelector('input[name="email"]')
            )
        );

        $this->driver->findElement(WebDriverBy::cssSelector('input[name="name"]'))
            ->sendKeys('User Selenium ' . $suffix);
        $this->driver->findElement(WebDriverBy::cssSelector('input[name="email"]'))
            ->sendKeys($email);
        $this->driver->findElement(WebDriverBy::cssSelector('input[name="password"]'))
            ->sendKeys($pass);
        $this->driver->findElement(WebDriverBy::cssSelector('input[name="password_confirmation"]'))
            ->sendKeys($pass);

        $this->driver->findElement(WebDriverBy::cssSelector('button[type="submit"]'))->click();

        // Après inscription : connexion auto + redirection hors /register.
        (new WebDriverWait($this->driver, 8))->until(
            WebDriverExpectedCondition::not(
                WebDriverExpectedCondition::urlContains('/register')
            )
        );
    }

    /**
     * ------------------------------------------------------------
     * loginAsDemo : connexion admin de démonstration
     * ------------------------------------------------------------
     * Remplit e-mail / mot de passe puis attend la sortie de /login.
     */
    private function loginAsDemo(): void
    {
        $this->loginWithCredentials(self::DEMO_EMAIL, self::DEMO_PASSWORD);
    }

    /**
     * ------------------------------------------------------------
     * loginWithCredentials : soumet le formulaire /login
     * ------------------------------------------------------------
     */
    private function loginWithCredentials(string $emailValue, string $passwordValue): void
    {
        $this->driver->get(self::LOGIN_URL);

        (new WebDriverWait($this->driver, 8))->until(
            WebDriverExpectedCondition::presenceOfElementLocated(
                WebDriverBy::cssSelector('input[name="email"]')
            )
        );

        $email = $this->driver->findElement(WebDriverBy::cssSelector('input[name="email"]'));
        $email->clear();
        $email->sendKeys($emailValue);

        $password = $this->driver->findElement(WebDriverBy::cssSelector('input[name="password"]'));
        $password->clear();
        $password->sendKeys($passwordValue);

        $this->driver->findElement(WebDriverBy::cssSelector('button[type="submit"]'))->click();

        (new WebDriverWait($this->driver, 8))->until(
            WebDriverExpectedCondition::not(
                WebDriverExpectedCondition::urlContains('/login')
            )
        );
    }

    /**
     * ------------------------------------------------------------
     * createProductViaUi : crée un produit et renvoie son id
     * ------------------------------------------------------------
     * @param int|null $categoryId Identifiant categories.id (null = aucune)
     */
    private function createProductViaUi(
        string $name,
        string $description,
        string $price,
        string $stock,
        ?int $categoryId = null
    ): int {
        $this->driver->get(self::PRODUCTS_URL . '/create');

        (new WebDriverWait($this->driver, 8))->until(
            WebDriverExpectedCondition::presenceOfElementLocated(
                WebDriverBy::cssSelector('input[name="name"]')
            )
        );

        $this->fillProductForm($name, $description, $price, $stock);

        // Sélection optionnelle de la catégorie (select name="category_id").
        if ($categoryId !== null && $categoryId > 0) {
            $this->selectCategoryId($categoryId);
        }

        // Cible le POST /products : le header a aussi un submit (logout).
        // scrollIntoView : le select catégorie allonge le formulaire (hors viewport 800px).
        $this->clickProductSubmit('form[method="POST"][action="/products"] button[type="submit"]');

        (new WebDriverWait($this->driver, 8))->until(
            WebDriverExpectedCondition::urlContains('products/show')
        );

        $query = parse_url($this->driver->getCurrentURL(), PHP_URL_QUERY) ?: '';
        parse_str($query, $params);

        return (int) ($params['id'] ?? 0);
    }

    /**
     * ------------------------------------------------------------
     * selectCategoryId : choisit une option du select category_id
     * ------------------------------------------------------------
     */
    private function selectCategoryId(int $categoryId): void
    {
        $select = new WebDriverSelect(
            $this->driver->findElement(WebDriverBy::cssSelector('select[name="category_id"]'))
        );
        $select->selectByValue((string) $categoryId);
    }

    /**
     * ------------------------------------------------------------
     * clickProductSubmit : scroll + clic sur le bouton du formulaire
     * ------------------------------------------------------------
     */
    private function clickProductSubmit(string $cssSelector): void
    {
        $bouton = $this->driver->findElement(WebDriverBy::cssSelector($cssSelector));
        // scroll + clic JS : un overlay/footer peut intercepter le clic natif (y≈750).
        $this->driver->executeScript(
            'arguments[0].scrollIntoView({block: "center", inline: "nearest"}); arguments[0].click();',
            [$bouton]
        );
    }

    /**
     * ------------------------------------------------------------
     * fillProductForm : remplit name, description, price, stock
     * ------------------------------------------------------------
     * Ne touche pas à category_id : voir selectCategoryId() si besoin.
     */
    private function fillProductForm(string $name, string $description, string $price, string $stock): void
    {
        $nameField = $this->driver->findElement(WebDriverBy::cssSelector('input[name="name"]'));
        $nameField->clear();
        $nameField->sendKeys($name);

        $descField = $this->driver->findElement(WebDriverBy::cssSelector('textarea[name="description"]'));
        $descField->clear();
        $descField->sendKeys($description);

        // clear() est parfois capricieux sur les inputs number : on sélectionne tout.
        $priceField = $this->driver->findElement(WebDriverBy::cssSelector('input[name="price"]'));
        $priceField->sendKeys(WebDriverKeys::CONTROL . 'a');
        $priceField->sendKeys($price);

        $stockField = $this->driver->findElement(WebDriverBy::cssSelector('input[name="stock"]'));
        $stockField->sendKeys(WebDriverKeys::CONTROL . 'a');
        $stockField->sendKeys($stock);
    }
}
