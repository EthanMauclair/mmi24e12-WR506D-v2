<?php

declare(strict_types=1);

namespace App\Entity;

use App\Exception\RegleMetierException;
use App\Repository\UtilisateurRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Un compte. Majeur, ou mineur rattaché à un compte majeur (son référent).
 *
 * Invariants garantis ici : I-01, I-02, I-21.
 * I-03 (suppression en cascade des mineurs) est garanti par la clé étrangère `referent_id` (ON DELETE CASCADE).
 */
#[ORM\Entity(repositoryClass: UtilisateurRepository::class)]
#[UniqueEntity(fields: ['pseudo'], message: 'Ce pseudo est déjà pris.')]
#[UniqueEntity(fields: ['email'], message: 'Cet e-mail est déjà utilisé.')]
class Utilisateur implements UserInterface, PasswordAuthenticatedUserInterface
{
    public const AGE_MAJORITE = 18;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 30, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 3, max: 30)]
    #[Assert\Regex(pattern: '/^[a-z0-9_.]+$/', message: 'Lettres minuscules, chiffres, « _ » et « . » uniquement.')]
    private string $pseudo;

    #[ORM\Column(length: 180, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Email]
    private string $email;

    /** Mot de passe haché, jamais en clair. */
    #[ORM\Column]
    private string $motDePasse = '';

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private \DateTimeImmutable $dateNaissance;

    #[ORM\Column]
    private bool $estPrive;

    /** Compte majeur auquel un mineur est rattaché. Null pour un majeur (I-01). */
    #[ORM\ManyToOne(targetEntity: self::class)]
    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    private ?Utilisateur $referent;

    #[ORM\Column]
    private \DateTimeImmutable $creeLe;

    private function __construct(
        string $pseudo,
        string $email,
        \DateTimeImmutable $dateNaissance,
        bool $estPrive,
        ?Utilisateur $referent,
        \DateTimeImmutable $maintenant,
    ) {
        $this->pseudo = $pseudo;
        $this->email = mb_strtolower($email);
        $this->dateNaissance = $dateNaissance;
        $this->estPrive = $estPrive;
        $this->referent = $referent;
        $this->creeLe = $maintenant;
    }

    /** Inscription classique (F-01) : réservée aux personnes majeures. */
    public static function creerMajeur(
        string $pseudo,
        string $email,
        \DateTimeImmutable $dateNaissance,
        bool $estPrive,
        \DateTimeImmutable $maintenant,
    ): self {
        if (self::ageA($dateNaissance, $maintenant) < self::AGE_MAJORITE) {
            throw new RegleMetierException('I-01', 'Un compte mineur doit être créé par un compte majeur.');
        }

        return new self($pseudo, $email, $dateNaissance, $estPrive, null, $maintenant);
    }

    /** Création d'un compte mineur par son référent (F-12). Toujours privé (I-21). */
    public static function creerMineur(
        self $referent,
        string $pseudo,
        string $email,
        \DateTimeImmutable $dateNaissance,
        \DateTimeImmutable $maintenant,
    ): self {
        if ($referent->estMineur($maintenant)) {
            throw new RegleMetierException('I-01', 'Le référent d\'un compte mineur doit être majeur.');
        }
        if (self::ageA($dateNaissance, $maintenant) >= self::AGE_MAJORITE) {
            throw new RegleMetierException('I-01', 'Une personne majeure crée son propre compte.');
        }

        return new self($pseudo, $email, $dateNaissance, true, $referent, $maintenant);
    }

    public function estMineur(\DateTimeImmutable $aujourdhui): bool
    {
        return self::ageA($this->dateNaissance, $aujourdhui) < self::AGE_MAJORITE;
    }

    /** F-02. Un compte mineur ne peut pas devenir public (I-21). */
    public function definirPrive(bool $estPrive, \DateTimeImmutable $aujourdhui): void
    {
        if (!$estPrive && $this->estMineur($aujourdhui)) {
            throw new RegleMetierException('I-21', 'Un compte mineur reste privé jusqu\'à ses 18 ans.');
        }
        $this->estPrive = $estPrive;
    }

    /**
     * I-02 : à 18 ans, le lien avec le référent disparaît et le compte devient indépendant.
     * Appelé chaque jour par une tâche planifiée (F-13).
     *
     * @return bool vrai si le compte vient de devenir indépendant
     */
    public function devenirIndependantSiMajeur(\DateTimeImmutable $aujourdhui): bool
    {
        if (null === $this->referent || $this->estMineur($aujourdhui)) {
            return false;
        }
        $this->referent = null;

        return true;
    }

    public function changerEmail(string $email): void
    {
        $this->email = mb_strtolower($email);
    }

    public function definirMotDePasseHache(string $motDePasseHache): void
    {
        $this->motDePasse = $motDePasseHache;
    }

    private static function ageA(\DateTimeImmutable $dateNaissance, \DateTimeImmutable $aujourdhui): int
    {
        return $dateNaissance->diff($aujourdhui)->y;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPseudo(): string
    {
        return $this->pseudo;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getDateNaissance(): \DateTimeImmutable
    {
        return $this->dateNaissance;
    }

    public function estPrive(): bool
    {
        return $this->estPrive;
    }

    public function getReferent(): ?self
    {
        return $this->referent;
    }

    public function getCreeLe(): \DateTimeImmutable
    {
        return $this->creeLe;
    }

    // ── Sécurité Symfony ───────────────────────────────────────────

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    /** @return list<string> */
    public function getRoles(): array
    {
        return ['ROLE_USER'];
    }

    public function getPassword(): string
    {
        return $this->motDePasse;
    }

    /** Aucune donnée sensible en clair n'est stockée dans l'objet. Méthode dépréciée par Symfony 7.3. */
    #[\Deprecated]
    public function eraseCredentials(): void
    {
    }
}
