<?php
/**
 * File with controllers.
 */
use \Moro\Platform\Application;
use \Symfony\Component\HttpFoundation\Request;
use \Symfony\Component\Security\Core\User\User;

// ============================================== //
//             Раздел методов админки             //
// ============================================== //
Application::getInstance(function (Application $app)
{
	$admin = (!defined('INDEX_PAGE') || INDEX_PAGE !== 'admin')
		? $app->getServiceControllersFactory()
		: $app;
	$admin instanceof Application || $app->mount('/admin', $admin);

	// ===== Редирект на начальную страницу админки.
	$admin->get('/', function() use ($app) {
		return $app->redirect($app->url('admin-about'));
	})->bind('admin-prefix');

	// ===== Страница для генерирование публичного хэша для пароля пользователя.
	$admin->match('/panel/security/{login}/{password}', function($login, $password) use ($app) {
		/** @var Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface $encoder */
		$encoder = $app['security.encoder_factory']->getEncoder(new User($login, $password));
		return $app->json($encoder->encodePassword($password, null));
	});


	// ===== Начальная страница админки с различной информацией.
	$admin->match('/panel', function() use ($app) {
		return $app->render('@PlatformCMS/admin/about.html.twig');
	})->bind('admin-about');


	// ===== Действие по компиляции страниц сайта в статический HTML.
	$admin->match('/panel/compile', 'Moro\\Platform\\Action\\Routes\\CompileRoutesAction')
		->bind('admin-compile');

	// ===== Страница со списком страниц для компиляции.
	$admin->match('/panel/pages', 'Moro\\Platform\\Action\\Routes\\IndexRoutesAction')
		->bind('admin-compile-list');


	// ===== Страница с основными настройками сайта.
	$admin->match('/panel/options', function(Request $request) use ($app) {
		$form = $app->getServiceOptions()->createAdminForm($app);

		if ($form->handleRequest($request)->isValid())
		{
			if ($app->isGranted('ROLE_EDITOR'))
			{
				$app->getServiceOptions()->commitAdminForm($app, $form);
				$app->getServiceFlash()->success('Изменения сохранены');
				$app->getServiceRoutes()->setCompileFlagForTag('options');
			}
			else
			{
				$app->getServiceFlash()->error('У вас недостаточно прав для изменения настроек.');
			}

			return $app->redirect($request->getUri());
		}

		return $app->render('@PlatformCMS/admin/options.html.twig', [
			'form' => $form->createView(),
		]);
	})->bind('admin-options');


	// ===== Страница с перенаправлением на главную закладку.
	$admin->match('/panel/content', function() use ($app) {
		return $app->redirect($app->url('admin-content-articles'));
	})->bind('admin-content-redirect');

	// ===== Страница со списком материалов сайта.
	$admin->match('/panel/content/articles', 'Moro\\Platform\\Action\\Articles\\IndexArticlesAction')
		->bind('admin-content-articles');

	// ===== Страница с формой создания материала.
	$admin->match('/panel/content/article/create', 'Moro\\Platform\\Action\\Articles\\CreateArticlesAction')
		->bind('admin-content-articles-create');

	// ===== Страница с формой удаления материала.
	$admin->match('/panel/content/article/delete/{id}', 'Moro\\Platform\\Action\\Articles\\DeleteArticlesAction')
		->assert('id', '\\d+(,\\d+)*')
		->bind('admin-content-articles-delete');

	//  ===== Страница с формой редактирования материала.
	$admin->match('/panel/content/article/update/{id}', 'Moro\\Platform\\Action\\Articles\\UpdateArticlesAction')
		->assert ('id', '\\d+')
		->convert('id', 'str_to_int')
		->bind('admin-content-articles-update');

	//  ===== Страница с действием по изменению порядка сортировки маетриала.
	$admin->match('/panel/content/article/set-top/{id}', 'Moro\\Platform\\Action\\Articles\\SetTopArticlesAction')
		->assert ('id', '\\d+')
		->convert('id', 'str_to_int')
		->bind('admin-content-articles-set-top');

	//  ===== Страница с действием по изменению связей материалов с ярлыками.
	$admin->match('/panel/content/article/set-tag/{id}', 'Moro\\Platform\\Action\\Articles\\SetTagArticlesAction')
		->assert ('id', '\\d+(,\\d+)*')
		->bind('admin-content-articles-set-tag');


	// ===== Страница со списком правил перелинковки.
	$admin->match('/panel/content/relink', 'Moro\\Platform\\Action\\Relink\\IndexRelinkAction')
		->bind('admin-content-relink');

	// ===== Страница с формой создания правила перелинковки.
	$admin->match('/panel/content/relink/create', 'Moro\\Platform\\Action\\Relink\\CreateRelinkAction')
		->bind('admin-content-relink-create');

	// ===== Страница с формой удаления правила перелинковки.
	$admin->match('/panel/content/relink/delete/{id}', 'Moro\\Platform\\Action\\Relink\\DeleteRelinkAction')
		->assert('id', '\\d+(,\\d+)*')
		->bind('admin-content-relink-delete');

	//  ===== Страница с формой редактирования правила перелинковки.
	$admin->match('/panel/content/relink/update/{id}', 'Moro\\Platform\\Action\\Relink\\UpdateRelinkAction')
		->assert ('id', '\\d+')
		->convert('id', 'str_to_int')
		->bind('admin-content-relink-update');

	//  ===== Страница с действием по изменению связей ярлыков с правилами перелинковки.
	$admin->match('/panel/content/relink/set-tag/{id}', 'Moro\\Platform\\Action\\Relink\\SetTagRelinkAction')
		->assert ('id', '\\d+(,\\d+)*')
		->bind('admin-content-relink-set-tag');


	// ===== Страница со списком ярлыков.
	$admin->match('/panel/content/tags', 'Moro\\Platform\\Action\\Tags\\IndexTagsAction')
		->bind('admin-content-tags');

	// ===== Страница с формой создания ярлыка.
	$admin->match('/panel/content/tag/create', 'Moro\\Platform\\Action\\Tags\\CreateTagsAction')
		->bind('admin-content-tags-create');

	// ===== Страница с формой удаления ярлыка.
	$admin->match('/panel/content/tag/delete/{id}', 'Moro\\Platform\\Action\\Tags\\DeleteTagsAction')
		->assert('id', '\\d+(,\\d+)*')
		->bind('admin-content-tags-delete');

	//  ===== Страница с формой редактирования ярлыка.
	$admin->match('/panel/content/tag/update/{id}', 'Moro\\Platform\\Action\\Tags\\UpdateTagsAction')
		->assert ('id', '\\d+')
		->convert('id', 'str_to_int')
		->bind('admin-content-tags-update');

	//  ===== Страница с действием по изменению связей ярлыков с ярлыками :-)
	$admin->match('/panel/content/tag/set-tag/{id}', 'Moro\\Platform\\Action\\Tags\\SetTagTagsAction')
		->assert ('id', '\\d+(,\\d+)*')
		->bind('admin-content-tags-set-tag');


	// ===== Отображение изображения.
	$admin->get('/images/{salt}/{hash}_{width}_{height}.{format}', 'Moro\\Platform\\Action\\Images\\ShowImagesAction')
		->assert('salt',   '[0-9a-v]{2}')
		->assert('hash',   '[0-9a-v]{32}')
		->assert('width',  '\\d+')
		->assert('height', '\\d+')
		->assert('format', 'jpg|png')
		->convert('width', 'str_to_int')
		->convert('height','str_to_int')
		->bind('admin-image');

	// ===== Страница со списком файлов-изображений.
	$admin->match('/panel/content/images', 'Moro\\Platform\\Action\\Images\\IndexImagesAction')
		->bind('admin-content-images');

	// ===== Ajax-Json ответ со списком изображений.
	$admin->match('/panel/content/images/select', 'Moro\\Platform\\Action\\Images\\IndexAjaxImagesAction')
		->bind('admin-content-images-select');

	// ===== Страница с формой редактирования изображения.
	$admin->match('/panel/content/images/update/{id}', 'Moro\\Platform\\Action\\Images\\UpdateImagesAction')
		->assert ('id', '\\d+')
		->convert('id', 'str_to_int')
		->bind('admin-content-images-update');

	// ===== Действие по удалению отдельного изображения.
	$admin->match('/panel/content/images/delete/{id}', 'Moro\\Platform\\Action\\Images\\DeleteImagesAction')
		->assert('id', '\\d+(,\\d+)*')
		->bind('admin-content-images-delete');

	// ===== Загрузка пользовательских файлов на сервер.
	$admin->post('/panel/content/images/upload', 'Moro\\Platform\\Action\\Images\\UploadImagesAction')
		->bind('admin-content-images-upload');

	// ===== Страница действия по изменению порядка сортировки изображений.
	$admin->match('/panel/content/images/set-top/{id}', 'Moro\\Platform\\Action\\Images\\SetTopImagesAction')
		->assert ('id', '\\d+')
		->convert('id', 'str_to_int')
		->bind('admin-content-images-set-top');

	//  ===== Страница с действием по изменению связей изображений с ярлыками.
	$admin->match('/panel/content/images/set-tag/{id}', 'Moro\\Platform\\Action\\Images\\SetTagImagesAction')
		->assert ('id', '\\d+(,\\d+)*')
		->bind('admin-content-images-set-tag');
});
