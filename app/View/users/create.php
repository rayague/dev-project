<?php
/**
 * ============================================================
 *  VUE : users/create.php  (CRÉATION D'UN UTILISATEUR — Create)
 * ============================================================
 *
 *  RÔLE DANS LE MVC :
 *  Formulaire d'ajout manuel d'un compte.
 *  POST /users → UserController::store().
 *
 *  VARIABLES REÇUES :
 *   - $old    : valeurs déjà saisies (après erreur)
 *   - $errors : messages d'erreur par champ
 * ============================================================
 */

$old    = $old ?? [];
$errors = $errors ?? [];

$valeur = function (string $champ) use ($old): string {
    return (string) ($old[$champ] ?? '');
};

$erreurDe = function (string $champ) use ($errors): string {
    return $errors[$champ][0] ?? '';
};
?>

<div class="mx-auto w-full max-w-2xl">

    <!-- En-tête de page -->
    <div class="anim-fade-up mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="font-display text-2xl font-bold tracking-tight text-white">Nouvel utilisateur</h2>
            <a href="<?= url('/users') ?>" class="mt-1 inline-flex items-center gap-1 text-sm font-medium text-emerald-300 transition hover:text-emerald-200">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4"><path d="m12 19-7-7 7-7"/><path d="M19 12H5"/></svg>
                Retour à la liste
            </a>
        </div>
        <span class="inline-flex w-fit items-center gap-1.5 rounded-full bg-emerald-400/10 px-3 py-1 text-xs font-semibold text-emerald-300 ring-1 ring-inset ring-emerald-400/25">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-3.5 w-3.5"><path d="M12 5v14"/><path d="M5 12h14"/></svg>
            Création
        </span>
    </div>

    <!-- ============================================================
         FORMULAIRE DE CRÉATION
         ============================================================ -->
    <div class="glass-card glass-card--flat anim-fade-up d-1 overflow-hidden">
        <div class="glass-head">
            <h2 class="glass-kicker">Informations du compte</h2>
            <p class="mt-0.5 text-xs text-slate-500">Les champs marqués d'un <span class="font-semibold text-red-400">*</span> sont obligatoires.</p>
        </div>

        <form method="POST" action="<?= url('/users') ?>" class="space-y-5 px-6 py-7 sm:px-8">
            <?= csrf_field() ?>

            <!-- CHAMP : Nom complet -->
            <div class="anim-fade-up d-1">
                <label for="name" class="field-label">Nom complet <span class="req">*</span></label>
                <div class="relative">
                    <span class="field-ic" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 3.6-6 8-6s8 2 8 6"/></svg>
                    </span>
                    <input type="text" id="name" name="name"
                           value="<?= e($valeur('name')) ?>"
                           required placeholder="Nom et prénom"
                           class="input input--pad-ic <?= $erreurDe('name') !== '' ? 'input--err' : '' ?>">
                </div>
                <?php if ($erreurDe('name') !== ''): ?>
                    <p class="field-err"><?= e($erreurDe('name')) ?></p>
                <?php endif; ?>
            </div>

            <!-- CHAMP : Adresse e-mail -->
            <div class="anim-fade-up d-2">
                <label for="email" class="field-label">Adresse e-mail <span class="req">*</span></label>
                <div class="relative">
                    <span class="field-ic" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg>
                    </span>
                    <input type="email" id="email" name="email"
                           value="<?= e($valeur('email')) ?>"
                           required placeholder="utilisateur@exemple.com"
                           class="input input--pad-ic <?= $erreurDe('email') !== '' ? 'input--err' : '' ?>">
                </div>
                <?php if ($erreurDe('email') !== ''): ?>
                    <p class="field-err"><?= e($erreurDe('email')) ?></p>
                <?php endif; ?>
            </div>

            <!-- CHAMP : Mot de passe -->
            <div class="anim-fade-up d-3">
                <label for="password" class="field-label">Mot de passe <span class="req">*</span></label>
                <div class="relative">
                    <span class="field-ic" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5"><path d="M6 11V8a6 6 0 0 1 12 0v3"/><rect x="4" y="11" width="16" height="10" rx="2"/></svg>
                    </span>
                    <input type="password" id="password" name="password"
                           required minlength="6" autocomplete="new-password"
                           placeholder="6 caractères minimum"
                           class="input input--pad-ic <?= $erreurDe('password') !== '' ? 'input--err' : '' ?>">
                </div>
                <?php if ($erreurDe('password') !== ''): ?>
                    <p class="field-err"><?= e($erreurDe('password')) ?></p>
                <?php endif; ?>
            </div>

            <!-- CHAMP : Rôle (RBAC) -->
            <div class="anim-fade-up d-4">
                <label for="role" class="field-label">Rôle <span class="req">*</span></label>
                <select id="role" name="role" required class="input">
                    <option value="user" <?= ($old['role'] ?? 'user') === 'user' ? 'selected' : '' ?>>Utilisateur — accès limité (lecture &amp; commandes)</option>
                    <option value="admin" <?= ($old['role'] ?? '') === 'admin' ? 'selected' : '' ?>>Administrateur — accès complet</option>
                </select>
                <p class="mt-1.5 text-xs text-slate-500">Seul un administrateur peut gérer les comptes et leurs rôles.</p>
            </div>

            <!-- BOUTONS D'ACTION -->
            <div class="anim-fade-up d-5 flex flex-wrap items-center gap-3 border-t border-white/5 pt-6">
                <button type="submit" class="btn btn-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><path d="M17 21v-8H7v8"/><path d="M7 3v5h8"/></svg>
                    Créer l'utilisateur
                </button>
                <a href="<?= url('/users') ?>" class="btn btn-ghost">Annuler</a>
            </div>
        </form>
    </div>
</div>