<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\Company;
use App\Entity\User;
use App\Entity\Role;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class CompanyControllerTest extends ApiTestCase
{
    private $entityManager;
    private $tokenManager;
    private $superAdminToken;

    protected function setUp(): void
    {
        parent::setUp();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->tokenManager = static::getContainer()->get(JWTTokenManagerInterface::class);

        $superAdmin = new User();
        $superAdmin->setName('Super Admin');
        $superAdmin->setRole(Role::ROLE_SUPER_ADMIN);
        $this->entityManager->persist($superAdmin);

        $company = new Company();
        $company->setName('Test Company');
        $this->entityManager->persist($company);

        $this->entityManager->flush();

        $this->superAdminToken = $this->createJwtToken($superAdmin);
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

    public function testSuperAdminCanCreateCompany(): void
    {
        $client = static::createClient();

        $response = $client->request('POST', '/api/companies', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->superAdminToken,
                'Content-Type' => 'application/ld+json',
                'Accept' => 'application/ld+json',
            ],
            'json' => [
                'name' => 'New Company',
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertJsonContains(['name' => 'New Company']);
    }

    public function testCannotCreateDuplicateCompanyName(): void
    {
        $client = static::createClient();

        $response = $client->request('POST', '/api/companies', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->superAdminToken,
                'Content-Type' => 'application/ld+json',
                'Accept' => 'application/ld+json',
            ],
            'json' => [
                'name' => 'Test Company',
            ],
        ]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertJsonContains([
            'violations' => [
                [
                    'propertyPath' => 'name',
                    'message' => 'This value is already used.',
                ],
            ],
        ]);
    }

    public function testSuperAdminCanDeleteCompany(): void
    {
        $client = static::createClient();

        $company = $this->entityManager->getRepository(Company::class)->findOneBy(['name' => 'Test Company']);
        $companyId = $company->getId();

        $client->request('DELETE', '/api/companies/' . $companyId, [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->superAdminToken,
            ],
        ]);

        $this->assertResponseStatusCodeSame(204);
    }

    public function testCompanyAdminCannotDeleteCompany(): void
    {
        $companyAdmin = new User();
        $companyAdmin->setName('Company Admin');
        $companyAdmin->setRole(Role::ROLE_COMPANY_ADMIN);
        $this->entityManager->persist($companyAdmin);
        $this->entityManager->flush();

        $companyAdminToken = $this->createJwtToken($companyAdmin);

        $client = static::createClient();

        $company = $this->entityManager->getRepository(Company::class)->findOneBy(['name' => 'Test Company']);
        $companyId = $company->getId();

        $client->request('DELETE', '/api/companies/' . $companyId, [
            'headers' => [
                'Authorization' => 'Bearer ' . $companyAdminToken,
            ],
        ]);

        $this->assertResponseStatusCodeSame(403);
    }
}
