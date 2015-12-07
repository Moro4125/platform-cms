<?php
// Used in task.0003.ini
/** @var $container  \ArrayAccess */
/** @var $service  \Moro\Migration\Handler\DoctrineDBALHandler */
/** @var $arguments *///* Arguments for this script (as GET query).
use \Doctrine\DBAL\Types\Type;

$name = 'api_key';
$result = [$name];
$schema = $service->getSchema();

if (!$schema->hasTable($name) || !$table = $schema->getTable($name))
{
	$table = $schema->createTable($name);
	$table->addColumn('id',         Type::INTEGER) ->setAutoincrement(true);
	$table->addColumn('key',        Type::STRING)  ->setNotnull(true);
	$table->addColumn('user',       Type::STRING)  ->setNotnull(true);
	$table->addColumn('roles',      Type::STRING)  ->setNotnull(true);
	$table->addColumn('target',     Type::STRING)  ->setNotnull(true);
	$table->addColumn('counter',    Type::INTEGER) ->setNotnull(true)->setDefault(-1);
	$table->addColumn('created_at', Type::DATETIME)->setNotnull(true);
	$table->addColumn('updated_at', Type::DATETIME)->setNotnull(true);

	$table->setPrimaryKey(['id'], 'idx_'.$name);
	$service->writeln("Table \"$name\" is created.");
}

foreach (['key' => true, 'user,target' => true] as $fields => $unique)
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