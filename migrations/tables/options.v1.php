<?php
// Used in task.0002.ini
/** @var $container  \ArrayAccess */
/** @var $service  \Moro\Migration\Handler\DoctrineDBALHandler */
/** @var $arguments *///* Arguments for this script (as GET query).
use \Doctrine\DBAL\Types\Type;

$name = 'options';
$result = [$name];
$schema = $service->getSchema();

if (!$schema->hasTable($name) || !$table = $schema->getTable($name))
{
	$table = $schema->createTable($name);
	$table->addColumn('id',        Type::INTEGER) ->setAutoincrement(true);
	$table->addColumn('code',      Type::STRING)  ->setNotnull(true);
	$table->addColumn('type',      Type::STRING)  ->setNotnull(true) ->setDefault('text');
	$table->addColumn('value',     Type::STRING)  ->setNotnull(false);
	$table->addColumn('block',     Type::STRING)  ->setNotnull(false);
	$table->addColumn('label',     Type::STRING)  ->setNotnull(false);
	$table->addColumn('validator', Type::STRING)  ->setNotnull(false);
	$table->addColumn('sort',      Type::INTEGER) ->setNotnull(true)->setDefault(0);

	$table->setPrimaryKey(['id'], 'idx_'.$name);
	$service->writeln("Table \"$name\" is created.");
}

foreach (['code' => true, 'sort' => false] as $fields => $unique)
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