<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Security;

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

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
                if ($data->getCompany() === null) {
                    $data->setCompany($currentUser->getCompany());
                }
            
                if ($data->getCompany() !== $currentUser->getCompany()) {
                    throw new AccessDeniedHttpException('You do not have permission to create a user for this company.');
                }
            }
        }

        $this->entityManager->persist($data);
        $this->entityManager->flush();
    }
}
