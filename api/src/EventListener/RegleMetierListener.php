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
 * - 403 pour une règle d'accès (A-xx) sur une ressource que l'on voit ;
 * - 409 quand l'action contredit l'état actuel (ex. I-04 : supprimer son compte en étant propriétaire) ;
 * - 422 sinon (données refusées par une règle, ex. I-21 : rendre public un compte mineur).
 */
#[AsEventListener(event: KernelEvents::EXCEPTION)]
final class RegleMetierListener
{
    private const CONFLITS = ['I-04', 'I-07', 'I-10'];
    /** Règles qui interdisent une action à une catégorie de compte (ex. I-08 : un mineur ne crée pas de logement). */
    private const INTERDITS = ['I-08'];

    public function __invoke(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        if (!$exception instanceof RegleMetierException) {
            return;
        }

        $statut = match (true) {
            str_starts_with($exception->invariant, 'A-'), \in_array($exception->invariant, self::INTERDITS, true) => 403,
            \in_array($exception->invariant, self::CONFLITS, true) => 409,
            default => 422,
        };

        $event->setResponse(new JsonResponse(
            ['title' => 'Règle métier non respectée', 'status' => $statut, 'detail' => $exception->getMessage()],
            $statut,
            ['Content-Type' => 'application/problem+json'],
        ));
    }
}
