<?php
/**
 * Class SendMessagesAction
 */
namespace Moro\Platform\Action\Messages;
use \Moro\Platform\Action\AbstractSilentAction;
use \Moro\Platform\Model\Implementation\Messages\MessagesInterface;
use \Moro\Platform\Application;
use \Exception;

/**
 * Class SendMessagesAction
 * @package Action\Messages
 *
 * @method \Moro\Platform\Model\Implementation\Messages\ServiceMessages getService()
 * @method \Moro\Platform\Model\Implementation\Messages\EntityMessages[] getEntities()
 */
class SendMessagesAction extends AbstractSilentAction
{
	public $serviceCode = Application::SERVICE_MESSAGES;
	public $route       = 'admin-content-messages-send';
	public $routeIndex  = 'admin-content-messages';

	/**
	 * @throws \Exception
	 */
	protected function _execute()
	{
		$app = $this->getApplication();
		$service = $this->getService();
		$time = time();

		try
		{
			$app->getServiceDataBase()->beginTransaction();

			foreach ($this->getEntities() as $entity)
			{
				if ($entity->getStatus() == MessagesInterface::STATUS_DRAFT)
				{
					$entity->setStatus(MessagesInterface::STATUS_COMPLETED);
					$entity->setOrderAt($time);
					$service->commit($entity);
				}
			}

			$app->getServiceDataBase()->commit();
		}
		catch (Exception $exception)
		{
			$app->getServiceDataBase()->rollBack();
			throw $exception;
		}
	}
}