<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;

class TokenController
{
    private $entityManager;
    private $tokenManager;

    public function __construct(EntityManagerInterface $entityManager, JWTTokenManagerInterface $tokenManager)
    {
        $this->entityManager = $entityManager;
        $this->tokenManager = $tokenManager;
    }

    /**
     * @Route("/test/token", name="api_test_token", methods={"GET"})
     */
    public function generateToken(Request $request): JsonResponse
    {
        $userId = $request->query->get('userId');
        
        if (!$userId) {
            return new JsonResponse(['error' => 'User ID is required'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $user = $this->entityManager->getRepository(User::class)->find($userId);

        if (!$user) {
            throw new UserNotFoundException('User not found');
        }

    
        $token = $this->tokenManager->create($user);

        return new JsonResponse(['token' => $token]);
    }
}
