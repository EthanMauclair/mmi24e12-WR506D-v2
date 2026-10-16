<?php

declare(strict_types=1);

namespace App\Tests\Unit\EventListener;

use App\EventListener\RegleMetierListener;
use App\Exception\RegleMetierException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Contrat d'API, § Conventions : une règle métier violée devient 403 (règle d'accès),
 * 409 (conflit avec l'état actuel) ou 422 (donnée refusée), au format application/problem+json.
 */
final class RegleMetierListenerTest extends TestCase
{
    #[Test]
    public function chaque_regle_violee_donne_le_statut_http_du_contrat(): void
    {
        foreach ([
            'A-10' => 403, 'A-17' => 403, 'I-08' => 403,
            'I-04' => 409, 'I-07' => 409, 'I-10' => 409, 'I-17' => 409,
            'I-01' => 422, 'I-11' => 422, 'I-21' => 422, 'H18' => 422,
        ] as $invariant => $statut) {
            $evenement = $this->evenement(new RegleMetierException($invariant, 'Message.'));

            (new RegleMetierListener())($evenement);

            $reponse = $evenement->getResponse();
            self::assertNotNull($reponse, $invariant);
            self::assertSame($statut, $reponse->getStatusCode(), $invariant);
            self::assertSame('application/problem+json', $reponse->headers->get('Content-Type'));
            self::assertStringContainsString('['.$invariant.'] Message.', (string) $reponse->getContent());
        }
    }

    #[Test]
    public function les_autres_erreurs_sont_laissees_a_symfony(): void
    {
        $evenement = $this->evenement(new \RuntimeException('Autre chose.'));

        (new RegleMetierListener())($evenement);

        self::assertNull($evenement->getResponse());
    }

    private function evenement(\Throwable $erreur): ExceptionEvent
    {
        return new ExceptionEvent($this->createStub(HttpKernelInterface::class), new Request(), HttpKernelInterface::MAIN_REQUEST, $erreur);
    }
}
