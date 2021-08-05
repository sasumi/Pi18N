<?php
namespace LFPhp\Pi18N;

use function LFPhp\Func\str_mixing;

abstract class Translate {
	/**
	 * translate text
	 * @param string $text
	 * @param array $param
	 * @param string $domain
	 * @return string
	 */
	public static function getText($text, $param = [], $domain = ''){
		$text = $domain ? dgettext($domain, $text) : gettext($text);
		return str_mixing($text, $param);
	}
}
