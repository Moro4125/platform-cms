<?php
/**
 * Class ContentForm
 */
namespace Moro\Platform\Form;
use \Symfony\Component\Form\FormBuilderInterface;
use \Moro\Platform\Model\Implementation\Content\EntityContent;
use \Symfony\Component\Validator\Constraints\NotBlank;
use \Symfony\Component\Validator\Constraints\Regex;
use \Moro\Platform\Form\Constraints\UniqueField;
use \Moro\Platform\Application;

/**
 * Class ContentForm
 * @package Form
 */
class ContentForm extends AbstractContent
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
	 * @param integer $id
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
	 * @param \Moro\Platform\Model\Implementation\Content\EntityContent[] $options
	 */
	public function buildForm(FormBuilderInterface $builder, array $options)
	{
		$builder->setMethod('POST');

		$builder->add('name', 'text', [
			'label' => 'Название',
		]);

		$builder->add('code', 'text', [
			'label' => 'Код',
			'constraints' => [
				new NotBlank(['message' => 'Необходимо заполнить поле "Символьный код".']),
				new Regex([
					'message' => 'Значение поля "Символьный код" должно состоять только из прописных латинских букв, цифр, дефиса и символа подчёркивания.'
							.' Рекомендуется использовать английские слова и обозначения. ',
					'pattern' => '{^[a-z][-a-z0-9_]*$}',
				]),
				new UniqueField([
					'message' => 'Значение поля "Символьный код" должно быть уникальным для каждого материала.',
					'table' => Application::getInstance()->getServiceContent()->getTableName(),
					'field' => EntityContent::PROP_CODE,
					'ignoreId' => $this->_id,
					'dbal' => Application::getInstance()->getServiceDataBase(),
				]),
			],
			'attr' => ['placeholder' => 'Символьный код', 'title' => 'Уникальный символьный код материала']
		]);

		$builder->add('icon', 'choice_image', [
			'label'    => 'Анонс',
			'required' => false,
		]);

		$builder->add('lead', 'textarea', [
			'label'    => '',
			'required' => false,
			'attr' => ['placeholder' => 'Лид материала']
		]);

		$builder->add('tags', 'choice_tags', [
			'label'    => 'Ярлыки',
			'multiple' => true,
			'required' => false,
			'choices'  => array_combine($this->_tags, $this->_tags),
		]);

		$builder->add('external', 'text', [
			'label' => 'Ссылка',
			'required' => false,
			'attr' => ['placeholder' => 'Только для внешней ссылки', 'title' => 'Ссылка, используемая в анонсе материала.']
		]);

		$builder->add('gallery', 'choice_image', [
			'label'    => 'Фотографии',
			'multiple' => true,
			'required' => false,
		]);

		$builder->add('gallery_text', 'textarea', [
			'label'       => 'Описание',
			'attr' => ['placeholder' => 'Описание'],
			'required'    => false,
		]);

		parent::buildForm($builder, $options);
	}
}