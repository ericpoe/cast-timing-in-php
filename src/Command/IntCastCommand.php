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
        $this->addArgument('quantity', InputArgument::OPTIONAL, 'Amount of items to cast');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $quantity = $input->getArgument('quantity') ?? 10000;
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

        $output->write($message, true);

        $io->success('Done');

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
        $formattedQuantity = $this->getLocalizedNumber($quantity);
        $formattedIntValCastDur = $this->getLocalizedNumber($intvalCastEvent->getDuration() / 1000);
        $formattedTradCastDur = $this->getLocalizedNumber($tradCastEvent->getDuration() / 1000);
        $formattedIntValCastMem = $this->getLocalizedNumber($intvalCastEvent->getMemory() / 1024);
        $formattedTradCastMem = $this->getLocalizedNumber($tradCastEvent->getMemory() / 1024);

        $message = <<<TPL
Casting an array of $formattedQuantity integer strings to int
=============================================
intval() casting   time: $formattedIntValCastDur s
(int) casting      time: $formattedTradCastDur s

intval() casting memory: $formattedIntValCastMem KB
(int) casting    memory: $formattedTradCastMem KB

TPL;


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
        return [$fasterEvent, $slowerEvent];
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

        return [$memoryHogEvent, $memoryBirdEvent];
    }

    /**
     * @param int|float $number
     * @return string|false String if it can be formatted, false if not
     */
    protected function getLocalizedNumber($number)
    {
        $locale = \Locale::getDefault() === "en_US_POSIX" ? 'en_US' : \Locale::getDefault();

        return \NumberFormatter::create($locale, \NumberFormatter::DECIMAL)->format($number);
    }
}
