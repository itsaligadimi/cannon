<?php

namespace App\Tests\Provider;

use ApiPlatform\Metadata\Operation;
use App\Entity\Company;
use App\Entity\User;
use App\State\UserStateProvider;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Security;
use ApiPlatform\Metadata\GetCollection;

class UserStateProviderTest extends TestCase
{
    private $security;
    private $entityManager;
    private $userRepository;
    private $userStateProvider;

    protected function setUp(): void
    {
        $this->security = $this->createMock(Security::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->userRepository = $this->createMock(EntityRepository::class);

        $this->entityManager->method('getRepository')
            ->with(User::class)
            ->willReturn($this->userRepository);

        $this->userStateProvider = new UserStateProvider($this->security, $this->entityManager);
    }

    public function testSuperAdminCanAccessAllUsers()
    {
        $superAdmin = $this->createMock(User::class);
        $this->security->method('isGranted')
            ->willReturnCallback(fn($role) => $role === 'ROLE_SUPER_ADMIN');
        $this->security->method('getUser')
            ->willReturn($superAdmin);

        $allUsers = [new User(), new User(), new User()];
        $this->userRepository->method('findAll')->willReturn($allUsers);

        $users = $this->userStateProvider->provide(new GetCollection(), [], []);
        $this->assertCount(3, $users, 'Super Admin should access all users');
    }

    public function testCompanyAdminCanAccessOnlyOwnCompanyUsers()
    {
        $company = new Company();
        $companyAdmin = $this->createMock(User::class);
        $companyAdmin->method('getCompany')->willReturn($company);

        $this->security->method('isGranted')
            ->willReturnCallback(fn($role) => $role === 'ROLE_COMPANY_ADMIN');
        $this->security->method('getUser')
            ->willReturn($companyAdmin);

        $companyUsers = [new User(), new User()];
        $this->userRepository->method('findBy')->with(['company' => $company])->willReturn($companyUsers);
        var_dump($companyUsers);

        $users = $this->userStateProvider->provide(new GetCollection(), [], []);
        $this->assertCount(2, $users, 'Company Admin should access only users from their own company');
    }
}
