<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\DataFixtures\Fournisseur\PenderieFournisseur;
use League\Flysystem\FilesystemOperator;

/**
 * Photos des fixtures : une image JPG légère (360 × 450 px) aux couleurs de l'article, avec sa catégorie.
 * Elles sont écrites dans le vrai stockage, au même format que celles envoyées par l'application
 * (photos/{id de l'auteur}/{32 caractères hexadécimaux}.jpg) : GET /api/articles/{id}/photo fonctionne.
 */
final class PhotoFactice
{
    private const LARGEUR = 360;
    private const HAUTEUR = 450;

    public function __construct(
        private readonly FilesystemOperator $defaultStorage,
    ) {
    }

    /** Supprime les photos d'un chargement précédent. */
    public function viderLeStockage(): void
    {
        $this->defaultStorage->deleteDirectory('photos');
    }

    /** @param string $graine même graine, même nom de fichier : le jeu de données est reproductible */
    public function creer(int $auteurId, string $categorie, ?string $couleur, string $graine): string
    {
        $chemin = \sprintf('photos/%d/%s.jpg', $auteurId, md5($graine));

        $image = imagecreatetruecolor(self::LARGEUR, self::HAUTEUR);
        [$r, $v, $b] = sscanf(PenderieFournisseur::codeCouleur($couleur), '#%02x%02x%02x');
        imagefill($image, 0, 0, (int) imagecolorallocate($image, 246, 243, 238));
        // Silhouette de l'article : un rectangle arrondi de sa couleur.
        imagefilledrectangle($image, 60, 70, 300, 360, (int) imagecolorallocate($image, (int) $r, (int) $v, (int) $b));
        $clair = ($r * 299 + $v * 587 + $b * 114) / 1000 > 150;
        $texte = (int) ($clair ? imagecolorallocate($image, 31, 21, 23) : imagecolorallocate($image, 255, 255, 255));
        $libelle = iconv('UTF-8', 'ASCII//TRANSLIT', $categorie) ?: $categorie;
        imagestring($image, 5, (int) ((self::LARGEUR - \strlen($libelle) * 9) / 2), 205, $libelle, $texte);

        ob_start();
        imagejpeg($image, null, 70);
        $this->defaultStorage->write($chemin, (string) ob_get_clean());

        return $chemin;
    }
}
