<?php
/**
 * Trait StarServiceTrait
 */
namespace Moro\Platform\Model\Accessory\Parameters\Star;
use \Moro\Platform\Model\Accessory\Parameters\Tags\TagsServiceInterface;
use \Moro\Platform\Model\Accessory\Parameters\ParametersInterface;

/**
 * Trait StarServiceTrait
 * @package Moro\Platform\Model\Accessory\Parameters\Star
 */
trait StarServiceTrait
{
	/**
	 * @return array
	 */
	protected function ___initTraitStar()
	{
		return [
			TagsServiceInterface::STATE_TAGS_GENERATE => '_starTagsGenerate',
		];
	}

	/**
	 * @param array $tags
	 * @param ParametersInterface $entity
	 * @return array
	 */
	protected function _starTagsGenerate(array $tags, ParametersInterface $entity)
	{
		$parameters = $entity->getParameters();

		if (isset($parameters['stars']))
		{
			foreach ($parameters['stars'] as $starBy)
			{
				$tags[] = "+star:$starBy";
			}
		}

		return $tags;
	}
}