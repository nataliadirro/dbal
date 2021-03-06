<?php

declare(strict_types=1);

namespace Doctrine\DBAL\Tests\Tools\Console;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Tools\Console\Command\RunSqlCommand;
use Doctrine\DBAL\Tools\Console\ConsoleRunner;
use LogicException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class RunSqlCommandTest extends TestCase
{
    /** @var CommandTester */
    private $commandTester;
    /** @var RunSqlCommand */
    private $command;

    /** @var Connection|MockObject */
    private $connectionMock;

    protected function setUp() : void
    {
        $this->command = new RunSqlCommand();

        (new Application())->add($this->command);

        $this->commandTester = new CommandTester($this->command);

        $this->connectionMock = $this->createMock(Connection::class);
        $this->connectionMock->method('fetchAll')
            ->willReturn([[1]]);
        $this->connectionMock->method('executeUpdate')
            ->willReturn(42);

        $helperSet = ConsoleRunner::createHelperSet($this->connectionMock);
        $this->command->setHelperSet($helperSet);
    }

    public function testMissingSqlArgument() : void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Argument "sql" is required in order to execute this command correctly.');

        $this->commandTester->execute([
            'command' => $this->command->getName(),
            'sql' => null,
        ]);
    }

    public function testIncorrectDepthOption() : void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Option "depth" must contains an integer value.');

        $this->commandTester->execute([
            'command' => $this->command->getName(),
            'sql' => 'SELECT 1',
            '--depth' => 'string',
        ]);
    }

    public function testSelectStatementsPrintsResult() : void
    {
        $this->expectConnectionFetchAll();

        $exitCode = $this->commandTester->execute([
            'command' => $this->command->getName(),
            'sql' => 'SELECT 1',
        ]);
        $this->assertSame(0, $exitCode);

        self::assertRegExp('@int.*1.*@', $this->commandTester->getDisplay());
        self::assertRegExp('@array.*1.*@', $this->commandTester->getDisplay());
    }

    public function testUpdateStatementsPrintsAffectedLines() : void
    {
        $this->expectConnectionExecuteUpdate();

        $this->commandTester->execute([
            'command' => $this->command->getName(),
            'sql' => 'UPDATE foo SET bar = 42',
        ]);

        self::assertRegExp('@int.*42.*@', $this->commandTester->getDisplay());
        self::assertNotRegExp('@array.*1.*@', $this->commandTester->getDisplay());
    }

    private function expectConnectionExecuteUpdate() : void
    {
        $this->connectionMock
            ->expects($this->exactly(1))
            ->method('executeUpdate');
        $this->connectionMock
            ->expects($this->exactly(0))
            ->method('fetchAll');
    }

    private function expectConnectionFetchAll() : void
    {
        $this->connectionMock
            ->expects($this->exactly(0))
            ->method('executeUpdate');
        $this->connectionMock
            ->expects($this->exactly(1))
            ->method('fetchAll');
    }

    public function testStatementsWithFetchResultPrintsResult() : void
    {
        $this->expectConnectionFetchAll();

        $this->commandTester->execute([
            'command' => $this->command->getName(),
            'sql' => '"WITH bar as (SELECT 1) SELECT * FROM bar',
            '--force-fetch' => true,
        ]);

        self::assertRegExp('@int.*1.*@', $this->commandTester->getDisplay());
        self::assertRegExp('@array.*1.*@', $this->commandTester->getDisplay());
    }
}
