<?php
/**
 * Fragment : boutons Précédent / Suivant de la liste produits.
 * Inclus par products/index.php et par ProductController::rows().
 *
 * Variables : $search, $activeCategoryId, $sort, $page, $pages
 */
$search           = $search ?? '';
$activeCategoryId = $activeCategoryId ?? null;
$sort             = $sort ?? '';
$page             = (int) ($page ?? 1);
$pages            = (int) ($pages ?? 1);

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

$prevQuery = http_build_query(array_merge($queryParams, ['page' => $page - 1]));
$nextQuery = http_build_query(array_merge($queryParams, ['page' => $page + 1]));
?>
<?php if ($page > 1): ?>
    <a href="<?= url('/products') ?>?<?= e($prevQuery) ?>" class="btn btn-ghost btn-sm !py-2">Précédent</a>
<?php else: ?>
    <span class="btn btn-sm !cursor-not-allowed border border-white/5 text-slate-600 !bg-white/[.02]">Précédent</span>
<?php endif; ?>

<span class="text-sm text-slate-500">
    Page <span class="font-semibold text-white"><?= (int) $page ?></span>
    sur <span class="font-semibold text-white"><?= (int) $pages ?></span>
</span>

<?php if ($page < $pages): ?>
    <a href="<?= url('/products') ?>?<?= e($nextQuery) ?>" class="btn btn-ghost btn-sm !py-2">Suivant</a>
<?php else: ?>
    <span class="btn btn-sm !cursor-not-allowed border border-white/5 text-slate-600 !bg-white/[.02]">Suivant</span>
<?php endif; ?>
