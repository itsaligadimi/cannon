<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;


#[ApiResource(
    security: "is_granted('ROLE_USER')",
    operations: [
        new GetCollection(security: "is_granted('ROLE_SUPER_ADMIN') or object.getCompany() == user.getCompany()"),
        new Get(security: "is_granted('ROLE_SUPER_ADMIN') or object.getCompany() == user.getCompany()"),
        new Post(security: "is_granted('ROLE_COMPANY_ADMIN') or is_granted('ROLE_SUPER_ADMIN')"),
        new Put(security: "is_granted('ROLE_SUPER_ADMIN') or (is_granted('ROLE_COMPANY_ADMIN') and object.getCompany() == user.getCompany())"),
        new Delete(security: "is_granted('ROLE_SUPER_ADMIN')")
    ]
)]
#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
class User
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
}
