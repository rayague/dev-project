<?php
/**
 * ============================================================
 *  VUE : errors/404.php  (PAGE « INTROUVABLE »)
 * ============================================================
 *
 *  RÔLE DANS LE MVC :
 *  Page d'erreur propre lorsque l'URL ne correspond à aucune
 *  route ou que l'objet demandé n'existe pas.
 *  Incluse par Router::renderError() — page AUTONOME.
 *
 *  VARIABLES REÇUES :
 *   - $errorMessage : explication technique de l'erreur
 * ============================================================
 */

$errorMessage = $errorMessage ?? 'La page demandée n\'existe pas.';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 — Page introuvable</title>

    <!-- Polices (Inter + Space Grotesk) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">

    <!-- Tailwind (Play CDN) + feuille de style "Aurore" -->
    <link href="<?= e(url('/assets/css/app.css')) ?>" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'ui-sans-serif', 'system-ui', 'sans-serif'],
                        display: ['"Space Grotesk"', 'Inter', 'sans-serif'],
                    },
                },
            },
        };
    </script>
</head>
<body class="font-sans text-slate-100 antialiased">

    <div class="relative flex min-h-screen flex-col items-center justify-center overflow-hidden bg-[#05070d] px-4 text-center">

        <!-- Grille lumineuse en filigrane (orbes : body::before / body::after) -->
        <div class="grid-overlay" aria-hidden="true"></div>

        <div class="anim-scale-in relative z-10">
            <!-- Gros chiffre de l'erreur -->
            <p class="font-display text-[9rem] font-bold leading-none tracking-tighter gradient-text" aria-hidden="true">
                404
            </p>

            <!-- Titre -->
            <h1 class="mt-4 text-2xl font-bold text-white">Page introuvable</h1>

            <!-- Explication fournie par le routeur -->
            <p class="mx-auto mt-2 max-w-md text-sm text-slate-400">
                <?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?>
            </p>

            <!-- Bouton de retour -->
            <a href="<?= url('/dashboard') ?>" class="btn btn-primary anim-fade-up d-3 mt-8">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4"><path d="m12 19-7-7 7-7"/><path d="M19 12H5"/></svg>
                Retour au tableau de bord
            </a>
        </div>
    </div>
</body>
</html>