<?php

namespace App\Command;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class RunAllCommand extends Command
{
    protected static $defaultName = 'app:run-all';
    protected static $defaultDescription = 'Add a short description for your command';

    protected function configure(): void
    {
        $this
            ->addArgument('quantity', InputArgument::OPTIONAL, 'Amount of items to cast', '10000')
            ->addOption('iterations', 'i', InputOption::VALUE_OPTIONAL, 'How many times to run this command', '1')
            ->addOption(
                'from-type',
                't',
                InputOption::VALUE_OPTIONAL,
                'Cast from "int", "float", "string" or "num-string" (ex. "99LuftBalloons")',
                'int'
            )
            ->addOption('csv-path', 'p', InputOption::VALUE_OPTIONAL, 'Path to CSV file for writing results')
            ->addOption('use-db', null, InputOption::VALUE_OPTIONAL, 'Write to DB?', 'n')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $inputOptions = [
            'command' => null, // 'command' needs to be the first item in the array
            '--iterations' => $input->getOption('iterations'),
        ];

        if ($input->getOption('csv-path')) {
            $inputOptions['--csv-path'] = $input->getOption('csv-path');
        }

        if ($input->getOption('use-db')) {
            $inputOptions['--use-db'] = $input->getOption('use-db');
        }

        /** @var Application $application */
        $application = $this->getApplication();

        $fromTypes = ['bool', 'float', 'int', 'num-string', 'string'];
        $commands = ['app:bool-cast', 'app:float-cast', 'app:int-cast', 'app:string-cast'];

        foreach ($commands as $command) {
            $inputOptions['command'] = $command;
            $quantities = [1, 10, 100, 1000, 10000, 100000, 1000000, 10000000, 100000000, ];

            foreach ($quantities as $quantity) {
                $inputOptions['quantity'] = $quantity;

                foreach ($fromTypes as $fromType) {
                    $inputOptions['--from-type'] = $fromType;
                    $commandInput = new ArrayInput($inputOptions);

                    $application->doRun($commandInput, $output);
                }
            }
        }

        return Command::SUCCESS;
    }
}
