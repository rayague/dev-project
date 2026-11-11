<?php
/**
 * ============================================================
 *  VUE : auth/login.php  (PAGE DE CONNEXION)
 * ============================================================
 *
 *  RÔLE DANS LE MVC :
 *  Affiche le formulaire de connexion. Aucune logique métier :
 *  - les messages d'erreur envoyés par AuthController
 *  - les valeurs déjà saisies ($old) en cas d'échec
 *
 *  Le formulaire est envoyé en POST vers la route /login
 *  (routes.php → AuthController::login()).
 *
 *  VARIABLES REÇUES :
 *   - $errors : erreurs de validation
 *   - $old    : anciennes valeurs saisies
 *
 *  Comptes démo affichés en bas de page :
 *   - admin@ascg.fr / password123  (rôle admin)
 *   - user@example.com / password123 (rôle user, RBAC lecture)
 * ============================================================
 */

$errors = $errors ?? [];
$old    = $old ?? [];

// 1er message d'erreur d'un champ.
$erreurDe = function (string $champ) use ($errors): string {
    return $errors[$champ][0] ?? '';
};

// Classe commune des champs : verre normal OU verre en erreur.
$clsChamp = 'input';
$clsKo    = 'input--err';
?>

<!-- ============================================================
     MARQUE (logo dégradé + anneau)
     ============================================================ -->
<div class="anim-fade-in mb-7 flex flex-col items-center">
    <span class="avatar-notch anim-scale-in h-16 w-16 shadow-xl shadow-emerald-500/10">
        <span class="avatar-core font-display text-2xl font-bold text-emerald-300">G</span>
    </span>
    <span class="mt-4 font-display text-xl font-bold tracking-tight gradient-text"><?= e(APP_NAME) ?></span>
</div>

<!-- ============================================================
     CARTE DE CONNEXION (verre)
     ============================================================ -->
<div class="anim-scale-in glass-card w-full max-w-md overflow-hidden p-8 sm:p-10">

    <!-- Halo décoratif interne -->
    <div class="pointer-events-none absolute -right-20 -top-24 h-52 w-52 rounded-full bg-emerald-500/15 blur-3xl" aria-hidden="true"></div>

    <!-- Titre -->
    <div class="anim-fade-up relative text-center">
        <h1 class="font-display text-2xl font-bold tracking-tight text-white">Se connecter</h1>
        <p class="mt-1.5 text-sm text-slate-400">Accédez à votre espace en quelques secondes</p>
    </div>

    <!-- ==========================================================
         FORMULAIRE DE CONNEXION (POST → AuthController::login)
         ========================================================== -->
    <form method="POST" action="<?= url('/login') ?>" class="anim-fade-up d-1 relative mt-8 space-y-5">

        <!-- Jeton CSRF -->
        <?= csrf_field() ?>

        <!-- CHAMP : Adresse e-mail -->
        <div>
            <label for="email" class="field-label">Adresse e-mail</label>
            <div class="relative">
                <span class="field-ic" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg>
                </span>
                <input
                    type="email"
                    id="email"
                    name="email"
                    value="<?= e($old['email'] ?? '') ?>"
                    required
                    autocomplete="email"
                    placeholder="vous@exemple.com"
                    class="<?= $clsChamp ?> input--pad-ic <?= $erreurDe('email') !== '' ? $clsKo : '' ?>"
                >
            </div>
            <?php if ($erreurDe('email') !== ''): ?>
                <p class="field-err">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" class="h-3.5 w-3.5 shrink-0 mt-px"><circle cx="12" cy="12" r="10"/><path d="M12 8v4"/><path d="M12 16h.01"/></svg>
                    <?= e($erreurDe('email')) ?>
                </p>
            <?php endif; ?>
        </div>

        <!-- CHAMP : Mot de passe -->
        <div>
            <label for="password" class="field-label">Mot de passe</label>
            <div class="relative">
                <span class="field-ic" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5"><path d="M6 11V8a6 6 0 0 1 12 0v3"/><rect x="4" y="11" width="16" height="10" rx="2"/></svg>
                </span>
                <input
                    type="password"
                    id="password"
                    name="password"
                    required
                    autocomplete="current-password"
                    placeholder="••••••••"
                    class="<?= $clsChamp ?> input--pad-ic <?= $erreurDe('password') !== '' ? $clsKo : '' ?>"
                >
            </div>
            <?php if ($erreurDe('password') !== ''): ?>
                <p class="field-err">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" class="h-3.5 w-3.5 shrink-0 mt-px"><circle cx="12" cy="12" r="10"/><path d="M12 8v4"/><path d="M12 16h.01"/></svg>
                    <?= e($erreurDe('password')) ?>
                </p>
            <?php endif; ?>
        </div>

        <!-- BOUTON : Se connecter -->
        <button type="submit" class="btn btn-primary group anim-fade-up d-2 mt-1 w-full !py-3 !text-sm">
            Se connecter
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" class="transition-transform duration-200 group-hover:translate-x-0.5"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
        </button>
    </form>

    <!-- Lien vers l'inscription -->
    <p class="anim-fade-up d-3 mt-7 text-center text-sm text-slate-400">
        Pas encore de compte ?
        <a href="<?= url('/register') ?>" class="font-semibold text-emerald-300 transition hover:text-emerald-200">
            Créer un compte
        </a>
    </p>

    <!-- Comptes de démonstration (admin + utilisateur simple pour le RBAC) -->
    <div class="anim-fade-up d-3 mt-6 space-y-3 rounded-2xl border border-white/[.08] bg-white/[.03] px-4 py-3.5 text-center text-xs text-slate-400">
        <p class="mb-1 font-medium uppercase tracking-wider text-slate-500">Comptes de démonstration</p>
        <!-- Admin : CRUD complet (produits, utilisateurs, etc.) -->
        <p>
            <span class="text-slate-500">Admin</span>
            &nbsp;·&nbsp;
            <span class="font-mono font-semibold text-emerald-300">admin@ascg.fr</span>
            &nbsp;·&nbsp;
            <span class="font-mono font-semibold text-emerald-300">password123</span>
        </p>
        <!-- Utilisateur : lecture produits / catalogue uniquement (pas d'écriture) -->
        <p>
            <span class="text-slate-500">Utilisateur</span>
            &nbsp;·&nbsp;
            <span class="font-mono font-semibold text-sky-300">user@example.com</span>
            &nbsp;·&nbsp;
            <span class="font-mono font-semibold text-sky-300">password123</span>
        </p>
    </div>
</div>

<!-- Signature discrète sous la carte -->
<p class="anim-fade-in d-4 mt-7 text-center text-xs text-slate-500">
    <?= e(APP_NAME) ?> — Projet Web · Université de Corse
</p>