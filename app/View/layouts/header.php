<?php
/**
 * ============================================================
 *  VUE : layouts/header.php  (DÉBUT DE TOUTES LES PAGES)
 * ============================================================
 *
 *  RÔLE DANS LE MVC :
 *  Gabarit commun du site : <!DOCTYPE html>, <head> (Tailwind en CDN
 *  + feuille de style maisonnée « Aurore ») et le début du <body>.
 *  Deux présentations possibles :
 *   - Visiteur NON connecté  : page centrée sur fond d'orbes animés.
 *   - Utilisateur CONNECTÉ   : barre latérale en verre + barre du haut.
 *
 *  Le style est chargé depuis public/assets/css/app.css (fichier
 *  statique servi normalement par Apache comme par le serveur PHP).
 *
 *  La fin du HTML est dans layouts/footer.php (mêmes structures à fermer).
 * ============================================================
 */

$title   = $title   ?? APP_NAME;   // Titre de la page
$errors  = $errors  ?? [];         // Erreurs de formulaire
$success = $success ?? [];         // Messages de succès

// ----------------------------------------------------------------
// DÉTECTION DE LA ROUTE COURANTE (pour surligner le lien du menu actif)
// ----------------------------------------------------------------
// Sous Apache, la route arrive dans $_GET['__path'] ; sinon on lit
// REQUEST_URI puis on retire le préfixe "/projet-web" du sous-dossier.
$routeCourante = '';
if (isset($_GET['__path']) && is_string($_GET['__path'])) {
    $routeCourante = '/' . trim($_GET['__path'], '/');
} else {
    $routeCourante = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
}
$prefixeBase = defined('BASE_URL') ? BASE_URL : '';
if ($prefixeBase !== '' && strpos($routeCourante, $prefixeBase) === 0) {
    $routeCourante = substr($routeCourante, strlen($prefixeBase)) ?: '/';
}

// Fonction : la route courante commence-t-elle par $prefixe ?
$estActif = function (string $prefixe) use ($routeCourante): bool {
    if ($prefixe === '/') {
        // La racine "/" et "/dashboard" affichent la même page.
        return $routeCourante === '/' || $routeCourante === '/dashboard';
    }
    $debut = rtrim($prefixe, '/');
    return $routeCourante === $debut || strpos($routeCourante, $debut . '/') === 0;
};
?>
<!DOCTYPE html>
<html lang="fr" class="dark">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#05070d">
    <title><?= e($title) ?> · <?= e(APP_NAME) ?></title>

    <!-- Polices : Inter (texte) + Space Grotesk (titres/chiffres) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">

    <!-- Feuille de style du design system (verre, boutons, animations…) -->
    <link rel="stylesheet" href="<?= e(url('/assets/css/app.css')) ?>">

    <!-- Tailwind CSS (CDN) -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Configuration Tailwind : polices du site -->
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'ui-sans-serif', 'system-ui', 'sans-serif'],
                        display: ['Space Grotesk', 'Inter', 'sans-serif'],
                    }
                }
            }
        }
    </script>
</head>

<?php if (Auth::check()): ?>
    <!-- ============================================================
         UTILISATEUR CONNECTÉ → SIDEBAR + BARRE DU HAUT
         ============================================================ -->
    <body class="app-shell font-sans antialiased">

        <div class="app-shell mx-auto flex min-h-screen w-full max-w-[1500px]">

            <!-- ====================================================
                 BARRE LATÉRALE (verre sombre, navigation principale)
                 ==================================================== -->
            <aside class="sidebar hidden md:flex">

                <!-- Logo / nom du site -->
                <div class="flex items-center gap-3 px-1 pb-6">
                    <span class="logo-mark" aria-hidden="true">G</span>
                    <span class="font-display text-lg font-bold tracking-tight gradient-text"><?= e(APP_NAME) ?></span>
                </div>

                <!-- Liens de navigation -->
                <nav class="nav flex-1">
                    <a href="<?= url('/dashboard') ?>" class="nav-item <?= $estActif('/') ? 'active' : '' ?>">
                        <svg class="nav-ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><path d="M9 22V12h6v10"/></svg>
                        <span>Tableau de bord</span>
                    </a>
                    <a href="<?= url('/orders') ?>" class="nav-item <?= $estActif('/orders') ? 'active' : '' ?>">
                        <svg class="nav-ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><path d="M3.3 7 12 12l8.7-5"/><path d="M12 22V12"/></svg>
                        <span>Commandes</span>
                    </a>
                    <a href="<?= url('/products') ?>" class="nav-item <?= $estActif('/products') ? 'active' : '' ?>">
                        <svg class="nav-ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m7.5 4.27 9 5.15"/><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/></svg>
                        <span>Produits</span>
                    </a>
                    <?php if (Auth::isAdmin()): ?>
                    <a href="<?= url('/users') ?>" class="nav-item <?= $estActif('/users') ? 'active' : '' ?>">
                        <svg class="nav-ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                        <span>Utilisateurs</span>
                    </a>
                    <?php else: ?>
                    <a href="<?= url('/users/show?id=' . (int) Auth::id()) ?>" class="nav-item <?= $routeCourante === '/users/show' && (int) ($_GET['id'] ?? 0) === Auth::id() ? 'active' : '' ?>">
                        <svg class="nav-ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg>
                        <span>Mon profil</span>
                    </a>
                    <?php endif; ?>
                </nav>

                <!-- Pied de barre : identité de l'utilisateur -->
                <div class="mt-6 border-t border-white/5 px-1 pt-5">
                    <div class="flex items-center gap-3">
                        <div class="avatar-notch w-11 h-11">
                            <div class="avatar-core text-sm <?= Auth::isAdmin() ? 'text-amber-300' : 'text-emerald-300' ?>">
                                <?= e(strtoupper(mb_substr(Auth::name(), 0, 1))) ?>
                            </div>
                        </div>
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-white"><?= e(Auth::name()) ?></p>
                            <p class="truncate text-xs text-slate-400"><?= e($_SESSION['user_email'] ?? '') ?></p>
                        </div>
                    </div>
                    <span class="role-chip mt-3 <?= Auth::isAdmin() ? 'role-chip--admin' : 'role-chip--user' ?>">
                        <span class="badge-dot"></span>
                        <?= Auth::isAdmin() ? 'Administrateur' : 'Utilisateur' ?>
                    </span>
                </div>
            </aside>

            <!-- ====================================================
                 COLONNE PRINCIPALE (barre du haut + contenu)
                 ==================================================== -->
            <div class="flex min-w-0 flex-1 flex-col">

                <!-- Barre du haut (verre collant) -->
                <header class="topbar">
                    <div class="flex min-w-0 items-center gap-3">
                        <span class="lg:hidden">
                            <span class="logo-mark !h-9 !w-9 !text-base" aria-hidden="true">G</span>
                        </span>
                        <h1 class="truncate font-display text-lg font-bold tracking-tight text-white"><?= e($title) ?></h1>
                    </div>

                    <div class="flex items-center gap-3">
                        <span class="hidden text-xs text-slate-400 xl:block"><?= e(date('l d F Y')) ?></span>

                        <!-- Identité courte -->
                        <div class="hidden items-center gap-2.5 sm:flex">
                            <span class="avatar-notch h-9 w-9">
                                <span class="avatar-core text-xs <?= Auth::isAdmin() ? 'text-amber-300' : 'text-emerald-300' ?>"><?= e(strtoupper(mb_substr(Auth::name(), 0, 1))) ?></span>
                            </span>
                            <span class="hidden leading-tight lg:block">
                                <span class="block text-xs font-semibold text-white"><?= e(Auth::name()) ?></span>
                                <span class="block text-[11px] <?= Auth::isAdmin() ? 'text-amber-300/80' : 'text-emerald-300/80' ?>"><?= Auth::isAdmin() ? 'Administrateur' : 'Utilisateur' ?></span>
                            </span>
                        </div>

                        <!-- Formulaire de déconnexion (POST + CSRF) -->
                        <form method="POST" action="<?= url('/logout') ?>">
                            <?= csrf_field() ?>
                            <button type="submit" title="Se déconnecter" class="btn btn-ghost">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5"/><path d="M21 12H9"/></svg>
                                <span class="hidden sm:inline">Déconnexion</span>
                            </button>
                        </form>
                    </div>
                </header>

                <!-- Navigation mobile (visible uniquement en petit écran) -->
                <nav class="flex gap-2 border-b border-white/5 bg-white/[.03] px-4 py-2.5 md:hidden">
                    <a href="<?= url('/dashboard') ?>" class="nav-item !px-3 !py-2 flex-1 justify-center !text-sm <?= $estActif('/') ? 'active' : '' ?>">Dashboard</a>
                    <a href="<?= url('/orders') ?>" class="nav-item !px-3 !py-2 flex-1 justify-center !text-sm <?= $estActif('/orders') ? 'active' : '' ?>">Commandes</a>
                    <a href="<?= url('/products') ?>" class="nav-item !px-3 !py-2 flex-1 justify-center !text-sm <?= $estActif('/products') ? 'active' : '' ?>">Produits</a>
                    <?php if (Auth::isAdmin()): ?>
                    <a href="<?= url('/users') ?>" class="nav-item !px-3 !py-2 flex-1 justify-center !text-sm <?= $estActif('/users') ? 'active' : '' ?>">Utilisateurs</a>
                    <?php else: ?>
                    <a href="<?= url('/users/show?id=' . (int) Auth::id()) ?>" class="nav-item !px-3 !py-2 flex-1 justify-center !text-sm">Profil</a>
                    <?php endif; ?>
                </nav>

                <!-- Contenu de la page (la vue demandée s'insérera ici) -->
                <main class="flex-1 px-4 py-7 sm:px-6 lg:px-10 lg:py-9">
<?php else: ?>
    <!-- ============================================================
         VISITEUR NON CONNECTÉ → PAGE CENTRÉE (orbes animés)
         ============================================================ -->
    <body class="font-sans antialiased">

        <!-- Conteneur centré : la vue de connexion/inscription s'insère ici -->
        <main class="relative z-10 flex min-h-screen w-full flex-col items-center justify-center px-4 py-10">
<?php endif; ?>

<?php
// ============================================================
// MESSAGES GÉNÉRAUX (flash messages) — affichés dans la zone contenu
// ============================================================
if (!empty($success)) {
    foreach ($success as $message) {
        ?>
        <div class="anim-fade-up mb-5 flex w-full items-start gap-3 rounded-2xl border border-emerald-400/20 bg-emerald-400/10 px-4 py-3.5 text-sm text-emerald-200 shadow-lg shadow-black/20 backdrop-blur">
            <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-emerald-400 text-emerald-950" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="h-3 w-3"><path d="M20 6 9 17l-5-5"/></svg>
            </span>
            <span><?= e($message) ?></span>
        </div>
        <?php
    }
}

if (!empty($errors['_global'])) {
    foreach ($errors['_global'] as $message) {
        ?>
        <div class="anim-fade-up mb-5 flex w-full items-start gap-3 rounded-2xl border border-red-400/20 bg-red-400/10 px-4 py-3.5 text-sm text-red-200 shadow-lg shadow-black/20 backdrop-blur">
            <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-red-400 text-red-950" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" class="h-3 w-3"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
            </span>
            <span><?= e($message) ?></span>
        </div>
        <?php
    }
}
?>