<?php

/**
 * ============================================================
 *  TESTS SELENIUM : BARRE DE PASTILLES CATÉGORIE
 * ============================================================
 *
 *  La barre sous la recherche (catalogue et liste) filtre via
 *  ?category=id. « Toutes » retire le filtre.
 *
 *  Scénarios :
 *   - un clic sur une pastille ne garde que cette catégorie
 *   - un second clic change le filtre
 *   - « Toutes » réaffiche tous les produits du critère de recherche
 *   - la pastille active porte aria-current (liste ET catalogue)
 *   - le select revient sur « Toutes les catégories »
 *   - sous 960px le select passe sur sa propre ligne
 *   - sous 760px le libellé de la liste passe sous le nom
 * ============================================================
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/SeleniumTest.php';

use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverDimension;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Facebook\WebDriver\WebDriverKeys;
use Facebook\WebDriver\WebDriverSelect;
use Facebook\WebDriver\WebDriverWait;

/**
 * Tests Selenium du filtre par pastilles de catégorie.
 */
final class CategoryPillFilterTest extends SeleniumTest
{
    /** Racine HTTP de l'application locale (php -S :8000). */
    private const BASE_URL = 'http://localhost:8000';

    /** Page de connexion (obligatoire avant les listes produits). */
    private const LOGIN_URL = self::BASE_URL . '/login';

    /** Liste des produits — GET /products. */
    private const PRODUCTS_URL = self::BASE_URL . '/products';

    /** Catalogue vitrine — GET /products-user. */
    private const CATALOG_URL = self::BASE_URL . '/products-user';

    /** Compte admin de démonstration (schema.sql). */
    private const DEMO_EMAIL = 'admin@ascg.fr';

    /** Mot de passe en clair du compte de démonstration. */
    private const DEMO_PASSWORD = 'password123';

    /**
     * ------------------------------------------------------------
     * testPillsFilterSwitchAndResetOnProductsList
     * ------------------------------------------------------------
     * Liste /products : Smartphone, puis Tablette, puis « Toutes ».
     */
    public function testPillsFilterSwitchAndResetOnProductsList(): void
    {
        $this->loginAsDemo();

        $suffix     = uniqid('', false);
        $smartphone = 'Selenium Pill Phone ' . $suffix;
        $tablette   = 'Selenium Pill Tab ' . $suffix;

        $this->createProductViaUi($smartphone, 'Pastille 1', '10.00', '1', 1);
        $this->createProductViaUi($tablette, 'Pastille 5', '20.00', '1', 5);

        $this->exercisePills(
            self::PRODUCTS_URL,
            'table.tbl',
            $suffix,
            $smartphone,
            $tablette
        );
    }

    /**
     * ------------------------------------------------------------
     * testPillsFilterSwitchAndResetOnCatalog
     * ------------------------------------------------------------
     * Catalogue /products-user : même scénario que la liste.
     */
    public function testPillsFilterSwitchAndResetOnCatalog(): void
    {
        $this->loginAsDemo();

        $suffix     = uniqid('', false);
        $smartphone = 'Selenium Pill Phone ' . $suffix;
        $tablette   = 'Selenium Pill Tab ' . $suffix;

        $this->createProductViaUi($smartphone, 'Pastille 1', '10.00', '1', 1);
        $this->createProductViaUi($tablette, 'Pastille 5', '20.00', '1', 5);

        $this->exercisePills(
            self::CATALOG_URL,
            'article.product-card',
            $suffix,
            $smartphone,
            $tablette
        );
    }

    /**
     * ------------------------------------------------------------
     * testCategoryControlsAdaptOnNarrowScreens
     * ------------------------------------------------------------
     * Sous 960px le select occupe sa propre ligne. Sous 760px
     * la colonne catégorie de la liste est remplacée par la
     * pastille sous le nom du produit.
     */
    public function testCategoryControlsAdaptOnNarrowScreens(): void
    {
        $this->loginAsDemo();
        $this->driver->get(self::PRODUCTS_URL);

        (new WebDriverWait($this->driver, 8))->until(
            WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::cssSelector('table.tbl'))
        );

        $this->driver->manage()->window()->setSize(new WebDriverDimension(700, 900));

        $narrow = $this->layoutSnapshot();
        $this->assertSame(1, $narrow['columns'], 'Le select doit passer sur sa propre ligne sous 960px');
        $this->assertSame('none', $narrow['columnDisplay']);
        $this->assertSame('inline-flex', $narrow['stackedDisplay']);

        $this->driver->manage()->window()->setSize(new WebDriverDimension(1280, 800));

        $wide = $this->layoutSnapshot();
        $this->assertGreaterThanOrEqual(3, $wide['columns']);
        $this->assertNotSame('none', $wide['columnDisplay']);
        $this->assertSame('none', $wide['stackedDisplay']);
    }

    /**
     * ------------------------------------------------------------
     * exercisePills : filtre, changement, remise à zéro
     * ------------------------------------------------------------
     * La recherche sur $suffix limite la page aux deux produits créés.
     */
    private function exercisePills(
        string $pageUrl,
        string $readySelector,
        string $suffix,
        string $smartphone,
        string $tablette
    ): void {
        $this->openFilteredPage($pageUrl, $readySelector, ['search' => $suffix]);

        $page = $this->driver->getPageSource();
        $this->assertStringContainsString($smartphone, $page);
        $this->assertStringContainsString($tablette, $page);
        $this->assertActivePill('Toutes');
        $this->assertSelectedCategory('Toutes les catégories');

        $this->clickPill('Smartphone');
        $this->waitForCategory($readySelector, 1);
        $this->assertStringContainsString($smartphone, $this->driver->getPageSource());
        $this->assertStringNotContainsString($tablette, $this->driver->getPageSource());
        $this->assertStringContainsString('category=1', strtolower($this->driver->getCurrentURL()));
        $this->assertActivePill('Smartphone');
        $this->assertInactivePill('Toutes');
        $this->assertSelectedCategory('Smartphone');

        $this->clickPill('Tablette');
        $this->waitForCategory($readySelector, 5);
        $this->assertStringContainsString($tablette, $this->driver->getPageSource());
        $this->assertStringNotContainsString($smartphone, $this->driver->getPageSource());
        $this->assertStringContainsString('category=5', strtolower($this->driver->getCurrentURL()));
        $this->assertActivePill('Tablette');
        $this->assertInactivePill('Smartphone');
        $this->assertSelectedCategory('Tablette');

        $this->clickPill('Toutes');
        $this->waitForCategory($readySelector, null);
        $page = $this->driver->getPageSource();
        $this->assertStringContainsString($smartphone, $page);
        $this->assertStringContainsString($tablette, $page);
        $this->assertStringNotContainsString('category=', strtolower($this->driver->getCurrentURL()));
        $this->assertActivePill('Toutes');
        $this->assertSelectedCategory('Toutes les catégories');
    }

    /**
     * ------------------------------------------------------------
     * openFilteredPage : ouvre la liste avec une query string
     * ------------------------------------------------------------
     * @param array<string, string> $query
     */
    private function openFilteredPage(string $pageUrl, string $readySelector, array $query): void
    {
        $this->driver->get($pageUrl . '?' . http_build_query($query));
        (new WebDriverWait($this->driver, 8))->until(
            WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::cssSelector($readySelector))
        );
    }

    /**
     * ------------------------------------------------------------
     * waitForCategory : attend ?category=id (ou son absence)
     * ------------------------------------------------------------
     */
    private function waitForCategory(string $readySelector, ?int $categoryId): void
    {
        (new WebDriverWait($this->driver, 8))->until(
            function () use ($categoryId): bool {
                $query = parse_url($this->driver->getCurrentURL(), PHP_URL_QUERY) ?: '';
                parse_str($query, $params);
                $current = isset($params['category']) ? (int) $params['category'] : 0;

                return $categoryId === null ? $current === 0 : $current === $categoryId;
            }
        );

        (new WebDriverWait($this->driver, 8))->until(
            WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::cssSelector($readySelector))
        );
    }

    /**
     * ------------------------------------------------------------
     * clickPill : clic sur une pastille de la barre
     * ------------------------------------------------------------
     */
    private function clickPill(string $label): void
    {
        $pill = $this->pill($label);
        $this->driver->executeScript(
            'arguments[0].scrollIntoView({block:"center", inline:"nearest"}); arguments[0].click();',
            [$pill]
        );
    }

    /**
     * ------------------------------------------------------------
     * assertActivePill : la pastille courante porte aria-current
     * ------------------------------------------------------------
     */
    private function assertActivePill(string $label): void
    {
        $this->assertSame('true', $this->pill($label)->getAttribute('aria-current'));
    }

    /**
     * ------------------------------------------------------------
     * assertInactivePill : la pastille n'est pas la catégorie active
     * ------------------------------------------------------------
     */
    private function assertInactivePill(string $label): void
    {
        $this->assertNotSame('true', (string) $this->pill($label)->getAttribute('aria-current'));
    }

    /**
     * ------------------------------------------------------------
     * assertSelectedCategory : libellé choisi dans le select
     * ------------------------------------------------------------
     */
    private function assertSelectedCategory(string $label): void
    {
        $select = new WebDriverSelect(
            $this->driver->findElement(WebDriverBy::cssSelector('select[name="category"]'))
        );
        $this->assertSame($label, trim($select->getFirstSelectedOption()->getText()));
    }

    /**
     * ------------------------------------------------------------
     * pill : lien de la barre dont le texte est exactement $label
     * ------------------------------------------------------------
     */
    private function pill(string $label): \Facebook\WebDriver\Remote\RemoteWebElement
    {
        $xpath = '//nav[@aria-label="Filtrer par catégorie"]//a[normalize-space()='
            . $this->xpathLiteral($label) . ']';

        return $this->driver->findElement(WebDriverBy::xpath($xpath));
    }

    /**
     * ------------------------------------------------------------
     * xpathLiteral : quote une chaîne pour un prédicat XPath
     * ------------------------------------------------------------
     */
    private function xpathLiteral(string $value): string
    {
        if (!str_contains($value, "'")) {
            return "'" . $value . "'";
        }

        $parts = explode("'", $value);
        $quoted = [];
        foreach ($parts as $part) {
            $quoted[] = "'" . $part . "'";
        }

        return 'concat(' . implode(", \"'\", ", $quoted) . ')';
    }

    /**
     * ------------------------------------------------------------
     * layoutSnapshot : colonnes du filtre et pastille étroite
     * ------------------------------------------------------------
     * @return array{columns:int, columnDisplay:string, stackedDisplay:string}
     */
    private function layoutSnapshot(): array
    {
        /** @var array{columns:int, columnDisplay:string, stackedDisplay:string} $snapshot */
        $snapshot = $this->driver->executeScript(
            <<<'JS'
            const grid = document.querySelector('.category-filter-grid');
            const tracks = getComputedStyle(grid).gridTemplateColumns.trim().split(/\s+/).filter(Boolean);
            const stacked = document.querySelector('.product-cat--stacked');
            const column = document.querySelector('td.col-category');
            return {
                columns: tracks.length,
                columnDisplay: column ? getComputedStyle(column).display : 'missing',
                stackedDisplay: stacked ? getComputedStyle(stacked).display : 'missing'
            };
            JS
        );

        return $snapshot;
    }

    /**
     * ------------------------------------------------------------
     * loginAsDemo : connexion admin de démonstration
     * ------------------------------------------------------------
     */
    private function loginAsDemo(): void
    {
        $this->driver->get(self::LOGIN_URL);

        (new WebDriverWait($this->driver, 8))->until(
            WebDriverExpectedCondition::presenceOfElementLocated(
                WebDriverBy::cssSelector('input[name="email"]')
            )
        );

        $email = $this->driver->findElement(WebDriverBy::cssSelector('input[name="email"]'));
        $email->clear();
        $email->sendKeys(self::DEMO_EMAIL);

        $password = $this->driver->findElement(WebDriverBy::cssSelector('input[name="password"]'));
        $password->clear();
        $password->sendKeys(self::DEMO_PASSWORD);

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
     */
    private function createProductViaUi(
        string $name,
        string $description,
        string $price,
        string $stock,
        int $categoryId
    ): int {
        $this->driver->get(self::PRODUCTS_URL . '/create');

        (new WebDriverWait($this->driver, 8))->until(
            WebDriverExpectedCondition::presenceOfElementLocated(
                WebDriverBy::cssSelector('input[name="name"]')
            )
        );

        $this->driver->findElement(WebDriverBy::cssSelector('input[name="name"]'))->sendKeys($name);
        $this->driver->findElement(WebDriverBy::cssSelector('textarea[name="description"]'))->sendKeys($description);

        $priceField = $this->driver->findElement(WebDriverBy::cssSelector('input[name="price"]'));
        $priceField->sendKeys(WebDriverKeys::CONTROL . 'a');
        $priceField->sendKeys($price);

        $stockField = $this->driver->findElement(WebDriverBy::cssSelector('input[name="stock"]'));
        $stockField->sendKeys(WebDriverKeys::CONTROL . 'a');
        $stockField->sendKeys($stock);

        $select = new WebDriverSelect(
            $this->driver->findElement(WebDriverBy::cssSelector('select[name="category_id"]'))
        );
        $select->selectByValue((string) $categoryId);

        $bouton = $this->driver->findElement(
            WebDriverBy::cssSelector('form[method="POST"][action="/products"] button[type="submit"]')
        );
        $this->driver->executeScript(
            'arguments[0].scrollIntoView({block:"center", inline:"nearest"}); arguments[0].click();',
            [$bouton]
        );

        (new WebDriverWait($this->driver, 8))->until(
            WebDriverExpectedCondition::urlContains('products/show')
        );

        $query = parse_url($this->driver->getCurrentURL(), PHP_URL_QUERY) ?: '';
        parse_str($query, $params);

        return (int) ($params['id'] ?? 0);
    }
}
