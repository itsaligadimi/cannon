<?php

namespace App\Tests\Processor;

use App\Entity\User;
use App\Entity\Company;
use App\State\UserStateProcessor;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;
use ApiPlatform\Metadata\Post;

class UserProcessorTest extends TestCase
{
    private $security;
    private $entityManager;
    private $userProcessor;

    protected function setUp(): void
    {
        $this->security = $this->createMock(Security::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);

        $this->userProcessor = new UserStateProcessor($this->security, $this->entityManager);
    }

    public function testCompanyAdminAssignsOwnCompany()
    {
        $companyAdmin = $this->createMock(User::class);
        $company = new Company();
        $companyAdmin->method('getCompany')->willReturn($company);
        
        $this->security->method('isGranted')->willReturnCallback(function($role) {
            return $role === 'ROLE_COMPANY_ADMIN';
        });
        $this->security->method('getUser')->willReturn($companyAdmin);
    
        $this->entityManager->expects($this->once())->method('persist');
        $this->entityManager->expects($this->once())->method('flush');
    
        $newUser = new User();
        $this->userProcessor->process($newUser, new Post()); 
    
        $this->assertSame($company, $newUser->getCompany(), 'Company Admin should assign their own company.');
    }
    

    public function testSuperAdminCanAssignAnyCompany()
    {
        $superAdmin = $this->createMock(UserInterface::class);

        $this->security->method('isGranted')->willReturnCallback(function($role) {
            return $role === 'ROLE_SUPER_ADMIN';
        });
        $this->security->method('getUser')->willReturn($superAdmin);

        $this->entityManager->expects($this->once())->method('persist');
        $this->entityManager->expects($this->once())->method('flush');

        $anyCompany = new Company();
        $newUser = new User();
        $newUser->setCompany($anyCompany);

        $this->userProcessor->process($newUser, new Post());

        $this->assertSame($anyCompany, $newUser->getCompany(), 'Super Admin should be able to assign any company.');
    }
}
