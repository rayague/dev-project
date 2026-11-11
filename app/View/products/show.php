<?php
/**
 * ============================================================
 *  VUE : products/show.php  (DÉTAIL D'UN PRODUIT — CRUD Read)
 * ============================================================
 *
 *  RÔLE DANS LE MVC :
 *  Fiche complète d'un produit : infos, description, dates,
 *  et boutons d'action (Modifier / Supprimer).
 *
 *  VARIABLES REÇUES :
 *   - $product : ligne SQL + category_name (ajouté par le contrôleur)
 * ============================================================
 */

$product = $product ?? [];
?>

<div class="mx-auto w-full max-w-4xl">

    <!-- ============================================================
         EN-TÊTE : titre + actions
         ============================================================ -->
    <div class="anim-fade-up mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="font-display text-2xl font-bold tracking-tight text-white"><?= e($product['name'] ?? '') ?></h2>
            <a href="<?= url('/products') ?>" class="mt-1 inline-flex items-center gap-1 text-sm font-medium text-emerald-300 transition hover:text-emerald-200">
                ← Retour à la liste
            </a>
        </div>

        <?php // RBAC : actions d'écriture réservées aux administrateurs. ?>
        <?php if (Auth::isAdmin()): ?>
        <div class="flex flex-wrap items-center gap-2">
            <a href="<?= url('/products/edit') ?>?id=<?= (int) ($product['id'] ?? 0) ?>" class="btn btn-warn">Modifier</a>

            <!-- Suppression : POST + CSRF + confirmation navigateur -->
            <form method="POST" action="<?= url('/products/delete') ?>"
                  onsubmit="return confirm('Voulez-vous vraiment supprimer ce produit ?');">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int) ($product['id'] ?? 0) ?>">
                <button type="submit" class="btn btn-danger">Supprimer</button>
            </form>
        </div>
        <?php endif; ?>
    </div>

    <!-- ============================================================
         FICHE DÉTAIL
         ============================================================ -->
    <div class="glass-card glass-card--flat anim-fade-up d-1 overflow-hidden">
        <div class="glass-head">
            <h2 class="glass-kicker">Informations du produit</h2>
        </div>

        <dl class="divide-y divide-white/5">
            <div class="flex items-center justify-between px-6 py-4">
                <dt class="text-sm font-medium text-slate-400">Identifiant</dt>
                <dd class="text-sm font-semibold text-white">#<?= (int) ($product['id'] ?? 0) ?></dd>
            </div>
            <div class="flex items-center justify-between px-6 py-4">
                <dt class="text-sm font-medium text-slate-400">Nom</dt>
                <dd class="text-sm font-semibold text-white"><?= e($product['name'] ?? '') ?></dd>
            </div>
            <!-- Libellé via Product::getCategoryName(category_id) -->
            <div class="flex items-center justify-between px-6 py-4">
                <dt class="text-sm font-medium text-slate-400">Catégorie</dt>
                <dd class="text-sm font-semibold text-white">
                    <?= !empty($product['category_name'])
                        ? e($product['category_name'])
                        : '<span class="font-normal text-slate-500">—</span>' ?>
                </dd>
            </div>
            <div class="flex items-center justify-between px-6 py-4">
                <dt class="text-sm font-medium text-slate-400">Prix</dt>
                <!-- money() formate en euros (helpers.php) -->
                <dd class="text-sm font-semibold text-white"><?= money($product['price'] ?? 0) ?></dd>
            </div>
            <div class="flex items-center justify-between px-6 py-4">
                <dt class="text-sm font-medium text-slate-400">Stock</dt>
                <dd class="text-sm font-semibold text-white"><?= (int) ($product['stock'] ?? 0) ?></dd>
            </div>
            <div class="px-6 py-4">
                <dt class="mb-2 text-sm font-medium text-slate-400">Description</dt>
                <dd class="text-sm text-slate-200">
                    <?php if (!empty($product['description'])): ?>
                        <!-- nl2br conserve les retours à la ligne saisis -->
                        <?= nl2br(e($product['description'])) ?>
                    <?php else: ?>
                        <span class="text-slate-500">—</span>
                    <?php endif; ?>
                </dd>
            </div>
            <div class="flex items-center justify-between px-6 py-4">
                <dt class="text-sm font-medium text-slate-400">Créé le</dt>
                <dd class="text-sm text-slate-300">
                    <?= !empty($product['created_at'])
                        ? e(date('d/m/Y à H:i', strtotime((string) $product['created_at'])))
                        : '—' ?>
                </dd>
            </div>
        </dl>
    </div>
</div>
