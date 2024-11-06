<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Delete;
use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

use App\State\UserStateProvider;
use App\State\UserStateProcessor;


#[ApiResource(
    security: "is_granted('IS_AUTHENTICATED_FULLY')",
    operations: [
        new GetCollection(
            provider: UserStateProvider::class
        ),
        new Get(security: "is_granted('ROLE_SUPER_ADMIN') or object.getCompany() == user.getCompany()"),
        new Post(
            security: "is_granted('ROLE_COMPANY_ADMIN') or is_granted('ROLE_SUPER_ADMIN')",
            processor: UserStateProcessor::class
        ),
        new Put(security: "is_granted('ROLE_SUPER_ADMIN') or (is_granted('ROLE_COMPANY_ADMIN') and object.getCompany() == user.getCompany())"),
        new Delete(security: "is_granted('ROLE_SUPER_ADMIN')")
    ]
)]
#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
class User implements UserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[Assert\NotBlank]
    #[Assert\Length(min: 3, max: 100)]
    #[Assert\Regex(pattern: '/^[A-Za-z\s]+$/', message: 'Only letters and spaces allowed.')]
    #[Assert\Regex(pattern: '/[A-Z]/', message: 'At least one uppercase letter required.')]
    #[ORM\Column(length: 100)]
    private ?string $name = null;

    #[ORM\Column(enumType: Role::class)]
    private ?Role $role = Role::ROLE_USER;   

    #[ORM\ManyToOne]
    private ?Company $company = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getRole(): ?Role
    {
        return $this->role;
    }

    public function setRole(Role $role): static
    {
        $this->role = $role;

        return $this;
    }

    public function getCompany(): ?Company
    {
        return $this->company;
    }

    public function setCompany(?Company $company): static
    {
        $this->company = $company;

        return $this;
    }

    public function getRoles(): array {
        return array($this->role->value);
    }

    public function eraseCredentials(): void {}

    public function getUserIdentifier(): string {
        return $this->name;
    }
}
