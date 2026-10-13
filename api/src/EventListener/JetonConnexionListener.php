<?php

declare(strict_types=1);

namespace App\EventListener;

use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Events;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

/**
 * Le contrat d'API (POST /api/auth/connexion) renvoie { "jeton": "…" } :
 * on renomme la clé « token » produite par LexikJWT.
 */
#[AsEventListener(event: Events::AUTHENTICATION_SUCCESS)]
final class JetonConnexionListener
{
    public function __invoke(AuthenticationSuccessEvent $event): void
    {
        $donnees = $event->getData();
        if (!isset($donnees['token'])) {
            return;
        }

        $donnees['jeton'] = $donnees['token'];
        unset($donnees['token']);
        $event->setData($donnees);
    }
}
