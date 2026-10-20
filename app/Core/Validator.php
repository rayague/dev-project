<?php
/**
 * ============================================================
 *  CLASSE : Validator (Validation des formulaires)
 * ============================================================
 *
 *  RÔLE DANS LE MVC :
 *  Avant d'insérer quoi que ce soit en base de données, il faut
 *  VÉRIFIER que ce que l'utilisateur a saisi est correct :
 *  - l'e-mail a-t-il une forme d'e-mail ?
 *  - le champ est-il rempli ?
 *  - le montant est-il un nombre positif ?
 *
 *  Cette classe produit un tableau d'erreurs compréhensibles,
 *  qui sera réaffiché dans le formulaire pour guider l'utilisateur.
 *
 *  SÉCURITÉ : NE JAMAIS faire confiance aux données du navigateur !
 * ============================================================
 */

declare(strict_types=1);

class Validator
{
    /**
     * @var array  Tableau des erreurs trouvées.
     *             Structure : ['email' => ['L'e-mail est invalide'], ...]
     */
    private array $errors = [];

    /**
     * ------------------------------------------------------------
     * VALIDER UN CHAMP SELON UNE LISTE DE RÈGLES
     * ------------------------------------------------------------
     * Exemple d'appel :
     *   $v->check('email', $email, ['required', 'email']);
     *
     * @param string $name     Nom du champ (sert de clé dans les erreurs)
     * @param mixed  $value    Valeur à tester (ex : ce que l'utilisateur a tapé)
     * @param array  $rules    Liste de règles : ['required', 'email', 'min:3']
     * @param string $label    Libellé lisible pour l'erreur ("Adresse e-mail")
     */
    public function check(string $name, mixed $value, array $rules, string $label = ''): void
    {
        // Si aucun libellé n'est fourni, on utilise le nom du champ.
        if ($label === '') {
            $label = $name;
        }

        // On parcourt chaque règle une par une.
        foreach ($rules as $rule) {

            // ------------------------------------------------
            // Cas particulier : règle avec paramètre ("min:3")
            // ------------------------------------------------
            // strpos cherche ":" dans la règle. S'il existe, on sépare
            // le nom de la règle et son paramètre.
            $param = null;
            if (strpos($rule, ':') !== false) {
                // "min:3" → $nom = "min", $param = "3"
                [$rule, $param] = explode(':', $rule, 2);
            }

            // ------------------------------------------------
            // RÈGLE : "required" (le champ est obligatoire)
            // ------------------------------------------------
            if ($rule === 'required') {
                // On considère vide : chaîne vide, null, ou tableau vide.
                $estVide = $value === null
                        || $value === ''
                        || (is_array($value) && count($value) === 0);

                if ($estVide) {
                    // On ajoute l'erreur et on ARRÊTE les autres règles :
                    // inutile de vérifier le format d'un champ vide.
                    $this->addError($name, $label . ' est obligatoire.');
                    return;
                }
                continue; // Le champ est rempli → on passe à la règle suivante
            }

            // ------------------------------------------------
            // RÈGLE : "email" (forme d'adresse e-mail valide)
            // ------------------------------------------------
            if ($rule === 'email') {
                // filter_var vérifie la syntaxe de l'e-mail.
                if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $this->addError($name, $label . ' n\'est pas une adresse e-mail valide.');
                    return;
                }
                continue;
            }

            // ------------------------------------------------
            // RÈGLE : "min:N" (longueur minimale de N caractères)
            // ------------------------------------------------
            if ($rule === 'min') {
                // mb_strlen = nombre de caractères (compatible accents).
                if (mb_strlen((string) $value) < (int) $param) {
                    $this->addError($name, $label . ' doit contenir au moins ' . $param . ' caractères.');
                    return;
                }
                continue;
            }

            // ------------------------------------------------
            // RÈGLE : "max:N" (longueur maximale de N caractères)
            // ------------------------------------------------
            if ($rule === 'max') {
                if (mb_strlen((string) $value) > (int) $param) {
                    $this->addError($name, $label . ' ne doit pas dépasser ' . $param . ' caractères.');
                    return;
                }
                continue;
            }

            // ------------------------------------------------
            // RÈGLE : "numeric" (doit être un nombre)
            // ------------------------------------------------
            if ($rule === 'numeric') {
                if (!is_numeric($value)) {
                    $this->addError($name, $label . ' doit être un nombre.');
                    return;
                }
                continue;
            }

            // ------------------------------------------------
            // RÈGLE : "positive" (doit être un nombre > 0)
            // ------------------------------------------------
            if ($rule === 'positive') {
                if (!is_numeric($value) || (float) $value <= 0) {
                    $this->addError($name, $label . ' doit être un nombre supérieur à 0.');
                    return;
                }
                continue;
            }

            // ------------------------------------------------
            // RÈGLE : "date" (format AAAA-MM-JJ valide)
            // ------------------------------------------------
            if ($rule === 'date') {
                // preg_match compare la chaîne avec un motif de date.
                $ok = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $value) === 1;
                if (!$ok) {
                    $this->addError($name, $label . ' doit être une date au format AAAA-MM-JJ.');
                    return;
                }
                continue;
            }

            // ------------------------------------------------
            // RÈGLE : "in:a,b,c" (la valeur doit être dans la liste)
            // ------------------------------------------------
            if ($rule === 'in') {
                $valeursAcceptees = explode(',', (string) $param); // "a,b,c" → ['a','b','c']
                if (!in_array((string) $value, $valeursAcceptees, true)) {
                    $this->addError($name, $label . ' contient une valeur non autorisée.');
                    return;
                }
                continue;
            }
        }
    }

    /**
     * ------------------------------------------------------------
     * AJOUTER UNE ERREUR À LA LISTE
     * ------------------------------------------------------------
     * @param string $name    Clé du champ concerné
     * @param string $message Message expliqué à l'utilisateur
     */
    private function addError(string $name, string $message): void
    {
        // Si la clé n'existe pas encore, on crée un tableau vide.
        if (!isset($this->errors[$name])) {
            $this->errors[$name] = [];
        }

        // On ajoute le message d'erreur dans la liste du champ.
        $this->errors[$name][] = $message;
    }

    /**
     * ------------------------------------------------------------
     * Y A-T-IL DES ERREURS ?
     * ------------------------------------------------------------
     * @return bool true si au moins une erreur a été détectée
     */
    public function fails(): bool
    {
        return count($this->errors) > 0;
    }

    /**
     * ------------------------------------------------------------
     * OBTENIR LA LISTE DES ERREURS
     * ------------------------------------------------------------
     * @return array  ['email' => ['Message 1'], 'name' => ['Message 2']]
     */
    public function errors(): array
    {
        return $this->errors;
    }

    /**
     * ------------------------------------------------------------
     * OBTENIR LA PREMIÈRE ERREUR D'UN CHAMP (affichage rapide)
     * ------------------------------------------------------------
     * @param  string $name Nom du champ
     * @return string|null  Le message, ou null s'il n'y en a pas
     */
    public function first(string $name): ?string
    {
        // On retourne le 1er message du champ s'il existe, sinon null.
        return $this->errors[$name][0] ?? null;
    }
}
