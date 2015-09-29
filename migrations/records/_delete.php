<?php
// Удаление записей.
/** @var $container  \ArrayAccess */
/** @var $service  \Moro\Migration\Handler\DoctrineDBALHandler */
/** @var $arguments *///* Arguments for this script from apply action.
$table = array_shift($arguments);
$statement = $service->getConnection()->prepare("DELETE FROM $table WHERE id = ?");
$count = 0;

foreach ($arguments as $id)
{
	 $count += (int)$statement->execute([ (int)$id ]);
}

$service->writeln("$count record(s) deleted.");