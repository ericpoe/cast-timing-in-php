<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Component\Stopwatch\StopwatchEvent;

class IntCastCommand extends Command
{
    protected static $defaultName = 'app:int-cast';
    protected static $defaultDescription = 'Generate timings for casting integers via `(int)` and `intval()`';

    protected function configure(): void
    {
        $this
            ->addArgument('quantity', InputArgument::OPTIONAL, 'Amount of items to cast')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $quantity = $input->getArgument('quantity') ?? 100;
        $quantity = (int) $quantity;

        $items = range(1, $quantity);
        $items = array_map(function ($value) {
            return (int) $value;
        }, $items);

        $tmp = null;
        $stopwatch = new Stopwatch(true);

        $stopwatch->reset();
        $stopwatch->start('(int) cast');
        foreach ($items as $item) {
            $tmp = (int) $item;
        }
        $tradCastEvent = $stopwatch->stop('(int) cast');

        $stopwatch->reset();
        $stopwatch->start('intval()');
        foreach ($items as $item) {
            $tmp = intval($item);
        }
        $intvalCastEvent = $stopwatch->stop('intval()');

        [$fasterEvent, $slowerEvent] = $this->getSpeedComparisons($intvalCastEvent, $tradCastEvent);

        [$memoryHogEvent, $memoryBirdEvent] = $this->getMemoryComparisons($intvalCastEvent, $tradCastEvent);

        $message = $this->getMessage(
            $quantity,
            $intvalCastEvent,
            $tradCastEvent,
            $fasterEvent,
            $slowerEvent,
            $memoryBirdEvent,
            $memoryHogEvent
        );

        $io->success(sprintf("%s\n", $message));

        return Command::SUCCESS;
    }

    public function getMessage(
        int $quantity,
        StopwatchEvent $intvalCastEvent,
        StopwatchEvent $tradCastEvent,
        ?StopwatchEvent $fasterEvent,
        ?StopwatchEvent $slowerEvent,
        ?StopwatchEvent $memoryBirdEvent,
        ?StopwatchEvent $memoryHogEvent
    ): string {
        $format = \NumberFormatter::create(\Locale::getDefault(), \NumberFormatter::DECIMAL);

        $message = sprintf(
            <<<TPL
Casting an array of %s integer strings to int
=============================================
intval() casting   time: %s s
(int) casting      time: %s s

intval() casting memory: %s KB
(int) casting    memory: %s KB

TPL,
            $format->format($quantity),
            $format->format($intvalCastEvent->getDuration() / 1000),
            $format->format($tradCastEvent->getDuration() / 1000),
            $format->format($intvalCastEvent->getMemory() / 1024),
            $format->format($tradCastEvent->getMemory() / 1024)
        );

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
        return $message;
    }

    /**
     * @return null[]|StopwatchEvent[]
     */
    public function getSpeedComparisons(StopwatchEvent $intvalCastEvent, StopwatchEvent $tradCastEvent): array
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
        return array($fasterEvent, $slowerEvent);
    }

    /**
     * @return null[]|StopwatchEvent[]
     */
    public function getMemoryComparisons(StopwatchEvent $intvalCastEvent, StopwatchEvent $tradCastEvent): array
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

        return array($memoryHogEvent, $memoryBirdEvent);
    }
}
