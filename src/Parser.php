<?php
namespace LFPhp\Pi18N;

use Locale;
use function LFPhp\Func\array_clear_empty;
use function LFPhp\Func\array_orderby;
use function LFPhp\Func\dump;
use function LFPhp\Func\explode_by;

abstract class Parser {
	/**
	 * 解析浏览器支持语言列表
	 * @return array
	 */
	public static function parseBrowserAcceptLanguages(){
		return self::parseAcceptString($_SERVER['HTTP_ACCEPT_LANGUAGE']);
	}

	/**
	 * 解析允许语言清单
	 * @param string $accept_lang_string $_SERVER['HTTP_ACCEPT_LANGUAGE']，格式如：zh-CN,zh-TW;q=0.9,zh;q=0.8,en-US;q=0.7,en;q=0.6,de;q=0.5
	 * @return array 按照权重依次排序[lang1, lang2, ...]
	 */
	public static function parseAcceptString($accept_lang_string){
		$items = array_clear_empty(explode_by(',;', $accept_lang_string));
		//格式：[ [[lang1,lang2], 0.9], [[lang3, lang4], 0.5], ...]
		$data = [];
		foreach($items as $item){
			if(stripos($item, 'q=') !== false){
				$quality = floatval(str_replace('q=', '', $item));
				if($data){
					$data[count($data) - 1][1] = $quality;
				}
			} //空数据，或者最后一个已经设置了权重，则追加新item
			else if(!$data || isset($data[count($data) - 1][1])){
				$data[] = [[$item]];
			} //最后一个未设置权重，则追加语言
			else{
				$data[count($data) - 1][0][] = $item;
			}
		}
		//restructure
		$groups = [];
		foreach($data as list($lang_list, $quality)){
			/** @var array $lang_list */
			$groups[(string)$quality] = array_merge($lang_list, $groups[$quality] ?: []);
		}
		uksort($groups, function($a, $b){
			return $a <= $b;
		});
		$lang_list = [];
		foreach($groups as $ls){
			$lang_list = array_merge($lang_list, $ls);
		}
		return $lang_list;
	}

	/**
	 * 语言相似度匹对
	 * @param string[] $accepted
	 * @param string[] $available
	 * @return string[] 结果格式：['zh-CN','de', ...]
	 */
	public static function matches(array $accepted, array $available){
		$matches = [];
		/** @var Lang[] $av_list */
		/** @var Lang[] $acc_list */
		$av_list = array_map(['self', 'parseLocal'], $available);
		$acc_list = array_map(['self', 'parseLocal'], $accepted);
		foreach($av_list as $av){
			foreach($acc_list as $acc){
				if($score = self::compareScore($av, $acc)){
					$matches[] = [
						'lang'  => $av->language,
						'score' => $score,
					];
				}
			}
		}
		$matches = array_orderby($matches, 'score', SORT_DESC);
		return array_unique(array_column($matches, 'lang'));
	}

	/**
	 * 解析语言定义字符串
	 * @param string $local_str
	 * @return Lang
	 */
	public static function parseLocal($local_str){
		$split = explode('-', $local_str);
		if(!method_exists('\Locale', 'parseLocale')){
			$ret = Locale::parseLocale($local_str);
			return new Lang($ret);
		}

		//init
		$script = $region = $variant1 = $variant2 = $variant3 = $private1 = $private2 = $private3 = '';

		$language = array_shift($split);

		if($split && strlen($split[0]) > 2){
			$script = array_shift($split);
		}
		if($split){
			$region = array_shift($split);
		}
		if($split){
			list($variant1, $variant2, $variant3, $private1, $private2, $private3) = $split;
		}

		return new Lang([
			'language' => $language,
			'script'   => $script,
			'region'   => $region,
			'variant1' => $variant1,
			'variant2' => $variant2,
			'variant3' => $variant3,
			'private1' => $private1,
			'private2' => $private2,
			'private3' => $private3,
		]);
	}

	/**
	 * 语言比较
	 * @todo
	 * @param Lang $a
	 * @param Lang $b
	 * @return float|int
	 */
	private static function compareScore(Lang $a, Lang $b){
		if($a->language != $b->language){
			return 0;
		}
		if(!array_diff((array)$a, (array)$b)){
			return 4;
		}
		$p = 0;
		$p += strcasecmp($a->region, $b->region) === 0 ? 2 : 0;
		$p += strcasecmp($a->script, $b->script) === 0 ? 1 : 0;
		$tmp = (array)$a;
		unset($tmp['language'], $tmp['region'], $tmp['script']);
		foreach($tmp as $field => $val){
			if($val && strcasecmp($val, $b[$field]) === 0){
				$p += 0.1;
			}
		}
		return $p;
	}
}
