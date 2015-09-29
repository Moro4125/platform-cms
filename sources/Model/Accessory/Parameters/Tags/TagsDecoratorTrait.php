<?php
/**
 * Class TagsDecoratorTrait
 */
namespace Moro\Platform\Model\Accessory\Parameters\Tags;

/**
 * Class TagsDecoratorTrait
 * @package Model\Accessory\Parameters\Tags
 */
trait TagsDecoratorTrait
{
	/**
	 * @return array
	 */
	public function getTags()
	{
		return isset($this->_entity) ? $this->_entity->getTags() : [];
	}

	/**
	 * @param array $tags
	 * @return $this
	 */
	public function setTags(array $tags)
	{
		isset($this->_entity) && $this->_entity->setTags($tags);

		return $this;
	}

	/**
	 * @param array $tags
	 * @return $this
	 */
	public function addTags(array $tags)
	{
		isset($this->_entity) && $this->_entity->addTags($tags);

		return $this;
	}

	/**
	 * @param array $tags
	 * @return $this
	 */
	public function delTags(array $tags)
	{
		isset($this->_entity) && $this->_entity->delTags($tags);

		return $this;
	}

	/**
	 * @param string $tag
	 * @return bool
	 */
	public function hasTag($tag)
	{
		return isset($this->_entity) ? $this->_entity->hasTag($tag) : false;
	}
}