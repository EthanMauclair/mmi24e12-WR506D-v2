<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Categorie;
use App\Repository\CategorieRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Proposition de catégorie, marque et couleur à partir de la photo (F-07, docs/07-choix-techniques.md, § 7, D-023).
 *
 * Le modèle (Google AI Studio, D-040) reçoit la photo déjà réduite et nettoyée de ses métadonnées. Sa réponse
 * n'est qu'une proposition : elle est renvoyée au front, jamais enregistrée sans validation (H6).
 * Toute panne (clé absente, réseau, délai, 429, réponse illisible) donne une proposition vide :
 * la saisie se fait alors à la main et la photo reste enregistrée (P2).
 */
final class ReconnaissanceArticles
{
    private const MARQUE_MAX = 80;
    private const COULEUR_MAX = 40;

    public function __construct(
        private readonly HttpClientInterface $reconnaissanceClient,
        private readonly CategorieRepository $categories,
        private readonly LoggerInterface $logger,
        #[Autowire(env: 'RECONNAISSANCE_CLE')]
        private readonly string $cle,
        #[Autowire(env: 'RECONNAISSANCE_MODELE')]
        private readonly string $modele,
    ) {
    }

    /**
     * @param string $jpeg la photo telle qu'enregistrée par PhotoArticles
     *
     * @return array{categorie: array{id: int|null, nom: string}, marque: string|null, couleur: string|null}|null
     */
    public function proposer(string $jpeg): ?array
    {
        if ('' === trim($this->cle)) {
            return null;
        }

        $categories = $this->categories->parNom();

        try {
            $reponse = $this->reconnaissanceClient->request('POST', 'chat/completions', [
                'auth_bearer' => $this->cle,
                'json' => [
                    'model' => $this->modele,
                    'temperature' => 0,
                    'max_tokens' => 200,
                    'messages' => [[
                        'role' => 'user',
                        'content' => [
                            ['type' => 'text', 'text' => $this->prompt($categories)],
                            ['type' => 'image_url', 'image_url' => ['url' => 'data:image/jpeg;base64,'.base64_encode($jpeg)]],
                        ],
                    ]],
                ],
            ]);
            $texte = $reponse->toArray()['choices'][0]['message']['content'] ?? null;
        } catch (ExceptionInterface $exception) {
            $this->logger->warning('Reconnaissance photo indisponible : {raison}', ['raison' => $exception->getMessage()]);

            return null;
        }

        $proposition = \is_string($texte) ? $this->lireJson($texte) : null;
        if (null === $proposition || !\is_string($proposition['categorie'] ?? null)) {
            $this->logger->warning('Reconnaissance photo : réponse inexploitable du modèle.');

            return null;
        }

        $categorie = $this->categories->nommee(trim($proposition['categorie'])) ?? $this->categories->nommee('Autre');
        if (null === $categorie) {
            return null;
        }

        return [
            'categorie' => ['id' => $categorie->getId(), 'nom' => $categorie->getNom()],
            'marque' => $this->texte($proposition['marque'] ?? null, self::MARQUE_MAX),
            'couleur' => $this->texte($proposition['couleur'] ?? null, self::COULEUR_MAX),
        ];
    }

    /** @param list<Categorie> $categories */
    private function prompt(array $categories): string
    {
        $noms = implode(', ', array_map(static fn (Categorie $categorie): string => $categorie->getNom(), $categories));

        return <<<PROMPT
            Tu aides à faire l'inventaire d'une penderie. Identifie l'objet principal de la photo.
            Réponds uniquement par un objet JSON, sans texte autour ni bloc de code :
            {"categorie": "…", "marque": "…", "couleur": "…"}
            - "categorie" : exactement un nom de cette liste, écrit à l'identique : {$noms}. Si aucun ne convient, "Autre".
            - "marque" : seulement si elle est lisible ou clairement reconnaissable sur la photo, sinon null.
            - "couleur" : la couleur principale en français, un ou deux mots en minuscules, sinon null.
            PROMPT;
    }

    /**
     * Le modèle ne garantit pas un JSON pur : on tolère un bloc de code ou du texte autour.
     *
     * @return array<string, mixed>|null
     */
    private function lireJson(string $texte): ?array
    {
        $debut = strpos($texte, '{');
        $fin = strrpos($texte, '}');
        if (false === $debut || false === $fin || $fin < $debut) {
            return null;
        }

        try {
            $donnees = json_decode(substr($texte, $debut, $fin - $debut + 1), true, 8, \JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return null;
        }

        return \is_array($donnees) ? $donnees : null;
    }

    private function texte(mixed $valeur, int $longueurMax): ?string
    {
        if (!\is_string($valeur)) {
            return null;
        }
        $valeur = trim($valeur);
        if ('' === $valeur || \in_array(mb_strtolower($valeur), ['null', 'inconnue', 'inconnu'], true)) {
            return null;
        }

        return mb_substr($valeur, 0, $longueurMax);
    }
}
