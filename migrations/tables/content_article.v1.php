<?php
// Used in task.0002.ini
/** @var $container  \ArrayAccess */
/** @var $service  \Moro\Migration\Handler\DoctrineDBALHandler */
/** @var $arguments *///* Arguments for this script (as GET query).
use \Doctrine\DBAL\Types\Type;

$name = 'content_article';
$schema = $service->getSchema();

if (!$schema->hasTable($name))
{
	$table = $schema->createTable($name);
	$table->addColumn('id',         Type::INTEGER)  ->setAutoincrement(true);
	$table->addColumn('code',       Type::STRING)   ->setNotnull(true);
	$table->addColumn('name',       Type::STRING)   ->setNotnull(false);
	$table->addColumn('icon',       Type::STRING)   ->setNotnull(false);
	$table->addColumn('version',    Type::INTEGER)  ->setNotnull(true)  ->setDefault(0);
	$table->addColumn('parameters', Type::TEXT)     ->setNotnull(false);
	$table->addColumn('created_at', Type::DATETIME) ->setNotnull(true);
	$table->addColumn('updated_at', Type::DATETIME) ->setNotnull(true);
	$table->addColumn('created_by', Type::STRING)   ->setNotnull(false);
	$table->addColumn('updated_by', Type::STRING)   ->setNotnull(false);
	$table->addColumn('order_at',   Type::DATETIME) ->setNotnull(false);

	$table->setPrimaryKey(['id'],    'idx_content_article');
	$table->addUniqueIndex(['code'], 'idx_content_article__code');

	$service->writeln("Table \"$name\" created.");
}

return [$name, 'idx_content_article__code'];