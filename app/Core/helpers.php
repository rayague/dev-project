<?php
/**
 * ============================================================
 *  FONCTIONS UTILITAIRES (helpers)
 * ============================================================
 *
 *  Ce fichier regroupe de PETITES fonctions réutilisées partout
 *  dans les vues et les contrôleurs. Il est chargé par
 *  public/index.php au démarrage du site.
 * ============================================================
 */

declare(strict_types=1);

/**
 * ------------------------------------------------------------
 * ÉCHAPPER UN TEXTE AVANT DE L'AFFICHER EN HTML (sécurité !)
 * ------------------------------------------------------------
 * Si un utilisateur tape "<script>alert('hacker')</script>" dans
 * un champ, cette fonction le transforme en texte inoffensif
 * "&lt;script&gt;..." qui s'affichera au lieu de s'exécuter.
 *
 * C'est LA protection contre les attaques XSS.
 *
 * @param  string $texte Le texte brut
 * @return string        Le texte sécurisé pour le HTML
 */
function e(?string $texte): string
{
    // htmlspecialchars convertit < > " ' & en codes HTML sûrs.
    return htmlspecialchars((string) $texte, ENT_QUOTES, 'UTF-8');
}

/**
 * ------------------------------------------------------------
 * FORMATER UN MONTANT EN EUROS
 * ------------------------------------------------------------
 * @param  float|string $montant Le montant (ex : 1250.5)
 * @return string        Texte formaté (ex : "1 250,50 €")
 */
function money(float|string $montant): string
{
    // number_format(montant, 2 décimales, ",", " espace pour les milliers")
    return number_format((float) $montant, 2, ',', ' ') . ' €';
}

/**
 * ------------------------------------------------------------
 * FORMATER UNE DATE DU FORMAT SQL VERS UN FORMAT LISIBLE
 * ------------------------------------------------------------
 * "2026-03-10" → "10/03/2026"
 *
 * @param  string|null $date Date au format AAAA-MM-JJ
 * @return string             Date au format JJ/MM/AAAA
 */
function date_fr(?string $date): string
{
    // Si la date est vide, on retourne un tiret (affichage propre).
    if ($date === null || $date === '') {
        return '—';
    }

    // strtotime interprète la date, date() la reformatte.
    // "d" = jour, "m" = mois, "Y" = année sur 4 chiffres.
    return date('d/m/Y', strtotime($date));
}

/**
 * ------------------------------------------------------------
 * RETOURNER LE LIBELLÉ FRANÇAIS D'UN STATUT DE COMMANDE
 * ------------------------------------------------------------
 * @param  string $statut Code stocké en base (ex : "en_attente")
 * @return string         Texte affiché (ex : "En attente")
 */
function statut_label(string $statut): string
{
    // On utilise un tableau associatif (dictionnaire code → libellé).
    $labels = [
        'en_attente' => 'En attente', // Statut 1
        'validee'    => 'Validée',    // Statut 2
        'livree'     => 'Livrée',     // Statut 3
        'annulee'    => 'Annulée',    // Statut 4
    ];

    // ?? = si le statut est inconnu, on affiche "Inconnu".
    return $labels[$statut] ?? 'Inconnu';
}

/**
 * ------------------------------------------------------------
 * RETOURNER LES CLASSES TAILWIND CSS POUR COLOLER UN STATUT
 * ------------------------------------------------------------
 * Ces classes servent à colorer les « badges » (petites pastilles)
 * dans les tableaux de commandes.
 *
 * @param  string $statut Code du statut
 * @return string         Chaîne de classes CSS Tailwind
 */
function statut_badge(string $statut): string
{
    // Chaque statut a sa propre couleur de verre (thème sombre) :
    // - ambre = en attente,  émeraude = validée
    // - ciel = livrée,       rouge = annulée
    $couleurs = [
        'en_attente' => 'bg-amber-400/10 text-amber-300 ring-amber-400/25',
        'validee'    => 'bg-emerald-400/10 text-emerald-300 ring-emerald-400/25',
        'livree'     => 'bg-sky-400/10 text-sky-300 ring-sky-400/25',
        'annulee'    => 'bg-red-400/10 text-red-300 ring-red-400/25',
    ];

    // Classes appliquées à TOUS les badges (la classe .chip est
    // définie dans public/assets/css/app.css).
    $commun = 'chip ring-1 ring-inset ';

    // On retourne le badge commun + la couleur du statut.
    return $commun . ($couleurs[$statut] ?? 'bg-white/10 text-slate-300 ring-white/15');
}

/**
 * ------------------------------------------------------------
 * GÉNÉRER UNE RÉFÉRENCE DE COMMANDE UNIQUE
 * ------------------------------------------------------------
 * Exemple de résultat : "CMD-2026-007"
 *
 * @return string La référence prête à être enregistrée
 */
function generer_reference(): string
{
    // Année en cours (ex : 2026)
    $annee = date('Y');

    // random_int = nombre aléatoire cryptographiquement sûr entre 100 et 999.
    $numero = random_int(100, 999);

    // On assemble les morceaux : "CMD-" + année + "-" + numéro.
    return 'CMD-' . $annee . '-' . $numero;
}

/**
 * ------------------------------------------------------------
 * OBTENIR LE JETON DE SÉCURITÉ CSRF DE LA SESSION
 * ------------------------------------------------------------
 * Le jeton est une clé secrète aléatoire stockée en session et
 * cachée dans CHAQUE formulaire. Au retour du formulaire, le
 * contrôleur vérifie que les deux jetons correspondent (csrfCheck).
 *
 * Cela empêche un site malveillant d'envoyer des formulaires à
 * notre insu (attaque appelée "CSRF").
 *
 * @return string Le jeton (64 caractères hexadécimaux)
 */
function csrf_token(): string
{
    // Si aucun jeton n'existe encore dans la session...
    if (empty($_SESSION['csrf_token'])) {
        // ...on en fabrique un nouveau, impossible à deviner.
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    // On retourne le jeton existant ou le nouveau.
    return $_SESSION['csrf_token'];
}

/**
 * ------------------------------------------------------------
 * GÉNÉRER LE CHAMP CACHÉ HTML DU JETON CSRF
 * ------------------------------------------------------------
 * À placer DANS CHAQUE formulaire :
 *     <form method="POST">
 *         <?= csrf_field() ?>
 *         ...
 *
 * @return string Une balise <input type="hidden"> complète
 */
function csrf_field(): string
{
    // On génère la balise HTML en échappant le jeton (sécurité XSS).
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

/**
 * ------------------------------------------------------------
 * CONSTRUIRE UNE URL INTERNE DU SITE (avec le sous-dossier)
 * ------------------------------------------------------------
 * Si le site est installé dans un sous-dossier XAMPP :
 *     http://localhost/projet-web/orders
 * alors BASE_URL vaut "/projet-web" et url('/orders') renvoie
 * "/projet-web/orders".
 *
 * Sans sous-dossier (serveur php -S, racine Apache...), BASE_URL
 * est vide et url('/orders') renvoie simplement "/orders".
 *
 * UTILISATION DANS LES VUES :
 *     <a href="<?= url('/orders') ?>">   →   href="/projet-web/orders"
 *
 * @param string $chemin Adresse interne, ex : "/orders/show?id=3"
 * @return string        Adresse complète à mettre dans le HTML
 */
function url(string $chemin = ''): string
{
    // BASE_URL (définie dans index.php) + la route demandée.
    // ltrim enlève le "/" de tête pour éviter un double "//".
    $url = BASE_URL . '/' . ltrim($chemin, '/');

    // Sauf pour la racine, on retire le "/" final (propreté).
    return rtrim($url, '/');
}
