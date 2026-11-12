<?php
/**
 * ============================================================
 *  VUE : products/catalog.php  (CATALOGUE LECTURE SEULE)
 * ============================================================
 *
 *  RÔLE DANS LE MVC :
 *  Vitrine des produits pour tout utilisateur connecté
 *  (rôle admin ou user). Aucune action d'écriture ici.
 *
 *  Route : GET /products-user → ProductController::catalog()
 *
 *  VARIABLES REÇUES :
 *   - $products, $total, $pages, $page, $search
 *   - $categories, $activeCategoryId (sélecteur de filtre)
 * ============================================================
 */

$products         = $products ?? [];
$total            = $total ?? 0;
$pages            = $pages ?? 1;
$page             = $page ?? 1;
$search           = $search ?? '';
$categories       = $categories ?? [];
$activeCategoryId = $activeCategoryId ?? null;

// Conserve les filtres dans la pagination.
$queryParams = [];
if ($search !== '') {
    $queryParams['search'] = $search;
}
if ($activeCategoryId !== null && (int) $activeCategoryId > 0) {
    $queryParams['category'] = (int) $activeCategoryId;
}
?>

<div class="mx-auto w-full max-w-6xl">

    <!-- En-tête du catalogue -->
    <div class="anim-fade-up mb-6">
        <h2 class="font-display text-2xl font-bold tracking-tight text-white">Catalogue</h2>
        <p class="mt-1 text-sm text-slate-400">
            <?= (int) $total ?> produit(s) disponible(s)
            <?php if (!Auth::isAdmin()): ?>
                <span class="text-slate-500">— consultation seule</span>
            <?php endif; ?>
        </p>
    </div>

    <!-- Recherche + sélecteur de catégorie (GET). Un changement filtre tout de suite. -->
    <form method="GET" action="<?= url('/products-user') ?>" class="glass-card anim-fade-up d-1 mb-6 p-4 sm:p-5">
        <div class="category-filter-grid">
            <div>
                <label for="search" class="field-label !text-xs">Rechercher</label>
                <input type="text" id="search" name="search" value="<?= e($search) ?>"
                       placeholder="Nom ou description…"
                       class="input">
            </div>
            <div class="category-field">
                <label for="category" class="field-label !text-xs">Catégorie</label>
                <select id="category" name="category" class="input input--category"
                        onchange="this.form.requestSubmit()">
                    <option value="">Toutes les catégories</option>
                    <?php foreach ($categories as $categorie): ?>
                        <option value="<?= (int) $categorie['id'] ?>"
                            <?= (int) $activeCategoryId === (int) $categorie['id'] ? 'selected' : '' ?>>
                            <?= e($categorie['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="btn btn-primary w-full !py-2.5">Filtrer</button>
                <a href="<?= url('/products-user') ?>" class="btn btn-ghost !h-[42px] !w-[42px] !p-0" title="Réinitialiser">×</a>
            </div>
        </div>
    </form>

    <!-- Grille catalogue : nom, description, prix, stock, catégorie -->
    <?php if ($products === []): ?>
        <div class="glass-card anim-fade-up d-2 p-10 text-center text-sm text-slate-400">
            Aucun produit ne correspond à votre recherche.
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <?php foreach ($products as $i => $product): ?>
                <article class="glass-card product-card anim-fade-up"
                         <?= $i > 0 ? 'style="animation-delay:' . min($i * 40, 240) . 'ms"' : '' ?>>
                    <div class="product-card__main">
                        <h3 class="product-card__title font-display text-lg font-semibold text-white">
                            <a href="<?= url('/products/show') ?>?id=<?= (int) $product['id'] ?>"
                               class="transition hover:text-emerald-300">
                                <?= e($product['name']) ?>
                            </a>
                        </h3>

                        <p class="mt-2 line-clamp-3 text-sm text-slate-400">
                            <?= e($product['description'] ?? '') !== ''
                                ? e((string) $product['description'])
                                : 'Aucune description.' ?>
                        </p>

                        <div class="mt-4 flex items-end justify-between gap-3 border-t border-white/5 pt-4">
                            <div class="min-w-0">
                                <p class="text-xs uppercase tracking-wider text-slate-500">Prix</p>
                                <p class="text-base font-semibold text-emerald-300"><?= money($product['price']) ?></p>
                            </div>
                            <div class="shrink-0 text-right">
                                <p class="text-xs uppercase tracking-wider text-slate-500">Stock</p>
                                <p class="text-base font-semibold text-white"><?= (int) $product['stock'] ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- Catégorie en bas de carte : le texte peut passer à la ligne, jamais tronqué. -->
                    <span class="product-cat">
                        <?= !empty($product['category_name'])
                            ? e($product['category_name'])
                            : 'Sans catégorie' ?>
                    </span>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Pagination -->
    <?php if ($pages > 1): ?>
        <?php
        $prevQuery = http_build_query(array_merge($queryParams, ['page' => $page - 1]));
        $nextQuery = http_build_query(array_merge($queryParams, ['page' => $page + 1]));
        ?>
        <div class="mt-8 flex items-center justify-between gap-3">
            <?php if ($page > 1): ?>
                <a href="<?= url('/products-user') ?>?<?= e($prevQuery) ?>" class="btn btn-ghost btn-sm !py-2">Précédent</a>
            <?php else: ?>
                <span></span>
            <?php endif; ?>
            <p class="text-sm text-slate-400">
                Page <span class="font-semibold text-white"><?= (int) $page ?></span>
                sur <span class="font-semibold text-white"><?= (int) $pages ?></span>
            </p>
            <?php if ($page < $pages): ?>
                <a href="<?= url('/products-user') ?>?<?= e($nextQuery) ?>" class="btn btn-ghost btn-sm !py-2">Suivant</a>
            <?php else: ?>
                <span></span>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>
