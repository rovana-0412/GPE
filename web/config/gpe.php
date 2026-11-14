<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Contexte national — GPE, République togolaise
    |--------------------------------------------------------------------------
    |
    | Paramètres propres au déploiement togolais : monnaie, formats
    | d'affichage et mentions institutionnelles.
    |
    */

    // Monnaie des montants patrimoniaux (franc CFA BCEAO)
    'currency' => 'XOF',
    'currency_symbol' => 'FCFA',

    // Séparateurs de milliers et décimales adaptés à l'usage francophone
    'number_format' => [
        'decimals' => 0,
        'decimal_separator' => ',',
        'thousands_separator' => ' ',
    ],

    // Mention apposée sur les documents et rapports officiels
    'institution' => 'République togolaise',
    'document_footer' => 'Plateforme nationale de Gestion du Patrimoine de l’État',

];
