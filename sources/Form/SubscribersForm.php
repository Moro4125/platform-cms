<?php
/**
 * Class SubscribersForm
 */
namespace Moro\Platform\Form;
use \Symfony\Component\Form\FormBuilderInterface;
use \Symfony\Component\Validator\Constraints\Regex;
use \Moro\Platform\Form\Constraints\UniqueField;
use \Moro\Platform\Model\Implementation\Users\UsersInterface;
use \Moro\Platform\Model\Implementation\Subscribers\SubscribersInterface;
use \Moro\Platform\Application;

/**
 * Class SubscribersForm
 * @package Moro\Platform\Form
 */
class SubscribersForm extends AbstractContent
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
			'label' => 'Имя',
			'constraints' => [
				new Regex([
					'message' => 'Поле "Имя" не должно содержать символа запятая.',
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
					'message'  => 'Email подписчика должен быть уникальным.',
					'table'    => Application::getInstance()->getServiceSubscribers()->getTableName(),
					'field'    => SubscribersInterface::PROP_EMAIL,
					'ignoreId' => $this->_id,
					'dbal'     => Application::getInstance()->getServiceDataBase(),
				]),
			],
			'required' => true,
		]);

		$builder->add('active', 'checkbox', [
			'label'    => 'Активен',
			'required' => false,
		]);

		$builder->add('tags', 'choice_tags', [
			'label'    => 'Ярлыки',
			'filter'   => 'service:'.Application::SERVICE_SUBSCRIBERS,
			'multiple' => true,
			'required' => false,
			'choices'  => array_combine($this->_tags, $this->_tags),
		]);

		parent::buildForm($builder, $options);
	}
}