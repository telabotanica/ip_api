SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='TRADITIONAL,ALLOW_INVALID_DATES';


-- -----------------------------------------------------
-- Table `del_utilisateur_infos`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `del_utilisateur_infos` ;

CREATE TABLE IF NOT EXISTS `del_utilisateur_infos` (
  `id_utilisateur` INT(11) NOT NULL,
  `admin` TINYINT(1) NULL,
  `preferences` LONGTEXT NULL,
  `date_premiere_utilisation` DATETIME NULL,
  `date_derniere_consultation_evenements` DATETIME NOT NULL,
  PRIMARY KEY (`id_utilisateur`))
ENGINE = MyISAM;


-- -----------------------------------------------------
-- Table `del_utilisateur`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `del_utilisateur` ;

CREATE TABLE IF NOT EXISTS `del_utilisateur` (
  `id_utilisateur` INT NOT NULL AUTO_INCREMENT COMMENT 'Type varchar pour éviter problème de jointure.',
  `prenom` VARCHAR(255) NOT NULL,
  `nom` VARCHAR(255) NOT NULL,
  `courriel` VARCHAR(255) NOT NULL,
  `mot_de_passe` VARCHAR(45) NULL DEFAULT NULL,
  PRIMARY KEY (`id_utilisateur`))
ENGINE = MyISAM
DEFAULT CHARACTER SET = utf8
COLLATE = utf8_general_ci;


-- -----------------------------------------------------
-- Table `del_observation`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `del_observation` ;

CREATE TABLE IF NOT EXISTS `del_observation` (
  `id_observation` BIGINT NOT NULL AUTO_INCREMENT,
  `ce_utilisateur` INT NOT NULL,
  `nom_sel` VARCHAR(255) NULL DEFAULT NULL COMMENT 'Nom scientifique sélectionné par l\'utilisateur.',
  `nom_sel_nn` DECIMAL(9,0) NULL DEFAULT NULL COMMENT 'Identifiant du nom sélectionné.',
  `nom_ret` VARCHAR(255) NULL DEFAULT NULL COMMENT 'Nom scientifique retenu correspondant au nom sélectionné.',
  `nom_ret_nn` DECIMAL(9,0) NULL DEFAULT NULL COMMENT 'Identifiant du nom retenu.',
  `nt` DECIMAL(9,0) NULL DEFAULT NULL COMMENT 'Numéro taxonomique du taxon correspondant au nom sélectionné.',
  `famille` VARCHAR(255) NULL DEFAULT NULL COMMENT 'Nom de la famille du nom sélectionné.',
  `nom_referentiel` VARCHAR(255) NULL DEFAULT NULL,
  `ce_zone_geo` VARCHAR(50) NULL DEFAULT NULL,
  `zone_geo` VARCHAR(255) NULL DEFAULT NULL,
  `lieudit` VARCHAR(255) NULL DEFAULT NULL,
  `station` VARCHAR(255) NULL DEFAULT NULL,
  `milieu` VARCHAR(255) NULL DEFAULT NULL,
  `date_observation` DATETIME NULL,
  `mots_cles_texte` LONGTEXT NULL DEFAULT NULL,
  `commentaire` TEXT NULL DEFAULT NULL,
  `date_creation` DATETIME NOT NULL,
  `date_modification` DATETIME NOT NULL,
  `date_transmission` DATETIME NOT NULL COMMENT 'Date à laquelle l\'observation a été rendu publique.',
  `certitude` VARCHAR(255) NOT NULL,
  `pays` VARCHAR(2) NOT NULL,
  PRIMARY KEY (`id_observation`))
ENGINE = MyISAM
DEFAULT CHARACTER SET = utf8
COLLATE = utf8_general_ci
COMMENT = 'Observations publiques.';


-- -----------------------------------------------------
-- Table `del_image`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `del_image` ;

CREATE TABLE IF NOT EXISTS `del_image` (
  `id_image` BIGINT NOT NULL AUTO_INCREMENT,
  `ce_observation` BIGINT NOT NULL,
  `ce_utilisateur` INT NOT NULL,
  `hauteur` INT NULL,
  `largeur` INT NULL,
  `date_prise_de_vue` DATETIME NULL,
  `mots_cles_texte` LONGTEXT NULL DEFAULT NULL,
  `commentaire` LONGTEXT NULL DEFAULT NULL,
  `nom_original` VARCHAR(255) NOT NULL,
  `date_creation` DATETIME NOT NULL,
  `date_modification` DATETIME NOT NULL,
  `date_liaison` DATETIME NOT NULL,
  `date_transmission` DATETIME NOT NULL,
  PRIMARY KEY (`id_image`))
ENGINE = MEMORY
DEFAULT CHARACTER SET = utf8
COLLATE = utf8_general_ci;


-- -----------------------------------------------------
-- Table `del_image_protocole`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `del_image_protocole` ;

CREATE TABLE IF NOT EXISTS `del_image_protocole` (
  `id_protocole` INT NOT NULL AUTO_INCREMENT,
  `intitule` VARCHAR(255) NOT NULL,
  `descriptif` TEXT NULL,
  `tag` VARCHAR(255) NULL,
  `mots_cles` VARCHAR(600) NOT NULL COMMENT 'Mots clés associés au protocole, ceux ci déterminent les mots clés présentés \"à cocher\" dans l\'interface, lorsque le protocole est selectionné',
  `identifie` BOOLEAN NOT NULL COMMENT 'Indique si être identifié est nécessaire pour voter pour ce protocole',
  PRIMARY KEY (`id_protocole`))
ENGINE = MyISAM
DEFAULT CHARACTER SET = utf8
COLLATE = utf8_general_ci;


-- -----------------------------------------------------
-- Table `del_image_vote`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `del_image_vote` ;

CREATE TABLE IF NOT EXISTS `del_image_vote` (
  `id_vote` BIGINT NOT NULL AUTO_INCREMENT,
  `ce_image` BIGINT NOT NULL,
  `ce_protocole` INT NOT NULL,
  `ce_utilisateur` VARCHAR(32) NOT NULL COMMENT 'Identifiant de session ou id utilisateur.',
  `valeur` TINYINT(1) NOT NULL,
  `date` DATETIME NOT NULL,
  PRIMARY KEY (`id_vote`),
  UNIQUE INDEX `protocole_image_utilisateur` (`ce_image` ASC, `ce_protocole` ASC, `ce_utilisateur` ASC))
ENGINE = MyISAM
DEFAULT CHARACTER SET = utf8
COLLATE = utf8_general_ci;


-- -----------------------------------------------------
-- Table `del_image_tag`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `del_image_tag` ;

CREATE TABLE IF NOT EXISTS `del_image_tag` (
  `id_tag` BIGINT NOT NULL AUTO_INCREMENT,
  `ce_image` BIGINT NOT NULL,
  `ce_utilisateur` VARCHAR(64) NOT NULL,
  `tag` VARCHAR(255) NOT NULL DEFAULT 'Mot clé saisi par l\'utilisateur.',
  `tag_normalise` VARCHAR(255) NOT NULL DEFAULT 'Mot clé normalisé (sans espace ni accent).',
  `date` DATETIME NOT NULL COMMENT 'Date de création du tag.',
  `actif` INT(1) NULL,
  `date_modification` DATETIME NULL,
  PRIMARY KEY (`id_tag`),
  INDEX `tag` (`tag` ASC),
  INDEX `tag_normalise` (`tag_normalise` ASC))
ENGINE = MyISAM
DEFAULT CHARACTER SET = utf8
COLLATE = utf8_general_ci;


-- -----------------------------------------------------
-- Table `del_commentaire`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `del_commentaire` ;

CREATE TABLE IF NOT EXISTS `del_commentaire` (
  `id_commentaire` BIGINT NOT NULL AUTO_INCREMENT,
  `ce_observation` BIGINT NOT NULL,
  `ce_proposition` INT NULL DEFAULT 0,
  `ce_commentaire_parent` BIGINT NULL DEFAULT 0,
  `texte` TEXT NULL DEFAULT NULL,
  `ce_utilisateur` INT NULL DEFAULT '0',
  `utilisateur_prenom` VARCHAR(255) NOT NULL,
  `utilisateur_nom` VARCHAR(255) NOT NULL,
  `utilisateur_courriel` VARCHAR(255) NOT NULL,
  `nom_sel` VARCHAR(255) NULL DEFAULT NULL,
  `nom_sel_nn` DECIMAL(9,0) NULL DEFAULT NULL,
  `nom_ret` VARCHAR(255) NULL DEFAULT NULL,
  `nom_ret_nn` DECIMAL(9,0) NULL DEFAULT NULL,
  `nt` DECIMAL(9,0) NULL DEFAULT NULL,
  `famille` VARCHAR(255) NULL DEFAULT NULL,
  `nom_referentiel` VARCHAR(255) NULL DEFAULT NULL,
  `date` DATETIME NOT NULL COMMENT 'Date de création du commentaire.',
  `proposition_initiale` INT(1) NOT NULL DEFAULT 0 COMMENT 'La proposition initiale est le nom_sel d\'origine copié ici dès lors que des commentaires adviennent.',
  `proposition_retenue` INT(1) NOT NULL DEFAULT 0 COMMENT 'La proposition \"validée\" une fois que l\'auteur à validé et que le nom_sel de cel_obs a été modifié à partir du nom_sel de del_commentaire.',
  `ce_validateur` INT(11) NULL,
  `date_validation` DATETIME NULL,
  PRIMARY KEY (`id_commentaire`))
ENGINE = MyISAM
DEFAULT CHARACTER SET = utf8
COLLATE = utf8_general_ci;


-- -----------------------------------------------------
-- Table `del_commentaire_vote`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `del_commentaire_vote` ;

CREATE TABLE IF NOT EXISTS `del_commentaire_vote` (
  `id_vote` BIGINT NOT NULL AUTO_INCREMENT,
  `ce_proposition` BIGINT NOT NULL,
  `ce_utilisateur` VARCHAR(32) NOT NULL DEFAULT '0' COMMENT 'Identifiant de session ou id de l\'utilisateur.',
  `valeur` TINYINT(1) NOT NULL,
  `date` DATETIME NOT NULL,
  PRIMARY KEY (`id_vote`))
ENGINE = MyISAM
DEFAULT CHARACTER SET = utf8
COLLATE = utf8_general_ci
COMMENT = 'Vote uniquement sur le commentaire de type \'proposition\'.';


-- -----------------------------------------------------
-- Table `del_image_stats`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `del_image_stats` ;

CREATE TABLE IF NOT EXISTS `del_image_stats` (
  `ce_image` BIGINT(20) NOT NULL COMMENT 'id_image (tb_cel.cel_images)',
  `ce_protocole` TINYINT NOT NULL DEFAULT 0 COMMENT 'un id de protocole',
  `moyenne` FLOAT NOT NULL DEFAULT 0 COMMENT 'moyenne des votes pour une image et un protocole donné',
  `nb_votes` SMALLINT NOT NULL DEFAULT 0 COMMENT 'nombre de votes pour une image et un protocole donné',
  `nb_tags` TINYINT NOT NULL DEFAULT 0 COMMENT 'nombre de tags pictoflora associés à une image',
  PRIMARY KEY (`ce_image`, `ce_protocole`),
  INDEX `ce_image` (`ce_image` ASC),
  INDEX `ce_protocole` (`ce_protocole` ASC, `moyenne` DESC),
  INDEX `nb_tags` (`nb_tags` DESC),
  INDEX `nb_votes` (`nb_votes` DESC))
ENGINE = MyISAM
DEFAULT CHARACTER SET = utf8
COMMENT = 'table de stockage des statistiques utilisées pour les tri de /* comment truncated */ /* PictoFlora*/';


-- -----------------------------------------------------
-- Placeholder table for view `del_utilisateurs`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `del_utilisateurs` (`id_utilisateur` INT, `prenom` INT, `nom` INT, `courriel` INT, `mot_de_passe` INT);

-- -----------------------------------------------------
-- Placeholder table for view `del_observations`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `del_observations` (`id_observation` INT, `ce_utilisateur` INT, `prenom_utilisateur` INT, `nom_utilisateur` INT, `courriel_utilisateur` INT, `nom_sel` INT, `nom_sel_nn` INT, `nom_ret` INT, `nom_ret_nn` INT, `nt` INT, `famille` INT, `ce_zone_geo` INT, `zone_geo` INT, `lieudit` INT, `station` INT, `milieu` INT, `nom_referentiel` INT, `date_observation` INT, `mots_cles_texte` INT, `commentaire` INT, `date_creation` INT, `date_modification` INT, `date_transmission` INT, `certitude` INT);

-- -----------------------------------------------------
-- Placeholder table for view `del_images`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `del_images` (`id_image` INT, `ce_observation` INT, `ce_utilisateur` INT, `prenom_utilisateur` INT, `nom_utilisateur` INT, `courriel_utilisateur` INT, `hauteur` INT, `largeur` INT, `date_prise_de_vue` INT, `mots_cles_texte` INT, `commentaire` INT, `nom_original` INT, `date_creation` INT, `date_modification` INT, `date_liaison` INT, `date_transmission` INT);

-- -----------------------------------------------------
-- Placeholder table for view `v_del_image`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `v_del_image` (`id_image` INT, `i_ce_utilisateur` INT, `i_prenom_utilisateur` INT, `i_nom_utilisateur` INT, `i_courriel_utilisateur` INT, `hauteur` INT, `largeur` INT, `date_prise_de_vue` INT, `i_mots_cles_texte` INT, `i_commentaire` INT, `nom_original` INT, `date_creation` INT, `date_modification` INT, `date_liaison` INT, `date_transmission` INT);

-- -----------------------------------------------------
-- View `del_utilisateurs`
-- -----------------------------------------------------
DROP VIEW IF EXISTS `del_utilisateurs` ;
DROP TABLE IF EXISTS `del_utilisateurs`;
CREATE  OR REPLACE VIEW del_utilisateur AS 
	SELECT 
    U_ID AS id_utilisateur, 
    CONVERT(U_SURNAME USING UTF8) AS prenom, 
    CONVERT(U_NAME USING UTF8) AS nom, 
    CONVERT(U_MAIL USING UTF8) AS courriel, 
    CONVERT(U_PASSWD USING UTF8) AS mot_de_passe 
	FROM tela_prod_v4.annuaire_tela;

-- -----------------------------------------------------
-- View `del_observations`
-- -----------------------------------------------------
DROP VIEW IF EXISTS `del_observations` ;
DROP TABLE IF EXISTS `del_observations`;
CREATE  OR REPLACE VIEW del_observation AS 
SELECT id_observation, 
        if((char_length(o.ce_utilisateur) <> 32),cast(o.ce_utilisateur as unsigned),0) AS ce_utilisateur, 
        o.prenom_utilisateur, o.nom_utilisateur, o.courriel_utilisateur, 
        nom_sel, nom_sel_nn, nom_ret, nom_ret_nn, nt, famille, 
        ce_zone_geo, zone_geo, lieudit, station, milieu, nom_referentiel, 
        date_observation, o.mots_cles_texte, o.commentaire, 
        o.date_creation, o.date_modification, o.date_transmission, certitude, pays
    FROM tb_cel.cel_obs AS o WHERE o.id_observation IN (SELECT i.ce_observation FROM tb_cel.cel_images i) 
    AND o.transmission = '1';

-- -----------------------------------------------------
-- View `del_images`
-- -----------------------------------------------------
DROP VIEW IF EXISTS `del_images` ;
DROP TABLE IF EXISTS `del_images`;
CREATE  OR REPLACE VIEW del_image AS
    SELECT i.id_image, i.ce_observation,
        if((char_length(i.ce_utilisateur) <> 32),cast(i.ce_utilisateur as unsigned),0) AS ce_utilisateur, i.prenom_utilisateur, i.nom_utilisateur, i.courriel_utilisateur, 
        i.hauteur, i.largeur, i.date_prise_de_vue, i.mots_cles_texte, i.commentaire, i.nom_original, 
        i.date_creation, i.date_modification, i.date_liaison, i.date_transmission 
    FROM tb_cel.cel_images AS i 
    WHERE i.transmission = '1';

-- -----------------------------------------------------
-- View `del_plantnet`
-- -----------------------------------------------------
DROP VIEW IF EXISTS `del_plantnet` ;
DROP TABLE IF EXISTS `del_plantnet`;
CREATE OR REPLACE VIEW del_plantnet AS
    SELECT `o`.`id_observation` AS `id_observation`,`o`.`ce_utilisateur` AS `ce_utilisateur`,`o`.`prenom_utilisateur` AS `prenom_utilisateur`,
		`o`.`nom_utilisateur` AS `nom_utilisateur`,`o`.`courriel_utilisateur` AS `courriel_utilisateur`,`o`.`nom_sel` AS `nom_sel`,
		`o`.`nom_sel_nn` AS `nom_sel_nn`,`o`.`nom_ret` AS `nom_ret`,`o`.`famille` AS `famille`,`o`.`nom_referentiel` AS `nom_referentiel`,
		`o`.`zone_geo` AS `zone_geo`,`o`.`latitude` AS `latitude`,`o`.`longitude` AS `longitude`,`o`.`altitude` AS `altitude`,
		`o`.`date_observation` AS `date_observation`,`o`.`mots_cles_texte` AS `mots_cles_texte`,`o`.`date_creation` AS `date_creation`,
		`o`.`date_modification` AS `date_modification`,`o`.`date_transmission` AS `date_transmission`,`i`.`id_image` AS `id_image`,
		`i`.`ce_utilisateur` AS `i_ce_utilisateur`,`i`.`mots_cles_texte` AS `i_mots_cles_texte`,`i`.`nom_original` AS `nom_original`,
		`i`.`date_creation` AS `i_date_creation`,`i`.`date_modification` AS `i_date_modification`
	FROM (`tb_cel_test`.`cel_obs` `o`
	JOIN `tb_cel_test`.`cel_images` `i` ON ((`o`.`id_observation` = `i`.`ce_observation`)))
	WHERE (`o`.`transmission` = '1');

SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;

-- -----------------------------------------------------
-- Data for table `del_image_protocole`
-- -----------------------------------------------------
START TRANSACTION;
INSERT INTO `del_image_protocole` (`id_protocole`, `intitule`, `descriptif`, `tag`, `mots_cles`) VALUES (3, 'Capitalisation d\'images', 'photographier en extérieur les organes (feuille, fruit, tronc, etc.) de plantes et transmettre les photos via le Carnet en ligne.', 'Plantnet', 'port,fleur,fruit,feuille,plantscan_new,ecorce,rameau,planche', '0');
INSERT INTO `del_image_protocole` (`id_protocole`, `intitule`, `descriptif`, `tag`, `mots_cles`) VALUES (1, 'Aide à l\'identification', 'Choisissez les photos les plus utiles pour vérifier la détermination d\'une espèce', 'caractere', '', '0');
INSERT INTO `del_image_protocole` (`id_protocole`, `intitule`, `descriptif`, `tag`, `mots_cles`) VALUES (4, 'Défi Photo', 'Choisissez les lauréats du défi photo', '', '', '1');
INSERT INTO `del_image_protocole` (`id_protocole`, `intitule`, `descriptif`, `tag`, `mots_cles`) VALUES (5, 'Enquête Gentiane-Azuré', 'Participez à la localisation des Gentianes Croisette, témoins et actrices du cycle de vie du papillon Azuré de la Croisette\r\n', 'GentianeAzure', '', '0');


-- -----------------------------------------------------
-- Table de données calculées pour le service d'export
-- PlantNet
--
-- Comporte la date dernière mise à jour d'une obs et
-- des données liées
-- -----------------------------------------------------
CREATE TABLE del_observation_modif_date
(
  id_observation BIGINT NOT NULL,
  modif_date datetime,

  UNIQUE (id_observation)
)


-- -----------------------------------------------------
-- Initialisation des dates de dernière màj d'une obs et
-- de ses données liées
-- -----------------------------------------------------
INSERT INTO del_observation_modif_date (id_observation, modif_date)
SELECT DISTINCT p.id_observation,
       GREATEST(IFNULL(p.date_creation, '1900-01-01'), IFNULL(p.date_modification, '1900-01-01'), IFNULL(MAX(image_tag.date), '1900-01-01'), IFNULL(MAX(image_tag.date_modification), '1900-01-01'), IFNULL(MAX(image_vote.date), '1900-01-01'), IFNULL(MAX(commentaire.date), '1900-01-01'), IFNULL(MAX(commentaire_vote.date), '1900-01-01')) AS modif_date
FROM del_plantnet AS p
LEFT JOIN del_image_vote AS image_vote ON (id_image = image_vote.ce_image
                                   AND image_vote.ce_protocole = 3)
LEFT JOIN del_image_tag AS image_tag ON (id_image = image_tag.ce_image
                                  AND image_tag.actif = 1)
LEFT JOIN del_commentaire AS commentaire ON (id_observation = commentaire.ce_observation)
LEFT JOIN del_commentaire_vote AS commentaire_vote ON (commentaire.id_commentaire = commentaire_vote.ce_proposition)
GROUP BY id_observation
HAVING MAX(p.date_creation) >= '1900-01-01'
OR MAX(p.date_modification) >= '1900-01-01'
OR MAX(image_tag.date) >= '1900-01-01'
OR MAX(image_tag.date_modification) >= '1900-01-01'
OR MAX(image_vote.date) >= '1900-01-01'
OR MAX(commentaire.date) >= '1900-01-01'
OR MAX(commentaire_vote.date) >= '1900-01-01'

-- -----------------------------------------------------
-- Triggers pour garder à jour les dates de dernière màj
-- d'une obs et de ses données liées
-- -----------------------------------------------------
-- cel_obs INSERT trigger --
DROP TRIGGER IF EXISTS tb_cel.TRIGGER_celObs_dateModif_INSERT;
CREATE TRIGGER tb_cel.TRIGGER_celObs_dateModif_INSERT
AFTER INSERT ON tb_cel.cel_obs FOR EACH ROW
INSERT INTO tb_del.del_observation_modif_date (id_observation, modif_date)
VALUES (NEW.id_observation, NEW.date_creation);

-- cel_obs UPDATE trigger --
CREATE TRIGGER tb_cel.TRIGGER_celObs_dateModif_UPDATE
AFTER UPDATE ON tb_cel.cel_obs FOR EACH ROW
UPDATE tb_del.del_observation_modif_date SET modif_date = NEW.date_modification
WHERE id_observation = NEW.id_observation;



-- del_commentaire INSERT trigger --
CREATE TRIGGER tb_del.TRIGGER_delCommentaire_dateModif_INSERT
AFTER INSERT ON tb_del.del_commentaire FOR EACH ROW
UPDATE tb_del.del_observation_modif_date SET modif_date = NEW.`date`
WHERE id_observation = NEW.ce_observation;

-- del_commentaire UPDATE trigger --
CREATE TRIGGER tb_del.TRIGGER_delCommentaire_dateModif_UPDATE
AFTER UPDATE ON tb_del.del_commentaire FOR EACH ROW
UPDATE tb_del.del_observation_modif_date SET modif_date = NEW.`date`
WHERE id_observation = NEW.ce_observation;



-- del_commentaire_vote INSERT trigger --
CREATE TRIGGER tb_del.TRIGGER_delCommentaireVote_dateModif_INSERT
AFTER INSERT ON tb_del.del_commentaire_vote FOR EACH ROW
UPDATE tb_del.del_observation_modif_date SET modif_date = NEW.`date`
WHERE id_observation = (SELECT ce_observation FROM tb_del.del_commentaire WHERE id_commentaire = NEW.ce_proposition);

-- del_commentaire_vote UPDATE trigger --
CREATE TRIGGER tb_del.TRIGGER_delCommentaireVote_dateModif_UPDATE
AFTER UPDATE ON tb_del.del_commentaire_vote FOR EACH ROW
UPDATE tb_del.del_observation_modif_date SET modif_date = NEW.`date`
WHERE id_observation = (SELECT ce_observation FROM tb_del.del_commentaire WHERE id_commentaire = NEW.ce_proposition);



-- del_image_vote INSERT trigger --
CREATE TRIGGER tb_del.TRIGGER_delImageVote_dateModif_INSERT
AFTER INSERT ON tb_del.del_image_vote FOR EACH ROW
UPDATE tb_del.del_observation_modif_date SET modif_date = NEW.`date`
WHERE id_observation = (SELECT ce_observation FROM tb_cel.cel_images WHERE id_image = NEW.ce_image);

-- del_image_vote UPDATE trigger --
CREATE TRIGGER tb_del.TRIGGER_delImageVote_dateModif_UPDATE
AFTER UPDATE ON tb_del.del_image_vote FOR EACH ROW
UPDATE tb_del.del_observation_modif_date SET modif_date = NEW.`date`
WHERE id_observation = (SELECT ce_observation FROM tb_cel.cel_images WHERE id_image = NEW.ce_image);



-- del_image_tag INSERT trigger --
CREATE TRIGGER tb_del.TRIGGER_delImageTag_dateModif_INSERT
AFTER INSERT ON tb_del.del_image_tag FOR EACH ROW
UPDATE tb_del.del_observation_modif_date SET modif_date = NEW.`date`
WHERE id_observation = (SELECT ce_observation FROM tb_cel.cel_images WHERE id_image = NEW.ce_image);

-- del_image_tag UPDATE trigger --
CREATE TRIGGER tb_del.TRIGGER_delImageTag_dateModif_UPDATE
AFTER UPDATE ON tb_del.del_image_tag FOR EACH ROW
UPDATE tb_del.del_observation_modif_date SET modif_date = NEW.`date`
WHERE id_observation = (SELECT ce_observation FROM tb_cel.cel_images WHERE id_image = NEW.ce_image);

-- -----------------------------------------------------
-- Fin des triggers pour garder à jour les dates de
-- dernière màj d'une obs et de ses données liées
-- -----------------------------------------------------


COMMIT;
