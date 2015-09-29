<?php
/**
 * Interface ContentActionsInterface
 */
namespace Moro\Platform\Model\Accessory;
use \Moro\Platform\Application;
use \Moro\Platform\Model\EntityInterface;
use \Symfony\Component\Form\Form;
use \Symfony\Component\HttpFoundation\Request;

/**
 * Interface ContentActionsInterface
 * @package Model\Accessory
 */
interface ContentActionsInterface
{
	/**
	 * @return \Moro\Platform\Model\EntityInterface
	 */
	function createNewEntityWithId();

	/**
	 * @param \Moro\Platform\Application $application
	 * @param null|integer $offset
	 * @param null|integer $count
	 * @param null|string $order
	 * @param null|string $where
	 * @param null|string $value
	 * @return Form
	 */
	function createAdminListForm(Application $application, $offset = null, $count = null, $order = null, $where = null, $value = null);

	/**
	 * @param null|integer $offset
	 * @param null|integer $count
	 * @param null|string $order
	 * @param null|string $where
	 * @param null|string $value
	 * @return EntityInterface[]
	 */
	function selectEntitiesForAdminListForm($offset = null, $count = null, $order = null, $where = null, $value = null);

	/**
	 * @param \Moro\Platform\Application $application
	 * @param EntityInterface $entity
	 * @param Request $request
	 * @return Form
	 */
	function createAdminUpdateForm(Application $application, EntityInterface $entity, Request $request);

	/**
	 * @param \Moro\Platform\Application $application
	 * @param EntityInterface $entity
	 * @param Form $form
	 */
	function applyAdminUpdateForm(Application $application, EntityInterface $entity, Form $form);

	/**
	 * @param integer $id
	 * @param null|bool $withoutException
	 * @return mixed
	 */
	function getEntityById($id, $withoutException = null);

	/**
	 * @param array $idList
	 * @return integer
	 */
	function deleteEntitiesById(array $idList);

	/**
	 * @param null|string $filter
	 * @param null|string $value
	 * @return int
	 */
	function getCountForAdminListForm($filter = null, $value = null);
}