<?php

namespace App\Service;

class UrlValidator
{
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

}