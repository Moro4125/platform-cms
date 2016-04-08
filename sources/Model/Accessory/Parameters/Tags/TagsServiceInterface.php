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
	const STATE_TAGS_DELETING = 1002;

	/**
	 * @param null|string|array $tags
	 * @param null|bool $useNamespace
	 * @param null|string $createdBy
	 * @return array
	 */
	function selectActiveTags($tags = null, $useNamespace = null, $createdBy = null);
}