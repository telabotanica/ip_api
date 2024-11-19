create table del_commentaire
(
    id_commentaire        bigint auto_increment comment 'identifiant d''un commentaire ou d''une proposition'
        primary key,
    ce_observation        bigint           not null,
    ce_proposition        int    default 0 null comment 'id_commentaire de la proposition à laquelle est liée le commentaire',
    ce_commentaire_parent bigint default 0 null comment 'id_commentaire du commentaire ou de la proposition parent',
    texte                 text             null,
    ce_utilisateur        int    default 0 null,
    utilisateur_prenom    varchar(255)     null,
    utilisateur_nom       varchar(255)     null,
    utilisateur_courriel  varchar(255)     not null,
    nom_sel               varchar(255)     null comment 'contient ce qu''il a été saisi dans le cel pas forcement nom latin',
    nom_sel_nn            decimal(9)       null comment 'attention peut être null ou 0 si pas de valeur',
    nom_ret               varchar(255)     null comment 'nom retenu du référentiel',
    nom_ret_nn            decimal(9)       null,
    nt                    decimal(9)       null,
    famille               varchar(255)     null,
    nom_referentiel       varchar(255)     null,
    date                  datetime         not null comment 'Date de création du commentaire.',
    proposition_initiale  int(1) default 0 not null,
    proposition_retenue   int(1) default 0 not null comment 'proposition qui peut être initiale ou non et qui a été validé par l''auteur comme nom',
    ce_validateur         int              null,
    date_validation       datetime         null
)
    engine = MyISAM
    charset = utf8;

create index ce_commentaire_parent
    on del_commentaire (ce_commentaire_parent);

create index ce_commentaire_parent_2
    on del_commentaire (ce_commentaire_parent);

create index ce_observation
    on del_commentaire (ce_observation);

create index ce_proposition
    on del_commentaire (ce_proposition);

create index ce_utilisateur
    on del_commentaire (ce_utilisateur);

create definer = apitela@localhost trigger TRIGGER_delCommentaire_dateModif_INSERT
    after insert
    on del_commentaire
    for each row
    if (SELECT 1 FROM tb_new_cel.occurrence WHERE id = NEW.`ce_observation` AND  	user_sci_name_id = NEW.`nom_sel_nn`) = 1 THEN

UPDATE tb_new_cel.`occurrence` SET `is_identiplante_validated`= case when NEW.proposition_retenue is null then 0 else NEW.proposition_retenue end WHERE `id` = NEW.`ce_observation`;

UPDATE tb_del.del_observation_modif_date SET modif_date = NEW.`date`
WHERE id_observation = NEW.ce_observation;

ELSE
UPDATE tb_del.del_observation_modif_date SET modif_date = NEW.`date`
WHERE id_observation = NEW.ce_observation;

END IF;

create definer = apitela@localhost trigger TRIGGER_delCommentaire_dateModif_UPDATE
    after update
    on del_commentaire
    for each row
    IF (NEW.proposition_retenue != OLD.proposition_retenue and NEW.proposition_initiale = 0) THEN

SET @score = (SELECT sum(case when `valeur` = 0 and 
`ce_utilisateur` REGEXP '^-?[0-9]+$' then -3 when `valeur` = 0 then -1 when `valeur` = 1 and `ce_utilisateur` REGEXP '^-?[0-9]+$' then 3 when `valeur` = 1 then 1 END) as score 
FROM tb_del.`del_commentaire_vote` sc
WHERE sc.ce_proposition = NEW.id_commentaire group by sc.`ce_proposition`);


UPDATE tb_new_cel.`occurrence` SET `identiplante_score`= @score, `is_identiplante_validated`= case when NEW.proposition_retenue is null then 0 else NEW.proposition_retenue end WHERE `id` = NEW.`ce_observation` ;

ELSEIF (SELECT 1 FROM tb_new_cel.occurrence WHERE id = NEW.`ce_observation` AND  	user_sci_name_id = NEW.`nom_sel_nn`) = 1 THEN

UPDATE tb_new_cel.`occurrence` SET `is_identiplante_validated`= case when NEW.proposition_retenue is null then 0 else NEW.proposition_retenue end WHERE `id` = NEW.`ce_observation`;

UPDATE tb_del.del_observation_modif_date SET modif_date = NEW.`date`
WHERE id_observation = NEW.ce_observation;

ELSE

UPDATE tb_del.del_observation_modif_date SET modif_date = NEW.`date`
WHERE id_observation = NEW.ce_observation;

END IF;

grant update (nom_sel, nom_sel_nn, nom_ret, nom_ret_nn, nt, famille), update (nom_referentiel) on table del_commentaire to manon;

create table del_commentaire_vote
(
    id_vote        bigint auto_increment
        primary key,
    ce_proposition bigint                  not null,
    ce_utilisateur varchar(32) default '0' not null comment 'Identifiant de session ou id de l''utilisateur.',
    valeur         tinyint(1)              not null,
    date           datetime                not null
)
    comment 'Vote uniquement sur le commentaire de type ''proposition''.' engine = MyISAM
                                                                          charset = utf8;

create index ce_proposition
    on del_commentaire_vote (ce_proposition);

create index ce_utilisateur
    on del_commentaire_vote (ce_utilisateur);

create definer = apitela@localhost trigger TRIGGER_delCommentaireVote_dateModif_INSERT
    after insert
    on del_commentaire_vote
    for each row
    if (NEW.ce_proposition IS NOT NULL) THEN
UPDATE tb_del.del_observation_modif_date SET modif_date = NEW.`date`
WHERE id_observation = (SELECT ce_observation FROM tb_del_test.del_commentaire WHERE id_commentaire = NEW.ce_proposition);

UPDATE tb_new_cel.`occurrence` join 
	(SELECT ce_observation, id_commentaire, nom_sel_nn, `proposition_retenue` FROM tb_del.`del_commentaire` WHERE id_commentaire = NEW.ce_proposition) c 	 	
	on id = ce_observation AND user_sci_name_id= `nom_sel_nn` 
	SET `identiplante_score`= case when NEW.`valeur` = 0 and NEW.`ce_utilisateur` REGEXP '^-?[0-9]+$' then ifnull(identiplante_score, 0) -3 when NEW.`valeur` = 0 then ifnull(identiplante_score, 0) -1 when NEW.`valeur` = 1 and NEW.`ce_utilisateur` REGEXP '^-?[0-9]+$' 
		then ifnull(identiplante_score, 0) + 3 when NEW.`valeur` = 1 then ifnull(identiplante_score, 0) + 1 END,`is_identiplante_validated`= case when proposition_retenue is null then 0 else proposition_retenue end;
END IF;

create definer = apitela@localhost trigger TRIGGER_delCommentaireVote_dateModif_UPDATE
    after update
    on del_commentaire_vote
    for each row
    IF (NEW.ce_proposition IS NOT NULL) THEN
UPDATE tb_del.del_observation_modif_date SET modif_date = NEW.`date`
WHERE id_observation = (SELECT ce_observation FROM tb_del_test.del_commentaire WHERE id_commentaire = NEW.ce_proposition);
    

UPDATE tb_new_cel.`occurrence` join 
	(SELECT ce_observation, id_commentaire, nom_sel_nn, `proposition_retenue` FROM tb_del.`del_commentaire` WHERE id_commentaire = NEW.ce_proposition) c 	 	
	on id = ce_observation AND user_sci_name_id= `nom_sel_nn` 
	SET `identiplante_score`= case when NEW.`valeur` = 0 and NEW.`ce_utilisateur` REGEXP '^-?[0-9]+$' then ifnull(identiplante_score, 0) -6 when NEW.`valeur` = 0 then ifnull(identiplante_score, 0) -2 when NEW.`valeur` = 1 and NEW.`ce_utilisateur` REGEXP '^-?[0-9]+$' 
		then ifnull(identiplante_score, 0) + 6 when NEW.`valeur` = 1 then ifnull(identiplante_score, 0) + 2 END,`is_identiplante_validated`= case when proposition_retenue is null then 0 else proposition_retenue end;
END IF;

create table del_image_protocole
(
    id_protocole int auto_increment
        primary key,
    intitule     varchar(255) not null,
    descriptif   text         null,
    tag          varchar(255) not null,
    mots_cles    varchar(600) not null,
    identifie    tinyint(1)   not null
)
    engine = MyISAM
    charset = utf8;

create table del_image_stat
(
    ce_image     bigint                      not null comment 'id_image (tb_cel.cel_images)',
    ce_protocole tinyint unsigned  default 0 not null comment 'un id de protocole',
    moyenne      float unsigned    default 0 not null comment 'moyenne des votes pour une image et un protocole donnÃ©',
    nb_votes     smallint unsigned default 0 not null comment 'nombre de votes pour une image et un protocole donnÃ©',
    nb_points    int               default 0 not null,
    nb_tags      tinyint unsigned  default 0 not null comment 'nombre de tags pictoflora pour une image donnÃ©e',
    primary key (ce_image, ce_protocole)
)
    comment 'table de stockage des statistiques utilisÃ©es pour les tri d' engine = MyISAM
                                                                           charset = utf8;

create index ce_image
    on del_image_stat (ce_image);

create index ce_protocole
    on del_image_stat (ce_protocole, moyenne);

create index moyenne
    on del_image_stat (moyenne);

create index nb_tags
    on del_image_stat (nb_tags);

create index nb_votes
    on del_image_stat (nb_votes);

create table del_image_tag
(
    id_tag            bigint auto_increment
        primary key,
    ce_image          bigint                               not null,
    ce_utilisateur    varchar(64)                          null,
    tag               varchar(255)                         not null,
    tag_normalise     varchar(255)                         not null,
    date              datetime default current_timestamp() not null comment 'Date de création du tag.',
    actif             int(1)                               not null,
    date_modification datetime                             null
)
    engine = MyISAM
    charset = utf8;

create index ce_image
    on del_image_tag (ce_image);

create index ce_utilisateur
    on del_image_tag (ce_utilisateur);

create index tag
    on del_image_tag (tag);

create index tag_normalise
    on del_image_tag (tag_normalise);

create definer = apitela@localhost trigger TRIGGER_delImageTag_dateModif_INSERT
    after insert
    on del_image_tag
    for each row
    UPDATE tb_del.del_observation_modif_date SET modif_date = NEW.`date`
WHERE id_observation = (SELECT ce_observation FROM tb_del.del_image WHERE id_image = NEW.ce_image);

create definer = apitela@localhost trigger TRIGGER_delImageTag_dateModif_UPDATE
    after update
    on del_image_tag
    for each row
    UPDATE tb_del.del_observation_modif_date SET modif_date = NEW.`date`
WHERE id_observation =  (SELECT ce_observation FROM tb_del.del_image WHERE id_image = NEW.ce_image);

create table del_image_tag_sauv
(
    id_tag            bigint auto_increment
        primary key,
    ce_image          bigint                               not null,
    ce_utilisateur    varchar(64)                          null,
    tag               varchar(255)                         not null,
    tag_normalise     varchar(255)                         not null,
    date              datetime default current_timestamp() not null comment 'Date de création du tag.',
    actif             int(1)                               not null,
    date_modification datetime                             null
)
    engine = MyISAM
    charset = utf8;

create index ce_image
    on del_image_tag_sauv (ce_image);

create index ce_utilisateur
    on del_image_tag_sauv (ce_utilisateur);

create index tag
    on del_image_tag_sauv (tag);

create index tag_normalise
    on del_image_tag_sauv (tag_normalise);

create table del_image_top
(
    nn          decimal(9)   not null,
    referentiel varchar(255) not null,
    ce_image    bigint       not null,
    organe      varchar(255) null,
    date_vote   datetime     null comment 'Date du dernier vote',
    primary key (nn, referentiel, ce_image)
)
    engine = MyISAM
    charset = utf8;

create index organe
    on del_image_top (organe);

create table del_image_vote
(
    id_vote        bigint auto_increment
        primary key,
    ce_image       bigint      not null,
    ce_protocole   int         not null,
    ce_utilisateur varchar(32) not null comment 'Identifiant de session ou id utilisateur.',
    valeur         tinyint(1)  not null,
    date           datetime    not null
)
    engine = MyISAM
    charset = utf8;

create index ce_image
    on del_image_vote (ce_image);

create index ce_protocole
    on del_image_vote (ce_protocole);

create index ce_utilisateur
    on del_image_vote (ce_utilisateur);

create definer = apitela@localhost trigger TRIGGER_delImageVote_dateModif_INSERT
    after insert
    on del_image_vote
    for each row
    UPDATE tb_del.del_observation_modif_date SET modif_date = NEW.`date`
WHERE id_observation = (SELECT ce_observation FROM tb_del.del_image WHERE id_image = NEW.ce_image);

create definer = apitela@localhost trigger TRIGGER_delImageVote_dateModif_UPDATE
    after update
    on del_image_vote
    for each row
    UPDATE tb_del.del_observation_modif_date SET modif_date = NEW.`date`
WHERE id_observation = (SELECT ce_observation FROM tb_del.del_image WHERE id_image = NEW.ce_image);

create table del_observation
(
    id_observation       bigint default 0                      not null
        primary key,
    ce_utilisateur       int                                   null,
    nom_utilisateur      varchar(155) charset utf8             null,
    prenom_utilisateur   varchar(5) collate utf8mb4_unicode_ci null,
    courriel_utilisateur varchar(155) charset utf8             null,
    nom_sel              varchar(255) charset utf8             null,
    nom_sel_nn           int                                   null comment 'Numéro du nom sélectionné.',
    nom_ret              varchar(255) charset utf8             null,
    nom_ret_nn           int                                   null comment 'Numéro du nom retenu.',
    nt                   int                                   null comment 'Numéro du nom retenu.',
    famille              varchar(100) charset utf8             null,
    ce_zone_geo          varchar(5) charset utf8               null,
    zone_geo             varchar(255) charset utf8             null,
    lieudit              varchar(255) charset utf8             null,
    station              varchar(255) charset utf8             null,
    milieu               varchar(255) charset utf8             null,
    nom_referentiel      varchar(25) charset utf8              null,
    date_observation     datetime                              null,
    mots_cles_texte      longtext charset utf8                 null comment 'Champ calculé contenant la liste des mots clés utilisateurs séparé par des virgules.',
    commentaire          text charset utf8                     null,
    date_creation        datetime                              null,
    date_modification    datetime                              null,
    date_transmission    datetime                              null,
    certitude            varchar(25) charset utf8              null,
    pays                 varchar(150) charset utf8             null comment 'Code de pays suivant le standard ISO 3166-2',
    input_source         varchar(15)                           null,
    donnees_standard     tinyint(1)                            null
);

create index ce_utilisateur
    on del_observation (ce_utilisateur);

create index certitude
    on del_observation (certitude);

create index courriel_utilisateur
    on del_observation (courriel_utilisateur);

create index nom_referentiel
    on del_observation (nom_referentiel);

create index nom_ret_nn
    on del_observation (nom_ret_nn);

create index nom_sel
    on del_observation (nom_sel);

create index nom_sel_nn
    on del_observation (nom_sel_nn);

create index source
    on del_observation (input_source);

grant select, update on table del_observation to manon;

create table del_observation_modif_date
(
    id_observation bigint   not null,
    modif_date     datetime null,
    constraint id_observation
        unique (id_observation)
)
    engine = MyISAM
    charset = utf8;

create table del_utilisateur_infos
(
    id_utilisateur                        int                              not null
        primary key,
    intitule                              varchar(128)                     null,
    prenom                                varchar(32)                      null,
    nom                                   varchar(32)                      null,
    courriel                              varchar(128)                     null,
    admin                                 tinyint(1)                       not null,
    preferences                           longtext collate utf8_unicode_ci not null,
    date_premiere_utilisation             datetime                         null,
    date_derniere_consultation_evenements datetime                         not null
)
    engine = MyISAM
    charset = utf8;

create index courriel_idx
    on del_utilisateur_infos (courriel);

grant update (admin) on table del_utilisateur_infos to manon;

create table pn_ident
(
    id      int auto_increment
        primary key,
    ce_obs  bigint       null,
    nom_sci varchar(128) null
)
    engine = MyISAM
    charset = utf8;

create index fk_del_obs
    on pn_ident (ce_obs);

create table reveries_sp
(
    num_nom int(10)      not null
        primary key,
    nom     varchar(100) not null
)
    comment 'à supprimer une fois l''export réalisé' engine = MyISAM
                                                     charset = utf8;

create definer = root@localhost view del_image as
select `i`.`id_image`             AS `id_image`,
       `i`.`ce_observation`       AS `ce_observation`,
       `o`.`ce_utilisateur`       AS `ce_utilisateur`,
       ''                         AS `prenom_utilisateur`,
       `o`.`pseudo_utilisateur`   AS `nom_utilisateur`,
       `o`.`courriel_utilisateur` AS `courriel_utilisateur`,
       '2400'                     AS `hauteur`,
       '3200'                     AS `largeur`,
       `i`.`date_prise_de_vue`    AS `date_prise_de_vue`,
       `i`.`mots_cles_texte`      AS `mots_cles_texte`,
       NULL                       AS `commentaire`,
       `i`.`nom_original`         AS `nom_original`,
       `i`.`date_creation`        AS `date_creation`,
       `i`.`date_modification`    AS `date_modification`,
       `i`.`date_liaison`         AS `date_liaison`,
       `o`.`date_transmission`    AS `date_transmission`
from (`tb_new_cel`.`cel_images_export` `i` left join `tb_new_cel`.`cel_export_total` `o`
      on (`o`.`id_observation` = `i`.`ce_observation`))
where `o`.`transmission` = '1';

-- comment on column del_image.date_prise_de_vue not supported: Date de la prise de vue

-- comment on column del_image.nom_original not supported: Nom du fichier image

-- comment on column del_image.date_creation not supported: Date de l'import du fichier

-- comment on column del_image.date_modification not supported: Date de dernière modification

-- comment on column del_image.date_liaison not supported: Date à laquelle la photo a été liée à une obs

create definer = root@localhost view del_observation_new as
select `o`.`id_observation`       AS `id_observation`,
       `o`.`ce_utilisateur`       AS `ce_utilisateur`,
       `o`.`pseudo_utilisateur`   AS `nom_utilisateur`,
       ''                         AS `prenom_utilisateur`,
       `o`.`courriel_utilisateur` AS `courriel_utilisateur`,
       `o`.`nom_sel`              AS `nom_sel`,
       `o`.`nom_sel_nn`           AS `nom_sel_nn`,
       `o`.`nom_ret`              AS `nom_ret`,
       `o`.`nom_ret_nn`           AS `nom_ret_nn`,
       `o`.`nom_ret_nn`           AS `nt`,
       `o`.`famille`              AS `famille`,
       `o`.`ce_zone_geo`          AS `ce_zone_geo`,
       `o`.`zone_geo`             AS `zone_geo`,
       `o`.`lieudit`              AS `lieudit`,
       `o`.`station`              AS `station`,
       `o`.`milieu`               AS `milieu`,
       `o`.`nom_referentiel`      AS `nom_referentiel`,
       `o`.`date_observation`     AS `date_observation`,
       `o`.`mots_cles_texte`      AS `mots_cles_texte`,
       `o`.`commentaire`          AS `commentaire`,
       `o`.`date_creation`        AS `date_creation`,
       `o`.`date_modification`    AS `date_modification`,
       `o`.`date_transmission`    AS `date_transmission`,
       `o`.`certitude`            AS `certitude`,
       `o`.`pays`                 AS `pays`
from `tb_new_cel`.`cel_export_total` `o`
where `o`.`images` is not null
  and `o`.`transmission` = '1';

-- comment on column del_observation_new.nom_sel_nn not supported: Numéro du nom sélectionné.

-- comment on column del_observation_new.nom_ret_nn not supported: Numéro du nom retenu.

-- comment on column del_observation_new.nt not supported: Numéro du nom retenu.

-- comment on column del_observation_new.mots_cles_texte not supported: Champ calculé contenant la liste des mots clés utilisateurs séparé par des virgules.

-- comment on column del_observation_new.pays not supported: Code de pays suivant le standard ISO 3166-2

create definer = root@localhost view del_plantnet as
select `o`.`id_observation`          AS `id_observation`,
       `o`.`id_plantnet`             AS `id_plantnet`,
       `o`.`ce_utilisateur`          AS `ce_utilisateur`,
       `o`.`courriel_utilisateur`    AS `courriel_utilisateur`,
       `o`.`nom_sel`                 AS `nom_sel`,
       `o`.`nom_sel_nn`              AS `nom_sel_nn`,
       `o`.`nom_ret`                 AS `nom_ret`,
       `o`.`nom_ret_nn`              AS `nom_ret_nn`,
       `o`.`famille`                 AS `famille`,
       `o`.`nom_referentiel`         AS `nom_referentiel`,
       `o`.`geometry`                AS `zone_geo`,
       `o`.`latitude`                AS `latitude`,
       `o`.`longitude`               AS `longitude`,
       `o`.`date_observation`        AS `date_observation`,
       `o`.`date_creation`           AS `date_created`,
       `o`.`date_modification`       AS `date_updated`,
       `o`.`date_transmission`       AS `date_published`,
       `o`.`type_donnees`            AS `type_donnees`,
       `o`.`score_identiplante`      AS `identiplante_score`,
       `o`.`validation_identiplante` AS `is_identiplante_validated`,
       `o`.`mots_cles_texte`         AS `mots_cles_cel_obs`,
       `o`.`programme`               AS `programme`,
       `i`.`id_image`                AS `id_image`,
       `i`.`mots_cles_texte`         AS `mots_cles_cel_image`,
       `i`.`nom_original`            AS `original_name`,
       `i`.`date_prise_de_vue`       AS `i_date_shot`,
       `i`.`date_creation`           AS `i_date_creation`,
       `i`.`date_modification`       AS `i_date_updated`
from (`tb_new_cel`.`cel_export_total` `o` join `tb_new_cel`.`cel_images_export` `i`
      on (`o`.`id_observation` = `i`.`ce_observation`))
where `o`.`transmission` = '1';

-- comment on column del_plantnet.nom_sel_nn not supported: Numéro du nom sélectionné.

-- comment on column del_plantnet.nom_ret_nn not supported: Numéro du nom retenu.

-- comment on column del_plantnet.is_identiplante_validated not supported: La proposition "validée" une fois que l'auteur a  validé et que le nom_sel de cel_obs a été modifié à partir du nom_sel de del_commentaire.

-- comment on column del_plantnet.mots_cles_cel_obs not supported: Champ calculé contenant la liste des mots clés utilisateurs séparé par des virgules.

-- comment on column del_plantnet.original_name not supported: Nom du fichier image

-- comment on column del_plantnet.i_date_shot not supported: Date de la prise de vue

-- comment on column del_plantnet.i_date_creation not supported: Date de l'import du fichier

-- comment on column del_plantnet.i_date_updated not supported: Date de dernière modification

create definer = root@localhost view del_plantnet_images as
select `o`.`id_observation`    AS `id_observation`,
       `o`.`id_plantnet`       AS `id_plantnet`,
       `o`.`ce_utilisateur`    AS `ce_utilisateur`,
       `i`.`id_image`          AS `id_image`,
       `i`.`mots_cles_texte`   AS `mots_cles_cel_image`,
       `i`.`nom_original`      AS `original_name`,
       `i`.`date_prise_de_vue` AS `i_date_shot`,
       `i`.`date_creation`     AS `i_date_creation`,
       `i`.`date_modification` AS `i_date_updated`
from (`tb_new_cel`.`cel_export_total` `o` join `tb_new_cel`.`cel_images_export` `i`
      on (`o`.`id_observation` = `i`.`ce_observation`))
where `o`.`transmission` = '1';

-- comment on column del_plantnet_images.original_name not supported: Nom du fichier image

-- comment on column del_plantnet_images.i_date_shot not supported: Date de la prise de vue

-- comment on column del_plantnet_images.i_date_creation not supported: Date de l'import du fichier

-- comment on column del_plantnet_images.i_date_updated not supported: Date de dernière modification

create
    definer = root@localhost procedure getCommentaires(IN _s1 int(20))
BEGIN
        SELECT id_commentaire, ce_observation, ce_proposition, texte, nom_sel, nom_sel_nn, proposition_retenue FROM `tb_del`.`del_commentaire` WHERE ce_observation = _s1;
  END;

create
    definer = root@localhost procedure rebuild_retenues()
BEGIN
  DROP TEMPORARY TABLE IF EXISTS _temp_having_retenue, _temp_multi_prop, _temp_mono_prop;

  
  CREATE TEMPORARY TABLE _temp_having_retenue ENGINE=MEMORY AS ( 
       SELECT ce_observation FROM `tb_del`.`del_commentaire` co WHERE proposition_retenue = 1);

  
  CREATE TEMPORARY TABLE IF NOT EXISTS _temp_multi_prop ENGINE=MEMORY AS ( 
       SELECT ce_observation FROM `tb_del`.`del_commentaire` co 
       WHERE nom_sel IS NOT NULL 
             AND proposition_retenue = 0  
       GROUP BY ce_observation HAVING COUNT(id_commentaire) > 1); 

  
  CREATE TEMPORARY TABLE IF NOT EXISTS _temp_mono_prop ENGINE=MEMORY AS ( 
       SELECT ce_observation FROM `tb_del`.`del_commentaire` 
       WHERE nom_sel IS NOT NULL 
             AND proposition_retenue = 0  
       GROUP BY ce_observation HAVING COUNT(id_commentaire) = 1);

  END;

create
    definer = root@localhost procedure update_struct()
BEGIN
       
       DECLARE CONTINUE HANDLER FOR SQLSTATE '42S21' SELECT "colonne proposition_retenue dÃ©jÃ  existante"; 
       ALTER TABLE `tb_del`.`del_commentaire` ADD `proposition_retenue` INT(1) NOT NULL DEFAULT 0 COMMENT "La proposition \"validÃ©e\" une fois que l'auteur Ã  validÃ© et que le nom_sel de cel_obs a Ã©tÃ© modifiÃ© Ã  partir du nom_sel de del_commentaire."; 
  END;


