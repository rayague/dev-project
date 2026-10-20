<?php
/**
 * ============================================================
 *  VUE : auth/register.php  (PAGE D'INSCRIPTION)
 * ============================================================
 *
 *  RÔLE DANS LE MVC :
 *  Formulaire de création de compte. Simple page HTML : toute la
 *  logique est dans AuthController::register().
 *
 *  VARIABLES REÇUES :
 *   - $errors : erreurs de validation à afficher en rouge
 *   - $old    : valeurs déjà saisies (nom, e-mail)
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
     CARTE D'INSCRIPTION (verre)
     ============================================================ -->
<div class="anim-scale-in glass-card w-full max-w-md overflow-hidden p-8 sm:p-10">

    <!-- Halo décoratif interne -->
    <div class="pointer-events-none absolute -right-20 -top-24 h-52 w-52 rounded-full bg-emerald-500/15 blur-3xl" aria-hidden="true"></div>

    <!-- Titre -->
    <div class="anim-fade-up relative text-center">
        <h1 class="font-display text-2xl font-bold tracking-tight text-white">Créer un compte</h1>
        <p class="mt-1.5 text-sm text-slate-400">Rejoignez <?= e(APP_NAME) ?> en quelques secondes</p>
    </div>

    <!-- ==========================================================
         FORMULAIRE D'INSCRIPTION (POST → AuthController::register)
         ========================================================== -->
    <form method="POST" action="<?= url('/register') ?>" class="anim-fade-up d-1 relative mt-8 space-y-5">

        <!-- Jeton CSRF -->
        <?= csrf_field() ?>

        <!-- CHAMP : Nom complet -->
        <div>
            <label for="name" class="field-label">Nom complet <span class="req">*</span></label>
            <div class="relative">
                <span class="field-ic" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 3.6-6 8-6s8 2 8 6"/></svg>
                </span>
                <input
                    type="text"
                    id="name"
                    name="name"
                    value="<?= e($old['name'] ?? '') ?>"
                    required
                    autocomplete="name"
                    placeholder="Marie Corse"
                    class="<?= $clsChamp ?> input--pad-ic <?= $erreurDe('name') !== '' ? $clsKo : '' ?>"
                >
            </div>
            <?php if ($erreurDe('name') !== ''): ?>
                <p class="field-err">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" class="h-3.5 w-3.5 shrink-0 mt-px"><circle cx="12" cy="12" r="10"/><path d="M12 8v4"/><path d="M12 16h.01"/></svg>
                    <?= e($erreurDe('name')) ?>
                </p>
            <?php endif; ?>
        </div>

        <!-- CHAMP : Adresse e-mail -->
        <div>
            <label for="email" class="field-label">Adresse e-mail <span class="req">*</span></label>
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
            <label for="password" class="field-label">Mot de passe <span class="req">*</span></label>
            <div class="relative">
                <span class="field-ic" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5"><path d="M6 11V8a6 6 0 0 1 12 0v3"/><rect x="4" y="11" width="16" height="10" rx="2"/></svg>
                </span>
                <input
                    type="password"
                    id="password"
                    name="password"
                    required
                    minlength="6"
                    autocomplete="new-password"
                    placeholder="6 caractères minimum"
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

        <!-- CHAMP : Confirmation du mot de passe -->
        <div>
            <label for="password_confirmation" class="field-label">Confirmer le mot de passe <span class="req">*</span></label>
            <div class="relative">
                <span class="field-ic" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5"><path d="M6 11V8a6 6 0 0 1 12 0v3"/><rect x="4" y="11" width="16" height="10" rx="2"/><path d="m9 16 2 2 4-4"/></svg>
                </span>
                <input
                    type="password"
                    id="password_confirmation"
                    name="password_confirmation"
                    required
                    autocomplete="new-password"
                    placeholder="Retapez le même mot de passe"
                    class="<?= $clsChamp ?> input--pad-ic <?= $erreurDe('password_confirmation') !== '' ? $clsKo : '' ?>"
                >
            </div>
            <?php if ($erreurDe('password_confirmation') !== ''): ?>
                <p class="field-err">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" class="h-3.5 w-3.5 shrink-0 mt-px"><circle cx="12" cy="12" r="10"/><path d="M12 8v4"/><path d="M12 16h.01"/></svg>
                    <?= e($erreurDe('password_confirmation')) ?>
                </p>
            <?php endif; ?>
        </div>

        <!-- BOUTON : Créer mon compte -->
        <button type="submit" class="btn btn-primary anim-fade-up d-2 mt-1 w-full !py-3 !text-sm">
            Créer mon compte
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
        </button>
    </form>

    <!-- Lien vers la connexion -->
    <p class="anim-fade-up d-3 mt-7 text-center text-sm text-slate-400">
        Déjà un compte ?
        <a href="<?= url('/login') ?>" class="font-semibold text-emerald-300 transition hover:text-emerald-200">
            Se connecter
        </a>
    </p>
</div>

<!-- Signature discrète sous la carte -->
<p class="anim-fade-in d-4 mt-7 text-center text-xs text-slate-500">
    <?= e(APP_NAME) ?> — Projet Web · Université de Corse
</p>