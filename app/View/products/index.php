<?php
/**
 * ============================================================
 *  VUE : products/index.php  (LISTE DES PRODUITS — CRUD Read)
 * ============================================================
 *
 *  RÔLE DANS LE MVC :
 *  Affiche le tableau des produits avec recherche, filtre
 *  catégorie, pagination et actions (Voir / Modifier / Supprimer).
 *
 *  VARIABLES REÇUES (depuis ProductController::index) :
 *   - $products         : lignes de la page courante (+ category_name)
 *   - $total            : nombre total (filtre inclus)
 *   - $pages            : nombre de pages
 *   - $page             : page courante
 *   - $search           : terme de recherche en cours
 *   - $categories       : liste des catégories (filtre)
 *   - $activeCategoryId : id de catégorie filtrée (ou null)
 *   - $sort             : '' (récents) ou 'category' (A→Z)
 * ============================================================
 */

// Valeurs par défaut si une variable manque (évite notices PHP).
$products         = $products ?? [];
$total            = $total ?? 0;
$pages            = $pages ?? 1;
$page             = $page ?? 1;
$search           = $search ?? '';
$categories       = $categories ?? []; // options du <select name="category">
$activeCategoryId = $activeCategoryId ?? null; // id filtré, ou null = toutes
$sort             = $sort ?? ''; // '' | 'category'

// Query string de pagination : conserve search, category et sort.
$queryParams = [];
if ($search !== '') {
    $queryParams['search'] = $search;
}
if ($activeCategoryId !== null && (int) $activeCategoryId > 0) {
    $queryParams['category'] = (int) $activeCategoryId;
}
if ($sort === 'category') {
    $queryParams['sort'] = 'category';
}

// Clic sur l'en-tête : active le tri, ou le retire s'il est déjà actif.
$sortBase = $queryParams;
unset($sortBase['sort']);
if ($sort === 'category') {
    $sortQuery = http_build_query($sortBase);
    $sortUrl   = url('/products') . ($sortQuery !== '' ? '?' . $sortQuery : '');
} else {
    $sortUrl = url('/products') . '?' . http_build_query(array_merge($sortBase, ['sort' => 'category']));
}
?>

<div class="mx-auto w-full max-w-6xl">

    <!-- ============================================================
         EN-TÊTE : titre + bouton de création
         ============================================================ -->
    <div class="anim-fade-up mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="font-display text-2xl font-bold tracking-tight text-white">Produits</h2>
            <p id="product-count" class="mt-1 text-sm text-slate-400"><?= (int) $total ?> produit(s) trouvé(s)</p>
        </div>

        <?php // RBAC : bouton de création visible uniquement pour les administrateurs. ?>
        <?php if (Auth::isAdmin()): ?>
        <a href="<?= url('/products/create') ?>" class="btn btn-primary anim-fade-up d-1">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" class="h-4 w-4"><path d="M12 5v14"/><path d="M5 12h14"/></svg>
            Nouveau produit
        </a>
        <?php endif; ?>
    </div>

    <!-- ============================================================
         FORMULAIRE DE RECHERCHE + FILTRE CATÉGORIE (GET → /products)
         ============================================================ -->
    <form method="GET" action="<?= url('/products') ?>" id="product-filter"
          data-rows-url="<?= e(url('/products/rows')) ?>"
          data-list-url="<?= e(url('/products')) ?>"
          class="glass-card anim-fade-up d-1 mb-6 p-4 sm:p-5">
        <?php if ($sort === 'category'): ?>
            <input type="hidden" name="sort" value="category">
        <?php endif; ?>
        <div class="category-filter-grid">
            <div>
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

            <!-- Filtre catégorie : GET ?category=id au clic sur Filtrer -->
            <div class="category-field">
                <label for="category" class="field-label !text-xs">Catégorie</label>
                <select id="category" name="category" class="input input--category">
                    <option value="">Toutes les catégories</option>
                    <?php foreach ($categories as $categorie): ?>
                        <!-- selected si ce filtre est celui de l'URL courante -->
                        <option value="<?= (int) $categorie['id'] ?>"
                            <?= (int) $activeCategoryId === (int) $categorie['id'] ? 'selected' : '' ?>>
                            <?= e($categorie['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
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
                        <th class="col-category">
                            <a href="<?= e($sortUrl) ?>" id="product-sort"
                               class="th-sort<?= $sort === 'category' ? ' th-sort--on' : '' ?>">Catégorie</a>
                        </th>
                        <th class="text-right">Prix</th>
                        <th class="text-right">Stock</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody id="product-rows">
                    <?php include VIEWS_PATH . '/products/_rows.php'; ?>
                </tbody>
            </table>
        </div>

        <!-- ============================================================
             PAGINATION
             ============================================================ -->
        <nav id="product-pagination" class="flex items-center justify-between gap-3 border-t border-white/5 px-5 py-3.5">
            <?php include VIEWS_PATH . '/products/_pagination.php'; ?>
        </nav>
    </div>
</div>
<script src="<?= e(url('/assets/js/products-filter.js')) ?>"></script>
