<?php

declare(strict_types=1);

require_once __DIR__ . '/SeleniumTest.php';

/**
 * Point d'entrée d'exemple pour les tests Selenium GestCommandes.
 *
 * La logique WebDriver (Chrome incognito / headless, ChromeDriver 9515,
 * ChromeOptions::CAPABILITY) est centralisée dans SeleniumTest.
 * Les scénarios concrets vivent dans des sous-dossiers
 * (ex. tests/login/LoginTest.php).
 */
final class ExampleSeleniumTest extends SeleniumTest
{
    /**
     * Smoke test : vérifie que la session WebDriver héritée est bien ouverte.
     */
    public function testDriverSessionIsAvailable(): void
    {
        // La session doit être créée par SeleniumTest::setUp().
        $this->assertNotNull($this->driver);
    }
}
