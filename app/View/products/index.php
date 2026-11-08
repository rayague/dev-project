<?php
/**
 * ============================================================
 *  VUE : products/index.php  (LISTE DES PRODUITS — CRUD Read)
 * ============================================================
 *
 *  RÔLE DANS LE MVC :
 *  Affiche le tableau des produits avec recherche, pagination
 *  et actions (Voir / Modifier / Supprimer).
 *
 *  VARIABLES REÇUES (depuis ProductController::index) :
 *   - $products : lignes de la page courante
 *   - $total    : nombre total (filtre inclus)
 *   - $pages    : nombre de pages
 *   - $page     : page courante
 *   - $search   : terme de recherche en cours
 * ============================================================
 */

// Valeurs par défaut si une variable manque (évite notices PHP).
$products = $products ?? [];
$total    = $total ?? 0;
$pages    = $pages ?? 1;
$page     = $page ?? 1;
$search   = $search ?? '';
?>

<div class="mx-auto w-full max-w-6xl">

    <!-- ============================================================
         EN-TÊTE : titre + bouton de création
         ============================================================ -->
    <div class="anim-fade-up mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="font-display text-2xl font-bold tracking-tight text-white">Produits</h2>
            <p class="mt-1 text-sm text-slate-400"><?= (int) $total ?> produit(s) trouvé(s)</p>
        </div>

        <a href="<?= url('/products/create') ?>" class="btn btn-primary anim-fade-up d-1">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" class="h-4 w-4"><path d="M12 5v14"/><path d="M5 12h14"/></svg>
            Nouveau produit
        </a>
    </div>

    <!-- ============================================================
         FORMULAIRE DE RECHERCHE (GET → /products)
         ============================================================ -->
    <form method="GET" action="<?= url('/products') ?>" class="glass-card anim-fade-up d-1 mb-6 p-4 sm:p-5">
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-4">
            <div class="sm:col-span-3">
                <label for="search" class="field-label !text-xs">Rechercher</label>
                <div class="relative">
                    <span class="field-ic" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.35-4.35"/></svg>
                    </span>
                    <input type="text" id="search" name="search" value="<?= e($search) ?>"
                           placeholder="Nom ou description..."
                           class="input input--pad-ic">
                </div>
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="btn btn-primary w-full !py-2.5">Filtrer</button>
                <!-- Remet la liste sans filtre -->
                <a href="<?= url('/products') ?>" title="Réinitialiser" class="btn btn-ghost !h-[42px] !w-[42px] !p-0">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" class="h-4 w-4"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                </a>
            </div>
        </div>
    </form>

    <!-- ============================================================
         TABLEAU DES PRODUITS
         ============================================================ -->
    <div class="glass-card glass-card--flat anim-fade-up d-2 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="tbl">
                <thead>
                    <tr>
                        <th>Nom</th>
                        <th class="text-right">Prix</th>
                        <th class="text-right">Stock</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($products) === 0): ?>
                        <!-- Aucun résultat pour la recherche / base vide -->
                        <tr>
                            <td colspan="4">
                                <div class="empty-box">
                                    <p class="font-semibold text-white">Aucun produit trouvé</p>
                                    <p class="mt-1 text-xs text-slate-500">Créez un premier produit pour commencer.</p>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($products as $i => $product): ?>
                            <tr class="anim-fade-in" <?= $i > 0 ? 'style="animation-delay:' . min($i * 30, 240) . 'ms"' : '' ?>>
                                <td>
                                    <a href="<?= url('/products/show') ?>?id=<?= (int) $product['id'] ?>"
                                       class="font-semibold text-white transition hover:text-emerald-300">
                                        <?= e($product['name']) ?>
                                    </a>
                                    <?php if (!empty($product['description'])): ?>
                                        <p class="mt-0.5 line-clamp-1 text-xs text-slate-500"><?= e($product['description']) ?></p>
                                    <?php endif; ?>
                                </td>
                                <td class="whitespace-nowrap text-right font-semibold text-white"><?= money($product['price']) ?></td>
                                <td class="whitespace-nowrap text-right text-slate-300"><?= (int) $product['stock'] ?></td>
                                <td class="whitespace-nowrap">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <!-- Voir / Modifier / Supprimer (POST + CSRF) -->
                                        <a href="<?= url('/products/show') ?>?id=<?= (int) $product['id'] ?>" class="btn btn-ghost btn-sm !py-2">Voir</a>
                                        <a href="<?= url('/products/edit') ?>?id=<?= (int) $product['id'] ?>" class="btn btn-warn btn-sm !py-2">Modifier</a>
                                        <form method="POST" action="<?= url('/products/delete') ?>"
                                              onsubmit="return confirm('Supprimer définitivement ce produit ?');">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="id" value="<?= (int) $product['id'] ?>">
                                            <button type="submit" class="btn btn-danger btn-sm !py-2">Supprimer</button>
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
             PAGINATION
             ============================================================ -->
        <nav class="flex items-center justify-between gap-3 border-t border-white/5 px-5 py-3.5">
            <?php if ($page > 1): ?>
                <a href="<?= url('/products') ?>?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>" class="btn btn-ghost btn-sm !py-2">Précédent</a>
            <?php else: ?>
                <span class="btn btn-sm !cursor-not-allowed border border-white/5 text-slate-600 !bg-white/[.02]">Précédent</span>
            <?php endif; ?>

            <span class="text-sm text-slate-500">
                Page <span class="font-semibold text-white"><?= (int) $page ?></span>
                sur <span class="font-semibold text-white"><?= (int) $pages ?></span>
            </span>

            <?php if ($page < $pages): ?>
                <a href="<?= url('/products') ?>?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>" class="btn btn-ghost btn-sm !py-2">Suivant</a>
            <?php else: ?>
                <span class="btn btn-sm !cursor-not-allowed border border-white/5 text-slate-600 !bg-white/[.02]">Suivant</span>
            <?php endif; ?>
        </nav>
    </div>
</div>
