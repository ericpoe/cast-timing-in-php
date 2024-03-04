<?php

namespace App\Command;

use App\Entity\Timing;
use Doctrine\ORM\EntityManagerInterface;
use League\Csv\Reader;
use League\Csv\Writer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Stopwatch\StopwatchEvent;

abstract class AbstractCastCommand extends Command
{
    /**
     * @var EntityManagerInterface $entityManager
     */
    protected $entityManager;

    abstract protected function getToType(): string;

    /**
     * @required
     */
    public function setEntityManager(EntityManagerInterface $entityManager): void
    {
        $this->entityManager = $entityManager;
    }


    protected function configure(): void
    {
        $this->addArgument('quantity', InputArgument::OPTIONAL, 'Amount of items to cast', '10000');
        $this->addOption('iterations', 'i', InputOption::VALUE_OPTIONAL, 'How many times to run this command', '1');
        $this->addOption('csv-path', 'p', InputOption::VALUE_OPTIONAL, 'Path to CSV file for writing results');
        $this->addOption(
            'from-type',
            't',
            InputOption::VALUE_OPTIONAL,
            'Cast from "int", "float", "string" or "num-string" (ex. "99LuftBalloons")',
            'int'
        );
        $this->addOption('use-db', null, InputOption::VALUE_REQUIRED, 'Write to DB?', 'y');
    }

    public function validateFromType(string $type): string
    {
        $validTypes = ['float', 'int', 'string', 'num-string'];

        if (!in_array($type, $validTypes)) {
            throw new \InvalidArgumentException(
                sprintf(
                    '%s is an invalid type and must be one of %s',
                    $type,
                    implode(', ', $validTypes)
                )
            );
        }

        return $type;
    }

    /**
     * @return array<int, int> | array<int, float> | array<int, string>
     */
    public function getItemsFromType(int $quantity, string $type = 'int'): array
    {
        $type = strtolower($type);

        if ($type === 'float') {
            return array_map(function ($value) {
                return $value;
            },
                range(0.1, $quantity + 0.1));
        }

        if ($type === 'string') {
            return array_map(function ($value) {
                return (string) $value;
            },
            range(1, $quantity));
        }

        if ($type === 'num-string') {
            return  array_map(function ($value) {
                return sprintf('%sLuftBalloons', $value);
            }, range(1, $quantity));
        }

        // default type is 'int'
        return range(1, $quantity);
    }

    protected function createResults(
        InputInterface $input,
        OutputInterface $output,
        StopwatchEvent $functionCastEvent,
        StopwatchEvent $tradCastEvent
    ): void {
        if (strtolower($input->getOption('use-db')) === 'y') {
            $this->writeToDb($input, $functionCastEvent, $tradCastEvent);
        } elseif ($input->getOption('csv-path')) {
            $this->writeToCsv($input, $functionCastEvent, $tradCastEvent);
        } else {
            $this->writeToScreen($output, $functionCastEvent, $tradCastEvent);
        }
    }

    protected function writeToScreen(
        OutputInterface $output,
        StopwatchEvent $functionCastEvent,
        StopwatchEvent $tradCastEvent
    ): void {
        $formattedIntValCastDur = $this->getLocalizedNumber($functionCastEvent->getDuration());
        $formattedTradCastDur = $this->getLocalizedNumber($tradCastEvent->getDuration());
        $formattedIntValCastMem = $this->getLocalizedNumber($functionCastEvent->getMemory() / 1024);
        $formattedTradCastMem = $this->getLocalizedNumber($tradCastEvent->getMemory() / 1024);

        $message = <<<TPL
=============================================
intval() casting   time: $formattedIntValCastDur ms
(int) casting      time: $formattedTradCastDur ms

intval() casting memory: $formattedIntValCastMem KB
(int) casting    memory: $formattedTradCastMem KB

TPL;

        [$fasterEvent, $slowerEvent] = $this->getSpeedComparisons($functionCastEvent, $tradCastEvent);
        [$memoryHogEvent, $memoryBirdEvent] = $this->getMemoryComparisons($functionCastEvent, $tradCastEvent);

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
                $functionCastEvent->getName(),
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
                $functionCastEvent->getName(),
                $tradCastEvent->getName()
            );
        }

        $output->write($message, true);
    }

    protected function writeToCsv(
        InputInterface $input,
        StopwatchEvent $functionCastEvent,
        StopwatchEvent $tradCastEvent
    ): void {
        $header = [
            'sample size',
            sprintf('duration %s', $functionCastEvent->getName()),
            sprintf('duration %s', $tradCastEvent->getName()),
            sprintf('memory %s', $functionCastEvent->getName()),
            sprintf('memory %s', $tradCastEvent->getName())
        ];
        $line = [
            (int) $input->getArgument('quantity'),
            (float) $functionCastEvent->getDuration(),
            (float) $tradCastEvent->getDuration(),
            $functionCastEvent->getMemory(),
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

    protected function writeToDb(
        InputInterface $input,
        StopwatchEvent $functionCastEvent,
        StopwatchEvent $tradCastEvent
    ): void {
        $timing = new Timing();

        $sampleSize = (int) $input->getArgument('quantity');

        /** @var string $fromType */
        $fromType = $input->getOption('from-type');
        $toType = $this->getToType();

        $timing->setFromType($fromType)
            ->setToType($toType)
            ->setSampleSize($sampleSize)
            ->setFunctionCastDuration($functionCastEvent->getDuration())
            ->setFunctionCastMemory($functionCastEvent->getMemory())
            ->setTradCastDuration($tradCastEvent->getDuration())
            ->setTradCastMemory($tradCastEvent->getMemory())
        ;

        $this->entityManager->persist($timing);
        $this->entityManager->flush();
    }

    /**
     * @return null[]|StopwatchEvent[]
     */
    protected function getSpeedComparisons(StopwatchEvent $functionCastEvent, StopwatchEvent $tradCastEvent): array
    {
        $fasterEvent = null;
        $slowerEvent = null;
        if ($functionCastEvent->getDuration() > $tradCastEvent->getDuration()) {
            $fasterEvent = $tradCastEvent;
            $slowerEvent = $functionCastEvent;
        } elseif ($functionCastEvent->getDuration() < $tradCastEvent->getDuration()) {
            $fasterEvent = $functionCastEvent;
            $slowerEvent = $tradCastEvent;
        }
        return [$fasterEvent, $slowerEvent];
    }

    /**
     * @return null[]|StopwatchEvent[]
     */
    protected function getMemoryComparisons(StopwatchEvent $functionCastEvent, StopwatchEvent $tradCastEvent): array
    {
        $memoryHogEvent = null;
        $memoryBirdEvent = null;
        if ($functionCastEvent->getMemory() > $tradCastEvent->getMemory()) {
            $memoryHogEvent = $functionCastEvent;
            $memoryBirdEvent = $tradCastEvent;
        } elseif ($functionCastEvent->getMemory() < $tradCastEvent->getMemory()) {
            $memoryHogEvent = $tradCastEvent;
            $memoryBirdEvent = $functionCastEvent;
        }

        return [$memoryHogEvent, $memoryBirdEvent];
    }

    /**
     * @param int|float $number
     */
    protected function getLocalizedNumber($number): string
    {
        $locale = \Locale::getDefault() === "en_US_POSIX" ? 'en-US' : \Locale::getDefault();
        \Locale::setDefault($locale);

        $formatted = \NumberFormatter::create($locale, \NumberFormatter::DECIMAL);

        if (!$formatted instanceof \NumberFormatter) {
            throw new \RuntimeException(
                sprintf(
                    'The locale of %s does not have a decimal formatter',
                    \Locale::getDefault()
                )
            );
        }

        /** @var string $formattedNumber */
        $formattedNumber = $formatted->format($number);

        /**
         * If the $number exists (zero is falsy, so must be accounted for) but cannot be formatted
         */
        if ($number && !$formattedNumber) {
            throw new \RuntimeException(
                sprintf(
                    '%s cannot be formatted as a number using the default locale of : %s',
                    $number,
                    $locale
                )
            );
        }

        return $formattedNumber;
    }
}
