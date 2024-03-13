<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Stopwatch\Stopwatch;

class FloatCastCommand extends AbstractCastCommand
{
    protected static $defaultName = 'app:float-cast';
    protected static $defaultDescription = 'Generate timings for casting floats via `(float)` and `floatval()`';

    protected function getToType(): string
    {
        return 'float';
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
                'Casting an array of %s %ss to Float',
                strtolower($this->getLocalizedNumber($quantity)),
                $type
            )
        );

        $tmp = null;
        $stopwatch = new Stopwatch(true);

        for ($i = 0; $i < $iterations; $i++) {
            $stopwatch->reset();
            $stopwatch->start('(float) cast');
            foreach ($items as $item) {
                $tmp = (float)$item;
            }
            $tradCastEvent = $stopwatch->stop('(float) cast');

            $stopwatch->reset();
            $stopwatch->start('floatval()');
            foreach ($items as $item) {
                $tmp = floatval($item);
            }
            $floatvalCastEvent = $stopwatch->stop('floatval()');

            $this->createResults(
                $input,
                $output,
                $floatvalCastEvent,
                $tradCastEvent
            );

            $progressBar->advance();
        }

        $progressBar->finish();

        $io->success('Done');

        return Command::SUCCESS;
    }
}
