<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Stopwatch\Stopwatch;

class BoolCastCommand extends AbstractCastCommand
{
    protected static $defaultName = 'app:bool-cast';
    protected static $defaultDescription = 'Generate timings for casting bools via `(bool)` and `boolval()`';

    protected function getToType(): string
    {
        return 'bool';
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $quantity_raw = (int) $input->getArgument('quantity');
        $quantity = $this->validateQuantity($quantity_raw);

        /** @var string $input_type */
        $input_type = $input->getOption('from-type');

        $type = $this->validateFromType($input_type);

        $io->note(
            sprintf(
                'Creating an array of %s %ss to cast',
                strtolower($this->getLocalizedNumber($quantity)),
                $type
            )
        );

        $iterations = (int)$input->getOption('iterations');
        $progressBar = new ProgressBar($output, $iterations);
        $progressBar->start();

        $items = $this->getItemsFromType($quantity, $type);

        $tmp = null;
        $stopwatch = new Stopwatch(true);

        for ($i = 0; $i < $iterations; $i++) {
            $stopwatch->reset();
            $stopwatch->start('(bool) cast');
            foreach ($items as $item) {
                $tmp = (bool)$item;
            }
            $tradCastEvent = $stopwatch->stop('(bool) cast');

            $stopwatch->reset();
            $stopwatch->start('boolval()');
            foreach ($items as $item) {
                $tmp = (bool) $item;
            }
            $boolvalCastEvent = $stopwatch->stop('boolval()');

            $this->createResults(
                $input,
                $output,
                $boolvalCastEvent,
                $tradCastEvent
            );

            $progressBar->advance();
        }

        $progressBar->finish();

        $io->success('Done');

        return Command::SUCCESS;
    }
}
