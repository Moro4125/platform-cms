<?php
/**
 * Class ImagesUploadForm
 */
namespace Moro\Platform\Form;
use \Symfony\Component\Form\AbstractType;
use \Symfony\Component\Form\Extension\Core\Type\FileType;
use \Symfony\Component\Form\FormBuilderInterface;

/**
 * Class ImagesUploadForm
 * @package Form
 */
class ImagesUploadForm extends AbstractType
{
	/**
	 * @var string
	 */
	protected $_action;

	/**
	 * @param string $action
	 * @return $this
	 */
	public function setAction($action)
	{
		$this->_action = $action;
		return $this;
	}

	/**
	 * @param FormBuilderInterface $builder
	 * @param \Moro\Platform\Model\Implementation\Content\EntityContent[] $options
	 */
	public function buildForm(FormBuilderInterface $builder, array $options)
	{
		$builder->setMethod('POST');
		$builder->setAction($this->_action);

		$builder->add('uploads', FileType::class, [
			'label' => 'Загрузка изображений на сервер',
			'multiple' => true,
			'required' => false,
			'attr' => ['class' => 'file-loading'],
		]);
	}
}