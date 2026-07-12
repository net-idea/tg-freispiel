<?php
declare(strict_types=1);

namespace App\Command;

use App\Entity\DateEntity;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:date:create',
    description: 'Create a public date for /termine (one-off via --starts-at or recurring via --recurrence)'
)]
class DateCreateCommand extends Command
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('title', InputArgument::REQUIRED, 'Title, e.g. "Probestunde zum Reinschnuppern" (asked interactively when omitted)')
            ->addOption('starts-at', null, InputOption::VALUE_REQUIRED, 'Start of a one-off date, e.g. "2026-09-12 10:30"')
            ->addOption('recurrence', null, InputOption::VALUE_REQUIRED, 'Schedule of a recurring date, e.g. "jeden Dienstag um 19 Uhr"')
            ->addOption('description', null, InputOption::VALUE_REQUIRED, 'Optional description shown on the website')
            ->addOption('sort', null, InputOption::VALUE_REQUIRED, 'Sort order (lower first)', '0')
            ->addOption('inactive', null, InputOption::VALUE_NONE, 'Create the date as inactive (hidden on the website)');
    }

    protected function interact(InputInterface $input, OutputInterface $output): void
    {
        if (null !== $input->getArgument('title')) {
            return;
        }

        $io = new SymfonyStyle($input, $output);

        $input->setArgument('title', $io->ask('Title', null, static function (?string $value): string {
            if (null === $value || '' === trim($value)) {
                throw new \InvalidArgumentException('The title must not be empty.');
            }

            return $value;
        }));

        if (null === $input->getOption('starts-at') && null === $input->getOption('recurrence')) {
            $type = $io->choice('Date type', ['one-off', 'recurring'], 'one-off');

            if ('one-off' === $type) {
                $input->setOption('starts-at', $io->ask('Start, e.g. "2026-09-12 10:30"', null, static function (?string $value): string {
                    try {
                        if (null === $value) {
                            throw new \Exception();
                        }
                        new \DateTimeImmutable($value);
                    } catch (\Exception) {
                        throw new \InvalidArgumentException('Please enter a valid date/time, e.g. "2026-09-12 10:30".');
                    }

                    return $value;
                }));
            } else {
                $input->setOption('recurrence', $io->ask('Schedule, e.g. "jeden Dienstag um 19 Uhr"', null, static function (?string $value): string {
                    if (null === $value || '' === trim($value)) {
                        throw new \InvalidArgumentException('The schedule must not be empty.');
                    }

                    return $value;
                }));
            }
        }

        if (null === $input->getOption('description')) {
            $description = $io->ask('Description (optional)');
            if (\is_string($description) && '' !== trim($description)) {
                $input->setOption('description', $description);
            }
        }

        $input->setOption('sort', $io->ask('Sort order (lower first)', '0', static function (?string $value): string {
            if (null === $value || 1 !== preg_match('/^-?\d+$/', $value)) {
                throw new \InvalidArgumentException('Please enter a whole number.');
            }

            return $value;
        }));

        if (false === $input->getOption('inactive') && !$io->confirm('Active (visible on the website)?', true)) {
            $input->setOption('inactive', true);
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $startsAt = $input->getOption('starts-at');
        $recurrence = $input->getOption('recurrence');

        if ((null === $startsAt) === (null === $recurrence)) {
            $io->error('Pass exactly one of --starts-at (one-off) or --recurrence (recurring).');

            return Command::FAILURE;
        }

        $date = (new DateEntity())
            ->setTitle((string) $input->getArgument('title'))
            ->setDescription($input->getOption('description'))
            ->setRecurrence($recurrence)
            ->setSortOrder((int) $input->getOption('sort'))
            ->setActive(true !== $input->getOption('inactive'));

        if (null !== $startsAt) {
            try {
                $date->setStartsAt(new \DateTimeImmutable($startsAt));
            } catch (\Exception) {
                $io->error(sprintf('Invalid --starts-at value "%s". Use e.g. "2026-09-12 10:30".', $startsAt));

                return Command::FAILURE;
            }
        }

        $this->em->persist($date);
        $this->em->flush();

        $io->success(sprintf('Date #%d "%s" created: %s', $date->getId(), $date->getTitle(), $date->formatGerman()));

        return Command::SUCCESS;
    }
}
