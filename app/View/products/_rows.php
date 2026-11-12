<?php
/**
 * Fragment : lignes du tableau produits.
 * Inclus par products/index.php et par ProductController::rows().
 *
 * Variables : $products
 */
$products = $products ?? [];
?>
<?php if (count($products) === 0): ?>
    <tr>
        <td colspan="5">
            <div class="empty-box">
                <p class="font-semibold text-white">Aucun produit trouvé</p>
                <p class="mt-1 text-xs text-slate-500">Créez un premier produit pour commencer.</p>
            </div>
        </td>
    </tr>
<?php else: ?>
    <?php foreach ($products as $i => $product): ?>
        <tr class="anim-fade-in" data-product-name="<?= e($product['name']) ?>" <?= $i > 0 ? 'style="animation-delay:' . min($i * 30, 240) . 'ms"' : '' ?>>
            <td>
                <a href="<?= url('/products/show') ?>?id=<?= (int) $product['id'] ?>"
                   class="font-semibold text-white transition hover:text-emerald-300">
                    <?= e($product['name']) ?>
                </a>
                <?php if (!empty($product['description'])): ?>
                    <p class="mt-0.5 line-clamp-1 text-xs text-slate-500"><?= e($product['description']) ?></p>
                <?php endif; ?>
                <span class="product-cat product-cat--stacked">
                    <?= !empty($product['category_name'])
                        ? e($product['category_name'])
                        : 'Sans catégorie' ?>
                </span>
            </td>
            <td class="col-category text-slate-300">
                <span class="product-cat product-cat--cell">
                    <?= !empty($product['category_name'])
                        ? e($product['category_name'])
                        : 'Sans catégorie' ?>
                </span>
            </td>
            <td class="whitespace-nowrap text-right font-semibold text-white"><?= money($product['price']) ?></td>
            <td class="whitespace-nowrap text-right text-slate-300"><?= (int) $product['stock'] ?></td>
            <td class="whitespace-nowrap">
                <div class="flex items-center justify-end gap-1.5">
                    <a href="<?= url('/products/show') ?>?id=<?= (int) $product['id'] ?>" class="btn btn-ghost btn-sm !py-2">Voir</a>
                    <?php if (Auth::isAdmin()): ?>
                    <a href="<?= url('/products/edit') ?>?id=<?= (int) $product['id'] ?>" class="btn btn-warn btn-sm !py-2">Modifier</a>
                    <form method="POST" action="<?= url('/products/delete') ?>"
                          onsubmit="return confirm('Supprimer définitivement ce produit ?');">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= (int) $product['id'] ?>">
                        <button type="submit" class="btn btn-danger btn-sm !py-2">Supprimer</button>
                    </form>
                    <?php endif; ?>
                </div>
            </td>
        </tr>
    <?php endforeach; ?>
<?php endif; ?>
