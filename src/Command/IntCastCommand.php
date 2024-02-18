<?php

namespace App\Command;

use League\Csv\Reader;
use League\Csv\Writer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Component\Stopwatch\StopwatchEvent;

class IntCastCommand extends Command
{
    protected static $defaultName = 'app:int-cast';
    protected static $defaultDescription = 'Generate timings for casting integers via `(int)` and `intval()`';

    protected function configure(): void
    {
        $this->addArgument('quantity', InputArgument::OPTIONAL, 'Amount of items to cast', '10000');
        $this->addOption('iterations', 'i', InputOption::VALUE_OPTIONAL, 'How many times to run this command', '1');
        $this->addOption('csv-path', 'p', InputOption::VALUE_OPTIONAL, 'Path to CSV file for writing results');
        $this->addOption('from-type', 't', InputOption::VALUE_OPTIONAL, 'Cast from "int", "float", or "string"', 'int');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $quantity = (int) $input->getArgument('quantity');

        $type = strtolower($input->getOption('from-type'));
        if (!in_array($type, ['float', 'int', 'string'])) {
            $type = 'int';
        }

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
        }

        $io->success('Done');

        return Command::SUCCESS;
    }

    protected function createResults(
        InputInterface $input,
        OutputInterface $output,
        StopwatchEvent $intvalCastEvent,
        StopwatchEvent $tradCastEvent
    ): void {
        if ($input->getOption('csv-path')) {
            $this->writeToCsv($input, $intvalCastEvent, $tradCastEvent);
        } else {
            $this->writeToScreen($output, $intvalCastEvent, $tradCastEvent);
        }
    }

    public function getItemsFromType(int $quantity, string $type = 'int'): array
    {
        $type = strtolower($type);

        if ($type === 'float') {
            return array_map(function ($value) {
                return (float) $value;
            },
                range(0.1, $quantity + 0.1));
        }

        if ($type === 'string') {
            return  array_map(function ($value) {
                return sprintf('%dLuftBalloons', $value);
            }, range(1, $quantity));
        }

        // default type is 'int'
        return range(1, $quantity);
    }

    protected function writeToScreen(
        OutputInterface $output,
        StopwatchEvent $intvalCastEvent,
        StopwatchEvent $tradCastEvent
    ): void {
        $formattedIntValCastDur = $this->getLocalizedNumber($intvalCastEvent->getDuration());
        $formattedTradCastDur = $this->getLocalizedNumber($tradCastEvent->getDuration());
        $formattedIntValCastMem = $this->getLocalizedNumber($intvalCastEvent->getMemory() / 1024);
        $formattedTradCastMem = $this->getLocalizedNumber($tradCastEvent->getMemory() / 1024);

        $message = <<<TPL
=============================================
intval() casting   time: $formattedIntValCastDur ms
(int) casting      time: $formattedTradCastDur ms

intval() casting memory: $formattedIntValCastMem KB
(int) casting    memory: $formattedTradCastMem KB

TPL;

        [$fasterEvent, $slowerEvent] = $this->getSpeedComparisons($intvalCastEvent, $tradCastEvent);
        [$memoryHogEvent, $memoryBirdEvent] = $this->getMemoryComparisons($intvalCastEvent, $tradCastEvent);

        if ($slowerEvent && $fasterEvent && $fasterEvent->getDuration()) {
            $speedIncrease = ($slowerEvent->getDuration()) / $fasterEvent->getDuration();
            $message .= sprintf(
                "\n%s is %f times faster than %s",
                $fasterEvent->getName(),
                $speedIncrease,
                $slowerEvent->getName()
            );
        } else {
            $message .= sprintf(
                "\nThere is no discernible speed difference between %s and %s",
                $intvalCastEvent->getName(),
                $tradCastEvent->getName()
            );
        }

        if ($memoryHogEvent && $memoryBirdEvent && $memoryBirdEvent->getDuration()) {
            $memoryDecrease = ($memoryHogEvent->getMemory()) / $memoryBirdEvent->getMemory();
            $message .= sprintf(
                "\n%s uses $%s times less memory than %s",
                $memoryBirdEvent->getName(),
                $memoryDecrease,
                $memoryHogEvent->getName()
            );
        } else {
            $message .= sprintf(
                "\nThere is no discernible memory difference between %s and %s",
                $intvalCastEvent->getName(),
                $tradCastEvent->getName()
            );
        }

        $output->write($message, true);
    }

    protected function writeToCsv(
        InputInterface $input,
        StopwatchEvent $intvalCastEvent,
        StopwatchEvent $tradCastEvent
    ): void {
        $header = [
            'sample size',
            sprintf('duration %s', $intvalCastEvent->getName()),
            sprintf('duration %s', $tradCastEvent->getName()),
            sprintf('memory %s', $intvalCastEvent->getName()),
            sprintf('memory %s', $tradCastEvent->getName())
        ];
        $line = [
            (int) $input->getArgument('quantity'),
            (float) $intvalCastEvent->getDuration(),
            (float) $tradCastEvent->getDuration(),
            $intvalCastEvent->getMemory(),
            $tradCastEvent->getMemory(),
        ];

        /** @var string $path */
        $path = $input->getOption('csv-path');

        $filesystem = new Filesystem();
        if (!$filesystem->exists($path)) {
            $filesystem->touch($path);
        }

        $csvReader = Reader::createFromPath($path);
        $csvReader->setHeaderOffset(0);

        $csvWriter = Writer::createFromPath($path, 'a+');

        // If this is a new file
        if (filesize($path) === 0 && $csvReader->getContent() === '') {
            $csvWriter->insertOne($header);
        }

        $csvWriter->insertOne($line);
    }

    /**
     * @return null[]|StopwatchEvent[]
     */
    protected function getSpeedComparisons(StopwatchEvent $intvalCastEvent, StopwatchEvent $tradCastEvent): array
    {
        $fasterEvent = null;
        $slowerEvent = null;
        if ($intvalCastEvent->getDuration() > $tradCastEvent->getDuration()) {
            $fasterEvent = $tradCastEvent;
            $slowerEvent = $intvalCastEvent;
        } elseif ($intvalCastEvent->getDuration() < $tradCastEvent->getDuration()) {
            $fasterEvent = $intvalCastEvent;
            $slowerEvent = $tradCastEvent;
        }
        return [$fasterEvent, $slowerEvent];
    }

    /**
     * @return null[]|StopwatchEvent[]
     */
    protected function getMemoryComparisons(StopwatchEvent $intvalCastEvent, StopwatchEvent $tradCastEvent): array
    {
        $memoryHogEvent = null;
        $memoryBirdEvent = null;
        if ($intvalCastEvent->getMemory() > $tradCastEvent->getMemory()) {
            $memoryHogEvent = $intvalCastEvent;
            $memoryBirdEvent = $tradCastEvent;
        } elseif ($intvalCastEvent->getMemory() < $tradCastEvent->getMemory()) {
            $memoryHogEvent = $tradCastEvent;
            $memoryBirdEvent = $intvalCastEvent;
        }

        return [$memoryHogEvent, $memoryBirdEvent];
    }

    /**
     * @param int|float $number
     * @return string|false String if it can be formatted, false if not
     */
    protected function getLocalizedNumber($number)
    {
        $locale = \Locale::getDefault() === "en_US_POSIX" ? 'en-US' : \Locale::getDefault();

        return \NumberFormatter::create($locale, \NumberFormatter::DECIMAL)->format($number);
    }
}
