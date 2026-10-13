<?php

declare(strict_types=1);

namespace App\Exception;

/**
 * Levée quand une action violerait un invariant métier (docs/05-modele-donnees.md).
 *
 * L'API la traduira en réponse 409 (conflit) ou 422 (données invalides) selon le cas.
 */
final class RegleMetierException extends \DomainException
{
    public function __construct(
        public readonly string $invariant,
        string $message,
    ) {
        parent::__construct(\sprintf('[%s] %s', $invariant, $message));
    }
}
