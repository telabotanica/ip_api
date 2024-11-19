-- -----------------------------------------------------
-- Data for table `del_image_protocole`
-- -----------------------------------------------------
START TRANSACTION;
INSERT INTO `del_image_protocole` (`id_protocole`, `intitule`, `descriptif`, `tag`, `mots_cles`) VALUES (3, 'Capitalisation d\'images', 'photographier en extérieur les organes (feuille, fruit, tronc, etc.) de plantes et transmettre les photos via le Carnet en ligne.', 'Plantnet', 'port,fleur,fruit,feuille,plantscan_new,ecorce,rameau,planche');
INSERT INTO `del_image_protocole` (`id_protocole`, `intitule`, `descriptif`, `tag`, `mots_cles`) VALUES (1, 'Aide à l\'identification', 'Choisissez les photos les plus utiles pour vérifier la détermination d\'une espèce', 'caractere', '');
INSERT INTO `del_image_protocole` (`id_protocole`, `intitule`, `descriptif`, `tag`, `mots_cles`) VALUES (4, 'Défi Photo', 'Choisissez les lauréats du défi photo', '', '');
INSERT INTO `del_image_protocole` (`id_protocole`, `intitule`, `descriptif`, `tag`, `mots_cles`) VALUES (5, 'Enquête Gentiane-Azuré', 'Participez à la localisation des Gentianes Croisette, témoins et actrices du cycle de vie du papillon Azuré de la Croisette\r\n', 'GentianeAzure', '');

COMMIT;