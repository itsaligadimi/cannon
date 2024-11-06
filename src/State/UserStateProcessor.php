<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Security;

use App\Entity\User;

class UserStateProcessor implements ProcessorInterface
{
    private Security $security;
    private EntityManagerInterface $entityManager;

    public function __construct(Security $security, EntityManagerInterface $entityManager)
    {
        $this->security = $security;
        $this->entityManager = $entityManager;
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): void
    {
        if ($data instanceof User) {
            $currentUser = $this->security->getUser();

            if ($this->security->isGranted('ROLE_COMPANY_ADMIN')) {
                $data->setCompany($currentUser->getCompany());
            }
        }

        $this->entityManager->persist($data);
        $this->entityManager->flush();
    }
}
