<?php
/**
 * ============================================================
 *  VUE : users/show.php  (FICHE UTILISATEUR — Read)
 * ============================================================
 *
 *  RÔLE DANS LE MVC :
 *  Fiche complète d'un compte : nom, e-mail, date d'inscription,
 *  nombre de commandes créées, et boutons Modifier / Supprimer.
 *
 *  VARIABLES REÇUES :
 *   - $user     : colonnes de l'utilisateur
 *   - $nbOrders : nombre de commandes créées par cet utilisateur
 * ============================================================
 */

$user     = $user ?? [];
$nbOrders = $nbOrders ?? 0;
?>

<div class="mx-auto w-full max-w-6xl">

    <!-- En-tête : titre + boutons d'action -->
    <div class="anim-fade-up mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-4">
            <!-- Pastille initiale -->
            <span class="avatar-notch h-12 w-12">
                <span class="avatar-core text-lg font-extrabold text-emerald-300">
                    <?= e(strtoupper(mb_substr((string) ($user['name'] ?? ''), 0, 1))) ?>
                </span>
            </span>
            <div>
                <h2 class="font-display text-2xl font-bold tracking-tight text-white"><?= e($user['name'] ?? '') ?></h2>
                <a href="<?= url('/users') ?>" class="mt-0.5 inline-flex items-center gap-1 text-sm font-medium text-emerald-300 transition hover:text-emerald-200">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4"><path d="m12 19-7-7 7-7"/><path d="M19 12H5"/></svg>
                    Retour à la liste
                </a>
            </div>
        </div>

        <!-- Boutons d'action -->
        <div class="flex flex-wrap items-center gap-2">
            <a href="<?= url('/users/edit') ?>?id=<?= (int) ($user['id'] ?? 0) ?>" class="btn btn-warn">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                Modifier
            </a>

            <!-- Suppression en formulaire POST -->
            <form method="POST" action="<?= url('/users/delete') ?>"
                  onsubmit="return confirm('Supprimer définitivement cet utilisateur ?');">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int) ($user['id'] ?? 0) ?>">
                <button type="submit" class="btn btn-danger">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4"><path d="M3 6h18"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M10 11v6"/><path d="M14 11v6"/></svg>
                    Supprimer
                </button>
            </form>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">

        <!-- ============================================================
             BLOC PRINCIPAL : INFORMATIONS DU COMPTE
             ============================================================ -->
        <div class="glass-card glass-card--flat anim-fade-up d-1 overflow-hidden lg:col-span-2">
            <div class="glass-head">
                <h2 class="glass-kicker">Informations du compte</h2>
            </div>

            <dl class="divide-y divide-white/5">
                <div class="flex items-center justify-between px-6 py-4">
                    <dt class="flex items-center gap-2 text-sm font-medium text-slate-400">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4" aria-hidden="true"><path d="M10 2v4"/><path d="M10 22v-4"/><path d="M4 6h15"/><path d="M5 6v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V6"/></svg>
                        Identifiant (ID)
                    </dt>
                    <dd class="font-mono text-sm font-semibold text-white">#<?= (int) ($user['id'] ?? 0) ?></dd>
                </div>
                <div class="flex items-center justify-between px-6 py-4">
                    <dt class="flex items-center gap-2 text-sm font-medium text-slate-400">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 3.6-6 8-6s8 2 8 6"/></svg>
                        Nom complet
                    </dt>
                    <dd class="text-sm font-semibold text-white"><?= e($user['name'] ?? '') ?></dd>
                </div>
                <div class="flex items-center justify-between px-6 py-4">
                    <dt class="flex items-center gap-2 text-sm font-medium text-slate-400">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg>
                        Adresse e-mail
                    </dt>
                    <dd class="text-sm font-semibold">
                        <a href="mailto:<?= e($user['email'] ?? '') ?>" class="text-emerald-300 transition hover:underline">
                            <?= e($user['email'] ?? '') ?>
                        </a>
                    </dd>
                </div>
                <div class="flex items-center justify-between px-6 py-4">
                    <dt class="flex items-center gap-2 text-sm font-medium text-slate-400">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 3.6-6 8-6s8 2 8 6"/><path d="m20 3 4 4"/><path d="m22 5-4 4"/></svg>
                        Inscrit le
                    </dt>
                    <dd class="text-sm text-slate-300">
                        <?= !empty($user['created_at'])
                            ? e(date('d/m/Y à H:i', strtotime($user['created_at'])))
                            : '—' ?>
                    </dd>
                </div>
            </dl>
        </div>

        <!-- ============================================================
             BLOC LATÉRAL : STATISTIQUES DE L'UTILISATEUR
             ============================================================ -->
        <aside class="glass-card anim-fade-up d-2 relative overflow-hidden p-6">
            <div class="pointer-events-none absolute -right-16 -top-16 h-40 w-40 rounded-full bg-emerald-400/15 blur-3xl" aria-hidden="true"></div>

            <h3 class="glass-kicker relative">Activité</h3>

            <div class="relative mt-5 flex items-end gap-3">
                <p class="stat-value text-5xl font-bold gradient-text"><?= (int) $nbOrders ?></p>
                <p class="pb-1.5 text-sm text-slate-400">commande(s)<br>créée(s)</p>
            </div>

            <a href="<?= url('/orders') ?>"
               class="btn btn-primary relative mt-7 w-full">
                Voir les commandes
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
            </a>
        </aside>
    </div>
</div>