<?php
// Удаление таблицы.
/** @var $container  \ArrayAccess */
/** @var $service  \Moro\Migration\Handler\DoctrineDBALHandler */
/** @var $arguments *///* Arguments for this script from apply action.

$schema = $service->getSchema();
$tableName = reset($arguments);

if ($tableName && $schema->hasTable($tableName))
{
	$table = $schema->getTable($tableName);

	foreach (array_reverse($arguments) as $indexName)
	{
		if ($table->hasIndex($indexName))
		{
			$table->dropIndex($indexName);
			$service->writeln("Delete index \"$indexName\".");
		}
	}

	$schema->dropTable($tableName);
	$service->writeln("Delete table \"$tableName\".");
}
else
{
	$service->writeln("<error>Table \"$tableName\" is not exists.</error>");
}