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
 *
 * Les réponses et les requêtes sont statiques : Symfony crée lui-même l'instance, et le test n'a pas à
 * la chercher dans le conteneur, que PHPStan lit en environnement de dev, où ce double n'existe pas.
 */
final class OpenRouterSimule
{
    /** @var list<ResponseInterface> */
    private static array $reponses = [];

    /** @var list<array{methode: string, url: string, options: array<string, mixed>}> */
    public static array $requetes = [];

    /** À appeler avant chaque test : aucune réponse prévue, aucune requête reçue. */
    public static function reinitialiser(): void
    {
        self::$reponses = [];
        self::$requetes = [];
    }

    public static function prevoir(ResponseInterface $reponse): void
    {
        self::$reponses[] = $reponse;
    }

    /** Réponse du modèle au format chat/completions, avec le texte donné. */
    public static function prevoirTexte(string $texte): void
    {
        self::prevoir(new MockResponse(json_encode([
            'choices' => [['message' => ['role' => 'assistant', 'content' => $texte]]],
        ], \JSON_THROW_ON_ERROR)));
    }

    /** @param array<string, mixed> $options */
    public function __invoke(string $methode, string $url, array $options): ResponseInterface
    {
        self::$requetes[] = ['methode' => $methode, 'url' => $url, 'options' => $options];

        return array_shift(self::$reponses) ?? new MockResponse('', ['http_code' => 503]);
    }
}
