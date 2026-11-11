<?php

declare(strict_types=1);

require_once __DIR__ . '/SeleniumTest.php';

/**
 * ============================================================
 *  TEST D'EXEMPLE : ExampleSeleniumTest
 * ============================================================
 *
 *  Smoke test minimal qui prouve que la classe de base
 *  SeleniumTest ouvre bien une session WebDriver.
 *
 *  Les scénarios métier sont ailleurs :
 *   - tests/login/LoginTest.php
 *   - tests/products/ProductTest.php (CRUD + catégories)
 * ============================================================
 */
final class ExampleSeleniumTest extends SeleniumTest
{
    /**
     * ------------------------------------------------------------
     * testDriverSessionIsAvailable
     * ------------------------------------------------------------
     * Vérifie uniquement que $this->driver a été créé par setUp().
     * Ne navigue vers aucune page de l'application.
     */
    public function testDriverSessionIsAvailable(): void
    {
        // La session doit être créée par SeleniumTest::setUp().
        $this->assertNotNull($this->driver);
    }
}
