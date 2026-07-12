<?php

declare(strict_types=1);

namespace App\Tests\Command;

use App\Entity\UserEntity;
use App\Tests\DatabaseTestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class UserCreateCommandTest extends DatabaseTestCase
{
    public function testCreatesUserWithHashedPassword(): void
    {
        self::bootKernel();

        $tester = $this->makeTester();
        $tester->execute([
            'email'      => 'admin@example.com',
            'name'       => 'Admin',
            '--password' => 's3cret-Pass!',
            '--admin'    => true,
        ]);

        $tester->assertCommandIsSuccessful();

        /** @var UserEntity[] $users */
        $users = $this->getEntityManager()->getRepository(UserEntity::class)->findAll();
        $this->assertCount(1, $users);
        $this->assertSame('admin@example.com', $users[0]->getEmail());
        $this->assertContains('ROLE_ADMIN', $users[0]->getRoles());
        $this->assertNotSame('s3cret-Pass!', $users[0]->getPassword(), 'password must be stored hashed');
        $this->assertNotEmpty($users[0]->getPassword());
    }

    public function testFailsForDuplicateEmail(): void
    {
        self::bootKernel();

        $tester = $this->makeTester();
        $tester->execute(['email' => 'admin@example.com', 'name' => 'Admin', '--password' => 'pass-1234']);
        $exitCode = $tester->execute(['email' => 'admin@example.com', 'name' => 'Twin', '--password' => 'pass-1234']);

        $this->assertNotSame(0, $exitCode);
        $this->assertCount(1, $this->getEntityManager()->getRepository(UserEntity::class)->findAll());
    }

    public function testFailsForInvalidEmail(): void
    {
        self::bootKernel();

        $tester = $this->makeTester();
        $exitCode = $tester->execute(['email' => 'not-an-email', 'name' => 'Broken', '--password' => 'pass-1234']);

        $this->assertNotSame(0, $exitCode);
        $this->assertCount(0, $this->getEntityManager()->getRepository(UserEntity::class)->findAll());
    }

    public function testFailsForShortPassword(): void
    {
        self::bootKernel();

        $tester = $this->makeTester();
        $exitCode = $tester->execute(['email' => 'short@example.com', 'name' => 'Short', '--password' => 'abc']);

        $this->assertNotSame(0, $exitCode);
        $this->assertCount(0, $this->getEntityManager()->getRepository(UserEntity::class)->findAll());
    }

    private function makeTester(): CommandTester
    {
        $application = new Application(self::$kernel);

        return new CommandTester($application->find('app:user:create'));
    }
}
