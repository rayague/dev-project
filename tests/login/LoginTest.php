<?php

/**
 * ============================================================
 *  TESTS SELENIUM : CONNEXION À L'APPLICATION
 * ============================================================
 *
 *  Scénarios couverts :
 *   - page de login atteignable + formulaire présent
 *   - connexion réussie (identifiants démo schema.sql)
 *   - échec avec mauvais mot de passe
 *   - échec avec champs vides (validation serveur)
 *
 *  Hérite de SeleniumTest (Chrome headless / incognito).
 *  Sert aussi de prérequis fonctionnel aux tests produits :
 *  le CRUD /products exige une session authentifiée.
 * ============================================================
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/SeleniumTest.php';

use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Facebook\WebDriver\WebDriverWait;

/**
 * Tests Selenium du parcours de connexion GestCommandes.
 */
final class LoginTest extends SeleniumTest
{
    /** URL de la page de connexion de l'application. */
    private const APP_URL = 'http://localhost:8000/login';

    /** E-mail du compte de démonstration (schema.sql). */
    private const DEMO_EMAIL = 'admin@ascg.fr';

    /** Mot de passe en clair du compte de démonstration. */
    private const DEMO_PASSWORD = 'password123';

    /**
     * ------------------------------------------------------------
     * testLoginPageIsReachable
     * ------------------------------------------------------------
     * Ouvre /login et vérifie la présence des champs e-mail,
     * mot de passe et du bouton « Se connecter ».
     */
    public function testLoginPageIsReachable(): void
    {
        // Navigation vers la page de login de GestCommandes.
        $this->driver->get(self::APP_URL);

        // Vérifie que l'URL atteinte contient bien "login".
        $this->assertStringContainsString('login', strtolower($this->driver->getCurrentURL()));

        // Vérifie qu'un champ e-mail est présent dans le formulaire.
        $this->assertNotEmpty(
            $this->driver->findElements(WebDriverBy::cssSelector('input[name="email"]'))
        );

        // Vérifie qu'un champ mot de passe est présent.
        $this->assertNotEmpty(
            $this->driver->findElements(WebDriverBy::cssSelector('input[name="password"]'))
        );

        // Vérifie qu'un bouton de soumission est présent.
        $this->assertNotEmpty(
            $this->driver->findElements(WebDriverBy::cssSelector('button[type="submit"]'))
        );
    }

    /**
     * ------------------------------------------------------------
     * testSuccessfulLoginWithValidCredentials
     * ------------------------------------------------------------
     * Soumet les identifiants démo valides et attend une
     * redirection hors de /login (tableau de bord / racine).
     */
    public function testSuccessfulLoginWithValidCredentials(): void
    {
        $this->driver->get(self::APP_URL);

        // Remplit le formulaire (le jeton CSRF est déjà dans le HTML).
        $this->fillLoginForm(self::DEMO_EMAIL, self::DEMO_PASSWORD);
        $this->submitLoginForm();

        // Après succès : redirection hors de /login (dashboard).
        (new WebDriverWait($this->driver, 8))->until(
            WebDriverExpectedCondition::not(
                WebDriverExpectedCondition::urlContains('/login')
            )
        );

        $url = strtolower($this->driver->getCurrentURL());
        $this->assertDoesNotMatchRegularExpression('#/login/?$#', $url);
        // Le tableau de bord (ou la racine authentifiée) doit s'afficher.
        $this->assertMatchesRegularExpression('#/(dashboard)?/?$#', parse_url($url, PHP_URL_PATH) ?: '/');
    }

    /**
     * ------------------------------------------------------------
     * testFailedLoginWithWrongPassword
     * ------------------------------------------------------------
     * Soumet un mot de passe incorrect et vérifie le message
     * d'erreur global + le maintien sur /login.
     */
    public function testFailedLoginWithWrongPassword(): void
    {
        $this->driver->get(self::APP_URL);

        $this->fillLoginForm(self::DEMO_EMAIL, 'mauvais-mot-de-passe');
        $this->submitLoginForm();

        // On reste sur la page de login avec un message d'erreur global.
        (new WebDriverWait($this->driver, 8))->until(
            WebDriverExpectedCondition::presenceOfElementLocated(
                WebDriverBy::xpath(
                    "//*[contains(normalize-space(.), 'Adresse e-mail ou mot de passe incorrect')]"
                )
            )
        );

        $this->assertStringContainsString('login', strtolower($this->driver->getCurrentURL()));
        $this->assertStringContainsString(
            'Adresse e-mail ou mot de passe incorrect',
            $this->driver->getPageSource()
        );
    }

    /**
     * ------------------------------------------------------------
     * testFailedLoginWithEmptyFields
     * ------------------------------------------------------------
     * Soumet le formulaire vide après avoir retiré les attributs
     * HTML5 "required", pour laisser la validation PHP s'afficher.
     */
    public function testFailedLoginWithEmptyFields(): void
    {
        $this->driver->get(self::APP_URL);

        // Désactive la validation HTML5 pour tester les messages PHP.
        $this->driver->executeScript(
            "document.querySelectorAll('#email, #password').forEach(function (el) {"
            . " el.removeAttribute('required');"
            . "});"
        );

        // Soumet le formulaire sans rien saisir.
        $this->submitLoginForm();

        // Les messages de validation serveur doivent apparaître.
        (new WebDriverWait($this->driver, 8))->until(
            WebDriverExpectedCondition::presenceOfElementLocated(
                WebDriverBy::cssSelector('.field-err')
            )
        );

        $this->assertStringContainsString('login', strtolower($this->driver->getCurrentURL()));
        $page = $this->driver->getPageSource();
        $this->assertTrue(
            str_contains($page, 'obligatoire') || str_contains($page, 'field-err'),
            'Un message de validation pour les champs vides était attendu.'
        );
    }

    /**
     * ------------------------------------------------------------
     * fillLoginForm : saisit e-mail et mot de passe
     * ------------------------------------------------------------
     * Helper réutilisé par les scénarios de succès / échec.
     */
    private function fillLoginForm(string $email, string $password): void
    {
        $emailField = $this->driver->findElement(WebDriverBy::cssSelector('input[name="email"]'));
        $emailField->clear();
        $emailField->sendKeys($email);

        $passwordField = $this->driver->findElement(WebDriverBy::cssSelector('input[name="password"]'));
        $passwordField->clear();
        $passwordField->sendKeys($password);
    }

    /**
     * ------------------------------------------------------------
     * submitLoginForm : clique sur « Se connecter »
     * ------------------------------------------------------------
     */
    private function submitLoginForm(): void
    {
        $this->driver->findElement(WebDriverBy::cssSelector('button[type="submit"]'))->click();
    }
}
