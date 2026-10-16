<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Entity\Utilisateur;
use App\Service\PhotoArticles;
use App\Service\PhotoRefuseeException;
use PHPUnit\Framework\Attributes\Test;

/**
 * Le service des photos avec sa vraie extension GD et son vrai stockage Flysystem
 * (var/storage/test en environnement de test). Les fichiers écrits sont effacés après chaque test.
 */
final class PhotoArticlesTest extends IntegrationTestCase
{
    private PhotoArticles $photos;
    /** @var list<string> */
    private array $aEffacer = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->photos = $this->service(PhotoArticles::class);
    }

    protected function tearDown(): void
    {
        foreach ($this->aEffacer as $fichier) {
            str_starts_with($fichier, 'photos/') ? $this->photos->supprimer($fichier) : @unlink($fichier);
        }
        parent::tearDown();
    }

    #[Test]
    public function f07_une_grande_photo_est_reduite_a_1600_px_et_rangee_dans_le_dossier_de_son_auteur(): void
    {
        $camille = $this->majeur('camille');

        $chemin = $this->enregistrer($this->jpeg(2400, 1200), $camille);

        self::assertMatchesRegularExpression('#^photos/'.$camille->getId().'/[0-9a-f]{32}\.jpg$#', $chemin);
        self::assertSame([1600, 800], $this->dimensions($chemin), 'Le côté le plus long ramené à 1600 px, proportions gardées.');
        self::assertTrue($this->photos->appartientA($chemin, $camille));
        self::assertFalse($this->photos->appartientA($chemin, $this->majeur('voisin')), 'On ne s\'approprie pas la photo d\'un autre.');
    }

    #[Test]
    public function f07_les_metadonnees_dont_la_position_gps_ne_sont_jamais_conservees(): void
    {
        $camille = $this->majeur('camille');
        $source = $this->jpeg(400, 300, exif: true);
        self::assertStringContainsString('Exif', (string) file_get_contents($source), 'Le fichier envoyé contient bien des métadonnées.');

        $chemin = $this->enregistrer($source, $camille);

        self::assertStringNotContainsString('Exif', $this->photos->lire($chemin));
        self::assertSame([400, 300], $this->dimensions($chemin), 'Une petite photo n\'est pas agrandie.');
    }

    #[Test]
    public function f07_une_photo_de_telephone_tenu_en_portrait_ou_a_l_envers_est_redressee(): void
    {
        $camille = $this->majeur('camille');

        foreach ([6 => [300, 400], 8 => [300, 400], 3 => [400, 300]] as $orientation => $attendu) {
            $chemin = $this->enregistrer($this->jpeg(400, 300, orientation: $orientation), $camille);
            self::assertSame($attendu, $this->dimensions($chemin), 'Orientation EXIF '.$orientation);
        }
    }

    #[Test]
    public function f07_une_photo_webp_est_acceptee_et_convertie(): void
    {
        $source = $this->fichier('.webp');
        imagewebp(imagecreatetruecolor(120, 80), $source);

        $chemin = $this->enregistrer($source, $this->majeur('camille'));

        self::assertSame([120, 80], $this->dimensions($chemin));
    }

    #[Test]
    public function f07_un_png_transparent_devient_un_jpg_sur_fond_blanc(): void
    {
        $image = imagecreatetruecolor(50, 50);
        imagesavealpha($image, true);
        imagefill($image, 0, 0, (int) imagecolorallocatealpha($image, 0, 0, 0, 127));
        $source = $this->fichier('.png');
        imagepng($image, $source);

        $chemin = $this->enregistrer($source, $this->majeur('camille'));

        $stockee = imagecreatefromstring($this->photos->lire($chemin));
        self::assertInstanceOf(\GdImage::class, $stockee);
        $rgb = imagecolorat($stockee, 25, 25);
        self::assertGreaterThan(250, ($rgb >> 16) & 0xFF, 'Fond blanc, pas noir.');
    }

    #[Test]
    public function f07_un_fichier_qui_n_est_pas_une_image_est_refuse(): void
    {
        $source = $this->fichier('.jpg');
        file_put_contents($source, '<?php echo "pas une image";');

        $this->expectException(PhotoRefuseeException::class);
        $this->photos->enregistrer($source, $this->majeur('camille'));
    }

    #[Test]
    public function f09_supprimer_une_photo_l_efface_du_stockage_et_une_seconde_fois_ne_casse_rien(): void
    {
        $camille = $this->majeur('camille');
        $chemin = $this->enregistrer($this->jpeg(100, 100), $camille);

        $this->photos->supprimer($chemin);
        $this->photos->supprimer($chemin);

        self::assertFalse($this->photos->appartientA($chemin, $camille), 'Le fichier n\'existe plus.');
    }

    #[Test]
    public function f07_un_chemin_trafique_n_est_jamais_accepte(): void
    {
        $camille = $this->majeur('camille');
        $chemin = $this->enregistrer($this->jpeg(100, 100), $camille);
        $dossier = 'photos/'.$camille->getId().'/';

        self::assertFalse($this->photos->appartientA($dossier.'../'.basename($chemin), $camille));
        self::assertFalse($this->photos->appartientA($dossier.str_repeat('0', 32).'.jpg', $camille), 'Bon format, mais aucun fichier.');
        self::assertFalse($this->photos->appartientA($chemin.'.php', $camille));
    }

    private function enregistrer(string $source, Utilisateur $auteur): string
    {
        $chemin = $this->photos->enregistrer($source, $auteur);
        $this->aEffacer[] = $chemin;

        return $chemin;
    }

    /** @return array{int, int} largeur et hauteur de la photo stockée */
    private function dimensions(string $chemin): array
    {
        $infos = getimagesizefromstring($this->photos->lire($chemin));
        self::assertNotFalse($infos);
        self::assertSame(\IMAGETYPE_JPEG, $infos[2], 'Toujours réencodée en JPG.');

        return [$infos[0], $infos[1]];
    }

    private function jpeg(int $largeur, int $hauteur, bool $exif = false, int $orientation = 1): string
    {
        ob_start();
        imagejpeg(imagecreatetruecolor($largeur, $hauteur));
        $jpeg = (string) ob_get_clean();
        if ($exif || 1 !== $orientation) {
            $jpeg = self::avecOrientation($jpeg, $orientation);
        }
        $fichier = $this->fichier('.jpg');
        file_put_contents($fichier, $jpeg);

        return $fichier;
    }

    private function fichier(string $extension): string
    {
        $fichier = sys_get_temp_dir().'/penderie-'.bin2hex(random_bytes(6)).$extension;
        $this->aEffacer[] = $fichier;

        return $fichier;
    }

    /** Ajoute un bloc EXIF minimal (une seule étiquette : Orientation), comme le ferait un téléphone. */
    private static function avecOrientation(string $jpeg, int $orientation): string
    {
        $entree = pack('nnN', 0x0112, 3, 1).pack('n', $orientation)."\x00\x00";
        $tiff = 'MM'."\x00\x2A".pack('N', 8).pack('n', 1).$entree.pack('N', 0);
        $app1 = "Exif\x00\x00".$tiff;

        return substr($jpeg, 0, 2)."\xFF\xE1".pack('n', \strlen($app1) + 2).$app1.substr($jpeg, 2);
    }
}
