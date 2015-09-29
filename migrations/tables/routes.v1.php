<?php
// Used in task.0002.ini
/** @var $container  \ArrayAccess */
/** @var $service  \Moro\Migration\Handler\DoctrineDBALHandler */
/** @var $arguments *///* Arguments for this script (as GET query).
use \Doctrine\DBAL\Types\Type;

$name = 'routes';
$result = [$name];
$schema = $service->getSchema();

if (!$schema->hasTable($name) || !$table = $schema->getTable($name))
{
	$table = $schema->createTable($name);
	$table->addColumn('id',           Type::INTEGER) ->setAutoincrement(true);
	$table->addColumn('route',        Type::STRING)  ->setNotnull(true);
	$table->addColumn('query',        Type::STRING)  ->setNotnull(false);
	$table->addColumn('parameters',   Type::STRING)  ->setNotnull(true);
	$table->addColumn('compile_flag', Type::INTEGER) ->setNotnull(true)->setDefault(0);
	$table->addColumn('title',        Type::STRING)  ->setNotnull(false);
	$table->addColumn('file',         Type::STRING)  ->setNotnull(false);
	$table->addColumn('created_at',   Type::DATETIME)->setNotnull(true);
	$table->addColumn('updated_at',   Type::DATETIME)->setNotnull(true);

	$table->setPrimaryKey(['id'], 'idx_'.$name);
	$service->writeln("Table \"$name\" is created.");
}

foreach (['route,query' => true, 'compile_flag,updated_at' => false, 'file' => false] as $fields => $unique)
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