<?php

namespace Tests\Sephin\Monolog\Handler;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Statement;
use Doctrine\DBAL\Schema\Table;
use Monolog\Logger;
use Sephin\Monolog\Handler\DBALHandler;

class DBALHandlerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var array
     */
    protected $settings = [
        'table_name'   => 'test_table',
        'save_context' => false,
    ];

    /**
     * @var DBALHandler
     */
    protected $object;
    
    /**
     * @covers Sephin\Monolog\Handler\DBALHandler::__construct
     */
    public function testConstructException()
    {
        $this->expectException(\InvalidArgumentException::class);
        
        $this->object = new DBALHandler($this->createMock(Connection::class), []);
    }
    
    /**
     * @covers Sephin\Monolog\Handler\DBALHandler::__construct
     * @covers Sephin\Monolog\Handler\DBALHandler::configureTable
     */
    public function testConfigureTable()
    {
        $this->object = new DBALHandler($this->createMock(Connection::class), $this->settings);
        
        $this->assertInstanceOf(Table::class, $this->object->configureTable());
    }
    
    /**
     * @covers Sephin\Monolog\Handler\DBALHandler::__construct
     * @covers Sephin\Monolog\Handler\DBALHandler::write
     * @covers Sephin\Monolog\Handler\DBALHandler::prepareWriteStatement
     */
    public function testHandle()
    {
        $msg = array(
            'level'      => Logger::ERROR,
            'level_name' => 'ERROR',
            'channel'    => 'meh',
            'context'    => array('foo' => 7, 'bar', 'class' => new \stdClass),
            'datetime'   => new \DateTime("@0"),
            'extra'      => array(),
            'message'    => 'log',
        );

        // mock prepared statement
        $connection = $this->createMock(Connection::class);
        $connection->method('prepare')
                   ->willReturn($this->createMock(Statement::class));

        
        $this->object = new DBALHandler($connection, $this->settings);
        
        $this->assertFalse($this->object->handle($msg));
    }

}
