<?php

declare(strict_types=1);

namespace Doctrine\DBAL\Tests\Events;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Event\ConnectionEventArgs;
use Doctrine\DBAL\Event\Listeners\SQLSessionInit;
use Doctrine\DBAL\Events;
use PHPUnit\Framework\TestCase;

/**
 * @group DBAL-169
 */
class SQLSessionInitTest extends TestCase
{
    public function testPostConnect() : void
    {
        $connectionMock = $this->createMock(Connection::class);
        $connectionMock->expects($this->once())
                       ->method('exec')
                       ->with($this->equalTo("SET SEARCH_PATH TO foo, public, TIMEZONE TO 'Europe/Berlin'"));

        $eventArgs = new ConnectionEventArgs($connectionMock);

        $listener = new SQLSessionInit("SET SEARCH_PATH TO foo, public, TIMEZONE TO 'Europe/Berlin'");
        $listener->postConnect($eventArgs);
    }

    public function testGetSubscribedEvents() : void
    {
        $listener = new SQLSessionInit("SET SEARCH_PATH TO foo, public, TIMEZONE TO 'Europe/Berlin'");
        self::assertEquals([Events::postConnect], $listener->getSubscribedEvents());
    }
}
