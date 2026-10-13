<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Liste fixe de catégories (D-013, D-021). L'administrateur l'étend ensuite avec
 * la commande app:categorie:ajouter ; aucune route publique ne la modifie.
 */
final class Version20261013131000 extends AbstractMigration
{
    /** Vêtements, chaussures, accessoires, objets, puis « Autre » quand rien ne convient. */
    private const CATEGORIES = [
        'T-shirt', 'Chemise', 'Pull', 'Sweat', 'Veste', 'Manteau', 'Pantalon', 'Jean', 'Short', 'Jupe',
        'Robe', 'Pyjama', 'Sous-vêtement', 'Maillot de bain',
        'Baskets', 'Bottines', 'Bottes', 'Sandales', 'Chaussures de ville',
        'Sac', 'Ceinture', 'Écharpe', 'Chapeau', 'Bonnet', 'Bijou', 'Montre', 'Lunettes',
        'Vinyle', 'Livre', 'Jeu vidéo',
        'Autre',
    ];

    public function getDescription(): string
    {
        return 'Remplit la liste fixe des catégories';
    }

    public function up(Schema $schema): void
    {
        foreach (self::CATEGORIES as $nom) {
            $this->addSql('INSERT INTO categorie (nom) VALUES (?)', [$nom]);
        }
    }

    public function down(Schema $schema): void
    {
        foreach (self::CATEGORIES as $nom) {
            $this->addSql('DELETE FROM categorie WHERE nom = ?', [$nom]);
        }
    }
}
