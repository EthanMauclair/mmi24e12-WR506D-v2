<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Utilisateur;
use League\Flysystem\FilesystemOperator;

/**
 * Photos des articles (docs/07-choix-techniques.md, § 6).
 *
 * À l'envoi, l'image est redressée, réduite et réencodée en JPG : le réencodage supprime toutes
 * les métadonnées, dont la position GPS. Elle est rangée dans le dossier de son auteur et n'est
 * jamais servie en accès direct : GET /api/articles/{id}/photo applique les règles de visibilité.
 */
final class PhotoArticles
{
    public const TAILLE_MAX = 10 * 1024 * 1024;
    private const COTE_MAX = 1600;
    private const QUALITE = 82;

    public function __construct(
        private readonly FilesystemOperator $defaultStorage,
    ) {
    }

    /**
     * @return string le chemin de la photo, à renvoyer dans POST /api/articles
     *
     * @throws PhotoRefuseeException si le fichier n'est pas une image JPG, PNG ou WebP lisible
     */
    public function enregistrer(string $fichier, Utilisateur $auteur): string
    {
        $image = $this->ouvrir($fichier);
        $image = $this->reduire($image);

        ob_start();
        imagejpeg($image, null, self::QUALITE);
        $jpeg = (string) ob_get_clean();

        $chemin = \sprintf('photos/%d/%s.jpg', $auteur->getId(), bin2hex(random_bytes(16)));
        $this->defaultStorage->write($chemin, $jpeg);

        return $chemin;
    }

    /** Une photo ne sert qu'à celui qui l'a envoyée : on ne s'approprie pas la photo d'un autre. */
    public function appartientA(string $chemin, Utilisateur $auteur): bool
    {
        return 1 === preg_match('#^photos/'.$auteur->getId().'/[0-9a-f]{32}\.jpg$#', $chemin)
            && $this->defaultStorage->fileExists($chemin);
    }

    public function lire(string $chemin): string
    {
        return $this->defaultStorage->read($chemin);
    }

    public function supprimer(string $chemin): void
    {
        if ($this->defaultStorage->fileExists($chemin)) {
            $this->defaultStorage->delete($chemin);
        }
    }

    private function ouvrir(string $fichier): \GdImage
    {
        $infos = @getimagesize($fichier);
        $image = match (false === $infos ? null : $infos[2]) {
            \IMAGETYPE_JPEG => @imagecreatefromjpeg($fichier),
            \IMAGETYPE_PNG => @imagecreatefrompng($fichier),
            \IMAGETYPE_WEBP => @imagecreatefromwebp($fichier),
            default => false,
        };
        if (!$image instanceof \GdImage) {
            throw new PhotoRefuseeException('La photo doit être une image JPG, PNG ou WebP.');
        }

        return \IMAGETYPE_JPEG === $infos[2] ? $this->redresser($image, $fichier) : $image;
    }

    /** Les téléphones enregistrent l'orientation dans les métadonnées, qui vont disparaître. */
    private function redresser(\GdImage $image, string $fichier): \GdImage
    {
        $exif = \function_exists('exif_read_data') ? @exif_read_data($fichier) : false;
        $angle = match (\is_array($exif) ? ($exif['Orientation'] ?? 1) : 1) {
            3 => 180,
            6 => -90,
            8 => 90,
            default => 0,
        };

        return 0 === $angle ? $image : (imagerotate($image, $angle, 0) ?: $image);
    }

    /** Côté le plus long ramené à 1600 px, sur fond blanc (la transparence n'existe pas en JPG). */
    private function reduire(\GdImage $image): \GdImage
    {
        $largeur = imagesx($image);
        $hauteur = imagesy($image);
        $ratio = min(1, self::COTE_MAX / max($largeur, $hauteur));
        $nouvelleLargeur = max(1, (int) round($largeur * $ratio));
        $nouvelleHauteur = max(1, (int) round($hauteur * $ratio));

        $toile = imagecreatetruecolor($nouvelleLargeur, $nouvelleHauteur);
        imagefill($toile, 0, 0, (int) imagecolorallocate($toile, 255, 255, 255));
        imagecopyresampled($toile, $image, 0, 0, 0, 0, $nouvelleLargeur, $nouvelleHauteur, $largeur, $hauteur);

        return $toile;
    }
}
