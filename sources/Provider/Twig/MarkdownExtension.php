<?php
/**
 * Class MarkdownExtension
 */
namespace Moro\Platform\Provider\Twig;
use \Aptoma\Twig\Extension\MarkdownExtension as CMarkdownExtension;

/**
 * Class MarkdownExtension
 * @package Provider\Twig
 */
class MarkdownExtension extends CMarkdownExtension
{
	/**
	 * Transform Markdown content to HTML
	 *
	 * @param string $content The Markdown content to be transformed
	 * @return string The result of the Markdown engine transformation
	 */
	public function parseMarkdown($content)
	{
		/** @noinspection PhpParamsInspection */
		$html = CMarkdownExtension::parseMarkdown($content);
		$html = preg_replace('{(/download/[0-9a-v]{2}/[0-9a-v]{32}\\.[a-z0-9]+"\\s+)title="([^"]+)"}', '$1download="$2"', $html);
		$html = preg_replace('{<a\\s+href="([^#/])}', '<a rel="nofollow" target="_blank" href="$1', $html);

		return $html;
	}
}