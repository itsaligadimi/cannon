<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Security;

use App\Entity\User;

class UserStateProvider implements ProviderInterface
{
    private $security;
    private $entityManager;

    public function __construct(Security $security, EntityManagerInterface $entityManager)
    {
        $this->security = $security;
        $this->entityManager = $entityManager;
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $user = $this->security->getUser();

        if ($this->security->isGranted('ROLE_SUPER_ADMIN')) {
            return $this->entityManager->getRepository(User::class)->findAll();
        }

        if ($this->security->isGranted('ROLE_COMPANY_ADMIN') || $this->security->isGranted('ROLE_USER')) {
            $company = $user->getCompany();

            return $this->entityManager->getRepository(User::class)
                ->findBy(['company' => $company]);
        }

        throw new \Exception("Access denied");
    }
}
