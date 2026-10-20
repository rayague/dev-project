<?php
/**
 * ============================================================
 *  VUE : orders/show.php  (DÉTAIL D'UNE COMMANDE — CRUD : Read)
 * ============================================================
 *
 *  RÔLE DANS LE MVC :
 *  Fiche complète d'UNE commande : toutes ses informations,
 *  sa description, ses métadonnées (auteur, dates) et les
 *  boutons d'action (Modifier / Supprimer).
 *
 *  VARIABLES REÇUES :
 *   - $order : tableau associatif de la ligne SQL complète
 * ============================================================
 */

$order = $order ?? [];
?>

<div class="mx-auto w-full max-w-6xl">

    <!-- En-tête : titre + boutons d'action -->
    <div class="anim-fade-up mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="flex items-center gap-3">
                <h2 class="font-mono text-2xl font-bold tracking-tight text-white"><?= e($order['reference'] ?? '') ?></h2>
                <span class="hidden text-xs text-slate-600 sm:inline">#<?= (int) ($order['id'] ?? 0) ?></span>
            </div>
            <a href="<?= url('/orders') ?>" class="mt-1 inline-flex items-center gap-1 text-sm font-medium text-emerald-300 transition hover:text-emerald-200">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4"><path d="m12 19-7-7 7-7"/><path d="M19 12H5"/></svg>
                Retour à la liste
            </a>
        </div>

        <!-- Boutons d'action -->
        <div class="flex flex-wrap items-center gap-2">
            <a href="<?= url('/orders/edit') ?>?id=<?= (int) ($order['id'] ?? 0) ?>" class="btn btn-warn">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                Modifier
            </a>

            <!-- Suppression en formulaire POST (obligatoire) -->
            <form method="POST" action="<?= url('/orders/delete') ?>"
                  onsubmit="return confirm('Voulez-vous vraiment supprimer cette commande ? Cette action est définitive.');">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int) ($order['id'] ?? 0) ?>">
                <button type="submit" class="btn btn-danger">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M10 11v6"/><path d="M14 11v6"/></svg>
                    Supprimer
                </button>
            </form>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">

        <!-- ============================================================
             COLONNE PRINCIPALE : INFORMATIONS DE LA COMMANDE
             ============================================================ -->
        <div class="glass-card glass-card--flat anim-fade-up d-1 overflow-hidden lg:col-span-2">
            <div class="glass-head">
                <h2 class="glass-kicker">Informations de la commande</h2>
            </div>

            <dl class="divide-y divide-white/5">
                <div class="flex items-center justify-between px-6 py-4">
                    <dt class="flex items-center gap-2 text-sm font-medium text-slate-400">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" class="h-4 w-4" aria-hidden="true"><path d="M12 2v4"/><path d="m16.2 3.8 2 3.4"/><path d="M19 12h4"/><path d="m18.2 18.8-2-3.4"/><path d="M12 22v-4"/><path d="m7.8 20.2-2-3.4"/><path d="M1 12h4"/><path d="m5.8 5.2 2 3.4"/><path d="M12 17a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"/></svg>
                        Référence
                    </dt>
                    <dd class="font-mono text-sm font-semibold text-white"><?= e($order['reference'] ?? '') ?></dd>
                </div>
                <div class="flex items-center justify-between px-6 py-4">
                    <dt class="flex items-center gap-2 text-sm font-medium text-slate-400">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 3.6-6 8-6s8 2 8 6"/></svg>
                        Client
                    </dt>
                    <dd class="text-sm font-semibold text-white"><?= e($order['client'] ?? '') ?></dd>
                </div>
                <div class="flex items-center justify-between px-6 py-4">
                    <dt class="flex items-center gap-2 text-sm font-medium text-slate-400">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg>
                        E-mail
                    </dt>
                    <dd class="text-sm font-semibold">
                        <?php if (!empty($order['email'])): ?>
                            <a href="mailto:<?= e($order['email']) ?>" class="text-emerald-300 transition hover:underline">
                                <?= e($order['email']) ?>
                            </a>
                        <?php else: ?>
                            <span class="text-slate-600">—</span>
                        <?php endif; ?>
                    </dd>
                </div>
                <div class="flex items-center justify-between px-6 py-4">
                    <dt class="flex items-center gap-2 text-sm font-medium text-slate-400">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4"/><path d="M8 2v4"/><path d="M3 10h18"/></svg>
                        Date de commande
                    </dt>
                    <dd class="text-sm font-semibold text-white"><?= date_fr($order['date_commande'] ?? null) ?></dd>
                </div>
                <div class="flex items-center justify-between px-6 py-4">
                    <dt class="flex items-center gap-2 text-sm font-medium text-slate-400">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4" aria-hidden="true"><line x1="12" x2="12" y1="2" y2="22"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                        Montant
                    </dt>
                    <dd class="stat-value text-lg font-bold gradient-text"><?= money($order['montant'] ?? 0) ?></dd>
                </div>
                <div class="flex items-center justify-between px-6 py-4">
                    <dt class="flex items-center gap-2 text-sm font-medium text-slate-400">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4" aria-hidden="true"><path d="m22 7-8.5 8.5-5-5L2 17"/><path d="M16 7h6v6"/></svg>
                        Statut
                    </dt>
                    <dd>
                        <span class="<?= statut_badge($order['statut'] ?? '') ?>">
                            <span class="badge-dot" aria-hidden="true"></span>
                            <?= e(statut_label($order['statut'] ?? '')) ?>
                        </span>
                    </dd>
                </div>
            </dl>

            <!-- Description (affichée seulement si renseignée) -->
            <?php if (!empty($order['description'])): ?>
                <div class="border-t border-white/5 px-6 py-5">
                    <h4 class="glass-kicker">Description</h4>
                    <p class="mt-2 whitespace-pre-line text-sm text-slate-300">
                        <?= nl2br(e($order['description'])) ?>
                    </p>
                </div>
            <?php endif; ?>
        </div>

        <!-- ============================================================
             COLONNE LATÉRALE : MÉTADONNÉES
             ============================================================ -->
        <aside class="glass-card anim-fade-up d-2 relative overflow-hidden p-6">
            <div class="pointer-events-none absolute -right-16 -top-16 h-40 w-40 rounded-full bg-violet-400/15 blur-3xl" aria-hidden="true"></div>

            <h2 class="glass-kicker relative">Détails techniques</h2>

            <dl class="relative mt-6 space-y-6">
                <div>
                    <dt class="text-sm font-medium text-slate-400">Créée par</dt>
                    <dd class="mt-1.5 flex items-center gap-2.5">
                        <span class="avatar-notch h-8 w-8">
                            <span class="avatar-core text-xs font-bold text-emerald-300">
                                <?= e(strtoupper(mb_substr((string) ($order['user_id'] ?? ''), 0, 1))) ?>
                            </span>
                        </span>
                        <span class="text-sm font-semibold text-white">Utilisateur n° <?= (int) ($order['user_id'] ?? 0) ?></span>
                    </dd>
                </div>

                <div>
                    <dt class="text-sm font-medium text-slate-400">Identifiant (ID)</dt>
                    <dd class="mt-1.5 font-mono text-sm text-slate-200">#<?= (int) ($order['id'] ?? 0) ?></dd>
                </div>

                <div>
                    <dt class="text-sm font-medium text-slate-400">Enregistrée le</dt>
                    <dd class="mt-1.5 text-sm text-slate-200">
                        <?php if (!empty($order['created_at'])): ?>
                            <?= e(date('d/m/Y à H:i', strtotime($order['created_at']))) ?>
                        <?php else: ?>
                            <span class="text-slate-600">—</span>
                        <?php endif; ?>
                    </dd>
                </div>

                <div>
                    <dt class="text-sm font-medium text-slate-400">Dernière modification</dt>
                    <dd class="mt-1.5 text-sm text-slate-200">
                        <?php if (!empty($order['updated_at'])): ?>
                            <?= e(date('d/m/Y à H:i', strtotime($order['updated_at']))) ?>
                        <?php else: ?>
                            <span class="text-slate-600">Jamais modifiée</span>
                        <?php endif; ?>
                    </dd>
                </div>
            </dl>
        </aside>
    </div>
</div>