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

    public function testGetItemsFromTypeMixedCaseInt(): void
    {
        $items = $this->command->getItemsFromType(2, 'InT');
        $this->assertIsInt($items[1]);
        $this->assertEquals(2, $items[1]);
    }

    public function testGetItemsFromTypeFloat(): void
    {
        $items = $this->command->getItemsFromType(2, 'float');
        $this->assertIsFloat($items[1]);
        $this->assertEquals(2.1, $items[1]);
    }

    public function testGetItemsFromTypeMixedCaseFloat(): void
    {
        $items = $this->command->getItemsFromType(2, 'fLoAt');
        $this->assertIsFloat($items[1]);
        $this->assertEquals(2.1, $items[1]);
    }

    public function testGetItemsFromTypeString(): void
    {
        $items = $this->command->getItemsFromType(99, 'string');
        $this->assertIsString($items[98]);
        $this->assertEquals('99LuftBalloons', $items[98]);
    }

    public function testGetItemsFromTypeMixedCaseString(): void
    {
        $items = $this->command->getItemsFromType(99, 'sTrInG');
        $this->assertIsString($items[98]);
        $this->assertEquals('99LuftBalloons', $items[98]);
    }

    public function testGetItemsFromTypeUnknownTypeInteger(): void
    {
        $items = $this->command->getItemsFromType(2, 'integer');
        $this->assertIsInt($items[1]);
        $this->assertEquals(2, $items[1]);
    }

    public function testGetItemsFromTypeTotallyMadeUp(): void
    {
        $items = $this->command->getItemsFromType(2, 'FXKAwpozikr7');
        $this->assertIsInt($items[1]);
        $this->assertEquals(2, $items[1]);
    }
}
