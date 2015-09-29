<?php
/**
 * Interface TagsServiceInterface
 */
namespace Moro\Platform\Model\Accessory\Parameters\Tags;

/**
 * Interface TagsServiceInterface
 * @package Model\Accessory\Parameters\Tags
 */
interface TagsServiceInterface
{
	const STATE_TAGS_GENERATE = 1001;

	/**
	 * @param null|string|array $tags
	 * @param null|bool $useNamespace
	 * @return array
	 */
	function selectActiveTags($tags = null, $useNamespace = null);
}