<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\ApiResource\RelationRessource;
use App\Dto\AmiEntree;
use App\Dto\RelationModification;
use App\Entity\Relation;
use App\Entity\Utilisateur;
use App\Exception\RegleMetierException;
use App\Repository\RelationRepository;
use App\Security\AccesLogement;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Clock\ClockInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * - abonnement_creer : je m'abonne (F-15, I-17, I-18) ; abonnement_supprimer : je me désabonne.
 * - ami_ajouter : j'ajoute un ami, ou j'élève au rang d'ami quelqu'un qui me suit déjà (F-17).
 * - relation_modifier : accepter une demande (F-16), rétrograder un ami (F-18).
 * - relation_supprimer : refuser une demande, retirer un abonné ou un ami (F-16, F-18).
 *
 * Seule la personne suivie modifie ou supprime une relation par /relations/{id} (403 pour le suiveur) ;
 * le suiveur, lui, se désabonne par /utilisateurs/{pseudo}/abonnement.
 *
 * Commentaires (D-004) : le choix « garder ou supprimer » est accepté mais sans effet tant que
 * les commentaires (lot 5) n'existent pas.
 *
 * @implements ProcessorInterface<AmiEntree|RelationModification|RelationRessource|null, RelationRessource|null>
 */
final class RelationProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly AccesLogement $acces,
        private readonly RelationProvider $provider,
        private readonly RelationRepository $relations,
        private readonly EntityManagerInterface $em,
        private readonly ClockInterface $horloge,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ?RelationRessource
    {
        $moi = $this->acces->utilisateur();

        $relation = match ($operation->getName()) {
            'abonnement_creer' => $this->abonner($moi, $this->provider->personne((string) $uriVariables['pseudo'])),
            'ami_ajouter' => $this->ajouterAmi($moi, $data),
            'abonnement_supprimer' => $this->supprimer($this->provider->monAbonnement((string) $uriVariables['pseudo'])),
            'relation_supprimer' => $this->supprimer($this->relationRecue($moi, $uriVariables['id'])),
            default => $this->modifier($this->relationRecue($moi, $uriVariables['id']), $data),
        };
        $this->em->flush();

        return null === $relation ? null : RelationRessource::depuis($relation);
    }

    private function abonner(Utilisateur $moi, Utilisateur $suivi): Relation
    {
        if (null !== $this->relations->entre($moi, $suivi)) {
            throw new RegleMetierException('I-17', 'Vous suivez déjà cette personne, ou votre demande est en attente.');
        }
        $relation = Relation::abonner($moi, $suivi, $this->horloge->now());
        $this->em->persist($relation);

        return $relation;
    }

    private function ajouterAmi(Utilisateur $moi, mixed $data): Relation
    {
        \assert($data instanceof AmiEntree);
        $ami = $this->provider->personne($data->pseudo);

        $relation = $this->relations->entre($ami, $moi);
        if (null !== $relation) {
            $relation->devenirAmi();

            return $relation;
        }
        $relation = Relation::ajouterAmi($moi, $ami, $this->horloge->now());
        $this->em->persist($relation);

        return $relation;
    }

    private function modifier(Relation $relation, mixed $data): Relation
    {
        \assert($data instanceof RelationModification);

        if ($data->contient('statut') && null !== $data->statut) {
            $relation->accepter();
        }
        if ($data->contient('niveau') && 'AMI' === $data->niveau && !$relation->estAmi()) {
            $relation->devenirAmi();
        }
        if ($data->contient('niveau') && 'ABONNE' === $data->niveau) {
            $relation->retrograder();
        }

        return $relation;
    }

    private function supprimer(Relation $relation): null
    {
        $this->em->remove($relation);

        return null;
    }

    /** Une relation dont je suis la personne suivie : 404 si je ne la vois pas, 403 si j'en suis le suiveur. */
    private function relationRecue(Utilisateur $moi, mixed $id): Relation
    {
        $relation = $this->provider->relationVisible($id);
        if ($relation->getSuivi() !== $moi) {
            throw new AccessDeniedHttpException('Seule la personne suivie gère cette relation ; pour vous désabonner, utilisez /api/utilisateurs/{pseudo}/abonnement.');
        }

        return $relation;
    }
}
