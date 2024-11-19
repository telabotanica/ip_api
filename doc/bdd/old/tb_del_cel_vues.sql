SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='TRADITIONAL,ALLOW_INVALID_DATES';

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
CREATE TABLE IF NOT EXISTS `del_images` (`id_image` INT, `ce_utilisateur` INT, `prenom_utilisateur` INT, `nom_utilisateur` INT, `courriel_utilisateur` INT, `hauteur` INT, `largeur` INT, `date_prise_de_vue` INT, `mots_cles_texte` INT, `commentaire` INT, `nom_original` INT, `date_creation` INT, `date_modification` INT, `date_liaison` INT, `date_transmission` INT);

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
        if((char_length(o.ce_utilisateur) <> 32 AND o.ce_utilisateur NOT LIKE '%@%'),cast(o.ce_utilisateur as unsigned),0) AS ce_utilisateur, 
        o.prenom_utilisateur, o.nom_utilisateur, o.courriel_utilisateur, 
        nom_sel, nom_sel_nn, nom_ret, nom_ret_nn, nt, famille, 
        ce_zone_geo, zone_geo, lieudit, station, milieu, nom_referentiel, 
        date_observation, o.mots_cles_texte, o.commentaire, 
        o.date_creation, o.date_modification, o.date_transmission, certitude, pays
    FROM tb_cel.cel_obs AS o WHERE o.id_observation IN (SELECT i.ce_observation FROM tb_cel.cel_images i) 
    AND o.transmission = '1' ;

-- -----------------------------------------------------
-- View `del_images`
-- -----------------------------------------------------
DROP VIEW IF EXISTS `del_images` ;
DROP TABLE IF EXISTS `del_images`;
CREATE  OR REPLACE VIEW del_image AS
    SELECT i.id_image, 
        if((char_length(i.ce_utilisateur) <> 32 AND i.ce_utilisateur NOT LIKE '%@%'),cast(i.ce_utilisateur as unsigned),0) AS ce_utilisateur, i.prenom_utilisateur, i.nom_utilisateur, i.courriel_utilisateur, 
        i.hauteur, i.largeur, i.date_prise_de_vue, i.mots_cles_texte, i.commentaire, i.nom_original, 
        i.date_creation, i.date_modification, i.date_liaison, i.date_transmission 
    FROM cel_images AS i 
    WHERE i.transmission = '1';

SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
