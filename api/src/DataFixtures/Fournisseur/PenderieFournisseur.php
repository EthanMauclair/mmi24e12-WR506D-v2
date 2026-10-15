<?php

declare(strict_types=1);

namespace App\DataFixtures\Fournisseur;

use Faker\Provider\Base;

/**
 * Fournisseur Faker propre à PENDERIE : Faker connaît les noms et les adresses, pas les garde-robes.
 * Les poids donnent une penderie plausible (plus de t-shirts que de manteaux, quelques livres et vinyles),
 * et chaque catégorie a ses marques et ses couleurs.
 */
final class PenderieFournisseur extends Base
{
    /** Catégorie => [poids, famille de marques]. Les noms sont ceux de la liste fixe (D-021). */
    private const CATEGORIES = [
        'T-shirt' => [12, 'vetement'], 'Pull' => [9, 'vetement'], 'Jean' => [7, 'vetement'],
        'Pantalon' => [7, 'vetement'], 'Chemise' => [6, 'vetement'], 'Robe' => [6, 'vetement'],
        'Sweat' => [5, 'vetement'], 'Veste' => [5, 'exterieur'], 'Baskets' => [5, 'chaussure'],
        'Jupe' => [4, 'vetement'], 'Sous-vêtement' => [4, 'lingerie'], 'Sac' => [4, 'accessoire'],
        'Manteau' => [3, 'exterieur'], 'Short' => [3, 'vetement'], 'Bijou' => [3, 'bijou'],
        'Livre' => [3, 'livre'], 'Vinyle' => [3, 'vinyle'], 'Pyjama' => [2, 'lingerie'],
        'Maillot de bain' => [2, 'lingerie'], 'Bottines' => [2, 'chaussure'], 'Sandales' => [2, 'chaussure'],
        'Chaussures de ville' => [2, 'chaussure'], 'Ceinture' => [2, 'accessoire'], 'Écharpe' => [2, 'accessoire'],
        'Bottes' => [1, 'chaussure'], 'Bonnet' => [1, 'accessoire'], 'Chapeau' => [1, 'accessoire'],
        'Montre' => [1, 'montre'], 'Lunettes' => [1, 'lunettes'], 'Jeu vidéo' => [1, 'jeu'],
        'Autre' => [1, 'autre'],
    ];

    private const MARQUES = [
        'vetement' => ['Zara', 'Uniqlo', 'Sézane', 'H&M', 'Petit Bateau', "Levi's", 'Maje', 'Sandro', 'Mango', 'Arket', 'COS', 'Kiabi', 'Agnès b.', 'Armor-Lux'],
        'exterieur' => ['Patagonia', 'The North Face', 'Columbia', 'Zara', 'Sézane', 'Uniqlo', 'Aigle'],
        'chaussure' => ['Nike', 'Adidas', 'Veja', 'New Balance', 'Converse', 'Dr. Martens', 'Birkenstock', 'Minelli', 'Clarks'],
        'lingerie' => ['Etam', 'Princesse tam.tam', 'Petit Bateau', 'Uniqlo', 'Undiz'],
        'accessoire' => ['Longchamp', 'Le Tanneur', 'Sézane', 'Zara', 'Herschel', 'Fjällräven'],
        'bijou' => ['Pandora', 'Agatha', 'Swarovski', 'Ginette NY'],
        'montre' => ['Swatch', 'Fossil', 'Casio', 'Tissot'],
        'lunettes' => ['Ray-Ban', 'Krys', 'Jimmy Fairly'],
        'livre' => ['Gallimard', 'Folio', 'Pocket', 'Actes Sud', 'Le Livre de Poche'],
        'vinyle' => ['Blue Note', 'Columbia', 'Warner', 'Ninja Tune', 'Because Music'],
        'jeu' => ['Nintendo', 'Sony', 'Ubisoft'],
        'autre' => [],
    ];

    /** Couleur => code utilisé pour la photo factice. */
    private const COULEURS = [
        'Noir' => '#222222', 'Blanc' => '#F2F2EE', 'Gris' => '#9A9A9A', 'Bleu marine' => '#1F2A44',
        'Bleu' => '#3A6EA5', 'Beige' => '#D8C7A8', 'Camel' => '#B08350', 'Kaki' => '#6B6B3A',
        'Bordeaux' => '#6E1A2B', 'Rouge' => '#B3261E', 'Rose' => '#E7A1B0', 'Vert' => '#3F7D4E',
        'Jaune' => '#E4C04A', 'Marron' => '#6B4A2E', 'Écru' => '#EDE6D6',
    ];

    /** Familles pour lesquelles une couleur n'a pas de sens. */
    private const SANS_COULEUR = ['livre', 'vinyle', 'jeu'];

    private const NOTES = [
        'Acheté à Milan, à faire retoucher', 'À donner', 'Taille un peu petite', 'Cadeau d\'anniversaire',
        'À vendre sur Vinted', 'Pressing à prévoir', 'Manque un bouton', 'Porté une seule fois',
        'Seconde main, très bon état', 'À ressortir l\'hiver prochain', 'Prêté à Sophie', 'Édition limitée',
    ];

    /** Une catégorie de la liste fixe, tirée selon sa fréquence dans une vraie penderie. */
    public function categorieArticle(): string
    {
        $total = array_sum(array_column(self::CATEGORIES, 0));
        $tirage = $this->generator->numberBetween(1, $total);
        foreach (self::CATEGORIES as $nom => [$poids]) {
            $tirage -= $poids;
            if ($tirage <= 0) {
                return $nom;
            }
        }

        return 'Autre';
    }

    /** Une marque plausible pour la catégorie, ou null (une pièce sur six n'a pas de marque notée). */
    public function marqueArticle(string $categorie): ?string
    {
        $marques = self::MARQUES[self::famille($categorie)];
        if ([] === $marques || $this->generator->boolean(17)) {
            return null;
        }

        return self::randomElement($marques);
    }

    /** Une couleur, sauf pour les livres, vinyles et jeux. */
    public function couleurArticle(string $categorie): ?string
    {
        if (\in_array(self::famille($categorie), self::SANS_COULEUR, true)) {
            return null;
        }

        return self::randomElement(array_keys(self::COULEURS));
    }

    /** Une note pour un article sur sept environ. */
    public function noteArticle(): ?string
    {
        return $this->generator->boolean(15) ? self::randomElement(self::NOTES) : null;
    }

    /** Famille de la catégorie, pour choisir un rangement plausible (chaussures dans l'entrée, livres au salon…). */
    public static function famille(string $categorie): string
    {
        return self::CATEGORIES[$categorie][1] ?? 'autre';
    }

    /** @return list<string> */
    public static function categories(): array
    {
        return array_keys(self::CATEGORIES);
    }

    public static function codeCouleur(?string $couleur): string
    {
        return self::COULEURS[$couleur] ?? '#C9BFB3';
    }
}
