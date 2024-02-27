<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Stopwatch\Stopwatch;

class FloatCastCommand extends AbstractCastCommand
{
    protected static $defaultName = 'app:float-cast';
    protected static $defaultDescription = 'Generate timings for casting floats via `(float)` and `floatval()`';

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $quantity = (int) $input->getArgument('quantity');

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
        $items = $this->getItemsFromType($quantity, $type);

        $tmp = null;
        $stopwatch = new Stopwatch(true);

        for ($i = 0; $i < (int) $input->getOption('iterations'); $i++) {
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
        }

        $io->success('Done');

        return Command::SUCCESS;
    }
}
