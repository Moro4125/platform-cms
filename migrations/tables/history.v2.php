<?php
// Used in task.0006.ini
/** @var $container  \ArrayAccess */
/** @var $service  \Moro\Migration\Handler\DoctrineDBALHandler */
/** @var $arguments *///* Arguments for this script (as GET query).
use \Doctrine\DBAL\Types\Type;

$name = 'history';
$result = ["!$name"];
$schema = $service->getSchema();

if (!$schema->hasTable($name) || !$table = $schema->getTable($name))
{
	$result = include(__DIR__.'/history.v1.php');
}

$table = $schema->getTable($name);

foreach (['updated_at' => Type::DATETIME] as $column => $type)
{
	if ($table->hasColumn($column) == false)
	{
		$table->addColumn($column, $type)->setNotnull(false);
		$service->writeln("Add column \"$name.$column\".");
		$result[] = $column;
	}
}

return $result;