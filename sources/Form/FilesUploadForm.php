<?php
/**
 * Class FilesUploadForm
 */
namespace Moro\Platform\Form;
use \Symfony\Component\Form\AbstractType;
use \Symfony\Component\Form\FormBuilderInterface;

/**
 * Class FilesUploadForm
 * @package Form
 */
class FilesUploadForm extends AbstractType
{
	/**
	 * @var string
	 */
	protected $_action;

	/**
	 * @param string $action
	 */
	public function __construct($action)
	{
		$this->_action = $action;
	}

	/**
	 * @return string
	 */
	public function getName()
	{
		return 'admin_upload';
	}

	/**
	 * @param FormBuilderInterface $builder
	 * @param \Moro\Platform\Model\Implementation\Content\EntityContent[] $options
	 */
	public function buildForm(FormBuilderInterface $builder, array $options)
	{
		$builder->setMethod('POST');
		$builder->setAction($this->_action);

		$builder->add('uploads', 'file', [
			'label' => 'Загрузка изображений на сервер',
			'multiple' => true,
			'required' => false,
			'attr' => ['class' => 'file-loading'],
		]);
	}
}