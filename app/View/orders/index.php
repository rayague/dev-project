<?php
/**
 * ============================================================
 *  VUE : orders/index.php  (LISTE DES COMMANDES — CRUD : READ)
 * ============================================================
 *
 *  RÔLE DANS LE MVC :
 *  Liste toutes les commandes avec recherche, filtre par statut,
 *  tableau d'actions (Voir / Modifier / Supprimer) et pagination.
 *
 *  VARIABLES REÇUES :
 *   - $orders : commandes de la page courante
 *   - $total  : nombre total (filtres compris)
 *   - $pages  : nombre de pages
 *   - $page   : page courante
 *   - $search : terme de recherche en cours
 *   - $statut : filtre de statut en cours
 * ============================================================
 */

$orders = $orders ?? [];
$total  = $total ?? 0;
$pages  = $pages ?? 1;
$page   = $page ?? 1;
$search = $search ?? '';
$statut = $statut ?? '';
?>

<div class="mx-auto w-full max-w-6xl">

    <!-- ============================================================
         EN-TÊTE DE PAGE : titre + bouton "Nouvelle commande"
         ============================================================ -->
    <div class="anim-fade-up mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="font-display text-2xl font-bold tracking-tight text-white">Commandes</h2>
            <p class="mt-1 text-sm text-slate-400"><?= (int) $total ?> commande(s) trouvée(s)</p>
        </div>

        <a href="<?= url('/orders/create') ?>" class="btn btn-primary anim-fade-up d-1">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" class="transition-transform duration-200 group-hover:rotate-90"><path d="M12 5v14"/><path d="M5 12h14"/></svg>
            Nouvelle commande
        </a>
    </div>

    <!-- ============================================================
         BLOC DE RECHERCHE ET DE FILTRE (formulaire GET)
         ============================================================ -->
    <form method="GET" action="<?= url('/orders') ?>" class="glass-card anim-fade-up d-1 mb-6 p-4 sm:p-5">
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-4">
            <div class="sm:col-span-2">
                <label for="search" class="field-label !text-xs">Rechercher</label>
                <div class="relative">
                    <span class="field-ic" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.35-4.35"/></svg>
                    </span>
                    <input type="text" id="search" name="search" value="<?= e($search) ?>"
                           placeholder="Nom du client ou n° de commande..."
                           class="input input--pad-ic">
                </div>
            </div>

            <div>
                <label for="statut" class="field-label !text-xs">Statut</label>
                <select id="statut" name="statut" class="input">
                    <option value="">Tous les statuts</option>
                    <?php foreach (Order::statuts() as $codeStatut): ?>
                        <option value="<?= e($codeStatut) ?>" <?= $statut === $codeStatut ? 'selected' : '' ?>>
                            <?= e(statut_label($codeStatut)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="flex items-end gap-2">
                <button type="submit" class="btn btn-primary w-full !py-2.5">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M22 3H2l8 9.46V19l4 2v-8.54Z"/></svg>
                    Filtrer
                </button>
                <a href="<?= url('/orders') ?>" title="Réinitialiser les filtres" class="btn btn-ghost !h-[42px] !w-[42px] !p-0">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" class="h-4 w-4"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                </a>
            </div>
        </div>
    </form>

    <!-- ============================================================
         TABLEAU DES COMMANDES
         ============================================================ -->
    <div class="glass-card glass-card--flat anim-fade-up d-2 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="tbl">
                <thead>
                    <tr>
                        <th>Référence</th>
                        <th>Client</th>
                        <th>Date</th>
                        <th class="text-right">Montant</th>
                        <th>Statut</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($orders) === 0): ?>
                        <!-- CAS 1 : AUCUNE COMMANDE -->
                        <tr>
                            <td colspan="6">
                                <div class="empty-box">
                                    <span class="empty-ic" aria-hidden="true">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" class="h-6 w-6"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><path d="M3.3 7 12 12l8.7-5"/><path d="M12 22V12"/></svg>
                                    </span>
                                    <p class="font-semibold text-white">Aucune commande trouvée</p>
                                    <p class="mt-1 text-xs text-slate-500">Modifiez vos filtres ou créez une première commande.</p>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <!-- CAS 2 : AFFICHAGE DE CHAQUE COMMANDE -->
                        <?php foreach ($orders as $i => $order): ?>
                            <tr class="anim-fade-in" <?= $i > 0 ? 'style="animation-delay:' . min($i * 30, 240) . 'ms"' : '' ?>>
                                <td class="whitespace-nowrap">
                                    <a href="<?= url('/orders/show') ?>?id=<?= (int) $order['id'] ?>"
                                       class="font-semibold text-white transition hover:text-emerald-300">
                                        <?= e($order['reference']) ?>
                                    </a>
                                </td>
                                <td class="whitespace-nowrap">
                                    <p class="font-medium text-slate-200"><?= e($order['client']) ?></p>
                                    <?php if (!empty($order['email'])): ?>
                                        <p class="text-xs text-slate-500"><?= e($order['email']) ?></p>
                                    <?php endif; ?>
                                </td>
                                <td class="whitespace-nowrap text-slate-400"><?= date_fr($order['date_commande']) ?></td>
                                <td class="whitespace-nowrap text-right font-semibold text-white"><?= money($order['montant']) ?></td>
                                <td class="whitespace-nowrap">
                                    <span class="<?= statut_badge($order['statut']) ?>">
                                        <span class="badge-dot" aria-hidden="true"></span>
                                        <?= e(statut_label($order['statut'])) ?>
                                    </span>
                                </td>
                                <td class="whitespace-nowrap">
                                    <div class="flex items-center justify-end gap-1.5">

                                        <!-- ACTION VOIR -->
                                        <a href="<?= url('/orders/show') ?>?id=<?= (int) $order['id'] ?>"
                                           title="Voir le détail"
                                           class="btn btn-ghost btn-sm !py-2">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="h-3.5 w-3.5"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                                            Voir
                                        </a>

                                        <!-- ACTION MODIFIER -->
                                        <a href="<?= url('/orders/edit') ?>?id=<?= (int) $order['id'] ?>"
                                           title="Modifier la commande"
                                           class="btn btn-warn btn-sm !py-2">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="h-3.5 w-3.5"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                                            Modifier
                                        </a>

                                        <!-- ACTION SUPPRIMER (formulaire POST + confirmation) -->
                                        <form method="POST" action="<?= url('/orders/delete') ?>"
                                              onsubmit="return confirm('Supprimer définitivement cette commande ?');">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="id" value="<?= (int) $order['id'] ?>">
                                            <button type="submit" title="Supprimer la commande" class="btn btn-danger btn-sm !py-2">
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="h-3.5 w-3.5"><path d="M3 6h18"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M10 11v6"/><path d="M14 11v6"/></svg>
                                                Supprimer
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- ============================================================
             PAGINATION (affichée même quand une seule page, pour la cohérence)
             ============================================================ -->
        <nav class="flex items-center justify-between gap-3 border-t border-white/5 px-5 py-3.5">
            <?php if ($page > 1): ?>
                <a href="<?= url('/orders') ?>?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&statut=<?= urlencode($statut) ?>"
                   class="btn btn-ghost btn-sm !py-2">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4"><path d="m12 19-7-7 7-7"/><path d="M19 12H5"/></svg>
                    Précédent
                </a>
            <?php else: ?>
                <span class="btn btn-sm !cursor-not-allowed border border-white/5 text-slate-600 !bg-white/[.02]">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4"><path d="m12 19-7-7 7-7"/><path d="M19 12H5"/></svg>
                    Précédent
                </span>
            <?php endif; ?>

            <span class="text-sm text-slate-500">
                Page <span class="font-semibold text-white"><?= $page ?></span> sur <span class="font-semibold text-white"><?= $pages ?></span>
            </span>

            <?php if ($page < $pages): ?>
                <a href="<?= url('/orders') ?>?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&statut=<?= urlencode($statut) ?>"
                   class="btn btn-ghost btn-sm !py-2">
                    Suivant
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4"><path d="m5 12 14 0"/><path d="m12 5 7 7-7 7"/></svg>
                </a>
            <?php else: ?>
                <span class="btn btn-sm !cursor-not-allowed border border-white/5 text-slate-600 !bg-white/[.02]">
                    Suivant
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4"><path d="m5 12 14 0"/><path d="m12 5 7 7-7 7"/></svg>
                </span>
            <?php endif; ?>
        </nav>
    </div>
</div>