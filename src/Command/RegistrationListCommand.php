<?php
declare(strict_types=1);

namespace App\Command;

use App\Repository\FormRegistrationRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:registration:list',
    description: 'List stored trial-session registrations'
)]
class RegistrationListCommand extends Command
{
    public function __construct(private readonly FormRegistrationRepository $registrations)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('limit', null, InputOption::VALUE_REQUIRED, 'Limit number of rows', '100')
            ->addOption('csv', null, InputOption::VALUE_NONE, 'Output CSV instead of a table');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $limit = (int)($input->getOption('limit') ?? 100);
        $csv = (bool)$input->getOption('csv');

        $rows = $this->registrations->createQueryBuilder('r')
            ->orderBy('r.createdAt', 'DESC')
            ->setMaxResults(max(1, $limit))
            ->getQuery()
            ->getResult();

        $headers = ['ID', 'Created At', 'Name', 'Email', 'Phone', 'Roles', 'Consent', 'Copy'];
        $data = [];
        foreach ($rows as $r) {
            $data[] = [
                $r->getId(),
                $r->getCreatedAt()->format('Y-m-d H:i:s'),
                $r->getName(),
                $r->getEmailAddress(),
                $r->getPhone(),
                implode(', ', $r->getRoleTypes()),
                $r->getConsent() ? 'yes' : 'no',
                $r->getCopy() ? 'yes' : 'no',
            ];
        }

        if ($csv) {
            $out = fopen('php://temp', 'r+');
            fputcsv($out, $headers, ',', '"', '\\');
            foreach ($data as $row) {
                fputcsv($out, $row, ',', '"', '\\');
            }
            rewind($out);
            $output->write(stream_get_contents($out));
            fclose($out);
        } else {
            $io->title('Trial-Session Registrations');
            $io->table($headers, $data);
            $io->success(sprintf('Rows %d from %d', count($data), $this->registrations->count([])));
        }

        return Command::SUCCESS;
    }
}
