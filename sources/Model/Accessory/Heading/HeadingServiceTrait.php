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
	 * @param string $name
	 * @return null|string
	 */
	public function getHeadingCodeByTagName($name)
	{
		assert('is_string($name)');
		$name = normalizeTag($name);

		if ('раздел:' === mb_substr($name, 0, 7))
		{
			if ($entity = $this->_traitHeadingTagsService->getEntityByCode($name, true))
			{
				foreach ($entity->getTags() as $tag)
				{
					$tag = normalizeTag($tag);

					if (strncmp('heading:', $tag, 8) === 0)
					{
						return substr($tag, 8);
					}
				}
			}
		}

		return null;
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
	 * @param \ArrayObject $args
	 */
	protected function _headingBeforeSelect($args)
	{
		assert(isset($this->_traitHeadingTagsService));

		if (is_string($args['filter']) && $args['filter'] == 'heading')
		{
			$args['value']  = $this->_searchHeadingTag($args['value']);
			$args['filter'] = $args['value'] ? 'tag' :( ($args['value'] = 0) ?: 'id');
		}
		elseif (is_array($args['filter']) && false !== $index = array_search('heading', $args['filter'], true))
		{
			$filter = $args['filter'];
			$value  = $args['value'];

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

			$args['filter'] = $filter ? array_values($filter) : null;
			$args['value']  = array_values($value);
		}
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
		$erased = normalizeTag('флаг: удалено');

		if (in_array($erased, $tags))
		{
			while (false !== $index = array_search($active, $tags, true))
			{
				unset($tags[$index]);
			}
		}
		elseif (!in_array($active, $tags))
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
			$result = false;

			foreach ($entity->getTags() as $tag)
			{
				$tag = normalizeTag($tag);

				if ($tag == 'флаг:удалено')
				{
					return false;
				}

				if ('раздел:' === mb_substr($tag, 0, 7))
				{
					$args[':heading'] = $tag;

					$builder->innerJoin('m', $this->getTableName().'_tags', 't', 't.target = m.id');
					$builder->andWhere('t.tag = :heading');
					$builder->groupBy('m.id');
					$result = true;
				}
			}

			return $result;
		}

		unset($nextFlag);
		return false;
	}
}