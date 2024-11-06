<?php

namespace App\Tests\Controller;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\Company;
use App\Entity\User;
use App\Entity\Role;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserControllerTest extends ApiTestCase
{
    private $entityManager;
    private $tokenManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->tokenManager = static::getContainer()->get(JWTTokenManagerInterface::class);

        $company = new Company();
        $company->setName('Test Company');
        $this->entityManager->persist($company);

        $superAdmin = new User();
        $superAdmin->setName('Super Admin');
        $superAdmin->setRole(Role::ROLE_SUPER_ADMIN);
        $this->entityManager->persist($superAdmin);

        $companyAdmin = new User();
        $companyAdmin->setName('Company Admin');
        $companyAdmin->setRole(Role::ROLE_COMPANY_ADMIN);
        $companyAdmin->setCompany($company);
        $this->entityManager->persist($companyAdmin);

        $this->entityManager->flush();
    }

    protected function tearDown(): void
    {
        $this->entityManager->createQuery('DELETE FROM App\Entity\User')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\Company')->execute();

        $this->entityManager->close();
        parent::tearDown();
    }

    private function createJwtToken(UserInterface $user): string
    {
        return $this->tokenManager->create($user);
    }

    public function testSuperAdminCanCreateUserForAnyCompany(): void
    {
        $superAdmin = $this->entityManager->getRepository(User::class)
            ->findOneBy(['name' => 'Super Admin']);

        $token = $this->createJwtToken($superAdmin);

        $newCompany = new Company();
        $newCompany->setName('New Company');
        $this->entityManager->persist($newCompany);
        $this->entityManager->flush();

        $client = static::createClient();
        $client->request('POST', '/api/users', [
            'headers' => $this->makeHeader($token),
            'json' => [
                'name' => 'New User',
                'role' => 'ROLE_USER',
                'company' => '/api/companies/' . $newCompany->getId(),
            ],
        ]);

        $responseContent = $client->getResponse()->getContent();

        $this->assertResponseStatusCodeSame(201);
    }

    public function testCompanyAdminCanOnlyCreateUserForOwnCompany(): void
    {
        $companyAdmin = $this->entityManager->getRepository(User::class)
            ->findOneBy(['name' => 'Company Admin']);

        $token = $this->createJwtToken($companyAdmin);

        $client = static::createClient();
        $client->request('POST', '/api/users', [
            'headers' => $this->makeHeader($token),
            'json' => [
                'name' => 'User in Own Company',
                'role' => 'ROLE_USER',
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);

        $anotherCompany = new Company();
        $anotherCompany->setName('Another Company');
        $this->entityManager->persist($anotherCompany);
        $this->entityManager->flush();

        $client->request('POST', '/api/users', [
            'headers' => $this->makeHeader($token),
            'json' => [
                'name' => 'User in Different Company',
                'role' => 'ROLE_USER',
                'company' => '/api/companies/' . $anotherCompany->getId(),
            ],
        ]);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testValidationFailsForInvalidNameLength(): void
    {
        $superAdmin = $this->entityManager->getRepository(User::class)
            ->findOneBy(['name' => 'Super Admin']);

        $token = $this->createJwtToken($superAdmin);

        $client = static::createClient();

        $client->request('POST', '/api/users', [
            'headers' => $this->makeHeader($token),
            'json' => [
                'name' => 'Al',
                'role' => 'ROLE_USER',
            ],
        ]);

        $this->assertResponseStatusCodeSame(422);
    }

    private function makeHeader($token){
        return [
            'Authorization' => 'Bearer ' . $token,
            'Content-Type' => 'application/ld+json',
            'Accept' => 'application/ld+json',
        ];
    }
}
