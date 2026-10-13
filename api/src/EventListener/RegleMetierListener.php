<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Exception\RegleMetierException;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Traduit une règle métier violée en réponse JSON lisible (contrat d'API, § Conventions) :
 * - 409 quand l'action contredit l'état actuel (ex. I-04 : supprimer son compte en étant propriétaire) ;
 * - 422 sinon (données refusées par une règle, ex. I-21 : rendre public un compte mineur).
 */
#[AsEventListener(event: KernelEvents::EXCEPTION)]
final class RegleMetierListener
{
    private const CONFLITS = ['I-04', 'I-07'];

    public function __invoke(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        if (!$exception instanceof RegleMetierException) {
            return;
        }

        $statut = \in_array($exception->invariant, self::CONFLITS, true) ? 409 : 422;

        $event->setResponse(new JsonResponse(
            ['title' => 'Règle métier non respectée', 'status' => $statut, 'detail' => $exception->getMessage()],
            $statut,
            ['Content-Type' => 'application/problem+json'],
        ));
    }
}
