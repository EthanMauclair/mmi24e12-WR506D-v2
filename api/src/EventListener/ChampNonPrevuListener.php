<?php

declare(strict_types=1);

namespace App\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Serializer\Exception\ExtraAttributesException;

/**
 * A-15 : un champ non prévu dans le corps d'une requête (ex. « roles », « referent », « auteur »)
 * est refusé en 422, au lieu d'être ignoré en silence ou de provoquer une erreur 500.
 */
#[AsEventListener(event: KernelEvents::EXCEPTION)]
final class ChampNonPrevuListener
{
    public function __invoke(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        while (null !== $exception && !$exception instanceof ExtraAttributesException) {
            $exception = $exception->getPrevious();
        }
        if (null === $exception) {
            return;
        }

        $event->setResponse(new JsonResponse(
            [
                'title' => 'Champ non prévu',
                'status' => 422,
                'detail' => 'Champ(s) non accepté(s) : '.implode(', ', $exception->getExtraAttributes()).'.',
            ],
            422,
            ['Content-Type' => 'application/problem+json'],
        ));
    }
}
