<?php

declare(strict_types=1);

use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use PHPUnit\Framework\TestCase;

/**
 * ============================================================
 *  CLASSE DE BASE : SeleniumTest
 * ============================================================
 *
 *  Centralise le démarrage / l'arrêt du navigateur Chrome pour
 *  tous les tests Selenium du projet GestCommandes
 *  (LoginTest, ProductTest, CategoryPillFilterTest, ExampleSeleniumTest…).
 *
 *  Prérequis :
 *   - ChromeDriver sur http://127.0.0.1:9515, ou
 *   - un serveur Selenium sur http://127.0.0.1:4444
 *   - le serveur PHP de l'app (ex. php -S localhost:8000 -t public).
 *
 *  Si aucun endpoint WebDriver n'est joignable, le test est ignoré
 *  (markTestSkipped) plutôt que d'échouer.
 * ============================================================
 */
abstract class SeleniumTest extends TestCase
{
    /** URL locale de ChromeDriver (port 9515). */
    protected const CHROMEDRIVER_URL = 'http://127.0.0.1:9515';

    /** URL alternative d'un hub Selenium (port 4444). */
    protected const SELENIUM_URL = 'http://127.0.0.1:4444/wd/hub';

    /** Session navigateur WebDriver (null tant que setUp n'a pas réussi). */
    protected ?RemoteWebDriver $driver = null;

    /**
     * ------------------------------------------------------------
     * setUp : ouvre une session Chrome avant chaque test
     * ------------------------------------------------------------
     * Détecte ChromeDriver/Selenium, configure Chrome (incognito,
     * headless), puis crée le RemoteWebDriver.
     */
    protected function setUp(): void
    {
        // Choisit le premier endpoint WebDriver qui répond.
        $serverUrl = $this->detectWebDriverUrl();
        if ($serverUrl === null) {
            $this->markTestSkipped(
                'No ChromeDriver (port 9515) or Selenium server (port 4444) is running.'
            );
        }

        // Options de lancement de Chrome.
        $options = new ChromeOptions();
        $options->addArguments([
            '--incognito',       // navigation privée (pas de cookies persistants)
            '--headless=new',    // sans fenêtre visible (CI / automatisation)
            '--disable-gpu',     // stabilité en mode headless
            '--window-size=1280,800',
        ]);

        // Capacités W3C : rattache les options Chrome (API moderne CAPABILITY).
        $capabilities = DesiredCapabilities::chrome();
        $capabilities->setCapability(ChromeOptions::CAPABILITY, $options);

        // Connexion au WebDriver (timeouts de création de session : 5 s).
        $this->driver = RemoteWebDriver::create($serverUrl, $capabilities, 5000, 5000);
    }

    /**
     * ------------------------------------------------------------
     * tearDown : ferme le navigateur après chaque test
     * ------------------------------------------------------------
     * Évite de laisser des process Chrome / chromedriver orphelins.
     */
    protected function tearDown(): void
    {
        if ($this->driver !== null) {
            $this->driver->quit();
            $this->driver = null;
        }
    }

    /**
     * ------------------------------------------------------------
     * detectWebDriverUrl : trouve un endpoint WebDriver vivant
     * ------------------------------------------------------------
     * Teste d'abord ChromeDriver (9515), puis Selenium (4444).
     *
     * @return string|null URL utilisable, ou null si rien n'écoute
     */
    protected function detectWebDriverUrl(): ?string
    {
        foreach ([self::CHROMEDRIVER_URL, self::SELENIUM_URL] as $url) {
            $host = parse_url($url, PHP_URL_HOST) ?: '127.0.0.1';
            $port = (int) (parse_url($url, PHP_URL_PORT) ?: 0);
            if ($port > 0 && $this->isPortOpen($host, $port)) {
                return $url;
            }
        }

        return null;
    }

    /**
     * ------------------------------------------------------------
     * isPortOpen : vérifie qu'un host:port TCP répond
     * ------------------------------------------------------------
     * Utilisé pour savoir si ChromeDriver / Selenium est démarré.
     *
     * @param  string $host Adresse (souvent 127.0.0.1)
     * @param  int    $port Numéro de port
     * @return bool         true si la connexion TCP réussit
     */
    protected function isPortOpen(string $host, int $port): bool
    {
        $errno = 0;
        $errstr = '';
        $socket = @fsockopen($host, $port, $errno, $errstr, 0.5);
        if ($socket === false) {
            return false;
        }

        fclose($socket);

        return true;
    }
}
