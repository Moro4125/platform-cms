<?php
/**
 * Class UsersForm
 */
namespace Moro\Platform\Form;
use \Moro\Platform\Form\Type\RolesChoiceType;
use \Moro\Platform\Form\Type\TagsChoiceType;
use \Symfony\Component\Form\Extension\Core\Type\TextType;
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
	 * @param FormBuilderInterface $builder
	 * @param array $options
	 */
	public function buildForm(FormBuilderInterface $builder, array $options)
	{
		$builder->setMethod('POST');

		$builder->add('name', TextType::class, [
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

		$builder->add('email', TextType::class, [
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

		$builder->add('first_name', TextType::class, [
			'label' => 'Имя',
			'required' => false,
		]);

		$builder->add('second_name', TextType::class, [
			'label' => 'Фамилия',
			'required' => false,
		]);

		$builder->add('patronymic', TextType::class, [
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

		$builder->add('tags', TagsChoiceType::class, [
			'label'    => 'Ярлыки',
			'filter'   => 'service:'.Application::SERVICE_USERS,
			'multiple' => true,
			'required' => false,
			'choices'  => array_combine($this->_tags, $this->_tags),
		]);

		// security.role_hierarchy

		$builder->add('roles', RolesChoiceType::class, [
			'label'    => 'Группы',
			'multiple' => true,
			'required' => false,
		]);

		parent::buildForm($builder, $options);
	}
}