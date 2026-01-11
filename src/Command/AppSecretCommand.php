<?php
declare(strict_types=1);

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:secret',
    description: 'Regenerate the APP_SECRET in the project env file',
)]
class AppSecretCommand extends Command
{
    // keep the default name aligned with the attribute
    protected static $defaultName = 'app:secret';

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Generate a 32-character hex secret (16 bytes -> 32 hex chars)
        try {
            $secret = bin2hex(random_bytes(16));
        } catch (\Exception $e) {
            $io->error('Failed to generate secret: ' . $e->getMessage());

            return Command::FAILURE;
        }

        // Prefer .env.local when present (local override), otherwise fall back to .env
        $envFile = file_exists('.env.local') ? '.env.local' : '.env';

        if (!file_exists($envFile)) {
            // If no env file exists yet, create it with the APP_SECRET line
            $content = "APP_SECRET={$secret}" . PHP_EOL;
            $written = file_put_contents($envFile, $content, LOCK_EX);

            if (false === $written) {
                $io->error('Failed to write new env file: ' . $envFile);

                return Command::FAILURE;
            }

            $io->success('Created ' . $envFile . ' and set APP_SECRET: ' . $secret);

            return Command::SUCCESS;
        }

        $content = file_get_contents($envFile);

        if (false === $content) {
            $io->error('Failed to read env file: ' . $envFile);

            return Command::FAILURE;
        }

        // Replace an existing APP_SECRET=... line, or append if missing
        if (preg_match('/^APP_SECRET=.*$/m', $content)) {
            $newContent = preg_replace('/^APP_SECRET=.*$/m', 'APP_SECRET=' . $secret, $content);
        } else {
            // append with a newline if file doesn't end with one
            $newContent = rtrim($content, "\r\n") . PHP_EOL . 'APP_SECRET=' . $secret . PHP_EOL;
        }

        $written = file_put_contents($envFile, $newContent, LOCK_EX);

        if (false === $written) {
            $io->error('Failed to write env file: ' . $envFile);

            return Command::FAILURE;
        }

        $io->success('New APP_SECRET was generated and written to ' . $envFile . ': ' . $secret);

        return Command::SUCCESS;
    }
}
