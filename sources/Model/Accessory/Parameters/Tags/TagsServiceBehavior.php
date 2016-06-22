<?php
/**
 * Class TagsServiceBehavior
 */
namespace Moro\Platform\Model\Accessory\Parameters\Tags;
use \Moro\Platform\Model\AbstractBehavior;
use \Moro\Platform\Model\AbstractService;
use \Moro\Platform\Model\Implementation\Tags\ServiceTags;
use \Moro\Platform\Model\Implementation\Tags\TagsInterface;

/**
 * Class TagsServiceBehavior
 * @package Model\Accessory\Parameters\Tags
 */
class TagsServiceBehavior extends AbstractBehavior
{
	/**
	 * @var \Moro\Platform\Application
	 */
	protected $_application;

	/**
	 * @param \Moro\Platform\Application $application
	 * @return $this
	 */
	public function setApplication($application)
	{
		$this->_application = $application;
		return $this;
	}

	/**
	 * @return ServiceTags
	 */
	public function getTagsService()
	{
		return $this->_application->getServiceTags();
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
	 * @param \Moro\Platform\Model\EntityInterface|\Moro\Platform\Model\Accessory\Parameters\ParametersInterface $entity
	 * @param string $table
	 */
	protected function _handlerCommitFinished($entity, $table)
	{
		$tags = [];
		$id = $entity->getId();

		$parameters = $entity->getParameters();
		$userTags = isset($parameters['tags']) ? $parameters['tags'] : [];
		$userTags = array_filter(array_map('normalizeTag', $userTags));

		if ($userTags && !in_array(normalizeTag('флаг: удалено'), $userTags))
		{
			foreach ($userTags as $tagCode)
			{
				foreach ($this->getTagsService()->selectEntities(0, 10, null, ['tag', 'kind'], [$tagCode, TagsInterface::KIND_SYNONYM]) as $tag)
				{
					if (!in_array($tag->getCode(), $userTags))
					{
						$tags[] = $tag->getCode();
					}
				}
			}

			$builder = $this->_application->getServiceDataBase()->createQueryBuilder();
			$sqlQuery = $builder->insert($table.'_tags')->values(['target' => '?', 'tag' => '?'])->getSQL();
			$statement = $this->_application->getServiceDataBase()->prepare($sqlQuery);

			foreach (array_unique($tags) as $tag)
			{
				$statement->execute([ $id, $tag ]);
			}
		}
	}
}