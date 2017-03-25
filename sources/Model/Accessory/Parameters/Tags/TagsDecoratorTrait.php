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
		/** @noinspection PhpUndefinedMethodInspection */
		return isset($this->_entity) ? $this->_entity->getTags() : [];
	}

	/**
	 * @param array $tags
	 * @return $this
	 */
	public function setTags(array $tags)
	{
		/** @noinspection PhpUndefinedMethodInspection */
		isset($this->_entity) && $this->_entity->setTags($tags);

		return $this;
	}

	/**
	 * @param array $tags
	 * @return $this
	 */
	public function addTags(array $tags)
	{
		/** @noinspection PhpUndefinedMethodInspection */
		isset($this->_entity) && $this->_entity->addTags($tags);

		return $this;
	}

	/**
	 * @param array $tags
	 * @return $this
	 */
	public function delTags(array $tags)
	{
		/** @noinspection PhpUndefinedMethodInspection */
		isset($this->_entity) && $this->_entity->delTags($tags);

		return $this;
	}

	/**
	 * @param string $tag
	 * @return bool
	 */
	public function hasTag($tag)
	{
		/** @noinspection PhpUndefinedMethodInspection */
		return isset($this->_entity) ? $this->_entity->hasTag($tag) : false;
	}
}