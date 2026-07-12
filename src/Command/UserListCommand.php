<?php
declare(strict_types=1);

namespace App\Command;

use App\Repository\UserRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:user:list',
    description: 'List users of the upcoming admin area'
)]
class UserListCommand extends Command
{
    public function __construct(private readonly UserRepository $users)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('csv', null, InputOption::VALUE_NONE, 'Output CSV instead of a table');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $csv = (bool)$input->getOption('csv');

        $rows = $this->users->findBy([], ['id' => 'ASC']);

        $headers = ['ID', 'Name', 'Email', 'Roles', 'Created'];
        $data = [];
        foreach ($rows as $u) {
            $data[] = [
                $u->getId(),
                $u->getName(),
                $u->getEmail(),
                implode(', ', $u->getRoles()),
                $u->getCreatedAt()->format('Y-m-d H:i'),
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
            $io->title('Users');
            $io->table($headers, $data);
            $io->success(sprintf('Rows %d from %d', count($data), $this->users->count([])));
        }

        return Command::SUCCESS;
    }
}
