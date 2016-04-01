<?php
// Used in task.0004.ini
/** @var $container  \ArrayAccess */
/** @var $service  \Moro\Migration\Handler\DoctrineDBALHandler */
/** @var $arguments *///* Arguments for this script (as GET query).
use \Doctrine\DBAL\Types\Type;

$name = 'history';
$result = [$name];
$schema = $service->getSchema();

if (!$schema->hasTable($name) || !$table = $schema->getTable($name))
{
	$table = $schema->createTable($name);
	$table->addColumn('id',         Type::INTEGER) ->setAutoincrement(true);
	$table->addColumn('service',    Type::STRING)  ->setNotnull(true);
	$table->addColumn('entity_id',  Type::INTEGER) ->setNotnull(true);
	$table->addColumn('request_id', Type::STRING)  ->setNotnull(true);
	$table->addColumn('parameters', Type::STRING)  ->setNotnull(true);
	$table->addColumn('created_at', Type::DATETIME)->setNotnull(true);
	$table->addColumn('created_by', Type::STRING)  ->setNotnull(true);

	$table->setPrimaryKey(['id'], 'idx_'.$name);
	$service->writeln("Table \"$name\" is created.");
}

foreach (['service,entity_id,created_at' => false, 'request_id' => false] as $fields => $unique)
{
	$fields = explode(',', $fields);
	$idxName = 'idx_'.$name.'__'.implode('_', $fields);

	if (!$table->hasIndex($idxName))
	{
		$result[] = $idxName;
		$unique ? $table->addUniqueIndex($fields, $idxName) : $table->addIndex($fields, $idxName);
		$service->writeln("Index \"$idxName\" is created.");
	}
}

return $result;