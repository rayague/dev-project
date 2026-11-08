<?php
/**
 * ============================================================
 *  VUE : products/create.php  (FORMULAIRE DE CRÉATION)
 * ============================================================
 *
 *  RÔLE DANS LE MVC :
 *  Formulaire HTML pour créer un produit.
 *  Envoi en POST vers /products → ProductController::store().
 *
 *  VARIABLES REÇUES :
 *   - $old    : valeurs déjà saisies (après une erreur de validation)
 *   - $errors : messages d'erreur par champ
 * ============================================================
 */

$old    = $old ?? [];
$errors = $errors ?? [];

// Récupère une ancienne valeur de champ (ou une valeur par défaut).
$valeur = function (string $champ, string $defaut = '') use ($old): string {
    return (string) ($old[$champ] ?? $defaut);
};

// Premier message d'erreur associé à un champ (ou chaîne vide).
$erreurDe = function (string $champ) use ($errors): string {
    return $errors[$champ][0] ?? '';
};
?>

<div class="mx-auto w-full max-w-4xl">

    <!-- En-tête de page -->
    <div class="anim-fade-up mb-6">
        <h2 class="font-display text-2xl font-bold tracking-tight text-white">Nouveau produit</h2>
        <a href="<?= url('/products') ?>" class="mt-1 inline-flex items-center gap-1 text-sm font-medium text-emerald-300 transition hover:text-emerald-200">
            ← Retour à la liste
        </a>
    </div>

    <div class="glass-card glass-card--flat anim-fade-up d-1 overflow-hidden">
        <div class="glass-head">
            <h2 class="glass-kicker">Informations du produit</h2>
        </div>

        <!-- Formulaire de création (CSRF inclus) -->
        <form method="POST" action="<?= url('/products') ?>" class="space-y-5 px-6 py-7 sm:px-8">
            <?= csrf_field() ?>

            <!-- Champ : nom (obligatoire) -->
            <div>
                <label for="name" class="field-label">Nom <span class="req">*</span></label>
                <input type="text" id="name" name="name" value="<?= e($valeur('name')) ?>" required
                       class="input <?= $erreurDe('name') !== '' ? 'input--err' : '' ?>"
                       placeholder="Nom du produit">
                <?php if ($erreurDe('name') !== ''): ?>
                    <p class="field-err"><?= e($erreurDe('name')) ?></p>
                <?php endif; ?>
            </div>

            <!-- Ligne : prix + stock -->
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
                           value="<?= e($valeur('stock', '0')) ?>" required
                           class="input <?= $erreurDe('stock') !== '' ? 'input--err' : '' ?>">
                    <?php if ($erreurDe('stock') !== ''): ?>
                        <p class="field-err"><?= e($erreurDe('stock')) ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Description facultative -->
            <div>
                <label for="description" class="field-label">Description <span class="font-normal text-slate-500">(facultatif)</span></label>
                <textarea id="description" name="description" rows="4"
                          class="input <?= $erreurDe('description') !== '' ? 'input--err' : '' ?>"><?= e($valeur('description')) ?></textarea>
                <?php if ($erreurDe('description') !== ''): ?>
                    <p class="field-err"><?= e($erreurDe('description')) ?></p>
                <?php endif; ?>
            </div>

            <div class="flex flex-wrap gap-3 pt-2">
                <button type="submit" class="btn btn-primary">Enregistrer</button>
                <a href="<?= url('/products') ?>" class="btn btn-ghost">Annuler</a>
            </div>
        </form>
    </div>
</div>
