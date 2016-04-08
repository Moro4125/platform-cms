<?php
/**
 * Class ChunkForm
 */
namespace Moro\Platform\Form;
use \Symfony\Component\Form\FormBuilderInterface;

/**
 * Class ChunkForm
 * @package Form
 */
class ChunkForm extends AbstractContent
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

		$builder->add('lead', 'textarea', [
			'label'    => 'Описание',
			'required' => false,
			'attr' => ['placeholder' => 'Лид'],
		]);

		$builder->add('gallery', 'choice_image', [
			'label'    => 'Фотографии',
			'multiple' => true,
			'required' => false,
		]);

		$builder->add('gallery_text', 'textarea', [
			'label'    => 'Описание',
			'attr'     => ['placeholder' => 'Описание'],
			'required' => false,
		]);

		$builder->add('articles', 'choice_article', [
			'label'    => 'Связанные материалы',
			'multiple' => true,
			'required' => false,
		]);

		parent::buildForm($builder, $options);

		$builder->add('get_chunk', 'submit', [
			'label' => 'Выбрать часть',
		]);

		$builder->add('add_chunk', 'submit', [
			'label' => 'Добавить часть',
		]);

		$builder->add('del_chunk', 'submit', [
			'label' => 'Удалить часть',
		]);
	}
}