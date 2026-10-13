<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Categorie;
use App\Repository\CategorieRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Seul l'administrateur étend la liste fixe des catégories (D-013), en ligne de commande :
 * aucune route de l'API ne la modifie (docs/08-contrat-api.md).
 */
#[AsCommand(name: 'app:categorie:ajouter', description: 'Ajoute une catégorie à la liste fixe')]
final class AjouterCategorieCommand
{
    private const LONGUEUR_MAX = 60;

    public function __construct(
        private readonly CategorieRepository $categories,
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function __invoke(
        SymfonyStyle $io,
        #[Argument(description: 'Nom de la catégorie, au singulier (ex. : « Casquette »)')] string $nom,
    ): int {
        $nom = trim($nom);
        if ('' === $nom || mb_strlen($nom) > self::LONGUEUR_MAX) {
            $io->error(\sprintf('Le nom doit contenir entre 1 et %d caractères.', self::LONGUEUR_MAX));

            return Command::INVALID;
        }
        $nom = mb_strtoupper(mb_substr($nom, 0, 1)).mb_substr($nom, 1);

        $existante = $this->categories->nommee($nom);
        if (null !== $existante) {
            $io->error(\sprintf('La catégorie « %s » existe déjà.', $existante->getNom()));

            return Command::FAILURE;
        }

        $this->em->persist(new Categorie($nom));
        $this->em->flush();
        $io->success(\sprintf('Catégorie « %s » ajoutée.', $nom));

        return Command::SUCCESS;
    }
}
