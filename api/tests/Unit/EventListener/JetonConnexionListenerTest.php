<?php

declare(strict_types=1);

namespace App\Tests\Unit\EventListener;

use App\EventListener\JetonConnexionListener;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\User\InMemoryUser;

/** Contrat d'API : la connexion renvoie { "jeton": … } et non { "token": … }. */
final class JetonConnexionListenerTest extends TestCase
{
    #[Test]
    public function la_cle_token_devient_jeton(): void
    {
        $evenement = $this->evenement(['token' => 'abc']);

        (new JetonConnexionListener())($evenement);

        self::assertSame(['jeton' => 'abc'], $evenement->getData());
    }

    #[Test]
    public function une_reponse_sans_jeton_n_est_pas_modifiee(): void
    {
        $evenement = $this->evenement(['autre' => 'valeur']);

        (new JetonConnexionListener())($evenement);

        self::assertSame(['autre' => 'valeur'], $evenement->getData());
    }

    /** @param array<string, string> $donnees */
    private function evenement(array $donnees): AuthenticationSuccessEvent
    {
        return new AuthenticationSuccessEvent($donnees, new InMemoryUser('camille', null), new Response());
    }
}
