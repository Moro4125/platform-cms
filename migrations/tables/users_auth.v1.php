<?php
// Used in task.0008.ini
/** @var $container  \ArrayAccess */
/** @var $service  \Moro\Migration\Handler\DoctrineDBALHandler */
/** @var $arguments *///* Arguments for this script (as GET query).
use \Doctrine\DBAL\Types\Type;

$name = 'users_auth';
$result = [$name];
$schema = $service->getSchema();

if (!$schema->hasTable($name) || !$table = $schema->getTable($name))
{
	$table = $schema->createTable($name);
	$table->addColumn('id',         Type::INTEGER)  ->setAutoincrement(true);
	$table->addColumn('user_id',    Type::INTEGER)  ->setNotnull(false);
	$table->addColumn('provider',   Type::STRING)   ->setNotnull(true);
	$table->addColumn('identifier', Type::STRING)   ->setNotnull(true);
	$table->addColumn('credential', Type::STRING)   ->setNotnull(true);
	$table->addColumn('roles',      Type::STRING)   ->setNotnull(false);
	$table->addColumn('parameters', Type::TEXT)     ->setNotnull(false);
	$table->addColumn('created_at', Type::DATETIME) ->setNotnull(true);
	$table->addColumn('order_at',   Type::DATETIME) ->setNotnull(true);
	$table->addColumn('updated_at', Type::DATETIME) ->setNotnull(true);
	$table->addColumn('updated_ip', Type::STRING)   ->setNotnull(false);
	$table->addColumn('success',    Type::INTEGER)  ->setNotnull(true)  ->setDefault(0);
	$table->addColumn('failure',    Type::INTEGER)  ->setNotnull(true)  ->setDefault(0);
	$table->addColumn('result',     Type::INTEGER)  ->setNotnull(true)  ->setDefault(1);
	$table->addColumn('banned',     Type::INTEGER)  ->setNotnull(true)  ->setDefault(0);

	$table->setPrimaryKey(['id'], 'idx_'.$name);
	$service->writeln("Table \"$name\" is created.");
}

foreach (['user_id,provider' => true, 'provider,identifier' => true] as $fields => $unique)
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