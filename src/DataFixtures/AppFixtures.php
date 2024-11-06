<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use App\Entity\Company;
use App\Entity\User;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use App\Entity\Role;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $companyA = new Company();
        $companyA->setName('Company A');
        $manager->persist($companyA);

        $companyB = new Company();
        $companyB->setName('Company B');
        $manager->persist($companyB);

    
        $superAdmin = new User();
        $superAdmin->setName('Super Admin');
        $superAdmin->setRole(Role::ROLE_SUPER_ADMIN);
        $superAdmin->setCompany(null);
        $manager->persist($superAdmin);

        $companyAdminA = new User();
        $companyAdminA->setName('Company Admin A');
        $companyAdminA->setRole(Role::ROLE_COMPANY_ADMIN);
        $companyAdminA->setCompany($companyA);
        $manager->persist($companyAdminA);

        $companyAdminB = new User();
        $companyAdminB->setName('Company Admin B');
        $companyAdminB->setRole(Role::ROLE_COMPANY_ADMIN);
        $companyAdminB->setCompany($companyB);
        $manager->persist($companyAdminB);

        for ($i = 1; $i <= 5; $i++) {
            $user = new User();
            $user->setName("User A$i");
            $user->setRole(Role::ROLE_USER);
            $user->setCompany($companyA);
            $manager->persist($user);
        }

        for ($i = 1; $i <= 5; $i++) {
            $user = new User();
            $user->setName("User B$i");
            $user->setRole(Role::ROLE_USER);
            $user->setCompany($companyB);
            $manager->persist($user);
        }

        $manager->flush();
    }
}
