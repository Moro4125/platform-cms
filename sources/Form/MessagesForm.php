<?php
/**
 * Class MessagesForm
 */
namespace Moro\Platform\Form;
use \Symfony\Component\Form\FormBuilderInterface;
use \Symfony\Component\Validator\Constraints\Regex;
use \Symfony\Component\Validator\Constraints\NotBlank;
use \Moro\Platform\Application;

/**
 * Class MessagesForm
 * @package Moro\Platform\Form
 */
class MessagesForm extends AbstractContent
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
			'label' => 'Заголовок',
			'constraints' => [
				new NotBlank(['message' => 'Необходимо заполнить поле "Заголовок".']),
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

		$builder->add('text', 'textarea', [
			'label'    => 'Сообщение',
			'required' => false,
			'attr'	=> [
				'placeholder' => 'Текст сообщения',
			]
		]);

		$builder->add('tags', 'choice_tags', [
			'label'    => 'Ярлыки',
			'filter'   => 'service:'.Application::SERVICE_MESSAGES,
			'multiple' => true,
			'required' => false,
			'choices'  => array_combine($this->_tags, $this->_tags),
		]);

		parent::buildForm($builder, $options);
	}
}