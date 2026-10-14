<?php

declare(strict_types=1);

namespace App\Tests\Double;

use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * Remplace OpenRouter pendant les tests (framework.http_client.mock_response_factory) :
 * aucun appel réel, et chaque test choisit la réponse du modèle.
 *
 * Sans réponse prévue, OpenRouter est « indisponible » (503) : la proposition est alors vide.
 */
final class OpenRouterSimule
{
    /** @var list<ResponseInterface> */
    private array $reponses = [];

    /** @var list<array{methode: string, url: string, options: array<string, mixed>}> */
    public array $requetes = [];

    public function prevoir(ResponseInterface $reponse): void
    {
        $this->reponses[] = $reponse;
    }

    /** Réponse du modèle au format chat/completions, avec le texte donné. */
    public function prevoirTexte(string $texte): void
    {
        $this->prevoir(new MockResponse(json_encode([
            'choices' => [['message' => ['role' => 'assistant', 'content' => $texte]]],
        ], \JSON_THROW_ON_ERROR)));
    }

    /** @param array<string, mixed> $options */
    public function __invoke(string $methode, string $url, array $options): ResponseInterface
    {
        $this->requetes[] = ['methode' => $methode, 'url' => $url, 'options' => $options];

        return array_shift($this->reponses) ?? new MockResponse('', ['http_code' => 503]);
    }
}
