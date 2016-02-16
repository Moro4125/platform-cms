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
	 * @var array
	 */
	protected $_typography = [
		'{(?<=^|\\s|\\r|\\n)"(["А-Яа-яЁёA-Za-z])}u' => '«$1',
		'{(["А-Яа-яЁёA-Za-z])"(?=$|\\s|\\r|\\n)}u' => '$1»',
		'{«"}u' => '««',
		'{"»}u' => '»»',
		'{(«[^»]*)«}u' => '$1„',
		'{»([^«]*»)}u' => '“$1',
		'{(?<!-)--(?!-)}u' => '—',
		'{([А-Яа-яЁёA-Za-z0-9,.!?:])[ ]+—}u' => '$1 —',
		'{\\((c|C)\\)}u' => '©',
		'{\\((r|R)\\)}u' => '®',
		'{\\((tm|TM)\\)}u' => '™',
		'{N(\\d)}u' => '№$1',
		'{-(\\d)}u' => '−$1',
		'{(?<=[0-9]|[()])(\\s)-(\\s)(?=[0-9]|[()])}u' => '$1−$2', // minus
		'{(?<=[0-9]|[()])(\\s)\\*(\\s)(?=[−0-9]|[()])}u' => '$1×$2', // times
		'{(?<=[0-9]|[()])(\\s)/(\\s)(?=[−0-9]|[()])}u' => '$1÷$2', // divide
		'{\\.\\.\\.}u' => '…',
	];

	/**
	 * @param string $text
	 * @return string
	 */
	protected function _filterTypography($text)
	{
		foreach ($this->_typography as $pattern => $replacement)
		{
			$text = preg_replace($pattern, $replacement, $text);
		}

		return $text;
	}

	/**
	 * Transform Markdown content to HTML
	 *
	 * @param string $content The Markdown content to be transformed
	 * @return string The result of the Markdown engine transformation
	 */
	public function parseMarkdown($content)
	{
		$content = $this->_filterTypography($content);

		/** @noinspection PhpParamsInspection */
		$html = CMarkdownExtension::parseMarkdown($content);
		$html = preg_replace('{(/download/[0-9a-v]{2}/[0-9a-v]{32}\\.[a-z0-9]+"\\s+)title="([^"]+)"}', '$1download="$2"', $html);
		$html = preg_replace('{<a\\s+href="([^#/])}', '<a rel="nofollow" target="_blank" href="$1', $html);

		return $html;
	}
}