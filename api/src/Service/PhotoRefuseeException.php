<?php

declare(strict_types=1);

namespace App\Service;

/** Le fichier envoyé n'est pas une image exploitable (réponse 422). */
final class PhotoRefuseeException extends \RuntimeException
{
}
