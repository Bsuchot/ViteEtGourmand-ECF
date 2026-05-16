CREATE DATABASE vite_et_gourmand;

USE vite_et_gourmand;

CREATE TABLE role
(
    id INT PRIMARY KEY NOT NULL AUTO_INCREMENT,
    libelle VARCHAR(50) NOT NULL
);


CREATE TABLE utilisateur
(
    id INT PRIMARY KEY NOT NULL AUTO_INCREMENT,
    email VARCHAR(254) NOT NULL UNIQUE,
    password VARCHAR(254) NOT NULL ,
    prenom VARCHAR(50) NOT NULL,
    nom VARCHAR(50) NOT NULL,
    telephone VARCHAR(20) NOT NULL,
    ville VARCHAR(100) NOT NULL,
    pays VARCHAR(100) NOT NULL,
    adresse VARCHAR(255) NOT NULL,
    statut VARCHAR(12),
    role_id INT NOT NULL,
    FOREIGN KEY (role_id) REFERENCES role(id)
);

CREATE TABLE avis
(
    id INT PRIMARY KEY NOT NULL AUTO_INCREMENT,
    titre VARCHAR(50) NOT NULL,
    description VARCHAR(125) NOT NULL,
    note TINYINT NOT NULL,
    statut VARCHAR (50) NOT NULL,
    date DATE NOT NULL,
    utilisateur_id INT NOT NULL,
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateur(id)
);

CREATE TABLE regime
(
    id INT PRIMARY KEY NOT NULL AUTO_INCREMENT,
    libelle VARCHAR(50) NOT NULL
);

CREATE TABLE theme
(
    id INT PRIMARY KEY NOT NULL AUTO_INCREMENT,
    libelle VARCHAR(50) NOT NULL
);

CREATE TABLE menu
(
    id INT PRIMARY KEY NOT NULL AUTO_INCREMENT,
    titre VARCHAR(50) NOT NULL,
    nombre_personne_minimum INT NOT NULL,
    prix_par_personne DOUBLE NOT NULL,
    description VARCHAR(50) NOT NULL,
    quantite_restante int NOT NULL,
    regime_id INT NOT NULL,
    theme_id INT NOT NULL,
    image BLOB NOT NULL,
    statut VARCHAR(50) NOT NULL,
    delai INT NOT NULL,
    service VARCHAR(50) NOT NULL,
    FOREIGN KEY (regime_id) REFERENCES regime(id),
    FOREIGN KEY (theme_id) REFERENCES theme(id)
);

CREATE TABLE plat
(
    id INT PRIMARY KEY NOT NULL AUTO_INCREMENT,
    titre VARCHAR(50) NOT NULL,
    category VARCHAR(50) NOT NULL,
    photo BLOB NOT NULL
);

CREATE TABLE allergene
(
    id INT PRIMARY KEY NOT NULL AUTO_INCREMENT,
    libelle VARCHAR(50) NOT NULL
);

CREATE TABLE menu_plat
(
    menu_id INT NOT NULL,
    plat_id INT NOT NULL,
    PRIMARY KEY (menu_id, plat_id),
    FOREIGN KEY (menu_id) REFERENCES menu(id),
    FOREIGN KEY (plat_id) REFERENCES plat(id)
);

CREATE TABLE plat_allergene
(
    allergene_id INT NOT NULL,
    plat_id INT NOT NULL,
    PRIMARY KEY (allergene_id, plat_id),
    FOREIGN KEY (allergene_id) REFERENCES allergene(id),
    FOREIGN KEY (plat_id) REFERENCES plat(id)
);

CREATE TABLE commande
(
    id INT PRIMARY KEY NOT NULL AUTO_INCREMENT,
    numero_de_commande VARCHAR(50) NOT NULL,
    date_commande DATE,
    date_prestation DATE,
    heure_livraison VARCHAR(50) NOT NULL,
    adresse_livraison VARCHAR(255) NOT NULL,
    prix_menu DOUBLE NOT NULL,
    nombre_personne INT NOT NULL,
    prix_livraison INT NOT NULL,
    statut VARCHAR(50) NOT NULL,
    pret_materiel BOOL NOT NULL,
    restitution_materiel BOOL NOT NULL,
    utilisateur_id INT NOT NULL,
    menu_id INT NOT NULL,
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateur(id),
    FOREIGN KEY (menu_id) REFERENCES menu(id)
);

CREATE TABLE horaire
(
    id INT PRIMARY KEY NOT NULL AUTO_INCREMENT,
    jour VARCHAR(50) NOT NULL,
    statut VARCHAR(50) NOT NULL,
    heure_ouverture VARCHAR(50) NOT NULL,
    heure_fermeture VARCHAR(50) NOT NULL
);

INSERT INTO role (libelle) VALUES
    ('ROLE_ADMIN'),
    ('ROLE_EMPLOYE'),
    ('ROLE_USER');

INSERT INTO utilisateur (email, password, prenom, nom, telephone, ville, pays, adresse, role_id) VALUES
    (
     'admin@mail.fr',
     '$2y$10$6W.7FUTUvRXWGQbaXp9Wlu51aNNigZ9VXxCX5vouW8QIxcLyN2EG2',
     'prenom',
     'nom',
     'telephone',
     'ville',
     'pays',
     'adresse',
     1
     );



