<?php
/**
 * Navigation helper.
 */
use \Symfony\Component\HttpFoundation\Request;
date_default_timezone_set('UTC');

if (file_exists(implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', 'vendor', 'autoload.php'])))
{
	/** @noinspection PhpIncludeInspection */
	require_once implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', 'vendor', 'autoload.php']);
}

header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0', true);
header('Pragma: no-cache');
header('Date: '.strtr(date(DateTime::RFC822), ['+0000' => 'GMT']));
header('Expires: '.strtr(date(DateTime::RFC1123, time() - 24*3660), ['+0000' => 'GMT']));

$request = Request::createFromGlobals();

if ($request->query->get('next') && !$request->query->get('back'))
{
	$back = $request->headers->get('Referer') ?: $request->getSchemeAndHttpHost().'/';
	$next = Request::create($request->query->get('next'), 'GET', ['back' => $back]);
	header('Location: '.$request->getSchemeAndHttpHost().$next->getRequestUri(), true, 302);
}
elseif ($request->query->get('back') === 'Y' && $request->headers->has('Referer'))
{
	$prev = Request::create($request->headers->get('Referer'));
	$back = Request::create($prev->query->get('back') ?: $request->getSchemeAndHttpHost().'/');
	header('Location: '.$request->getSchemeAndHttpHost().$back->getRequestUri(), true, 302);
}
else // This is for wrong usage.
{
	header('Location: '.$request->getSchemeAndHttpHost().'/', true, 302);
}
