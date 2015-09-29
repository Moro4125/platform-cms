<?php
// Used in task.0002.ini
/** @var $container  \ArrayAccess */
/** @var $service  \Moro\Migration\Handler\DoctrineDBALHandler */
/** @var $arguments *///* Arguments for this script (as GET query).
use \Doctrine\DBAL\Types\Type;

$name = 'options';
$schema = $service->getSchema();

if (!$schema->hasTable($name))
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

	$table->setPrimaryKey(['id'], 'idx_options');
	$table->addIndex(['code'],    'idx_options__code');
	$table->addIndex(['sort'],    'idx_options__sort');

	$service->writeln("Table \"$name\" created.");
}

return [$name, 'idx_options__code', 'idx_options__sort'];