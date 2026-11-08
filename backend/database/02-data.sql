-- ============================================================
-- Jeu de données de DÉVELOPPEMENT (fictif) - Vite & Gourmand
-- À placer dans backend/database/02-seed.sql (après 01-schema.sql)
-- Les id sont générés par AUTO_INCREMENT : ce script suppose une base NEUVE,
-- les références (role_id, menu_id...) reposent sur l'ordre d'insertion.
-- Compte admin : admin@mail.fr (mot de passe d'origine)
-- Autres comptes : mot de passe Motdepasse123!
-- ============================================================
SET NAMES utf8mb4;

-- ---------- Rôles ----------
INSERT INTO role (libelle) VALUES
    ('ROLE_ADMIN'),
    ('ROLE_EMPLOYE'),
    ('ROLE_USER');

-- ---------- Utilisateurs ----------
INSERT INTO utilisateur (email, password, prenom, nom, telephone, ville, pays, adresse, statut, role_id) VALUES
    ('admin@mail.fr', '$2y$10$6W.7FUTUvRXWGQbaXp9Wlu51aNNigZ9VXxCX5vouW8QIxcLyN2EG2', 'prenom', 'nom', 'telephone', 'ville', 'pays', 'adresse', 'actif', 1 ),
    ('employe@example.com', '$2y$10$jJ5Gd2nKh2VEwvsVnG5Ca.RLNjHBnjM3dC/kvvByT15jD/drQDldW', 'Bruno',   'Employé', '0556000002', 'Bordeaux', 'France', '8 cours de l''Intendance',  'actif', 2),
    ('client1@example.com', '$2y$10$jJ5Gd2nKh2VEwvsVnG5Ca.RLNjHBnjM3dC/kvvByT15jD/drQDldW', 'Camille', 'Martin',  '0612345601', 'Mérignac', 'France', '5 avenue de la Libération', 'actif', 3),
    ('client2@example.com', '$2y$10$jJ5Gd2nKh2VEwvsVnG5Ca.RLNjHBnjM3dC/kvvByT15jD/drQDldW', 'David',   'Durand',  '0612345602', 'Pessac',   'France', '27 rue Eugène Jacquet',    'actif', 3),
    ('client3@example.com', '$2y$10$jJ5Gd2nKh2VEwvsVnG5Ca.RLNjHBnjM3dC/kvvByT15jD/drQDldW', 'Emma',    'Bernard', '0612345603', 'Talence',  'France', '14 rue Frédéric Sévène',   'actif', 3);

-- ---------- Régimes ----------
INSERT INTO regime (libelle) VALUES
    ('Classique'),
    ('Végétarien'),
    ('Vegan');

-- ---------- Thèmes ----------
INSERT INTO theme (libelle) VALUES
    ('Classique'),
    ('Noël'),
    ('Pâques'),
    ('Mariage'),
    ('Événement');

-- ---------- Allergènes (14 allergènes réglementaires) ----------
INSERT INTO allergene (libelle) VALUES
    ('Gluten'),
    ('Crustacés'),
    ('Œufs'),
    ('Poissons'),
    ('Arachides'),
    ('Soja'),
    ('Lait'),
    ('Fruits à coque'),
    ('Céleri'),
    ('Moutarde'),
    ('Sésame'),
    ('Sulfites'),
    ('Lupin'),
    ('Mollusques');

-- ---------- Plats (photo vide : à remplacer par vos images) ----------
INSERT INTO plat (titre, category, photo) VALUES
    ('Velouté de potimarron',            'entrée',  ''),
    ('Foie gras et chutney de figues',   'entrée',  ''),
    ('Salade de chèvre chaud',           'entrée',  ''),
    ('Magret de canard sauce miel',      'plat',    ''),
    ('Dos de cabillaud et risotto',      'plat',    ''),
    ('Curry de légumes et lait de coco', 'plat',    ''),
    ('Bûche chocolat noisette',          'dessert', ''),
    ('Tarte aux fruits de saison',       'dessert', ''),
    ('Salade de fruits frais',           'dessert', '');

-- ---------- Plat <-> Allergènes (plat_id, allergene_id) ----------
INSERT INTO plat_allergene (plat_id, allergene_id) VALUES
    (1, 7),
    (2, 1), (2, 12),
    (3, 1), (3, 7), (3, 10),
    (4, 12),
    (5, 4), (5, 7),
    (6, 6),
    (7, 1), (7, 3), (7, 7), (7, 8),
    (8, 1), (8, 3), (8, 7);

-- ---------- Menus (image vide : à remplacer par vos images) ----------
INSERT INTO menu (titre, nombre_personne_minimum, prix_par_personne, description, quantite_restante, regime_id, theme_id, image, statut, delai) VALUES
    ('Menu Tradition', 6, 38.00, 'Un repas complet aux saveurs du terroir, idéal pour les réunions de famille et les repas entre amis.', 20, 1, 1, '', 'actif', 7),
    ('Menu Noël Gourmand', 8, 55.00, 'Foie gras, magret de canard et bûche maison : le menu festif pour célébrer les fêtes de fin d''année.', 10, 1, 2, '', 'actif', 14),
    ('Menu Végétarien Éveil', 4,  32.00, 'Une sélection de plats végétariens colorés et de saison, préparés avec des produits locaux.', 15, 2, 5, '', 'actif', 5),
    ('Menu Mariage Prestige', 20, 72.00, 'Un menu raffiné en trois services pour faire de votre mariage un moment inoubliable.', 5, 1, 4, '', 'actif', 30);

-- ---------- Menu <-> Plats (menu_id, plat_id) ----------
INSERT INTO menu_plat (menu_id, plat_id) VALUES
    (1, 3), (1, 4), (1, 8),
    (2, 2), (2, 4), (2, 7),
    (3, 1), (3, 6), (3, 9),
    (4, 2), (4, 5), (4, 7);

-- ---------- Avis ----------
INSERT INTO avis (titre, description, note, statut, date, utilisateur_id) VALUES
    ('Excellent repas', 'Tout était délicieux et la livraison était à l''heure. Je recommande.', 5, 'validé',     '2026-09-14', 3),
    ('Très bon moment', 'Menu généreux, nos invités ont adoré le magret.',                       4, 'validé',     '2026-10-02', 4),
    ('Service parfait', 'Équipe professionnelle et à l''écoute pour notre mariage.',             5, 'en attente', '2026-11-03', 5);

-- ---------- Commandes ----------
INSERT INTO commande (numero_de_commande, date_commande, date_prestation, heure_livraison, adresse_livraison, prix_menu, nombre_personne, prix_livraison, statut, pret_materiel, restitution_materiel, utilisateur_id, menu_id) VALUES
    ('CMD-2026-0001', '2026-09-01', '2026-09-12', '12:00', '5 avenue de la Libération, 33700 Mérignac', 228.00, 6,  5, 'terminée',   0, 0, 3, 1),
    ('CMD-2026-0002', '2026-09-20', '2026-10-01', '19:30', '27 rue Eugène Jacquet, 33600 Pessac',      440.00, 8, 12, 'livré',      1, 1, 4, 2),
    ('CMD-2026-0003', '2026-10-25', '2026-11-20', '13:00', '14 rue Frédéric Sévène, 33400 Talence',    128.00, 4,  8, 'en attente', 0, 0, 5, 3);

-- ---------- Horaires ----------
INSERT INTO horaire (jour, statut, heure_ouverture, heure_fermeture) VALUES
    ('Lundi',    'ouvert', '09:00', '18:00'),
    ('Mardi',    'ouvert', '09:00', '18:00'),
    ('Mercredi', 'ouvert', '09:00', '18:00'),
    ('Jeudi',    'ouvert', '09:00', '18:00'),
    ('Vendredi', 'ouvert', '09:00', '19:00'),
    ('Samedi',   'ouvert', '10:00', '17:00'),
    ('Dimanche', 'fermé',  '00:00', '00:00');