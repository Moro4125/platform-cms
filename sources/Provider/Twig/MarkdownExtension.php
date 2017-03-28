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
		'{^(\\[[^\\]]+\\]:\\s*[^\\s]+)\\s+\'(.*?)\'}um' => "\$1\t\"\$2\"\t",
		'{(?<=^| |\\r|\\n)"(["0-9А-Яа-яЁёA-Za-z*_])}u' => '«$1',
		'{(["0-9А-Яа-яЁёA-Za-z*_])"(?=$| |\\r|\\n|\\?|\\.|!|:|;|,)}u' => '$1»',
		'{\\\\"}u' => '"',
		'{«"}u' => '««',
		'{"»}u' => '»»',
		'{(«[^»]*)«}u' => '$1„',
		'{»([^«]*»)}u' => '“$1',
		'{(?<!-|;|!)--(?!-)(?!>)}u' => '—',
		'{([А-Яа-яЁёA-Za-z0-9,.!?:])[ ]+—}u' => '$1 —',
		'{\\((c|C)\\)}u' => '©',
		'{\\((r|R)\\)}u' => '®',
		'{\\((tm|TM)\\)}u' => '™',
		'{(?<=^|\\s)N(\\d)}u' => '№$1',
		'{(?<=^|\\s)-(\\d)}u' => '−$1',
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
		$result = '';

		foreach (preg_split('{(?>(</?[A-Za-z][^>]*>))}', $text, -1, PREG_SPLIT_DELIM_CAPTURE) as $index => $chunk)
		{
			if ($index % 2)
			{
				$result .= $chunk;
				continue;
			}

			foreach ($this->_typography as $pattern => $replacement)
			{
				$chunk = preg_replace($pattern, $replacement, $chunk);
			}

			$result .= $chunk;
		}

		return $result;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getFilters()
	{
		return array_merge(parent::getFilters(), [
			'markdownClean' => new \Twig_Filter_Method($this, 'cleanMarkdown', ['is_safe' => ['html']])
		]);
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
		$html = parent::parseMarkdown($content);
		$html = preg_replace('{(/download/[0-9a-v]{2}/[0-9a-v]{32}\\.[a-z0-9]+"\\s+)title="([^"]+)"}', '$1download="$2"', $html);
		$html = preg_replace('{<a\\s+href="([^#/])}', '<a rel="nofollow" target="_blank" href="$1', $html);

		return $html;
	}

	/**
	 * Transform Markdown content to simple text.
	 *
	 * @param string $content The Markdown content to be transformed
	 * @return string
	 */
	public function cleanMarkdown($content)
	{
		return strtr($content, ['*' => '', '[' => '', ']' => ' ']);
	}
}