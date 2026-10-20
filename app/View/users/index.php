<?php
/**
 * ============================================================
 *  VUE : users/index.php  (LISTE DES UTILISATEURS — CRUD : Read)
 * ============================================================
 *
 *  RÔLE DANS LE MVC :
 *  Liste des comptes utilisateurs avec recherche, pagination et
 *  actions Voir / Modifier / Supprimer.
 *
 *  VARIABLES REÇUES :
 *   - $users  : utilisateurs de la page courante
 *   - $total  : nombre total (filtre compris)
 *   - $pages  : nombre de pages
 *   - $page   : page courante
 *   - $search : terme de recherche en cours
 * ============================================================
 */

$users  = $users ?? [];
$total  = $total ?? 0;
$pages  = $pages ?? 1;
$page   = $page ?? 1;
$search = $search ?? '';
?>

<div class="mx-auto w-full max-w-6xl">

    <!-- ============================================================
         EN-TÊTE DE PAGE : titre + bouton "Nouvel utilisateur"
         ============================================================ -->
    <div class="anim-fade-up mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="font-display text-2xl font-bold tracking-tight text-white">Utilisateurs</h2>
            <p class="mt-1 text-sm text-slate-400"><?= (int) $total ?> compte(s) inscrit(s)</p>
        </div>

        <a href="<?= url('/users/create') ?>" class="btn btn-primary anim-fade-up d-1">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" class="transition-transform duration-200 group-hover:rotate-90"><path d="M12 5v14"/><path d="M5 12h14"/></svg>
            Nouvel utilisateur
        </a>
    </div>

    <!-- ============================================================
         FORMULAIRE DE RECHERCHE (méthode GET)
         ============================================================ -->
    <form method="GET" action="<?= url('/users') ?>" class="glass-card anim-fade-up d-1 mb-6 p-4 sm:p-5">
        <div class="flex flex-col gap-3 sm:flex-row">
            <label for="search-users" class="sr-only">Rechercher un utilisateur</label>
            <div class="relative flex-1">
                <span class="field-ic" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.35-4.35"/></svg>
                </span>
                <input type="text" id="search-users" name="search"
                       value="<?= e($search) ?>"
                       placeholder="Rechercher par nom ou e-mail..."
                       class="input input--pad-ic">
            </div>
            <button type="submit" class="btn btn-primary">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.35-4.35"/></svg>
                Rechercher
            </button>
        </div>
    </form>

    <!-- ============================================================
         TABLEAU DES UTILISATEURS
         ============================================================ -->
    <div class="glass-card glass-card--flat anim-fade-up d-2 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="tbl">
                <thead>
                    <tr>
                        <th>Utilisateur</th>
                        <th>E-mail</th>
                        <th>Inscrit le</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($users) === 0): ?>
                        <!-- CAS 1 : AUCUN UTILISATEUR -->
                        <tr>
                            <td colspan="4">
                                <div class="empty-box">
                                    <span class="empty-ic" aria-hidden="true">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" class="h-6 w-6"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                                    </span>
                                    <p class="font-semibold text-white">Aucun utilisateur trouvé</p>
                                    <p class="mt-1 text-xs text-slate-500">Créez un premier compte utilisateur.</p>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <!-- CAS 2 : AFFICHAGE DE CHAQUE UTILISATEUR -->
                        <?php foreach ($users as $i => $user): ?>
                            <tr class="anim-fade-in" <?= $i > 0 ? 'style="animation-delay:' . min($i * 30, 240) . 'ms"' : '' ?>>
                                <td class="whitespace-nowrap">
                                    <div class="flex items-center gap-3">
                                        <!-- Pastille d'initiale -->
                                        <span class="avatar-notch h-9 w-9">
                                            <span class="avatar-core text-xs font-bold text-emerald-300">
                                                <?= e(strtoupper(mb_substr((string) ($user['name'] ?? ''), 0, 1))) ?>
                                            </span>
                                        </span>
                                        <a href="<?= url('/users/show') ?>?id=<?= (int) $user['id'] ?>"
                                           class="font-semibold text-white transition hover:text-emerald-300">
                                            <?= e($user['name']) ?>
                                        </a>
                                    </div>
                                </td>
                                <td class="whitespace-nowrap text-slate-400"><?= e($user['email']) ?></td>
                                <td class="whitespace-nowrap text-slate-400"><?= date_fr($user['created_at'] ?? null) ?></td>
                                <td class="whitespace-nowrap">
                                    <div class="flex items-center justify-end gap-1.5">

                                        <!-- ACTION VOIR -->
                                        <a href="<?= url('/users/show') ?>?id=<?= (int) $user['id'] ?>"
                                           title="Voir la fiche"
                                           class="btn btn-ghost btn-sm !py-2">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="h-3.5 w-3.5"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                                            Voir
                                        </a>

                                        <!-- ACTION MODIFIER -->
                                        <a href="<?= url('/users/edit') ?>?id=<?= (int) $user['id'] ?>"
                                           title="Modifier"
                                           class="btn btn-warn btn-sm !py-2">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="h-3.5 w-3.5"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                                            Modifier
                                        </a>

                                        <!-- ACTION SUPPRIMER (formulaire POST + confirmation) -->
                                        <form method="POST" action="<?= url('/users/delete') ?>"
                                              onsubmit="return confirm('Supprimer cet utilisateur ? Ses commandes seront aussi supprimées.');">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="id" value="<?= (int) $user['id'] ?>">
                                            <button type="submit" title="Supprimer" class="btn btn-danger btn-sm !py-2">
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
             PAGINATION (affichée également sur une seule page, cohérence)
             ============================================================ -->
        <nav class="flex items-center justify-between gap-3 border-t border-white/5 px-5 py-3.5">
            <?php if ($page > 1): ?>
                <a href="<?= url('/users') ?>?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>"
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
                <a href="<?= url('/users') ?>?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>"
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