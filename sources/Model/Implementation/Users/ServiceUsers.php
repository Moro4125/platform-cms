<?php
/**
 * Class ServiceUsers
 */
namespace Moro\Platform\Model\Implementation\Users;
use \Moro\Platform\Model\EntityInterface;
use \Moro\Platform\Model\AbstractService;
use \Moro\Platform\Model\Accessory\ContentActionsInterface;
use \Moro\Platform\Model\Accessory\Parameters\Tags\TagsServiceInterface;
use \Moro\Platform\Model\Implementation\History\HistoryInterface;
use \Moro\Platform\Model\Implementation\Users\Auth\UsersAuthInterface;
use \Moro\Platform\Model\Implementation\Users\Auth\ServiceUsersAuth;
use \Moro\Platform\Model\Exception\EntityNotFoundException;
use \Moro\Platform\Form\Index\AbstractIndexForm;
use \Moro\Platform\Form\UsersForm;
use \Moro\Platform\Application;
use \Symfony\Component\HttpFoundation\Request;
use \Symfony\Component\Form\Form;
use \Symfony\Component\Security\Core\Encoder\Pbkdf2PasswordEncoder;
use \ArrayObject;
use \PDO;
use \Exception;

/**
 * Class ServiceUsers
 * @package Moro\Platform\Model\Implementation\Users
 *
 * @method UsersInterface getEntityById($id, $withoutException = null, $flags = null)
 */
class ServiceUsers extends AbstractService implements ContentActionsInterface, TagsServiceInterface
{
	use \Moro\Platform\Model\Accessory\UpdatedBy\UpdatedByServiceTrait;
	use \Moro\Platform\Model\Accessory\Parameters\Tags\TagsServiceTrait;
	use \Moro\Platform\Model\Accessory\Parameters\Star\StarServiceTrait;
	use \Moro\Platform\Model\Accessory\LockTrait;
	use \Moro\Platform\Model\Accessory\MonologServiceTrait;

	protected $_table = 'users';

	/**
	 * @var ServiceUsersAuth
	 */
	protected $_serviceAuth;

	/**
	 * @return void
	 */
	protected function _initialization()
	{
		parent::_initialization();
		$this->_traits[static::class][self::STATE_TAGS_GENERATE] = '_tagsGeneration';
		$this->_traits[static::class][self::STATE_COMMIT_STARTED] = '_onCommitStarted';
		$this->_traits[static::class][self::STATE_DELETE_FINISHED] = '_onDeleteFinished';
		$this->_traits[static::class][HistoryInterface::STATE_TRY_MERGE_HISTORY] = '_mergeHistory';
	}

	/**
	 * @param array $tags
	 * @param EntityUsers $entity
	 * @return array
	 */
	protected function _tagsGeneration($tags, $entity)
	{
		/** @var \Moro\Platform\Model\Accessory\Parameters\Tags\TagsServiceBehavior $that */
		$that = $this;
		$args = $entity->getParameters();

		foreach (['first_name', 'second_name', 'patronymic'] as $key)
		{
			foreach (empty($args[$key]) ? [] : array_filter(array_map('trim', explode(' ', $args[$key]))) as $word)
			{
				if (strlen($word) > 2)
				{
					$tags[] = normalizeTag($word);
				}
			}
		}

		$service = $that->getTagsService();

		if (empty($args['roles']))
		{
			$tags[] = normalizeTag('Группа: ∅'); // ⁃※∞∻
		}
		else
		{
			foreach ($args['roles'] as $role)
			{
				if ($tagEntity = $service->getEntityByTag(strtr($role, ['ROLE_' => 'Role: '])))
				{
					$tags[] = normalizeTag($tagEntity->getName());
				}
			}
		}

		$list = $this->_serviceAuth->selectEntities(0, null, null, UsersAuthInterface::PROP_USER_ID, $entity->getId());

		if (empty($list))
		{
			$tags[] = normalizeTag('Регистрация: ∅'); // ⁃※∞∻
		}
		else
		{
			foreach ($list as $enter)
			{
				$tags[] = normalizeTag('Регистрация: '.$enter->getProperty(UsersAuthInterface::PROP_PROVIDER));
			}
		}

		return $tags;
	}

	/**
	 * @param ArrayObject $next
	 * @param ArrayObject $prev
	 */
	protected function _mergeHistory(ArrayObject $next, ArrayObject $prev)
	{
		foreach ($next->getArrayCopy() as $key => $value)
		{
			if ($key == 'parameters.tags' || $key == 'parameters.roles')
			{
				/** @noinspection PhpUndefinedMethodInspection Call history helper function. */
				$this->historyMergeList($key, $next, $prev);
			}
			elseif (in_array($key, ['name', 'email', 'parameters.first_name', 'parameters.second_name', 'parameters.patronymic']))
			{
				/** @noinspection PhpUndefinedMethodInspection Call history helper function. */
				$this->historyMergeSimple($key, $next, $prev);
			}
		}
	}

	/**
	 * @param UsersInterface $entity
	 */
	protected function _onCommitStarted(UsersInterface $entity)
	{
		$id = (int)$entity->getId();

		$builder = $this->_connection->createQueryBuilder()
			->select('id')
			->from($this->_table)
			->where(UsersInterface::PROP_NAME.'=?')
			->andWhere(UsersInterface::PROP_ID.'<>?');
		$statement = $this->_connection->prepare($builder->getSQL());

		if ($statement->execute([$entity->getName(), $id]) && $result = $statement->fetch())
		{
			$statement->closeCursor();
			$entity->setName(preg_replace('{\\s*\\d+\\s*$}', '', $entity->getName()).' '.mt_rand(1000, 9999));
			$this->_onCommitStarted($entity);
		}
	}

	/**
	 * @param UsersInterface $entity
	 * @throws Exception
	 */
	protected function _onDeleteFinished(UsersInterface $entity)
	{
		$service = $this->_serviceAuth;
		$list = $service->selectEntities(null, null, null, UsersAuthInterface::PROP_USER_ID, $entity->getId());
		$service->deleteEntitiesById(array_map(function(UsersAuthInterface $entity) { return $entity->getId(); }, $list));
	}

	/**
	 * @param ServiceUsersAuth $service
	 * @return $this;
	 */
	public function setServiceUsersAuth(ServiceUsersAuth $service)
	{
		$this->_serviceAuth = $service;
		return $this;
	}

	/**
	 * @param string $email
	 * @param null|bool $withoutCommit
	 * @return UsersInterface
	 */
	public function createEntity($email, $withoutCommit = null)
	{
		$fields = [UsersInterface::PROP_EMAIL => $email];
		$entity = $this->_newEntityFromArray($fields, EntityInterface::FLAG_GET_FOR_UPDATE);

		$withoutCommit || $this->commit($entity);
		return $entity;
	}

	/**
	 * @param string $code
	 * @param null|int $flags
	 * @param null|bool $withoutException
	 * @return UsersInterface|null
	 *
	 * @throws \Doctrine\DBAL\DBALException
	 */
	public function getEntityByCode($code, $withoutException = null, $flags = null)
	{
		assert(is_string($code));

		$builder = $this->_connection->createQueryBuilder();
		$sqlQuery = $builder->select('*')->from($this->_table)->where(UsersInterface::PROP_EMAIL.'=?')->getSQL();
		$statement = $this->_connection->prepare($sqlQuery);

		if ($statement->execute([ (string)$code ]) && $record = $statement->fetch(PDO::FETCH_ASSOC))
		{
			return $this->_newEntityFromArray($record, $flags);
		}

		if (empty($withoutException))
		{
			$message = sprintf(EntityNotFoundException::M_NOT_FOUND, 'EMAIL', $code);
			throw new EntityNotFoundException($message, EntityNotFoundException::C_BY_CODE);
		}

		return null;
	}

	/**
	 * @param null|integer $offset
	 * @param null|integer $count
	 * @param null|integer $order
	 * @param null|string $where
	 * @param null|string $value
	 * @return \Moro\Platform\Model\EntityInterface[]
	 */
	public function selectEntitiesForAdminListForm($offset = null, $count = null, $order = null, $where = null, $value = null)
	{
		$list  = $this->selectEntities($offset, $count, $order, $where, $value, EntityInterface::FLAG_GET_FOR_UPDATE);

		if ($this->_userToken)
		{
			$user  = '+star:'.$this->_userToken->getUsername();
			$stars = $this->selectEntities(0, ceil($count / 3), '!updated_at', 'tag', $user, EntityInterface::FLAG_GET_FOR_UPDATE);
			$list  = array_merge($stars, $list);
		}

		return $list;
	}

	/**
	 * @param null|string|array $where
	 * @param null|string|array $value
	 * @return int
	 */
	public function getCountForAdminListForm($where = null, $value = null)
	{
		return $this->getCount($where, $value, EntityInterface::FLAG_GET_FOR_UPDATE);
	}

	/**
	 * @param null|string $email
	 * @return UsersInterface
	 */
	public function createNewEntityWithId($email = null)
	{
		assert(is_string($email) && strlen($email));
		return $this->createEntity($email);
	}

	/**
	 * @param Application $application
	 * @param null|integer $offset
	 * @param null|integer $count
	 * @param null|string $order
	 * @param null|string $where
	 * @param null|string $value
	 * @return \Symfony\Component\Form\FormInterface
	 */
	public function createAdminListForm(Application $application, $offset = null, $count = null, $order = null, $where = null, $value = null)
	{
		$list = $this->selectEntitiesForAdminListForm($offset, $count, $order, $where, $value);

		$application->extend(AbstractIndexForm::class, function(AbstractIndexForm $form, $app) use ($list) {
			$form->setApplication($app);
			$form->setList($list);
			return $form;
		});

		$service = $application->getServiceFormFactory();
		$dataArr = array_fill_keys(array_keys($list), false);
		$builder = $service->createNamedBuilder('admin_list', AbstractIndexForm::class, $dataArr);

		return $builder->getForm();
	}

	/**
	 * @param Application $application
	 * @param UsersInterface|EntityInterface $entity
	 * @param Request $request
	 * @return \Symfony\Component\Form\FormInterface
	 */
	public function createAdminUpdateForm(Application $application, EntityInterface $entity, Request $request)
	{
		$args = $entity->getParameters();
		$tags = @$request->get('admin_update')['tags'] ?: [];
		$tags = array_unique(array_merge($tags, isset($args['tags']) ? $args['tags'] : []));

		$data = [
			'name'         => $entity->getName(),
			'email'       => $entity->getEmail(),
			'first_name'  => empty($args['first_name'])  ? '' : $args['first_name'],
			'second_name' => empty($args['second_name']) ? '' : $args['second_name'],
			'patronymic'  => empty($args['patronymic'])  ? '' : $args['patronymic'],
			'tags' => isset($args['tags']) ? $args['tags'] : [],
			'roles' => isset($args['roles']) ? $args['roles'] : [],
		];

		$application->extend(UsersForm::class, function(UsersForm $form) use ($entity, $tags) {
			$form->setId($entity->getId());
			$form->setTags($tags);
			return $form;
		});

		$service = $application->getServiceFormFactory();
		$builder = $service->createNamedBuilder('admin_update', UsersForm::class, $data);

		return $builder->getForm();
	}

	/**
	 * @param \Moro\Platform\Application $application
	 * @param UsersInterface|\Moro\Platform\Model\EntityInterface $entity
	 * @param Form $form
	 */
	public function applyAdminUpdateForm(Application $application, EntityInterface $entity, Form $form)
	{
		$data = $form->getData();
		$parameters = $entity->getParameters();

		$this->_connection->beginTransaction();

		try
		{
			foreach (isset($parameters['roles']) ? $parameters['roles'] : [] as $role)
			{
				if (!$application->isGranted('ROLE_RS_GROUP'.substr($role, 4)))
				{
					$application->getServiceFlash()->error('У Вас недостаточно прав на изменение записи этого пользователя.');
					return;
				}
			}

			foreach (isset($data['roles']) ? array_values($data['roles']): [] as $role)
			{
				if (!$application->isGranted('ROLE_RS_GROUP'.substr($role, 4)))
				{
					$application->getServiceFlash()->error('У Вас недостаточно прав на данное изменение прав доступа.');
					return;
				}
			}

			$service = $this->_serviceAuth;

			if (!empty($data['password']))
			{
				$data['comment'] = "* **Пароль** был изменён.\n\n".$data['comment'];

				$identifier = $data['email'];
				$credential = (new Pbkdf2PasswordEncoder())->encodePassword($data['password'], $data['email']);
				$filter = [
					UsersAuthInterface::PROP_USER_ID => $entity->getId(),
					UsersAuthInterface::PROP_PROVIDER => UsersAuthInterface::MAIN_PROVIDER,
				];

				if ($auth = $service->selectEntities(0, 1, null, $filter, null, UsersInterface::FLAG_GET_FOR_UPDATE))
				{
					$auth = reset($auth);
				}
				else
				{
					$auth = $service->createEntity(UsersAuthInterface::MAIN_PROVIDER, $identifier, $credential, true);
				}

				$auth->setProperty(UsersAuthInterface::PROP_USER_ID,    $entity->getId());
				$auth->setProperty(UsersAuthInterface::PROP_PROVIDER,   UsersAuthInterface::MAIN_PROVIDER);
				$auth->setProperty(UsersAuthInterface::PROP_IDENTIFIER, $identifier);
				$auth->setProperty(UsersAuthInterface::PROP_CREDENTIAL, $credential);

				$this->_serviceAuth->commit($auth);
			}

			$parameters['tags']        = array_values($data['tags']);
			$parameters['roles']       = array_values($data['roles']);
			$parameters['first_name']  = $data['first_name'];
			$parameters['second_name'] = $data['second_name'];
			$parameters['patronymic']  = $data['patronymic'];
			$parameters['comment']     = $data['comment'];

			$entity->setName($data['name']);
			$entity->setEmail($data['email']);
			$entity->setParameters($parameters);

			foreach ($service->selectEntitiesByUser($entity, UsersInterface::FLAG_SYSTEM_CHANGES) as $auth)
			{
				$auth->setProperty(UsersAuthInterface::PROP_ROLES, implode(',', $parameters['roles']));
				$service->commit($auth);
			}

			$this->commit($entity);
			$this->_connection->commit();
			$application->getServiceFlash()->success('Изменения в записи пользователя были успешно сохранены.');
		}
		catch (Exception $exception)
		{
			$this->_connection->rollBack();
			$application->getServiceFlash()->error(get_class($exception).': '.$exception->getMessage());
			($sentry = $application->getServiceSentry()) && $sentry->captureException($exception);
		}

		unset($application);
	}

	/**
	 * @param int $id
	 * @param null|bool $withoutException
	 * @param \Moro\Platform\Application $application
	 * @return bool|null
	 */
	public function deleteEntityById($id, $withoutException = null, $application = null)
	{
		if ($entity = $this->getEntityById($id))
		{
			$parameters = $entity->getParameters();

			foreach (isset($parameters['roles']) ? $parameters['roles'] : [] as $role)
			{
				if (!$application->isGranted('ROLE_RS_GROUP'.substr($role, 4)))
				{
					$application->getServiceFlash()->error('У Вас недостаточно прав для удаления записи этого пользователя.');
					return null;
				}
			}
		}

		return parent::deleteEntityById($id, $withoutException);
	}
}