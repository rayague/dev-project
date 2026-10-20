<?php
/**
 * ============================================================
 *  VUE : orders/create.php  (FORMULAIRE DE CRÉATION — CRUD : Create)
 * ============================================================
 *
 *  RÔLE DANS LE MVC :
 *  Formulaire de saisie d'une NOUVELLE commande.
 *  POST /orders → OrderController::store().
 *
 *  VARIABLES REÇUES :
 *   - $old     : anciennes valeurs saisies (après erreur)
 *   - $errors  : messages d'erreur par champ
 *   - $statuts : statuts autorisés (liste déroulante)
 * ============================================================
 */

$old     = $old ?? [];
$errors  = $errors ?? [];
$statuts = $statuts ?? Order::statuts();

$valeur = function (string $champ, string $defaut = '') use ($old): string {
    return (string) ($old[$champ] ?? $defaut);
};

$erreurDe = function (string $champ) use ($errors): string {
    return $errors[$champ][0] ?? '';
};
?>

<div class="mx-auto w-full max-w-4xl">

    <!-- En-tête de page -->
    <div class="anim-fade-up mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="font-display text-2xl font-bold tracking-tight text-white">Nouvelle commande</h2>
            <a href="<?= url('/orders') ?>" class="mt-1 inline-flex items-center gap-1 text-sm font-medium text-emerald-300 transition hover:text-emerald-200">
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
        <!-- Bandeau de section -->
        <div class="glass-head">
            <div>
                <h2 class="glass-kicker">Informations de la commande</h2>
                <p class="mt-0.5 text-xs text-slate-500">Les champs marqués d'un <span class="font-semibold text-red-400">*</span> sont obligatoires.</p>
            </div>
        </div>

        <form method="POST" action="<?= url('/orders') ?>" class="space-y-5 px-6 py-7 sm:px-8">
            <?= csrf_field() ?>

            <!-- LIGNE 1 : Référence (auto) + Client -->
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <div class="anim-fade-up d-1">
                    <span class="field-label">Référence</span>
                    <div class="flex items-center gap-3 rounded-2xl border border-white/10 bg-white/[.04] px-4 py-3">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-emerald-400/15 text-emerald-300 ring-1 ring-inset ring-emerald-400/30" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5"><path d="M12 3v3"/><path d="M18.4 5.6 16.2 7.8"/><path d="M21 12h-3"/><path d="m18.4 18.4-2.2-2.2"/><path d="M12 21v-3"/><path d="m5.6 18.4 2.2-2.2"/><path d="M3 12h3"/><path d="m5.6 5.6 2.2 2.2"/><path d="M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"/></svg>
                        </span>
                        <span class="leading-tight">
                            <span class="block text-sm font-semibold text-white">Générée automatiquement</span>
                            <span class="block text-xs text-slate-400">Format CMD-AAAA-NNN</span>
                        </span>
                    </div>
                </div>

                <div class="anim-fade-up d-2">
                    <label for="client" class="field-label">Client <span class="req">*</span></label>
                    <div class="relative">
                        <span class="field-ic" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 3.6-6 8-6s8 2 8 6"/></svg>
                        </span>
                        <input type="text" id="client" name="client"
                               value="<?= e($valeur('client')) ?>"
                               required placeholder="Nom du client"
                               class="input input--pad-ic <?= $erreurDe('client') !== '' ? 'input--err' : '' ?>">
                    </div>
                    <?php if ($erreurDe('client') !== ''): ?>
                        <p class="field-err"><?= e($erreurDe('client')) ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- LIGNE 2 : E-mail + Date -->
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <div class="anim-fade-up d-2">
                    <label for="email" class="field-label">
                        E-mail du client <span class="font-normal text-slate-500">(facultatif)</span>
                    </label>
                    <div class="relative">
                        <span class="field-ic" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg>
                        </span>
                        <input type="email" id="email" name="email"
                               value="<?= e($valeur('email')) ?>"
                               placeholder="client@exemple.com"
                               class="input input--pad-ic <?= $erreurDe('email') !== '' ? 'input--err' : '' ?>">
                    </div>
                    <?php if ($erreurDe('email') !== ''): ?>
                        <p class="field-err"><?= e($erreurDe('email')) ?></p>
                    <?php endif; ?>
                </div>

                <div class="anim-fade-up d-3">
                    <label for="date_commande" class="field-label">Date de commande <span class="req">*</span></label>
                    <div class="relative">
                        <span class="field-ic" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4"/><path d="M8 2v4"/><path d="M3 10h18"/></svg>
                        </span>
                        <input type="date" id="date_commande" name="date_commande"
                               value="<?= e($valeur('date_commande', date('Y-m-d'))) ?>"
                               required
                               class="input input--pad-ic <?= $erreurDe('date_commande') !== '' ? 'input--err' : '' ?>">
                    </div>
                    <?php if ($erreurDe('date_commande') !== ''): ?>
                        <p class="field-err"><?= e($erreurDe('date_commande')) ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- LIGNE 3 : Montant + Statut -->
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <div class="anim-fade-up d-3">
                    <label for="montant" class="field-label">Montant (€) <span class="req">*</span></label>
                    <div class="relative">
                        <span class="field-ic" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5"><line x1="12" x2="12" y1="2" y2="22"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                        </span>
                        <input type="number" id="montant" name="montant" step="0.01" min="0.01"
                               value="<?= e($valeur('montant')) ?>"
                               required placeholder="0.00"
                               class="input input--pad-ic <?= $erreurDe('montant') !== '' ? 'input--err' : '' ?>">
                    </div>
                    <?php if ($erreurDe('montant') !== ''): ?>
                        <p class="field-err"><?= e($erreurDe('montant')) ?></p>
                    <?php endif; ?>
                </div>

                <div class="anim-fade-up d-4">
                    <label for="statut" class="field-label">Statut <span class="req">*</span></label>
                    <select id="statut" name="statut" required
                            class="input <?= $erreurDe('statut') !== '' ? 'input--err' : '' ?>">
                        <?php foreach ($statuts as $code): ?>
                            <option value="<?= e($code) ?>" <?= $valeur('statut', 'en_attente') === $code ? 'selected' : '' ?>>
                                <?= e(statut_label($code)) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if ($erreurDe('statut') !== ''): ?>
                        <p class="field-err"><?= e($erreurDe('statut')) ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- CHAMP : Description -->
            <div class="anim-fade-up d-4">
                <label for="description" class="field-label">
                    Description <span class="font-normal text-slate-500">(facultatif)</span>
                </label>
                <textarea id="description" name="description" rows="4"
                          placeholder="Remarques, détails de la commande..."
                          class="input <?= $erreurDe('description') !== '' ? 'input--err' : '' ?>"><?= e($valeur('description')) ?></textarea>
                <?php if ($erreurDe('description') !== ''): ?>
                    <p class="field-err"><?= e($erreurDe('description')) ?></p>
                <?php endif; ?>
            </div>

            <!-- BOUTONS D'ACTION -->
            <div class="anim-fade-up d-5 flex flex-wrap items-center gap-3 border-t border-white/5 pt-6">
                <button type="submit" class="btn btn-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><path d="M17 21v-8H7v8"/><path d="M7 3v5h8"/></svg>
                    Enregistrer la commande
                </button>
                <a href="<?= url('/orders') ?>" class="btn btn-ghost">Annuler</a>
            </div>
        </form>
    </div>
</div>