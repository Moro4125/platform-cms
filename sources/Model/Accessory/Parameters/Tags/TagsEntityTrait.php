<?php
/**
 * Class TagsEntityTrait
 */
namespace Moro\Platform\Model\Accessory\Parameters\Tags;
use \Moro\Platform\Model\Accessory\Parameters\ParametersInterface;
use \BadMethodCallException;

/**
 * Class TagsEntityTrait
 * @package Model\Accessory\Parameters\Tags
 */
trait TagsEntityTrait
{
	/**
	 * @return array
	 */
	public function getTags()
	{
		if ($this instanceof ParametersInterface)
		{
			$parameters = $this->getParameters();
			return empty($parameters['tags']) ? [] : $parameters['tags'];
		}

		throw new BadMethodCallException(__METHOD__);
	}

	/**
	 * @param array $tags
	 * @return $this
	 */
	public function setTags(array $tags)
	{
		if ($this instanceof ParametersInterface)
		{
			$parameters = $this->getParameters();
			$parameters['tags'] = $tags;
			$this->setParameters($parameters);

			return $this;
		}

		throw new BadMethodCallException(__METHOD__);
	}

	/**
	 * @param array $tags
	 * @return $this
	 */
	public function addTags(array $tags)
	{
		if ($this instanceof ParametersInterface)
		{
			$parameters = $this->getParameters();
			$tags = array_merge(empty($parameters['tags']) ? [] : $parameters['tags'], $tags);
			$parameters['tags'] = array_values(array_unique($tags));
			$this->setParameters($parameters);

			return $this;
		}

		throw new BadMethodCallException(__METHOD__);
	}

	/**
	 * @param array $tags
	 * @return $this
	 */
	public function delTags(array $tags)
	{
		if ($this instanceof ParametersInterface)
		{
			$parameters = $this->getParameters();

			$oldTags = array_unique(empty($parameters['tags']) ? [] : $parameters['tags']);
			$oldTags = array_combine($oldTags, array_map('normalizeTag', $oldTags));
			$delTags = array_map('normalizeTag', $tags);

			$parameters['tags'] = array_keys(array_diff($oldTags, $delTags));
			$this->setParameters($parameters);

			return $this;
		}

		throw new BadMethodCallException(__METHOD__);
	}

	/**
	 * @param string $tag
	 * @return bool
	 */
	public function hasTag($tag)
	{
		if ($this instanceof ParametersInterface)
		{
			$tag = normalizeTag($tag);
			$parameters = $this->getParameters();
			return !empty($parameters['tags']) && in_array($tag, array_map('normalizeTag', $parameters['tags']), true);
		}

		throw new BadMethodCallException(__METHOD__);
	}
}