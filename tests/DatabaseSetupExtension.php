<?php

declare(strict_types=1);

namespace App\Tests;

use PHPUnit\Runner\BeforeFirstTestHook;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

/**
 * PHPUnit extension that sets up the test database before running tests.
 */
final class DatabaseSetupExtension implements BeforeFirstTestHook
{
    public function executeBeforeFirstTest(): void
    {
        if ('test' !== ($_ENV['APP_ENV'] ?? $_SERVER['APP_ENV'] ?? null)) {
            return;
        }

        $kernel = new \App\Kernel('test', true);
        $kernel->boot();

        $application = new Application($kernel);
        $application->setAutoExit(false);
        $output = new NullOutput();

        // Drop and recreate database schema
        $application->run(new ArrayInput([
            'command'     => 'doctrine:database:drop',
            '--if-exists' => true,
            '--force'     => true,
        ]), $output);

        $application->run(new ArrayInput([
            'command' => 'doctrine:database:create',
        ]), $output);

        $application->run(new ArrayInput([
            'command' => 'doctrine:schema:create',
        ]), $output);

        $kernel->shutdown();
    }
}
