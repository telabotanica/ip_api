<?php

namespace App\Service;

use App\Model\UrlCriteria;
use Symfony\Component\HttpFoundation\Request;

class UrlValidator
{
    private const URL_CRITERIA_MAPPING = [
        'masque_referentiel' => ['url_parameter' => 'masque.referentiel', 'bdd_column' => 'nom_referentiel', 'is_exact' => true],
        'masque_nom_ret' => ['url_parameter' => 'masque.nom_ret', 'bdd_column' => 'nom_ret', 'is_exact' => false],
        'masque_nom_ret_nn' => ['url_parameter' => 'masque.nom_ret_nn', 'bdd_column' => 'nom_ret_nn', 'is_exact' => true],
        'masque_nom_sel' => ['url_parameter' => 'masque.nom_sel', 'bdd_column' => 'nom_sel', 'is_exact' => false],
        'masque_nom_sel_nn' => ['url_parameter' => 'masque.nom_sel_nn', 'bdd_column' => 'nom_sel_nn', 'is_exact' => true],
        'masque_famille' => ['url_parameter' => 'masque.famille', 'bdd_column' => 'famille', 'is_exact' => false],
        'masque_tag' => ['url_parameter' => 'masque.tag', 'bdd_column' => 'mots_cles_texte', 'is_exact' => false],
        'masque_cp' => ['url_parameter' => 'masque.cp', 'bdd_column' => 'ce_zone_geo', 'is_exact' => true],
        'masque_auteur' => ['url_parameter' => 'masque.auteur', 'bdd_column' => '', 'is_exact' => false],
        'masque' => ['url_parameter' => 'masque', 'bdd_column' => '', 'is_exact' => false],
        ];
    /*
     * masque.genre
     * masque.ns
     * masque.date (observation date)
     * masque.pays
     * masque.departement
     * masque.commune
     */


    public function validateType(string $type){
        $typeAutorises = ['adeterminer', 'aconfirmer', 'validees', 'monactivite', 'tous'];
        if (!in_array($type, $typeAutorises, true)) {
            $type = 'tous';
        }
        return $type;
    }

    public function validateTri(string $tri){
        $triAutorises = ['date_transmission', 'date_observation', 'nb_commentaires'];
        if (!in_array($tri, $triAutorises, true)) {
            $tri = 'date_transmission';
        }
        return $tri;
    }

    public function validateOrder(string $order){
        $orderAutorises = ['asc', 'desc'];
        if (!in_array($order, $orderAutorises, true)) {
            $order = 'desc';
        }
        return $order;
    }

    public function mapUrlParameters(Request $request){
        $criteriaObjects = [];

        foreach ($request->query->all() as $key => $value) {
            if (isset(self::URL_CRITERIA_MAPPING[$key]) && $value != '') {
                $mapping = self::URL_CRITERIA_MAPPING[$key];

                $criteriaObjects[] = new UrlCriteria(
                    query_parameter: $key,
                    url_parameter: $mapping['url_parameter'],
                    bdd_column: $mapping['bdd_column'],
                    value: $value,
                    is_exact: $mapping['is_exact']
                );
            }
        }

        return $criteriaObjects;
    }
}