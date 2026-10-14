<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Lot 3 : relations entre comptes (abonnés et amis, F-15 à F-18).
 * Une seule relation par couple (I-17) ; supprimée avec l'un des deux comptes.
 */
final class Version20261014120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Crée la table relation (abonnés et amis)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE relation (id INT AUTO_INCREMENT NOT NULL, niveau VARCHAR(20) NOT NULL, statut VARCHAR(20) NOT NULL, cree_le DATETIME NOT NULL, suiveur_id INT NOT NULL, suivi_id INT NOT NULL, UNIQUE INDEX relation_unique (suiveur_id, suivi_id), INDEX IDX_6289474935E10B95 (suiveur_id), INDEX IDX_628947497FEA59C0 (suivi_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE relation ADD CONSTRAINT FK_6289474935E10B95 FOREIGN KEY (suiveur_id) REFERENCES utilisateur (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE relation ADD CONSTRAINT FK_628947497FEA59C0 FOREIGN KEY (suivi_id) REFERENCES utilisateur (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE relation DROP FOREIGN KEY FK_6289474935E10B95');
        $this->addSql('ALTER TABLE relation DROP FOREIGN KEY FK_628947497FEA59C0');
        $this->addSql('DROP TABLE relation');
    }
}
