<?php
/**
 * ============================================================
 *  VUE : products/edit.php  (FORMULAIRE DE MODIFICATION)
 * ============================================================
 *
 *  RÔLE DANS LE MVC :
 *  Formulaire pré-rempli pour modifier un produit existant.
 *  Envoi en POST vers /products/update → ProductController::update().
 *
 *  VARIABLES REÇUES :
 *   - $product    : ligne produit (ou valeurs resaisies après erreur)
 *   - $errors     : messages d'erreur par champ
 *   - $categories : liste pour le select category_id
 * ============================================================
 */

$product    = $product ?? [];
$errors     = $errors ?? [];
$categories = $categories ?? []; // peuplé par ProductController::edit / update

// Lit une propriété du produit (ou une valeur par défaut).
$valeur = function (string $champ, string $defaut = '') use ($product): string {
    return (string) ($product[$champ] ?? $defaut);
};

// Premier message d'erreur d'un champ.
$erreurDe = function (string $champ) use ($errors): string {
    return $errors[$champ][0] ?? '';
};
?>

<div class="mx-auto w-full max-w-4xl">

    <!-- En-tête avec le nom du produit -->
    <div class="anim-fade-up mb-6">
        <h2 class="font-display text-2xl font-bold tracking-tight text-white">
            Modifier <span class="gradient-text"><?= e($valeur('name')) ?></span>
        </h2>
        <a href="<?= url('/products/show') ?>?id=<?= (int) ($product['id'] ?? 0) ?>"
           class="mt-1 inline-flex items-center gap-1 text-sm font-medium text-emerald-300 transition hover:text-emerald-200">
            ← Retour au détail
        </a>
    </div>

    <div class="glass-card glass-card--flat anim-fade-up d-1 overflow-hidden">
        <div class="glass-head">
            <h2 class="glass-kicker">Informations du produit</h2>
        </div>

        <!-- id caché : indispensable pour ProductController::findOr404() -->
        <form method="POST" action="<?= url('/products/update') ?>" class="space-y-5 px-6 py-7 sm:px-8">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int) ($product['id'] ?? 0) ?>">

            <div>
                <label for="name" class="field-label">Nom <span class="req">*</span></label>
                <input type="text" id="name" name="name" value="<?= e($valeur('name')) ?>" required
                       class="input <?= $erreurDe('name') !== '' ? 'input--err' : '' ?>">
                <?php if ($erreurDe('name') !== ''): ?>
                    <p class="field-err"><?= e($erreurDe('name')) ?></p>
                <?php endif; ?>
            </div>

            <!-- Catégorie facultative : pré-sélection via $product['category_id'] -->
            <div>
                <label for="category_id" class="field-label">Catégorie <span class="font-normal text-slate-500">(facultatif)</span></label>
                <select id="category_id" name="category_id"
                        class="input <?= $erreurDe('category_id') !== '' ? 'input--err' : '' ?>">
                    <option value="">— Aucune —</option>
                    <?php foreach ($categories as $categorie): ?>
                        <!-- Compare en string : PDO peut renvoyer id en string -->
                        <option value="<?= (int) $categorie['id'] ?>"
                            <?= (string) $valeur('category_id') === (string) $categorie['id'] ? 'selected' : '' ?>>
                            <?= e($categorie['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if ($erreurDe('category_id') !== ''): ?>
                    <p class="field-err"><?= e($erreurDe('category_id')) ?></p>
                <?php endif; ?>
            </div>

            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <div>
                    <label for="price" class="field-label">Prix (€) <span class="req">*</span></label>
                    <input type="number" step="0.01" min="0.01" id="price" name="price"
                           value="<?= e($valeur('price')) ?>" required
                           class="input <?= $erreurDe('price') !== '' ? 'input--err' : '' ?>">
                    <?php if ($erreurDe('price') !== ''): ?>
                        <p class="field-err"><?= e($erreurDe('price')) ?></p>
                    <?php endif; ?>
                </div>
                <div>
                    <label for="stock" class="field-label">Stock <span class="req">*</span></label>
                    <input type="number" step="1" min="0" id="stock" name="stock"
                           value="<?= e($valeur('stock')) ?>" required
                           class="input <?= $erreurDe('stock') !== '' ? 'input--err' : '' ?>">
                    <?php if ($erreurDe('stock') !== ''): ?>
                        <p class="field-err"><?= e($erreurDe('stock')) ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <div>
                <label for="description" class="field-label">Description</label>
                <textarea id="description" name="description" rows="4"
                          class="input <?= $erreurDe('description') !== '' ? 'input--err' : '' ?>"><?= e($valeur('description')) ?></textarea>
                <?php if ($erreurDe('description') !== ''): ?>
                    <p class="field-err"><?= e($erreurDe('description')) ?></p>
                <?php endif; ?>
            </div>

            <div class="flex flex-wrap gap-3 pt-2">
                <button type="submit" class="btn btn-primary">Enregistrer</button>
                <a href="<?= url('/products/show') ?>?id=<?= (int) ($product['id'] ?? 0) ?>" class="btn btn-ghost">Annuler</a>
            </div>
        </form>
    </div>
</div>
