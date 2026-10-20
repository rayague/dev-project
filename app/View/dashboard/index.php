<?php
/**
 * ============================================================
 *  VUE : dashboard/index.php  (TABLEAU DE BORD)
 * ============================================================
 *
 *  RÔLE DANS LE MVC :
 *  Page d'accueil après connexion :
 *   - 4 cartes de statistiques (chiffres clés)
 *   - la répartition des commandes par statut
 *   - les 4 dernières commandes enregistrées
 *
 *  Variables fournies par DashboardController (via Order::stats()).
 *  VARIABLES REÇUES :
 *   - $stats   : ['total_commandes'=>int, 'montant_total'=>float,
 *                 'par_statut'=>[...], 'dernieres'=>[...]]
 *   - $nbUsers : nombre d'utilisateurs inscrits
 * ============================================================
 */

$stats   = $stats ?? [];
$nbUsers = $nbUsers ?? 0;

$totalCommandes = $stats['total_commandes'] ?? 0;
$montantTotal   = $stats['montant_total'] ?? 0;
$parStatut      = $stats['par_statut'] ?? [];
$dernieres      = $stats['dernieres'] ?? [];
?>

<div class="mx-auto w-full max-w-6xl space-y-6">

    <!-- ============================================================
         BANDEAU D'ACCUEIL (héros)
         ============================================================ -->
    <div class="hero-band anim-fade-up relative p-6 sm:p-8">
        <span class="hero-orb" style="width: 220px; height: 220px; right: -40px; top: -80px; background: radial-gradient(circle, rgba(52,211,153,.75), transparent 70%);" aria-hidden="true"></span>
        <span class="hero-orb" style="width: 260px; height: 260px; left: -70px; bottom: -130px; background: radial-gradient(circle, rgba(139,92,246,.65), transparent 70%);" aria-hidden="true"></span>

        <div class="relative flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="glass-kicker">Tableau de bord</p>
                <h2 class="mt-1 font-display text-2xl font-bold tracking-tight text-white sm:text-3xl">
                    Bonjour, <span class="gradient-text"><?= e(explode(' ', trim(Auth::name()))[0]) ?></span>
                </h2>
                <p class="mt-1.5 text-sm text-slate-400">Voici un aperçu de votre activité du jour.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <span class="<?= Auth::isAdmin() ? 'role-chip role-chip--admin' : 'role-chip role-chip--user' ?>">
                    <span class="badge-dot" aria-hidden="true"></span>
                    <?= Auth::isAdmin() ? 'Administrateur' : 'Utilisateur' ?>
                </span>
                <span class="tag"><?= e(date('d/m/Y')) ?></span>
            </div>
        </div>
    </div>

    <!-- ============================================================
         SECTION 1 : CARTES DE STATISTIQUES
         ============================================================ -->
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">

        <!-- ---------- CARTE 1 : Total commandes ---------- -->
        <div class="glass-card card-hover anim-fade-up d-1 p-5">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-400">Total commandes</p>
                    <p class="stat-value mt-2 text-3xl font-bold text-white"><?= (int) $totalCommandes ?></p>
                    <p class="mt-1 text-xs text-slate-500">Commandes enregistrées</p>
                </div>
                <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-400/10 text-emerald-300 ring-1 ring-inset ring-emerald-400/20">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><path d="M3.3 7 12 12l8.7-5"/><path d="M12 22V12"/></svg>
                </span>
            </div>
        </div>

        <!-- ---------- CARTE 2 : Montant total ---------- -->
        <div class="glass-card card-hover anim-fade-up d-2 p-5">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-400">Montant total</p>
                    <p class="stat-value mt-2 text-3xl font-bold gradient-text"><?= money($montantTotal) ?></p>
                    <p class="mt-1 text-xs text-slate-500">Somme de toutes les commandes</p>
                </div>
                <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-sky-400/10 text-sky-300 ring-1 ring-inset ring-sky-400/20">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5"><line x1="12" x2="12" y1="2" y2="22"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                </span>
            </div>
        </div>

        <!-- ---------- CARTE 3 : En attente ---------- -->
        <div class="glass-card card-hover anim-fade-up d-3 p-5">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-400">En attente</p>
                    <p class="stat-value mt-2 text-3xl font-bold text-amber-300"><?= (int) ($parStatut['en_attente'] ?? 0) ?></p>
                    <p class="mt-1 text-xs text-slate-500">À traiter en priorité</p>
                </div>
                <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-amber-400/10 text-amber-300 ring-1 ring-inset ring-amber-400/20">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                </span>
            </div>
        </div>

        <!-- ---------- CARTE 4 : Utilisateurs inscrits ---------- -->
        <div class="glass-card card-hover anim-fade-up d-4 p-5">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-400">Utilisateurs</p>
                    <p class="stat-value mt-2 text-3xl font-bold text-violet-300"><?= (int) $nbUsers ?></p>
                    <p class="mt-1 text-xs text-slate-500">Comptes créés sur le site</p>
                </div>
                <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-violet-400/10 text-violet-300 ring-1 ring-inset ring-violet-400/20">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                </span>
            </div>
        </div>
    </div>

    <!-- ============================================================
         SECTION 2 : RÉPARTITION PAR STATUT + RACCOURCIS
         ============================================================ -->
    <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">

        <!-- ---------- BLOC A : Répartition des statuts ---------- -->
        <div class="glass-card glass-card--flat anim-fade-up d-2 lg:col-span-2">
            <div class="glass-head">
                <div>
                    <h2 class="glass-kicker">Répartition par statut</h2>
                    <p class="mt-1 text-sm text-slate-400">Volume de commandes pour chaque état</p>
                </div>
            </div>

            <ul class="space-y-5 p-6">
                <?php
                $lignes = [
                    ['code' => 'en_attente', 'libelle' => 'En attente', 'couleur' => 'bg-amber-400'],
                    ['code' => 'validee',    'libelle' => 'Validées',   'couleur' => 'bg-emerald-400'],
                    ['code' => 'livree',     'libelle' => 'Livrées',    'couleur' => 'bg-sky-400'],
                    ['code' => 'annulee',    'libelle' => 'Annulées',   'couleur' => 'bg-red-400'],
                ];
                $totalStatuts = array_sum($parStatut);
                ?>
                <?php foreach ($lignes as $i => $ligne): ?>
                    <?php $nombre = (int) ($parStatut[$ligne['code']] ?? 0); ?>
                    <?php $pourcent = $totalStatuts > 0 ? round($nombre * 100 / $totalStatuts) : 0; ?>
                    <li>
                        <div class="mb-2 flex items-center justify-between">
                            <span class="flex items-center gap-2.5 text-sm font-medium text-slate-300">
                                <span class="h-2.5 w-2.5 rounded-full <?= e($ligne['couleur']) ?>" aria-hidden="true"></span>
                                <?= e($ligne['libelle']) ?>
                            </span>
                            <span class="text-xs text-slate-500"><?= $nombre ?> commande(s) · <?= $pourcent ?> %</span>
                        </div>
                        <!-- Barre de progression (animation bar-grow : CSS pur) -->
                        <div class="h-2 w-full overflow-hidden rounded-full bg-white/5">
                            <div class="bar-fill h-2 rounded-full <?= e($ligne['couleur']) ?> d-<?= $i + 1 ?> shadow-[0_0_10px_rgba(52,211,153,.4)]" style="width: <?= $pourcent ?>%"></div>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <!-- ---------- BLOC B : Raccourcis d'action ---------- -->
        <div class="glass-card anim-fade-up d-3 relative overflow-hidden p-6">
            <div class="pointer-events-none absolute -right-16 -top-16 h-40 w-40 rounded-full bg-emerald-400/15 blur-3xl" aria-hidden="true"></div>

            <h2 class="glass-kicker relative">Actions rapides</h2>
            <p class="relative mt-1 text-sm text-slate-400">Gagnez du temps avec ces raccourcis</p>

            <div class="relative mt-6 space-y-2.5">
                <a href="<?= url('/orders/create') ?>" class="btn btn-primary w-full !justify-between !py-3">
                    Nouvelle commande
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 5v14"/><path d="M5 12h14"/></svg>
                </a>
                <?php if (Auth::isAdmin()): ?>
                <a href="<?= url('/users/create') ?>" class="btn btn-ghost w-full !justify-between !py-3">
                    Nouvel utilisateur
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                </a>
                <?php endif; ?>
                <a href="<?= url('/orders') ?>" class="btn btn-ghost w-full !justify-between !py-3">
                    Voir toutes les commandes
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                </a>
            </div>
        </div>
    </div>

    <!-- ============================================================
         SECTION 3 : DERNIÈRES COMMANDES
         ============================================================ -->
    <div class="glass-card glass-card--flat anim-fade-up d-3 overflow-hidden">

        <!-- En-tête du tableau -->
        <div class="glass-head">
            <div>
                <h2 class="glass-kicker">Dernières commandes</h2>
            </div>
            <a href="<?= url('/orders') ?>" class="flex items-center gap-1.5 text-xs font-semibold text-emerald-300 transition hover:text-emerald-200">
                Tout afficher
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-3.5 w-3.5"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
            </a>
        </div>

        <!-- Tableau -->
        <div class="overflow-x-auto">
            <table class="tbl">
                <thead>
                    <tr>
                        <th>Référence</th>
                        <th>Client</th>
                        <th>Date</th>
                        <th>Montant</th>
                        <th>Statut</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($dernieres) === 0): ?>
                        <tr>
                            <td colspan="5" class="text-center text-slate-500">
                                Aucune commande pour le moment.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($dernieres as $commande): ?>
                            <tr>
                                <td class="whitespace-nowrap">
                                    <a href="<?= url('/orders/show') ?>?id=<?= (int) $commande['id'] ?>"
                                       class="font-semibold text-white transition hover:text-emerald-300">
                                        <?= e($commande['reference']) ?>
                                    </a>
                                </td>
                                <td class="whitespace-nowrap text-slate-300"><?= e($commande['client']) ?></td>
                                <td class="whitespace-nowrap text-slate-400"><?= date_fr($commande['date_commande']) ?></td>
                                <td class="whitespace-nowrap font-medium text-white"><?= money($commande['montant']) ?></td>
                                <td class="whitespace-nowrap">
                                    <span class="<?= statut_badge($commande['statut']) ?>">
                                        <span class="badge-dot" aria-hidden="true"></span>
                                        <?= e(statut_label($commande['statut'])) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>