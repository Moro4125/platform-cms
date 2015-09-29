<?php
/**
 * Class TagsServiceBehavior
 */
namespace Moro\Platform\Model\Accessory\Parameters\Tags;
use \Moro\Platform\Model\AbstractBehavior;
use \Moro\Platform\Model\AbstractService;
use \Moro\Platform\Model\Implementation\Tags\ServiceTags;
use \Moro\Platform\Model\Implementation\Tags\TagsInterface;
use \Doctrine\DBAL\Connection;

/**
 * Class TagsServiceBehavior
 * @package Model\Accessory\Parameters\Tags
 */
class TagsServiceBehavior extends AbstractBehavior
{
	/**
	 * @var ServiceTags
	 */
	protected $_service;

	/**
	 * @var Connection
	 */
	protected $_connection;

	/**
	 * @param ServiceTags $service
	 * @return $this
	 */
	public function setTagsService(ServiceTags $service)
	{
		$this->_service = $service;
		return $this;
	}

	/**
	 * @param Connection $connection
	 * @return $this
	 */
	public function setDbConnection(Connection $connection)
	{
		$this->_connection = $connection;
		return $this;
	}

	/**
	 * @param \Moro\Platform\Model\AbstractService $service
	 */
	protected function _initContext($service)
	{
		$this->_context[self::KEY_HANDLERS] = [
			AbstractService::STATE_COMMIT_FINISHED => '_handlerCommitFinished',
		];
	}

	/**
	 * @param \Moro\Platform\Model\AbstractService $owner
	 * @param \Moro\Platform\Model\EntityInterface|\Moro\Platform\Model\Accessory\Parameters\ParametersInterface $entity
	 * @param string $table
	 */
	protected function _handlerCommitFinished($owner, $entity, $table)
	{
		$tags = [];
		$id = $entity->getId();

		$parameters = $entity->getParameters();
		$userTags = isset($parameters['tags']) ? $parameters['tags'] : [];
		$userTags = array_filter(array_map('normalizeTag', $userTags));

		if ($userTags)
		{
			foreach ($userTags as $tagCode)
			{
				/** @var \Moro\Platform\Model\Implementation\Tags\TagsInterface $tag */
				foreach ($this->_service->selectEntities(0, 10, null, ['tag', 'kind'], [$tagCode, TagsInterface::KIND_SYNONYM]) as $tag)
				{
					if (!in_array($tag->getCode(), $userTags))
					{
						$tags[] = $tag->getCode();
					}
				}
			}

			/** @var \Doctrine\DBAL\Query\QueryBuilder $builder */
			$builder = $this->_connection->createQueryBuilder();
			$sqlQuery = $builder->insert($table.'_tags')->values(['target' => '?', 'tag' => '?'])->getSQL();
			$statement = $this->_connection->prepare($sqlQuery);

			foreach (array_unique($tags) as $tag)
			{
				$statement->execute([ $id, $tag ]);
			}
		}

		unset($owner);
	}
}