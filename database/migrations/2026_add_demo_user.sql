-- ============================================================
--  MIGRATION : compte utilisateur de démonstration (rôle user)
-- ============================================================
--
--  OBJECTIF :
--  Ajouter user@example.com / password123 (rôle « user ») pour
--  tester le RBAC lecture seule sur les produits / le catalogue.
--  Idempotent : ne crée pas de doublon si l'e-mail existe déjà.
--
--  Mot de passe : même hachage bcrypt que admin@ascg.fr (password123).
-- ============================================================

USE gestion_commandes;

INSERT INTO users (name, email, password, role)
SELECT
    'Utilisateur Demo',
    'user@example.com',
    '$2y$10$GanD1bpzEtFgCiIu6U0sm./W.e94S14Zokr9MPA.792k.cT6FsKg2',
    'user'
WHERE NOT EXISTS (
    SELECT 1 FROM users WHERE email = 'user@example.com'
);
