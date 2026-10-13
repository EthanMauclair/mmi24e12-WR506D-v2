<?php

declare(strict_types=1);

namespace App\Tests\Fonctionnel;

use PHPUnit\Framework\Attributes\Test;

final class CategorieApiTest extends ApiTestCase
{
    #[Test]
    public function la_liste_des_categories_est_publique_et_triee(): void
    {
        $this->json('GET', '/api/categories');

        self::assertResponseIsSuccessful();
        $categories = $this->reponse();
        $noms = array_column($categories, 'nom');
        self::assertContains('Vinyle', $noms, 'La liste fixe est remplie par la migration.');
        self::assertContains('Autre', $noms);
        self::assertSame(['id', 'nom'], array_keys($categories[0]));
        self::assertLessThan(array_search('Pull', $noms, true), array_search('Autre', $noms, true), 'Ordre alphabétique.');
        self::assertLessThan(array_search('Vinyle', $noms, true), array_search('Pull', $noms, true));
    }

    #[Test]
    public function aucune_route_ne_cree_de_categorie(): void
    {
        $camille = $this->creerMajeur('camille');

        $this->json('POST', '/api/categories', ['nom' => 'Vinyles'], $this->jetonDe($camille));

        self::assertResponseStatusCodeSame(405);
    }
}
