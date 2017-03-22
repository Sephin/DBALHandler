<?php
/**
 * @author Yuen Li <li.tsanyuen@gmail.com>
 * @version 1.0
 */
namespace Sephin\Monolog\Handler;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Statement;
use Doctrine\DBAL\Schema\Schema;
use Monolog\Logger;
use Monolog\Handler\AbstractProcessingHandler;

/**
 * Monolog Handler using Doctrine DBAL.
 */
class DBALHandler extends AbstractProcessingHandler
{
    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @var Statement
     */
    protected $writeStatement;

    /**
     * @var string
     */
    protected $tableName;

    /**
     * @var boolean
     */
    protected $saveContext = false;

    /**
     * Constructor
     * @param Connection $connection
     * @param array $settings Settings containing 'table_name' and 'save_context'
     * @param int $level Logging level
     * @param boolean $bubble Propagate the message or not
     */
    public function __construct(Connection $connection, array $settings, $level = Logger::DEBUG, $bubble = true)
    {
        parent::__construct($level, $bubble);

        if (!isset($settings['table_name'])) {
            throw new \InvalidArgumentException('DBALHandler expects a table name');
        }

        $this->connection = $connection;
        $this->tableName  = $settings['table_name'];

        // optional settings
        if (isset($settings['save_context']) && is_bool($settings['save_context'])) {
             $this->saveContext = $settings['save_context'];
        }
    }

    /**
     * Creates the table needed to store the logs.
     * @return \Doctrine\DBAL\Schema\Table
     */
    public function configureTable()
    {
        $schema = new Schema();

        $table = $schema->createTable($this->tableName);
        $table->addColumn('id', 'bigint', ['autoincrement' => true, 'unsigned' => true]);
        $table->addColumn('channel', 'string', ['length' => 255]);
        $table->addColumn('level', 'integer');
        $table->addColumn('message', 'text');
        $table->addColumn('context', 'text');
        $table->addColumn('recorded_on', 'string', ['length' => 32]);
        $table->setPrimaryKey(['id']);

        return $table;
    }

    /**
     * {@inheritDoc}
     */
    protected function write(array $record)
    {
        $context = (true === $this->saveContext && !empty($record['context'])) ? json_encode($record['context']) : '';

        $statement = $this->prepareWriteStatement();
        $statement->execute([
            ':channel'     => $record['channel'],
            ':level'       => $record['level'],
            ':message'     => $record['message'],
            ':context'     => $context,
            ':recorded_on' => $record['datetime']->format('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Prepares and returns the write statement.
     * @return Statement
     */
    protected function prepareWriteStatement()
    {
        if (null === $this->writeStatement) {
            $sql = "INSERT INTO `" .  $this->tableName . "`
                    (channel, level, message, context, recorded_on)
                    VALUES
                    (:channel, :level, :message, :context, :recorded_on)";
            $this->writeStatement = $this->connection->prepare($sql);
        }

        return $this->writeStatement;
    }

}
