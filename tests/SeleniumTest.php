<?php

declare(strict_types=1);

use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use PHPUnit\Framework\TestCase;

/**
 * Classe de base des tests Selenium pour GestCommandes.
 *
 * Prérequis :
 *  - ChromeDriver sur http://127.0.0.1:9515, ou
 *  - un serveur Selenium sur http://127.0.0.1:4444
 *  - le serveur PHP de l'app (ex. php -S localhost:8000 -t public).
 *
 * Le test est ignoré (skip) si aucun endpoint WebDriver n'est joignable.
 */
abstract class SeleniumTest extends TestCase
{
    /** Endpoint local de ChromeDriver (port 9515). */
    protected const CHROMEDRIVER_URL = 'http://127.0.0.1:9515';

    /** Endpoint alternatif d'un hub Selenium. */
    protected const SELENIUM_URL = 'http://127.0.0.1:4444/wd/hub';

    /** Session navigateur contrôlée par WebDriver. */
    protected ?RemoteWebDriver $driver = null;

    protected function setUp(): void
    {
        // Choisit ChromeDriver (9515) ou Selenium (4444) s'il répond.
        $serverUrl = $this->detectWebDriverUrl();
        if ($serverUrl === null) {
            $this->markTestSkipped(
                'No ChromeDriver (port 9515) or Selenium server (port 4444) is running.'
            );
        }

        // Options de lancement de Chrome.
        $options = new ChromeOptions();
        $options->addArguments([
            '--incognito',       // mode navigation privée (pas de cookies/session persistants)
            '--headless=new',    // sans fenêtre visible (CI / automatisation)
            '--disable-gpu',     // stabilité en headless
            '--window-size=1280,800',
        ]);

        // Capacités W3C : on attache les options Chrome au profil "chrome".
        $capabilities = DesiredCapabilities::chrome();
        $capabilities->setCapability(ChromeOptions::CAPABILITY, $options);

        // Connexion au WebDriver (souvent ChromeDriver sur le port 9515).
        // Les timeouts (5 s) limitent l'attente de création de session.
        $this->driver = RemoteWebDriver::create($serverUrl, $capabilities, 5000, 5000);
    }

    protected function tearDown(): void
    {
        // Ferme le navigateur pour ne pas laisser de process Chrome orphelins.
        if ($this->driver !== null) {
            $this->driver->quit();
            $this->driver = null;
        }
    }

    /**
     * Retourne la première URL WebDriver joignable, ou null.
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
     * Teste rapidement si un port TCP local (host:port) est ouvert.
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
