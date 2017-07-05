<?php
/**
 * Class AbstractContent
 */
namespace Moro\Platform\Form;
use \Symfony\Component\Form\AbstractType;
use \Symfony\Component\Form\Extension\Core\Type\HiddenType;
use \Symfony\Component\Form\Extension\Core\Type\SubmitType;
use \Symfony\Component\Form\FormBuilderInterface;

/**
 * Class AbstractContent
 * @package Moro\Platform\Form
 */
abstract class AbstractContent extends AbstractType
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
	 * @return $this
	 */
	public function setId($id)
	{
		$this->_id = (int)$id;
		return $this;
	}

	/**
	 * @param array $tags
	 * @return $this
	 */
	public function setTags(array $tags)
	{
		$this->_tags = $tags;
		return $this;
	}

	/**
	 * @return string
	 *
	public function getName()
	{
		return 'admin_update';
	}
	 */

	/**
	 * @param FormBuilderInterface $builder
	 * @param \Moro\Platform\Model\Implementation\Content\EntityContent[] $options
	 */
	public function buildForm(FormBuilderInterface $builder, array $options)
	{
		$builder->add('comment', HiddenType::class, [
			'required' => false,
		]);

		$builder->add('commit', SubmitType::class, [
			'label' => 'Сохранить',
			'attr' => [
				'data-lock' => '1',
			],
		]);

		$builder->add('apply', SubmitType::class, [
			'label' => 'Применить',
		]);

		$builder->add('cancel', SubmitType::class, [
			'label' => 'Отмена',
		]);

		$builder->add('delete', SubmitType::class, [
			'label' => 'Удалить',
		]);
	}
}