<?php

declare(strict_types=1);

namespace App\ApiResource;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Dto\ArticleEntree;
use App\Dto\ArticleModification;
use App\Entity\Article;
use App\State\ArticleProcessor;
use App\State\ArticleProvider;

/**
 * Un article, sous deux formes (docs/08-contrat-api.md, § 2) :
 * - ArticleMembre pour un membre du logement : tout, dont la note et l'emplacement ;
 * - ArticlePublic pour un visiteur : ni note, ni pièce, ni stockage (test S-05).
 *
 * Les champs réservés aux membres restent à null dans la vue publique, et API Platform
 * omet les valeurs nulles (skip_null_values) : ils n'apparaissent donc pas dans la réponse.
 */
#[ApiResource(
    shortName: 'Article',
    operations: [
        new GetCollection(uriTemplate: '/articles', provider: ArticleProvider::class),
        new Post(uriTemplate: '/articles', input: ArticleEntree::class, processor: ArticleProcessor::class),
        new Get(uriTemplate: '/articles/{id}', provider: ArticleProvider::class),
        new Patch(uriTemplate: '/articles/{id}', input: ArticleModification::class, provider: ArticleProvider::class, processor: ArticleProcessor::class),
        new Delete(uriTemplate: '/articles/{id}', provider: ArticleProvider::class, processor: ArticleProcessor::class),
    ],
)]
final class ArticleRessource
{
    #[ApiProperty(identifier: true)]
    public ?int $id = null;

    /** La photo passe toujours par l'API, qui applique les règles de visibilité (D-015). */
    public string $photoUrl = '';

    /** @var array{id: int|null, nom: string} */
    public array $categorie = ['id' => null, 'nom' => ''];

    public ?string $marque = null;

    public ?string $couleur = null;

    /** @var array{pseudo: string} */
    public array $auteur = ['pseudo' => ''];

    /** @var array{nom: string} */
    public array $logement = ['nom' => ''];

    // Vue membre uniquement.

    public ?string $note = null;

    public ?bool $estPersonnel = null;

    /** @var array{logement: array{id: int|null, nom: string}, piece: array{id: int|null, nom: string}, stockage: array{id: int|null, nom: string}}|null */
    public ?array $emplacement = null;

    public ?\DateTimeImmutable $creeLe = null;

    public static function depuis(Article $article, bool $vueMembre): self
    {
        $ressource = new self();
        $ressource->id = $article->getId();
        $ressource->photoUrl = '/api/articles/'.$article->getId().'/photo';
        $ressource->categorie = ['id' => $article->getCategorie()->getId(), 'nom' => $article->getCategorie()->getNom()];
        $ressource->marque = $article->getMarque();
        $ressource->couleur = $article->getCouleur();
        $ressource->auteur = ['pseudo' => $article->getAuteur()->getPseudo()];
        $ressource->logement = ['nom' => $article->getLogement()->getNom()];

        if ($vueMembre) {
            $stockage = $article->getStockage();
            $ressource->note = $article->getNote();
            $ressource->estPersonnel = $article->estPersonnel();
            $ressource->emplacement = [
                'logement' => ['id' => $stockage->getLogement()->getId(), 'nom' => $stockage->getLogement()->getNom()],
                'piece' => ['id' => $stockage->getPiece()->getId(), 'nom' => $stockage->getPiece()->getNom()],
                'stockage' => ['id' => $stockage->getId(), 'nom' => $stockage->getNom()],
            ];
            $ressource->creeLe = $article->getCreeLe();
        }

        return $ressource;
    }
}
