<?php
declare(strict_types=1);

namespace App\Command;

use App\Repository\DateRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:date:list',
    description: 'List public dates shown on /termine'
)]
class DateListCommand extends Command
{
    public function __construct(private readonly DateRepository $dates)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('upcoming', null, InputOption::VALUE_NONE, 'Only active upcoming dates (the website view)')
            ->addOption('csv', null, InputOption::VALUE_NONE, 'Output CSV instead of a table');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $csv = (bool)$input->getOption('csv');

        $rows = true === $input->getOption('upcoming')
            ? $this->dates->findUpcoming()
            : $this->dates->findBy([], ['sortOrder' => 'ASC', 'startsAt' => 'ASC']);

        $headers = ['ID', 'Title', 'When', 'Recurring', 'Active', 'Sort'];
        $data = [];
        foreach ($rows as $d) {
            $data[] = [
                $d->getId(),
                $d->getTitle(),
                $d->formatGerman(),
                null !== $d->getRecurrence() ? 'yes' : 'no',
                $d->isActive() ? 'yes' : 'no',
                $d->getSortOrder(),
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
            $io->title('Public Dates');
            $io->table($headers, $data);
            $io->success(sprintf('Rows %d from %d', count($data), $this->dates->count([])));
        }

        return Command::SUCCESS;
    }
}
