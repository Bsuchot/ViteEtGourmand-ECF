CREATE DATABASE vite_et_gourmand;

USE vite_et_gourmand;

CREATE TABLE role
(
    role_id INT PRIMARY KEY NOT NULL AUTO_INCREMENT,
    libelle VARCHAR(50) NOT NULL
);


CREATE TABLE utilisateur
(
    utilisateur_id INT PRIMARY KEY NOT NULL AUTO_INCREMENT,
    email VARCHAR(50) NOT NULL,
    password VARCHAR(255) NOT NULL UNIQUE,
    prenom VARCHAR(50) NOT NULL,
    nom VARCHAR(50) NOT NULL,
    telephone VARCHAR(50) NOT NULL,
    ville VARCHAR(50) NOT NULL,
    pays VARCHAR(50) NOT NULL,
    adresse VARCHAR(50) NOT NULL,
    api_token VARCHAR(50),
    role_id INT NOT NULL,
    FOREIGN KEY (role_id) REFERENCES role(role_id)
);

CREATE TABLE avis
(
    avis_id INT PRIMARY KEY NOT NULL AUTO_INCREMENT,
    titre VARCHAR(50) NOT NULL,
    description VARCHAR(125) NOT NULL,
    note TINYINT NOT NULL,
    statut VARCHAR (50) NOT NULL,
    date DATE NOT NULL,
    utilisateur_id INT NOT NULL,
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateur(utilisateur_id)
);

CREATE TABLE regime
(
    regime_id INT PRIMARY KEY NOT NULL AUTO_INCREMENT,
    libelle VARCHAR(50) NOT NULL
);

CREATE TABLE theme
(
    theme_id INT PRIMARY KEY NOT NULL AUTO_INCREMENT,
    libelle VARCHAR(50) NOT NULL
);

CREATE TABLE menu
(
    menu_id INT PRIMARY KEY NOT NULL AUTO_INCREMENT,
    titre VARCHAR(50) NOT NULL,
    nombre_personne_minimum INT NOT NULL,
    prix_par_personne DOUBLE NOT NULL,
    description VARCHAR(50) NOT NULL,
    quantite_restante int NOT NULL,
    regime_id INT NOT NULL,
    theme_id INT NOT NULL,
    image BLOB NOT NULL,
    FOREIGN KEY (regime_id) REFERENCES regime(regime_id),
    FOREIGN KEY (theme_id) REFERENCES theme(theme_id)
);

CREATE TABLE plat
(
    plat_id INT PRIMARY KEY NOT NULL AUTO_INCREMENT,
    titre_plat VARCHAR(50) NOT NULL,
    category VARCHAR(50) NOT NULL,
    photo BLOB NOT NULL
);

CREATE TABLE allergene
(
    allergene_id INT PRIMARY KEY NOT NULL AUTO_INCREMENT,
    libelle VARCHAR(50) NOT NULL
);

CREATE TABLE menu_plat
(
    menu_id INT NOT NULL,
    plat_id INT NOT NULL,
    PRIMARY KEY (menu_id, plat_id),
    FOREIGN KEY (menu_id) REFERENCES menu(menu_id),
    FOREIGN KEY (plat_id) REFERENCES plat(plat_id)
);

CREATE TABLE allergene_plat
(
    allergene_id INT NOT NULL,
    plat_id INT NOT NULL,
    PRIMARY KEY (allergene_id, plat_id),
    FOREIGN KEY (allergene_id) REFERENCES allergene(allergene_id),
    FOREIGN KEY (plat_id) REFERENCES plat(plat_id)
);

CREATE TABLE commande
(
    numero_de_commande VARCHAR(50) PRIMARY KEY NOT NULL,
    date_commande DATE,
    date_prestation DATE,
    heure_livraison VARCHAR(50) NOT NULL,
    prix_menu DOUBLE NOT NULL,
    nombre_personne INT NOT NULL,
    prix_livraison INT NOT NULL,
    statut VARCHAR(50) NOT NULL,
    pret_materiel BOOL NOT NULL,
    restitution_materiel BOOL NOT NULL,
    utilisateur_id INT NOT NULL,
    menu_id INT NOT NULL,
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateur(utilisateur_id),
    FOREIGN KEY (menu_id) REFERENCES menu(menu_id)
);

CREATE TABLE horaire
(
    horaire_id INT PRIMARY KEY NOT NULL AUTO_INCREMENT,
    jour VARCHAR(50) NOT NULL,
    statut VARCHAR(50) NOT NULL,
    heure_ouverture VARCHAR(50) NOT NULL,
    heure_fermeture VARCHAR(50) NOT NULL
);

INSERT INTO role (libelle) VALUES ('ROLE_ADMIN');
INSERT INTO role (libelle) VALUES ('ROLE_EMPLOYE');
INSERT INTO role (libelle) VALUES ('ROLE_USER');

