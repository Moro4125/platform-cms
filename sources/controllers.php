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
	$admin instanceof Application || $app->mount(php_sapi_name() == 'cli' ? '/' : '/admin', $admin);

	$actionRules = [
		'compile-site-map'               => ['/sitemap.xml',                    'Routes\\SiteMapAction'],
		'users-register'                 => ['/register.html',                  'Tools\\RegisterAction'],
		'users-login'                    => ['/login.html',                     'Tools\\LoginAction'],
		'users-restore'                  => ['/restore.html',                   'Tools\\RestoreAction'],
		'admin-prefix'                   => ['/',                               'Tools\\PrefixAction'],
		'admin-about-prefix'             => ['/panel/',                         'Tools\\PrefixAction'],
		'admin-about'                    => ['/panel',                          'Tools\\AboutAction'],
		'admin-markdown-help'            => ['help/markdown',                   'Tools\\MarkdownHelpAction'],
		'admin-compile-list'             => ['pages',                           'Routes\\IndexRoutesAction'],
		'admin-compile'                  => ['pages/compile',                   'Routes\\CompileRoutesAction'],
		'admin-options'                  => ['options',                         'Tools\\OptionsAction'],
		'admin-content'                  => ['content',                         'Tools\\PrefixAction'],
		'admin-content-articles'         => ['content/articles',                'Articles\\IndexArticlesAction'],
		'admin-content-articles-select'  => ['content/articles/select',         'Articles\\IndexAjaxArticlesAction'],
		'admin-content-articles-create'  => ['content/articles/create',         'Articles\\CreateArticlesAction'],
		'admin-content-articles-update'  => ['content/articles/update/{id}',    'Articles\\UpdateArticlesAction'],
		'admin-content-articles-attach'  => ['content/articles/attach/{id}',    'Articles\\FileAttach2ArticlesAction'],
		'admin-content-articles-detach'  => ['content/articles/detach/{id}',    'Articles\\FileDetach2ArticlesAction'],
		'admin-content-articles-delete'  => ['content/articles/delete/{ids}',   'Articles\\DeleteArticlesAction'],
		'admin-content-articles-set-top' => ['content/articles/set-top/{id}',   'Articles\\SetTopArticlesAction'],
		'admin-content-articles-set-tag' => ['content/articles/set-tag/{ids}',  'Articles\\SetTagArticlesAction'],
		'admin-content-articles-star'    => ['content/articles/star/{id}',      'Articles\\ToggleStarArticlesAction'],
		'admin-content-chunks-create'    => ['content/articles/create/{id}',    'Articles\\Chunks\\CreateChunksAction'],
		'admin-content-chunks-update'    => ['content/articles/update/{id}/{n}','Articles\\Chunks\\UpdateChunksAction'],
		'admin-content-chunks-delete'    => ['content/articles/delete/{id}/{n}','Articles\\Chunks\\DeleteChunksAction'],
		'admin-content-messages'         => ['content/messages',                'Messages\\IndexMessagesAction'],
		'admin-content-messages-create'  => ['content/messages/create',         'Messages\\CreateMessagesAction'],
		'admin-content-messages-update'  => ['content/messages/update/{id}',    'Messages\\UpdateMessagesAction'],
		'admin-content-messages-attach'  => ['content/messages/attach/{id}',    'Messages\\FileAttach2MessagesAction'],
		'admin-content-messages-detach'  => ['content/messages/detach/{id}',    'Messages\\FileDetach2MessagesAction'],
		'admin-content-messages-delete'  => ['content/messages/delete/{ids}',   'Messages\\DeleteMessagesAction'],
		'admin-content-messages-set-tag' => ['content/messages/set-tag/{ids}',  'Messages\\SetTagMessagesAction'],
		'admin-content-messages-star'    => ['content/messages/star/{id}',      'Messages\\ToggleStarMessagesAction'],
		'admin-content-messages-send'    => ['content/messages/send/{ids}',     'Messages\\SendMessagesAction'],
		'admin-content-relink'           => ['content/relink',                  'Relink\\IndexRelinkAction'],
		'admin-content-relink-create'    => ['content/relink/create',           'Relink\\CreateRelinkAction'],
		'admin-content-relink-update'    => ['content/relink/update/{id}',      'Relink\\UpdateRelinkAction'],
		'admin-content-relink-clone'     => ['content/relink/clone/{id}',       'Relink\\CloneRelinkAction'],
		'admin-content-relink-delete'    => ['content/relink/delete/{ids}',     'Relink\\DeleteRelinkAction'],
		'admin-content-relink-set-tag'   => ['content/relink/set-tag/{ids}',    'Relink\\SetTagRelinkAction'],
		'admin-content-relink-star'      => ['content/relink/star/{id}',        'Relink\\ToggleStarRelinkAction'],
		'admin-content-tags'             => ['content/tags',                    'Tags\\IndexTagsAction'],
		'admin-content-tags-create'      => ['content/tags/create',             'Tags\\CreateTagsAction'],
		'admin-content-tags-update'      => ['content/tags/update/{id}',        'Tags\\UpdateTagsAction'],
		'admin-content-tags-delete'      => ['content/tags/delete/{ids}',       'Tags\\DeleteTagsAction'],
		'admin-content-tags-set-tag'     => ['content/tags/set-tag/{ids}',      'Tags\\SetTagTagsAction'],
		'admin-content-tags-star'        => ['content/tags/star/{id}',          'Tags\\ToggleStarTagsAction'],
		'admin-content-images'           => ['content/images',                  'Images\\IndexImagesAction'],
		'admin-content-images-select'    => ['content/images/select',           'Images\\IndexAjaxImagesAction'],
		'admin-content-images-update'    => ['content/images/update/{id}',      'Images\\UpdateImagesAction'],
		'admin-content-images-delete'    => ['content/images/delete/{ids}',     'Images\\DeleteImagesAction'],
		'admin-content-images-set-top'   => ['content/images/set-top/{id}',     'Images\\SetTopImagesAction'],
		'admin-content-images-set-tag'   => ['content/images/set-tag/{ids}',    'Images\\SetTagImagesAction'],
		'admin-content-images-star'      => ['content/images/star/{id}',        'Images\\ToggleStarImagesAction'],
		'admin-content-images-upload'    => ['content/images/upload',           'Images\\UploadImagesAction'],
		'admin-content-images-watermark' => ['content/images/watermark/{ids}',  'Images\\WatermarkImagesAction'],
		'admin-content-images-mask'      => ['content/images/mask/{ids}',       'Images\\MaskImagesAction'],
		'admin-users'                    => ['users',                           'Tools\\PrefixAction'],
		'admin-users-profiles'           => ['users/profiles',                  'Profiles\\IndexProfilesAction'],
		'admin-users-profiles-create'    => ['users/profiles/create',           'Profiles\\CreateProfilesAction'],
		'admin-users-profiles-update'    => ['users/profiles/update/{id}',      'Profiles\\UpdateProfilesAction'],
		'admin-users-profiles-delete'    => ['users/profiles/delete/{ids}',     'Profiles\\DeleteProfilesAction'],
		'admin-users-profiles-set-tag'   => ['users/profiles/set-tag/{ids}',    'Profiles\\SetTagProfilesAction'],
		'admin-users-profiles-star'      => ['users/profiles/star/{id}',        'Profiles\\ToggleStarProfilesAction'],
		'admin-users-profiles-auth-info' => ['users/profiles/auth-info/{id}',   'Profiles\\AuthInformationAction'],
		'admin-users-profiles-auth-ban'  => ['users/profiles/auth-ban/{id}',    'Profiles\\AuthBanAction'],
		'admin-users-subscribers'        => ['users/subscribers',               'Subscribers\\IndexSubscribersAction'],
		'admin-users-subscribers-create' => ['users/subscribers/create',        'Subscribers\\CreateSubscribersAction'],
		'admin-users-subscribers-update' => ['users/subscribers/update/{id}',   'Subscribers\\UpdateSubscribersAction'],
		'admin-users-subscribers-delete' => ['users/subscribers/delete/{ids}',  'Subscribers\\DeleteSubscribersAction'],
		'admin-users-subscribers-set-tag'=> ['users/subscribers/set-tag/{ids}', 'Subscribers\\SetTagSubscribersAction'],
		'admin-users-subscribers-star'   => ['users/subscribers/star/{id}',     'Subscribers\\ToggleStarSubscribersAction'],
		'admin-image' => ['/images/{salt}/{hash}_{width}_{height}.{format}',    'Images\\ShowImagesAction'],
		'download'    => ['/download/{salt}/{hash}.{extension}',                'Tools\\DownloadAction'],
		'api'                            => ['/platform',                       'Tools\\PrefixAction'],
		'api-content-articles-rss'       => ['content/articles/rss.xml',        'Articles\\ApiRssArticlesAction'],
		'api-content-images-rss'         => ['content/images/rss.xml',          'Images\\ApiRssImagesAction'],
		'api-users-reset-password'       => ['users/reset-password',            'Profiles\\Api\\ResetPasswordAction'],
		'api-users-apply-rights'         => ['users/apply-rights',              'Profiles\\Api\\ApplyRightsAction'],
		'api-users-disable-social'       => ['users/disable-social',            'Profiles\\Api\\DisableSocialAction'],
		'api-subscribers-update'         => ['subscribers/update',              'Subscribers\\Api\\UpdateEntityAction'],
		'api-subscribers-delete'         => ['subscribers/delete',              'Subscribers\\Api\\DeleteEntityAction'],
	];

	$assertRules = [
		'n'         => '\\d+',
		'id'        => '\\d+',
		'ids'       => '\\d+(,\\d+)*',
		'salt'      => '[0-9a-v]{2}',
		'hash'      => '[0-9a-v]{32}',
		'width'     => '\\d+',
		'height'    => '\\d+',
		'format'    => 'jpg|png|gif',
		'extension' => '[0-9a-z]+',
	];

	$convertRules = [
		'n'      => 'str_to_int',
		'id'     => 'str_to_int',
		'width'  => 'str_to_int',
		'height' => 'str_to_int',
	];

	$prefix = '';

	foreach ($actionRules as $route => list($pattern, $class))
	{
		$class = $app->offsetGet($route.'.action.class', ($class[0] == '\\') ? $class : __NAMESPACE__.'\\'.$class);
		$pattern = ($pattern[0] == '/') ?( $prefix = $pattern ): $prefix.'/'.$pattern;
		$controller = $admin->match($pattern, $class)->bind($route);

		if (preg_match_all('{\\{(.+?)\\}}', $pattern, $matches, PREG_PATTERN_ORDER))
		{
			foreach ($matches[1] as $parameter)
			{
				isset($assertRules[$parameter])  && $controller->assert($parameter,  $assertRules[$parameter]);
				isset($convertRules[$parameter]) && $controller->convert($parameter, $convertRules[$parameter]);
			}
		}
	}
});