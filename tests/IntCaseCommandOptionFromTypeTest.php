<?php

namespace App\Tests;

use App\Command\IntCastCommand;
use PHPUnit\Framework\TestCase;

class IntCaseCommandOptionFromTypeTest extends TestCase
{
    /** @var IntCastCommand $command */
    private $command;
    public function setUp(): void
    {
        $this->command = new IntCastCommand('app:int-cast');
    }

    public function testGetItemsFromTypeDefault(): void
    {
        $items = $this->command->getItemsFromType(2);
        $this->assertIsInt($items[1]);
        $this->assertEquals(2, $items[1]);
    }

    public function testGetItemsFromTypeInt(): void
    {
        $items = $this->command->getItemsFromType(2, 'int');
        $this->assertIsInt($items[1]);
        $this->assertEquals(2, $items[1]);
    }

    public function testGetItemsFromTypeFloat(): void
    {
        $items = $this->command->getItemsFromType(2, 'float');
        $this->assertIsFloat($items[1]);
        $this->assertEquals(1.1, $items[1]);
    }

    public function testGetItemsFromTypeString(): void
    {
        $items = $this->command->getItemsFromType(99, 'string');
        $this->assertIsString($items[98]);
        $this->assertEquals('99', $items[98]);
    }

    public function testGetItemsFromTypeNumericString(): void
    {
        $items = $this->command->getItemsFromType(99, 'num-string');
        $this->assertIsString($items[98]);
        $this->assertEquals('99LuftBalloons', $items[98]);
    }

    /**
     * @@dataProvider badTypeProvider
     */
    public function testValidateFromBadType(string $input): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->command->validateFromType($input);
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function badTypeProvider(): array
    {
        return [
            'from foo' => ['foo'],
            'from sTRIng' => ['sTRIng'],
            'from fLOAt' => ['fLOAt'],
            'from made up word' => ['FXKAwpozikr7'],
        ];
    }
}
