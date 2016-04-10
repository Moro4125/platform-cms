<?php
// Обновление записей (через пересохранение).
/** @var $container  \ArrayAccess */
/** @var $service  \Moro\Migration\Handler\DoctrineDBALHandler */
/** @var $arguments *///* Arguments for this script from apply action.
global $totalAffectedRecords;
empty($totalAffectedRecords) && $totalAffectedRecords = 0;

if (isset($arguments['service']) && isset($container[$arguments['service']]))
{
	/** @var \Doctrine\DBAL\Connection $connection */
	$connection = $container['db'];
	$manager = $container[$arguments['service']];

	if ($manager instanceof \Moro\Platform\Model\AbstractService)
	{
		$service->writeln('Service '.$manager->getServiceCode().':');

		$offset = 0;
		$count = 100;

		$filter = empty($arguments['filter']) ? null : $arguments['filter'];
		$value  = empty($arguments['filter']) ? null : $arguments['value'];
		$lastId = 0;

		while (true)
		{
			$list = $manager->selectEntities($offset, $count, 'id', $filter, $value);

			try
			{
				$connection->beginTransaction();
				$transaction = true;

				/** @var \Moro\Platform\Model\EntityInterface $entity */
				foreach ($list as $entity)
				{
					$lastId = $entity->getId();
					$entity->addFlag(\Moro\Platform\Model\EntityInterface::FLAG_SYSTEM_CHANGES);
					$manager->commit($entity);
				}

				if ($connection->isRollbackOnly())
				{
					throw new \Exception('Transaction now is in "rollback only" mode.');
				}

				unset($transaction);
				$connection->commit();
				$totalAffectedRecords += count($list);

				$service->writeln(count($list).' records updated. Total: '.$totalAffectedRecords);
			}
			catch (\Exception $e)
			{
				isset($transaction) && $connection->rollBack();
				throw new \RuntimeException('Error for record with ID '.$lastId, $e->getCode(), $e);
			}

			if (count($list) < $count)
			{
				break;
			}

			$offset += $count;
		}
	}
	else
	{
		$service->error('Unknown service interface: '.get_class($manager));
	}
}
else
{
	$service->error('Unknown service: '.(isset($arguments['service']) ? $arguments['service'] : '~null~'));
}