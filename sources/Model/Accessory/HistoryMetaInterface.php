<?php
/**
 * Interface HistoryMetaInterface
 */
namespace Moro\Platform\Model\Accessory;

/**
 * Interface HistoryMetaInterface
 * @package Model\Accessory
 */
interface HistoryMetaInterface
{
	const HISTORY_META_PATCH_FIELDS = 1;
	const HISTORY_META_BLACK_FIELDS = 2;
	const HISTORY_META_WHITE_FIELDS = 3;

	/**
	 * @return array
	 */
	function getHistoryMetadata();
}