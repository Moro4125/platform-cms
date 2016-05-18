<?php
/**
 * Class UsersForm
 */
namespace Moro\Platform\Form;
use \Symfony\Component\Form\FormBuilderInterface;
use \Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use \Symfony\Component\Form\Extension\Core\Type\PasswordType;
use \Symfony\Component\Validator\Constraints\NotBlank;
use \Symfony\Component\Validator\Constraints\Regex;
use \Moro\Platform\Form\Constraints\UniqueField;
use \Moro\Platform\Model\Implementation\Users\UsersInterface;
use \Moro\Platform\Application;

/**
 * Class UsersForm
 * @package Moro\Platform\Form
 */
class UsersForm extends AbstractContent
{
	/**
	 * @var integer
	 */
	protected $_id;

	/**
	 * @var array
	 */
	protected $_tags;

	/**
	 * @param int $id
	 * @param array $tags
	 */
	public function __construct($id, array $tags)
	{
		$this->_id = $id;
		$this->_tags = $tags;
	}

	/**
	 * @return string
	 */
	public function getName()
	{
		return 'admin_update';
	}

	/**
	 * @param FormBuilderInterface $builder
	 * @param array $options
	 */
	public function buildForm(FormBuilderInterface $builder, array $options)
	{
		$builder->setMethod('POST');

		$builder->add('name', 'text', [
			'label' => 'Псевдоним',
			'constraints' => [
				new NotBlank(['message' => 'Необходимо заполнить поле "Псевдоним".']),
				new Regex([
					'message' => 'Поле "Псевдоним" не должно содержать символа запятая.',
					'pattern' => '{^[^,]*$}',
				]),
			],
			'required' => false,
			'attr' => [
				'autofocus' => 'autofocus',
			],
		]);

		$builder->add('email', 'text', [
			'label' => 'E-mail',
			'constraints' => [
				new UniqueField([
					'message'  => 'Email пользователя должен быть уникальным.',
					'table'    => Application::getInstance()->getServiceUsers()->getTableName(),
					'field'    => UsersInterface::PROP_EMAIL,
					'ignoreId' => $this->_id,
					'dbal'     => Application::getInstance()->getServiceDataBase(),
				]),
			],
			'required' => true,
		]);

		$builder->add('first_name', 'text', [
			'label' => 'Имя',
			'required' => false,
		]);

		$builder->add('second_name', 'text', [
			'label' => 'Фамилия',
			'required' => false,
		]);

		$builder->add('patronymic', 'text', [
			'label' => 'Отчество',
			'required' => false,
		]);

		$builder->add('password', RepeatedType::class, [
			'type' => PasswordType::class,
			'invalid_message' => 'Значение в полях ввода пароля должны совпадать',
			'options' => ['attr' => ['class' => 'password-field']],
			'required' => false,
			'first_options'  => ['label' => 'Пароль'],
			'second_options' => ['label' => 'Повтор'],
		]);

		$builder->add('tags', 'choice_tags', [
			'label'    => 'Ярлыки',
			'filter'   => 'service:'.Application::SERVICE_USERS,
			'multiple' => true,
			'required' => false,
			'choices'  => array_combine($this->_tags, $this->_tags),
		]);

		// security.role_hierarchy

		$builder->add('roles', 'choice_roles', [
			'label'    => 'Группы',
			'multiple' => true,
			'required' => false,
		]);

		parent::buildForm($builder, $options);
	}
}