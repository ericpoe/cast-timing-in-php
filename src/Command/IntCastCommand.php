<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Stopwatch\Stopwatch;

class IntCastCommand extends AbstractCastCommand
{
    protected static $defaultName = 'app:int-cast';
    protected static $defaultDescription = 'Generate timings for casting integers via `(int)` and `intval()`';

    protected function getToType(): string
    {
        return 'int';
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

        $io->note(
            sprintf(
                'Casting an array of %s %ss to Integer',
                strtolower($this->getLocalizedNumber($quantity)),
                $type
            )
        );

        $tmp = null;
        $stopwatch = new Stopwatch(true);

        for ($i = 0; $i < (int) $input->getOption('iterations'); $i++) {
            $stopwatch->reset();
            $stopwatch->start('(int) cast');
            foreach ($items as $item) {
                $tmp = (int)$item;
            }
            $tradCastEvent = $stopwatch->stop('(int) cast');

            $stopwatch->reset();
            $stopwatch->start('intval()');
            foreach ($items as $item) {
                $tmp = intval($item);
            }
            $intvalCastEvent = $stopwatch->stop('intval()');

            $this->createResults(
                $input,
                $output,
                $intvalCastEvent,
                $tradCastEvent
            );

            $progressBar->advance();
        }

        $progressBar->finish();

        $io->success('Done');

        return Command::SUCCESS;
    }
}
