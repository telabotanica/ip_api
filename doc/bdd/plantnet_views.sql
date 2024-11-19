CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `del_plantnet`  AS
SELECT `o`.`id_observation` AS `id_observation`,
       `o`.`id_plantnet` AS `id_plantnet`,
       `o`.`ce_utilisateur` AS `ce_utilisateur`,
       `o`.`courriel_utilisateur` AS `courriel_utilisateur`,
       `o`.`nom_sel` AS `nom_sel`,
       `o`.`nom_sel_nn` AS `nom_sel_nn`,
       `o`.`nom_ret` AS `nom_ret`,
       `o`.`nom_ret_nn` AS `nom_ret_nn`,
       `o`.`famille` AS `famille`,
       `o`.`nom_referentiel` AS `nom_referentiel`,
       `o`.`geometry` AS `zone_geo`,
       `o`.`latitude` AS `latitude`,
       `o`.`longitude` AS `longitude`,
       `o`.`date_observation` AS `date_observation`,
       `o`.`date_creation` AS `date_created`,
       `o`.`date_modification` AS `date_updated`,
       `o`.`date_transmission` AS `date_published`,
       `o`.`type_donnees` AS `type_donnees`,
       `o`.`score_identiplante` AS `identiplante_score`,
       `o`.`validation_identiplante` AS `is_identiplante_validated`,
       `o`.`mots_cles_texte` AS `mots_cles_cel_obs`,
       `o`.`programme` AS `programme`,
       `i`.`id_image` AS `id_image`,
       `i`.`mots_cles_texte` AS `mots_cles_cel_image`,
       `i`.`nom_original` AS `original_name`,
       `i`.`date_prise_de_vue` AS `i_date_shot`,
       `i`.`date_creation` AS `i_date_creation`,
       `i`.`date_modification` AS `i_date_updated`
FROM
    (`tb_new_cel`.`cel_export_total` `o`
        join `tb_new_cel`.`cel_images_export` `i` on(`o`.`id_observation` = `i`.`ce_observation`)
        ) WHERE `o`.`transmission` = '1'  ;

*************************************************************************************************

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `del_plantnet_images`  AS
SELECT
    `o`.`id_observation` AS `id_observation`,
    `o`.`id_plantnet` AS `id_plantnet`,
    `o`.`ce_utilisateur` AS `ce_utilisateur`,
    `i`.`id_image` AS `id_image`,
    `i`.`mots_cles_texte` AS `mots_cles_cel_image`,
    `i`.`nom_original` AS `original_name`,
    `i`.`date_prise_de_vue` AS `i_date_shot`,
    `i`.`date_creation` AS `i_date_creation`,
    `i`.`date_modification` AS `i_date_updated`
FROM
    (`tb_new_cel`.`cel_export_total` `o`
        join `tb_new_cel`.`cel_images_export` `i` on(`o`.`id_observation` = `i`.`ce_observation`)
        ) WHERE `o`.`transmission` = '1'  ;
