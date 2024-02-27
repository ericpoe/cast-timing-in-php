<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Stopwatch\Stopwatch;

class StringCastCommand extends AbstractCastCommand
{
    protected static $defaultName = 'app:string-cast';
    protected static $defaultDescription = 'Generate timings for casting strings via `(string)` and `strval()`';

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $quantity = (int)$input->getArgument('quantity');

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

        for ($i = 0; $i < (int)$input->getOption('iterations'); $i++) {
            $stopwatch->reset();
            $stopwatch->start('(string) cast');
            foreach ($items as $item) {
                $tmp = (string)$item;
            }
            $tradCastEvent = $stopwatch->stop('(string) cast');

            $stopwatch->reset();
            $stopwatch->start('strval()');
            foreach ($items as $item) {
                $tmp = strval($item);
            }
            $strvalCastEvent = $stopwatch->stop('strval()');

            $this->createResults(
                $input,
                $output,
                $strvalCastEvent,
                $tradCastEvent
            );
        }

        $io->success('Done');

        return Command::SUCCESS;
    }
}
