<?php
/**
 * File with controllers.
 */
namespace Moro\Platform\Action;
use \Moro\Platform\Application;

// ============================================== //
//             Раздел методов админки             //
// ============================================== //
Application::getInstance(function (Application $app)
{
	$admin = (!defined('INDEX_PAGE') || INDEX_PAGE !== 'admin')
		? $app->getServiceControllersFactory()
		: $app;
	$admin instanceof Application || $app->mount('/admin', $admin);

	$actionRules = [
		'admin-prefix'                   => ['/',                             'Tools\\PrefixAction'],
		'admin-about'                    => ['/panel',                        'Tools\\AboutAction'],
		'admin-password'                 => ['security/{login}/{password}',   'Tools\\SecurityAction'],
		'admin-compile-list'             => ['pages',                         'Routes\\IndexRoutesAction'],
		'admin-compile'                  => ['pages/compile',                 'Routes\\CompileRoutesAction'],
		'admin-options'                  => ['options',                       'Tools\\OptionsAction'],
		'admin-content-articles'         => ['content/articles',              'Articles\\IndexArticlesAction'],
		'admin-content-articles-create'  => ['content/article/create',        'Articles\\CreateArticlesAction'],
		'admin-content-articles-update'  => ['content/article/update/{id}',   'Articles\\UpdateArticlesAction'],
		'admin-content-articles-delete'  => ['content/article/delete/{ids}',  'Articles\\DeleteArticlesAction'],
		'admin-content-articles-set-top' => ['content/article/set-top/{id}',  'Articles\\SetTopArticlesAction'],
		'admin-content-articles-set-tag' => ['content/article/set-tag/{ids}', 'Articles\\SetTagArticlesAction'],
		'admin-content-relink'           => ['content/relink',                'Relink\\IndexRelinkAction'],
		'admin-content-relink-create'    => ['content/relink/create',         'Relink\\CreateRelinkAction'],
		'admin-content-relink-update'    => ['content/relink/update/{id}',    'Relink\\UpdateRelinkAction'],
		'admin-content-relink-delete'    => ['content/relink/delete/{ids}',   'Relink\\DeleteRelinkAction'],
		'admin-content-relink-set-tag'   => ['content/relink/set-tag/{ids}',  'Relink\\SetTagRelinkAction'],
		'admin-content-tags'             => ['content/tags',                  'Tags\\IndexTagsAction'],
		'admin-content-tags-create'      => ['content/tag/create',            'Tags\\CreateTagsAction'],
		'admin-content-tags-update'      => ['content/tag/update/{id}',       'Tags\\UpdateTagsAction'],
		'admin-content-tags-delete'      => ['content/tag/delete/{ids}',      'Tags\\DeleteTagsAction'],
		'admin-content-tags-set-tag'     => ['content/tag/set-tag/{ids}',     'Tags\\SetTagTagsAction'],
		'admin-content-images'           => ['content/images',                'Images\\IndexImagesAction'],
		'admin-content-images-select'    => ['content/images/select',         'Images\\IndexAjaxImagesAction'],
		'admin-content-images-update'    => ['content/images/update/{id}',    'Images\\UpdateImagesAction'],
		'admin-content-images-delete'    => ['content/images/delete/{ids}',   'Images\\DeleteImagesAction'],
		'admin-content-images-set-top'   => ['content/images/set-top/{id}',   'Images\\SetTopImagesAction'],
		'admin-content-images-set-tag'   => ['content/images/set-tag/{ids}',  'Images\\SetTagImagesAction'],
		'admin-content-images-upload'    => ['content/images/upload',         'Images\\UploadImagesAction'],
		'admin-image' => ['/images/{salt}/{hash}_{width}_{height}.{format}',  'Images\\ShowImagesAction'],
	];

	$assertRules = [
		'id'     => '\\d+',
		'ids'    => '\\d+(,\\d+)*',
		'salt'   => '[0-9a-v]{2}',
		'hash'   => '[0-9a-v]{32}',
		'width'  => '\\d+',
		'height' => '\\d+',
		'format' => 'jpg|png',
	];

	$convertRules = [
		'id'     => 'str_to_int',
		'width'  => 'str_to_int',
		'height' => 'str_to_int',
	];

	foreach ($actionRules as $route => list($pattern, $class))
	{
		$class = $app->offsetGet($route.'.action.class', ($class[0] == '\\') ? $class : __NAMESPACE__.'\\'.$class);
		$controller = $admin->match(($pattern[0] == '/') ? $pattern : '/panel/'.$pattern, $class)->bind($route);
		preg_match_all('{\\{(.+?)\\}}', $pattern, $matches, PREG_PATTERN_ORDER);

		foreach ($matches[1] as $parameter)
		{
			isset($assertRules[$parameter])  && $controller->assert($parameter,  $assertRules[$parameter]);
			isset($convertRules[$parameter]) && $controller->convert($parameter, $convertRules[$parameter]);
		}
	}
});