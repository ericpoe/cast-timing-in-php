<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class IntCaseCommandTest extends KernelTestCase
{
    /** @var CommandTester $commandTester */
    private $commandTester;

    public function setUp(): void
    {
        self::bootKernel();
        $application = new Application(self::$kernel);

        $command = $application->find('app:int-cast');
        $this->commandTester = new CommandTester($command);
    }

    /**
     * @dataProvider quantityProvider
     */
    public function testQuantity(int $quantity, string $expected): void
    {
        $this->commandTester->execute([
            'quantity' => $quantity,
        ]);
        $this->commandTester->assertCommandIsSuccessful();

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString(sprintf('Creating an array of %s ints to cast', $expected), $output);
    }

    /**
     * @return array<string, array<int, int|string>>
     */
    public function quantityProvider(): array
    {
        return [
            'one' => [1, '1'],
            'ten' => [10, '10'],
            'one hundred' => [100, '100'],
            'one thousand' => [1000, '1,000'],
            'ten thousand' => [10000, '10,000'],
        ];
    }

    /**
     * @dataProvider typeProvider
     */
    public function testGetItemsFromGoodType(string $type, string $expected): void
    {
        $this->commandTester->execute([
            'quantity' => 1,
            '--from-type' => $type,
        ]);
        $this->commandTester->assertCommandIsSuccessful();

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString(sprintf('Creating an array of 1 %ss to cast', $expected), $output);
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function typeProvider(): array
    {
        return [
            'from int' => ['int', 'int'],
            'from foo' => ['float', 'float'],
            'from string' => ['string', 'string'],
            'from num-string' => ['num-string', 'num-string'],
        ];
    }
}
