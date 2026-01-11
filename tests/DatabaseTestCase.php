<?php

declare(strict_types=1);

namespace App\Tests;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Base test case for tests that require database access.
 * Automatically creates the database schema for each test.
 *
 * For integration tests using createClient(), the schema is created automatically
 * after the first client is created. The schema is dropped after each test.
 */
abstract class DatabaseTestCase extends WebTestCase
{
    protected ?EntityManagerInterface $entityManager = null;

    protected function tearDown(): void
    {
        if (null !== $this->entityManager) {
            $this->entityManager->close();
            $this->entityManager = null;
        }

        parent::tearDown();
    }

    /**
     * Boot kernel and setup database schema + fixtures.
     * Ensure schema is created on every boot, which is necessary for :memory: SQLite.
     */
    protected static function bootKernel(array $options = []): \Symfony\Component\HttpKernel\KernelInterface
    {
        $kernel = parent::bootKernel($options);

        self::setUpDatabaseSchema();

        return $kernel;
    }

    /**
     * Get the entity manager
     */
    protected function getEntityManager(): EntityManagerInterface
    {
        if (null === $this->entityManager) {
            if (null === static::$kernel) {
                static::bootKernel();
            }

            $this->entityManager = self::getContainer()->get('doctrine')->getManager();
        }

        return $this->entityManager;
    }

    /**
     * Override this method to load test data
     */
    protected static function loadFixtures(EntityManagerInterface $entityManager): void
    {
        // Implement in subclasses or add default fixtures here
    }

    /**
     * Set up the database schema and load fixtures
     */
    private static function setUpDatabaseSchema(): void
    {
        $container = self::getContainer();

        // Skip if doctrine is not available (e.g. unit tests abusing this class)
        if (!$container->has('doctrine')) {
            return;
        }

        /** @var EntityManagerInterface $entityManager */
        $entityManager = $container->get('doctrine')->getManager();

        $schemaTool = new SchemaTool($entityManager);
        $metadata = $entityManager->getMetadataFactory()->getAllMetadata();

        // Create schema
        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);

        // Load mocks/fixtures
        static::loadFixtures($entityManager);
    }
}
