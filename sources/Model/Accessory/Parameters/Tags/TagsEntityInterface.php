<?php
/**
 * Interface TagsEntityInterface
 */
namespace Moro\Platform\Model\Accessory\Parameters\Tags;

/**
 * Interface TagsEntityInterface
 * @package Model\Accessory\Parameters\Tags
 */
interface TagsEntityInterface
{
	/**
	 * @return array
	 */
	function getTags();

	/**
	 * @param array $tags
	 * @return $this
	 */
	function setTags(array $tags);

	/**
	 * @param array $tags
	 * @return $this
	 */
	function addTags(array $tags);

	/**
	 * @param array $tags
	 * @return $this
	 */
	function delTags(array $tags);

	/**
	 * @param string $tag
	 * @return bool
	 */
	function hasTag($tag);
}