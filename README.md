# GestCommandes — Application de gestion de commandes (PHP + MySQL)

Projet web universitaire : une application **MVC** en PHP pur (sans framework)
permettant de gérer des commandes, avec authentification et gestion des
utilisateurs. Interface stylée avec **Tailwind CSS** (CDN).

---

## 1. Fonctionnalités

| Fonction | Détail |
|---|---|
| Inscription / Connexion / Déconnexion | Mots de passe hachés (bcrypt), jetons CSRF |
| Tableau de bord | Statistiques (total, montant, dernières commandes) |
| Commandes (CRUD) | Créer, lister, rechercher, filtrer, modifier, supprimer |
| Utilisateurs (CRUD) | Créer, lister, rechercher, modifier, supprimer |
| Filtres & recherche | Par statut, nom du client, référence ; pagination |
| Sécurité | Requêtes préparées (anti-injection SQL), CSRF, sessions protégées |

> Le CRUD principal du site porte sur les **commandes**. La gestion des
> utilisateurs est un CRUD secondaire (accès réservé à tous les membres
> connectés, conformément au cahier des charges).

## 2. Prérequis

- **XAMPP** (Apache + PHP 8+ + MySQL) : <https://www.apachefriends.org/>
- PHP 8.0 minimum recommandé (développé sous PHP 8.2)

## 3. Installation

### 3.1. Copier le projet

Dans le dossier `C:\xampp\htdocs\`, copier ce dossier sous le nom `projet-web`.
Résultat attendu :

```
C:\xampp\htdocs\projet-web\public\index.php   ← point d'entrée
```

### 3.2. Créer la base de données

1. Démarrer **MySQL** dans le panneau de contrôle XAMPP.
2. Ouvrir **phpMyAdmin** (http://localhost/phpmyadmin).
3. Onglet **Importer** → choisir le fichier `database/schema.sql` → Exécuter.

Ou en ligne de commande :

```
mysql -u root < database/schema.sql
```

Le script crée la base `gestion_commandes` et la remplit avec un compte
de démonstration et 5 commandes d'exemple.

### 3.3. Connexion à la base

Tout est réglé dans `config/config.php` (par défaut : `root` sans mot de
passe, base `gestion_commandes`). Aucune modification requise sur XAMPP
standard.

## 4. Lancement

### Méthode A — Avec XAMPP / Apache (recommandée)

1. Démarrer **Apache** et **MySQL** dans le panneau de contrôle XAMPP.
2. Ouvrir : **http://localhost/projet-web/login**

Le `.htaccess` racine envoie automatiquement les URL vers `public/index.php`
(on n'expose jamais `app/`, `config/` ni `database/`).

### Méthode B — Avec le serveur intégré de PHP (tests)

Depuis la racine du projet :

```
php -S 127.0.0.1:8000 -t public server.php
```

Puis ouvrir : **http://127.0.0.1:8000/login**

> Le fichier `server.php` joue le rôle du `.htaccess` car le serveur intégré
> de PHP ne le lit pas.

## 5. Compte de démonstration

| Identifiant | Mot de passe |
|---|---|
| `test@test.com` | `password123` |

Sinon, utiliser le formulaire **Inscription** pour créer un compte.

## 6. Architecture du projet (schéma MVC)

```
projet-web/
├── public/                 ← SEUL dossier exposé par le serveur
│   ├── index.php           ← Point d'entrée unique (front controller)
│   └── .htaccess           ← Réécriture des URL vers index.php
├── app/
│   ├── Core/               ← Moteur de l'application
│   │   ├── Router.php      ← Chef de gare : associe URL → contrôleur
│   │   ├── Controller.php  ← Classe de base des contrôleurs
│   │   ├── Model.php       ← Classe de base des modèles (CRUD générique)
│   │   ├── Database.php    ← Connexion PDO à MySQL
│   │   ├── Auth.php        ← Sessions, connexion, protection des pages
│   │   ├── Validator.php   ← Validation des formulaires
│   │   └── helpers.php     ← Fonctions utiles (e(), money(), badges...)
│   ├── Controllers/        ← La logique de chaque page
│   ├── Models/             ← L'accès aux données (User, Order)
│   └── View/               ← Les pages HTML (affichage uniquement)
│       ├── layouts/        ← En-tête et pied de page du site
│       └── auth|dashboard|orders|users|errors/
├── config/config.php       ← Réglages (base de données, débogage...)
├── database/schema.sql     ← Création de la base + données d'exemple
├── routes.php              ← La table des routes (URL → contrôleur)
├── .htaccess               ← Redirection vers public/ (Apache)
└── server.php              ← Équivalent du .htaccess pour php -S
```

### Qui fait quoi ?

- **Vue** (`app/View/...`) : uniquement du HTML concerté avec PHP pour
  afficher les données. Aucune logique métier.
- **Contrôleur** (`app/Controllers/...`) : il lit le formulaire envoyé,
  demande les données au modèle, contrôle l'affichage.
- **Modèle** (`app/Models/...`) : il parle à MySQL (requêtes SQL) et
  renvoie les résultats.

## 7. Les routes

Toutes les routes sont déclarées dans `routes.php`. Format de base :

| Méthode | URL | Rôle |
|---|---|---|
| GET | `/login` | Formulaire de connexion |
| POST | `/login` | Traiter la connexion |
| GET | `/register` | Formulaire d'inscription |
| POST | `/register` | Créer le compte |
| POST | `/logout` | Déconnexion |
| GET | `/dashboard` | Tableau de bord (page privée) |
| GET | `/orders` | Liste des commandes (+ recherche/filtres) |
| GET | `/orders/create` | Formulaire de création |
| POST | `/orders` | Enregistrer la commande |
| GET | `/orders/show?id=3` | Détail d'une commande |
| GET | `/orders/edit?id=3` | Formulaire de modification |
| POST | `/orders/update` | Enregistrer la modification |
| POST | `/orders/delete` | Supprimer la commande |
| GET/POST | `/users...` | CRUD des utilisateurs (mêmes formats) |

## 8. Sécurité mise en place

- **Requêtes préparées** partout (PDO) → aucune injection SQL possible.
- **Jetons CSRF** sur tous les formulaires → un faux formulaire envoyé
  depuis un autre site est refusé.
- **Mots de passe hachés** avec `password_hash()` (bcrypt) → jamais
  stockés en clair.
- **Vérification des sessions** sur chaque page privée → un visiteur se
  retrouve redirigé vers `/login`.
- **Sorties encodées** avec `htmlspecialchars()` → pas d'injection XSS.
- **Fichiers PHP bloqués** hors du dossier `public/` (respect du MVC).

## 9. Dépannage rapide

| Problème | Solution |
|---|---|
| `Unknown database 'gestion_commandes'` | Importer `database/schema.sql` |
| `Access denied for user 'root'` | Adapter `config/config.php` |
| Page blanche | Vérifier que MySQL est démarré (panneau XAMPP) |
| 404 sur toutes les URL (Apache) | Vérifier que `.htaccess` est bien présent et `mod_rewrite` activé |