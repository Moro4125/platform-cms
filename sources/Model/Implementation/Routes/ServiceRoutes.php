<?php
/**
 * Class ServiceRoutes
 */
namespace Moro\Platform\Model\Implementation\Routes;
use \Moro\Platform\Application;
use \Moro\Platform\Model\AbstractService;
use \Moro\Platform\Model\Accessory\Parameters\Tags\TagsServiceInterface;

use \Moro\Platform\Form\RoutesForm;


use \Symfony\Component\Form\Form;
use \Exception;
use \PDO;

/**
 * Class ServiceRoutes
 * @package Model\Routes
 *
 * @method RoutesInterface[] selectEntities($offset = null, $count = null, $orderBy = null, $filter = null, $value = null)
 */
class ServiceRoutes extends AbstractService implements TagsServiceInterface
{
	use \Moro\Platform\Model\Accessory\Parameters\Tags\TagsServiceTrait;

	/**
	 * @var string
	 */
	protected $_table = 'routes';

	/**
	 * @param string $route
	 * @param array $query
	 * @return RoutesInterface
	 * @throws \Doctrine\DBAL\DBALException
	 */
	public function getByRouteAndQuery($route, array $query)
	{
		$entity = $this->_newEntityFromArray([]);
		$entity->setRoute($route);
		$entity->setQuery($query);
		$entity->setFlags(EntityRoutes::FLAG_DATABASE | $entity->getFlags());

		$builder = $this->_connection->createQueryBuilder();
		$sqlQuery = $builder->select('*')->from($this->_table)
			->andWhere(EntityRoutes::PROP_ROUTE.'=?')
			->andWhere(EntityRoutes::PROP_QUERY.'=?')
			->getSQL();

		$statement = $this->_connection->prepare($sqlQuery);
		$statement->setFetchMode(PDO::FETCH_ASSOC);

		if ($statement->execute([ $entity->getRoute(), $entity->getQuery() ]) && $record = $statement->fetch())
		{
			return $this->_newEntityFromArray($record);
		}

		$entity->setFlags($entity->getFlags() & ~EntityRoutes::FLAG_DATABASE);
		return $entity;
	}

	/**
	 * @param string $fileName
	 * @return RoutesInterface|null
	 * @throws \Doctrine\DBAL\DBALException
	 */
	public function getByFileName($fileName)
	{
		$builder = $this->_connection->createQueryBuilder();
		$sqlQuery = $builder->select('*')->from($this->_table)
			->andWhere(EntityRoutes::PROP_FILE.'=?')
			->setMaxResults(1)
			->getSQL();

		$statement = $this->_connection->prepare($sqlQuery);
		$statement->setFetchMode(PDO::FETCH_ASSOC);

		if ($statement->execute([ $fileName ]) && $record = $statement->fetch())
		{
			return $this->_newEntityFromArray($record);
		}

		return null;
	}

	/**
	 * @param string|array $tag
	 * @return $this
	 * @throws Exception
	 * @throws \Doctrine\DBAL\ConnectionException
	 */
	public function setCompileFlagForTag($tag)
	{
		try
		{
			$this->_connection->beginTransaction();

			foreach ((array)$tag as $tagEx)
			{
				foreach ($this->selectEntities(null, null, null, 'tag', $tagEx) as $entity)
				{
					$entity->setCompileFlag(true);
					$this->commit($entity);
				}
			}

			$this->_connection->commit();
		}
		catch (Exception $exception)
		{
			$this->_connection->rollBack();
			throw $exception;
		}

		return $this;
	}

	/**
	 * @return RoutesInterface[]
	 * @throws \Doctrine\DBAL\DBALException
	 */
	public function selectActiveOnly()
	{
		$list = [];

		$builder = $this->_connection->createQueryBuilder();
		$sqlQuery = $builder->select('*')->from($this->_table)->where(EntityRoutes::PROP_COMPILE_FLAG.'=1')->getSQL();
		$statement = $this->_connection->prepare($sqlQuery);

		foreach ($statement->execute() ? $statement->fetchAll(PDO::FETCH_ASSOC) :[] as $record)
		{
			$list[] = $this->_newEntityFromArray($record);
		}

		return $list;
	}

	/**
	 * @return RoutesInterface[]
	 * @throws \Doctrine\DBAL\DBALException
	 */
	public function selectInnerOnly()
	{
		$list = [];

		$builder = $this->_connection->createQueryBuilder();
		$sqlQuery = $builder->select('*')->from($this->_table)->where(EntityRoutes::PROP_COMPILE_FLAG.'=2')->getSQL();
		$statement = $this->_connection->prepare($sqlQuery);

		foreach ($statement->execute() ? $statement->fetchAll(PDO::FETCH_ASSOC) :[] as $record)
		{
			$list[] = $this->_newEntityFromArray($record);
		}

		return $list;
	}

	/**
	 * @param string|array $route
	 * @return RoutesInterface[]
	 * @throws \Doctrine\DBAL\DBALException
	 */
	public function selectByRoute($route)
	{
		$list = [];

		$routes = is_array($route) ? $route : func_get_args();
		$builder = $this->_connection->createQueryBuilder();
		$builder->select('*')->from($this->_table);

		for ($i = count($routes); $i; $i--)
		{
			$builder->orWhere(EntityRoutes::PROP_ROUTE.'=?');
		}

		$statement = $this->_connection->prepare($builder->getSQL());

		foreach ($statement->execute($routes) ? $statement->fetchAll(PDO::FETCH_ASSOC) : [] as $record)
		{
			$list[] = $this->_newEntityFromArray($record);
		}

		return $list;
	}

	/**
	 * @param \Moro\Platform\Application $application
	 * @param RoutesInterface[] $list
	 * @return Form
	 */
	public function createAdminListForm(Application $application, $list)
	{
		$service = $application->getServiceFormFactory();
		$builder = $service->createBuilder(new RoutesForm($list), array_map(function(RoutesInterface $entity) {
			return (bool)$entity->getCompileFlag();
		}, $list));

		return $builder->getForm();
	}

	/**
	 * @param \Moro\Platform\Application $application
	 * @param Form $form
	 * @return int
	 */
	public function commitAdminListForm(Application $application, Form $form)
	{
		$affected =  0;
		$list = $this->selectEntities();

		foreach ($form->getData() as $code => $value)
		{
			if (empty($list[$code]))
			{
				continue;
			}

			if ($list[$code]->getCompileFlag() != $value)
			{
				$list[$code]->setCompileFlag($value);
				$this->commit($list[$code]);
				$affected++;
			}
		}

		unset($application);
		return $affected;
	}
}