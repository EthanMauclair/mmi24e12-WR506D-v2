<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Lot 1 : comptes, logements et leurs membres, pièces, stockages, catégories, articles.
 * Voir docs/05-modele-donnees.md.
 */
final class Version20261013093044 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Crée les tables du lot 1 : utilisateur, logement, membre, piece, stockage, categorie, article';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE article (id INT AUTO_INCREMENT NOT NULL, marque VARCHAR(80) DEFAULT NULL, couleur VARCHAR(40) DEFAULT NULL, note LONGTEXT DEFAULT NULL, photo VARCHAR(255) NOT NULL, est_personnel TINYINT NOT NULL, cree_le DATETIME NOT NULL, stockage_id INT NOT NULL, auteur_id INT NOT NULL, categorie_id INT NOT NULL, INDEX article_categorie_idx (categorie_id), INDEX IDX_23A0E66DAA83D7F (stockage_id), INDEX IDX_23A0E6660BB6FE6 (auteur_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE categorie (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(60) NOT NULL, UNIQUE INDEX UNIQ_497DD6346C6E55B5 (nom), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE logement (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(80) NOT NULL, cree_le DATETIME NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE membre (id INT AUTO_INCREMENT NOT NULL, role VARCHAR(20) NOT NULL, statut VARCHAR(20) NOT NULL, arrive_le DATETIME DEFAULT NULL, utilisateur_id INT NOT NULL, logement_id INT NOT NULL, UNIQUE INDEX membre_unique (utilisateur_id, logement_id), INDEX IDX_F6B4FB29FB88E14F (utilisateur_id), INDEX IDX_F6B4FB2958ABF955 (logement_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE piece (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(80) NOT NULL, logement_id INT NOT NULL, INDEX IDX_44CA0B2358ABF955 (logement_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE stockage (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(80) NOT NULL, piece_id INT NOT NULL, INDEX IDX_CABCB492C40FCFA8 (piece_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE utilisateur (id INT AUTO_INCREMENT NOT NULL, pseudo VARCHAR(30) NOT NULL, email VARCHAR(180) NOT NULL, mot_de_passe VARCHAR(255) NOT NULL, date_naissance DATE NOT NULL, est_prive TINYINT NOT NULL, cree_le DATETIME NOT NULL, referent_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_1D1C63B386CC499D (pseudo), UNIQUE INDEX UNIQ_1D1C63B3E7927C74 (email), INDEX IDX_1D1C63B335E47E35 (referent_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE article ADD CONSTRAINT FK_23A0E66DAA83D7F FOREIGN KEY (stockage_id) REFERENCES stockage (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE article ADD CONSTRAINT FK_23A0E6660BB6FE6 FOREIGN KEY (auteur_id) REFERENCES utilisateur (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE article ADD CONSTRAINT FK_23A0E66BCF5E72D FOREIGN KEY (categorie_id) REFERENCES categorie (id)');
        $this->addSql('ALTER TABLE membre ADD CONSTRAINT FK_F6B4FB29FB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE membre ADD CONSTRAINT FK_F6B4FB2958ABF955 FOREIGN KEY (logement_id) REFERENCES logement (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE piece ADD CONSTRAINT FK_44CA0B2358ABF955 FOREIGN KEY (logement_id) REFERENCES logement (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE stockage ADD CONSTRAINT FK_CABCB492C40FCFA8 FOREIGN KEY (piece_id) REFERENCES piece (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE utilisateur ADD CONSTRAINT FK_1D1C63B335E47E35 FOREIGN KEY (referent_id) REFERENCES utilisateur (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE article DROP FOREIGN KEY FK_23A0E66DAA83D7F');
        $this->addSql('ALTER TABLE article DROP FOREIGN KEY FK_23A0E6660BB6FE6');
        $this->addSql('ALTER TABLE article DROP FOREIGN KEY FK_23A0E66BCF5E72D');
        $this->addSql('ALTER TABLE membre DROP FOREIGN KEY FK_F6B4FB29FB88E14F');
        $this->addSql('ALTER TABLE membre DROP FOREIGN KEY FK_F6B4FB2958ABF955');
        $this->addSql('ALTER TABLE piece DROP FOREIGN KEY FK_44CA0B2358ABF955');
        $this->addSql('ALTER TABLE stockage DROP FOREIGN KEY FK_CABCB492C40FCFA8');
        $this->addSql('ALTER TABLE utilisateur DROP FOREIGN KEY FK_1D1C63B335E47E35');
        $this->addSql('DROP TABLE article');
        $this->addSql('DROP TABLE categorie');
        $this->addSql('DROP TABLE logement');
        $this->addSql('DROP TABLE membre');
        $this->addSql('DROP TABLE piece');
        $this->addSql('DROP TABLE stockage');
        $this->addSql('DROP TABLE utilisateur');
    }
}
