<?php

/*
 * Style du code PHP (séance 15) : règles @Symfony, avec deux exceptions propres au projet.
 * Vérification : make cs ; correction : make cs-corriger.
 */

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__)
    ->exclude('var')
    ->notPath([
        'config/bundles.php',
        'config/reference.php',
    ])
;

return (new PhpCsFixer\Config())
    ->setRules([
        '@Symfony' => true,
        // Les tests portent le numéro de la règle testée puis une phrase : f07_j_envoie_une_photo…
        'php_unit_method_case' => ['case' => 'snake_case'],
        // Ne pas ajouter un point après une phrase qui finit déjà par « … ».
        'phpdoc_summary' => false,
    ])
    ->setFinder($finder)
;
