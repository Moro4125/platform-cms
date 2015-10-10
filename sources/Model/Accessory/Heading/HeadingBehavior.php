<?php
/**
 * Class HeadingBehavior
 */
namespace Moro\Platform\Model\Accessory\Heading;
use \Moro\Platform\Model\AbstractBehavior;

/**
 * Class HeadingBehavior
 * @package Moro\Platform\Model\Accessory\Heading
 */
class HeadingBehavior extends AbstractBehavior
{
	use HeadingServiceTrait;

	/**
	 * @param \Moro\Platform\Model\AbstractService $service
	 */
	protected function _initContext($service)
	{
		$this->_context['table'] = $service->getTableName();
		$this->_context[self::KEY_HANDLERS] = $this->___initTraitHeading();
	}

	/**
	 * @return string
	 */
	public function getTableName()
	{
		return $this->_context['table'];
	}
}