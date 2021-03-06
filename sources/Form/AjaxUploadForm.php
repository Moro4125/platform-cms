<?php
/**
 * Class AjaxUploadForm
 */
namespace Moro\Platform\Form;
use \Symfony\Component\Form\AbstractType;
use \Symfony\Component\Form\FormBuilderInterface;

/**
 * Class AjaxUploadForm
 * @package Form
 */
class AjaxUploadForm extends AbstractType
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
		return 'admin_ajax_upload';
	}

	/**
	 * @param FormBuilderInterface $builder
	 * @param \Moro\Platform\Model\Implementation\Content\EntityContent[] $options
	 */
	public function buildForm(FormBuilderInterface $builder, array $options)
	{
		$builder->setMethod('POST');

		$builder->add('uploads', 'file', [
			'label' => ' ',
			'multiple' => true,
			'required' => false,
			'attr' => ['class' => 'file-loading', 'data-upload-url' => $this->_action],
		]);
	}
}