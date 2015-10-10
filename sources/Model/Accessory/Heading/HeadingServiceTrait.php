<?php
/**
 * Trait HeadingServiceTrait
 */
namespace Moro\Platform\Model\Accessory\Heading;
use \Moro\Platform\Model\AbstractService;
use \Moro\Platform\Model\Implementation\Tags\ServiceTags;
use \Moro\Platform\Model\Accessory\Parameters\Tags\TagsServiceInterface;
use \Moro\Platform\Model\Accessory\Parameters\Tags\TagsEntityInterface;
use \Moro\Platform\Model\Accessory\Parameters\Chain\ChainServiceInterface;

/**
 * Trait HeadingServiceTrait
 * @package Moro\Platform\Model\Accessory\Heading
 *
 * @method getTableName()
 */
trait HeadingServiceTrait
{
	/**
	 * @var ServiceTags
	 */
	protected $_traitHeadingTagsService;

	/**
	 * @var array
	 */
	protected $_traitHeadingCache;

	/**
	 * @param ServiceTags $service
	 * @return $this
	 */
	public function setServiceTags(ServiceTags $service)
	{
		$this->_traitHeadingTagsService = $service;
		return $this;
	}

	/**
	 * @return array
	 */
	protected function ___initTraitHeading()
	{
		return [
			AbstractService::STATE_BEFORE_SELECT           => '_headingBeforeSelect',
			TagsServiceInterface::STATE_TAGS_GENERATE      => '_headingTagsGenerate',
			ChainServiceInterface::STATE_BUILD_CHAIN_QUERY => '_headingChainGenerate',
		];
	}

	/**
	 * @param null|int $offset
	 * @param null|int $count
	 * @param null|string $orderBy
	 * @param null|string|array $filter
	 * @param null|mixed $value
	 * @return mixed
	 */
	protected function _headingBeforeSelect($offset, $count, $orderBy, $filter, $value)
	{
		assert(isset($this->_traitHeadingTagsService));

		if (is_string($filter) && $filter == 'heading')
		{
			$value  = $this->_searchHeadingTag($value);
			$filter = $value ? 'tag' :( ($value = 0) ?: 'id');
			return [$offset, $count, $orderBy, $filter, $value];
		}
		elseif (is_array($filter) && false !== $index = array_search('heading', $filter, true))
		{
			if ($tag = $this->_searchHeadingTag($value[$index]))
			{
				unset($filter[$index]);
				unset($value[$index]);

				if (false !== $index = array_search('tag', $filter, true))
				{
					$value[$index] = is_array($value[$index])
						? array_merge($value[$index], [$tag])
						: "$tag,".$value[$index];
				}
				else
				{
					$filter[] = 'tag';
					$value[] = $tag.'.';
				}
			}
			else
			{
				$filter = ['id'];
				$value = [0];
			}

			return [$offset, $count, $orderBy, $filter ? array_values($filter) : null, array_values($value)];
		}

		return null;
	}

	/**
	 * @param string $value
	 * @return string|bool
	 */
	protected function _searchHeadingTag($value)
	{
		if (isset($this->_traitHeadingCache[$value]))
		{
			return $this->_traitHeadingCache[$value];
		}

		$this->_traitHeadingCache[$value] = false;

		if ($list = $this->_traitHeadingTagsService->selectEntities(null, null, null, 'tag', 'heading:'.$value))
		{
			$entity = reset($list);
			$this->_traitHeadingCache[$value] = $entity->getCode();
		}

		return $this->_traitHeadingCache[$value];
	}

	/**
	 * @param array $tags
	 * @return array
	 */
	protected function _headingTagsGenerate($tags)
	{
		$draft  = normalizeTag('флаг: черновик');
		$active = normalizeTag('флаг: опубликовано');

		if (!in_array($active, $tags))
		{
			while (false !== $index = array_search($draft, $tags, true))
			{
				unset($tags[$index]);
			}

			while (true)
			{
				foreach ($tags as $tag)
				{
					$tag = mb_strtolower($tag);

					if ('раздел:' === mb_substr($tag, 0, 7))
					{
						$tags[] = $active;
						break 2;
					}
				}

				$tags[] = $draft;

				break;
			}
		}

		return $tags;
	}

	/**
	 * @param \Doctrine\DBAL\Query\QueryBuilder $builder
	 * @param \ArrayObject $args
	 * @param TagsEntityInterface $entity
	 * @param bool $nextFlag
	 * @return bool
	 */
	protected function _headingChainGenerate($builder, $args, $entity, $nextFlag)
	{
		if ($entity instanceof TagsEntityInterface)
		{
			foreach ($entity->getTags() as $tag)
			{
				$tag = normalizeTag($tag);

				if ('раздел:' === mb_substr($tag, 0, 7))
				{
					$args[':heading'] = $tag;

					$builder->innerJoin('m', $this->getTableName().'_tags', 't', 't.target = m.id');
					$builder->andWhere('t.tag = :heading');
					$builder->groupBy('m.id');
					return true;
				}
			}
		}

		unset($nextFlag);
		return false;
	}
}