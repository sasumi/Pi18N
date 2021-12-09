<?php
namespace LFPhp\Pi18N;

use function LFPhp\Func\array_clear_empty;
use function LFPhp\Func\array_orderby;
use function LFPhp\Func\explode_by;

abstract class Parser {
	/**
	 * 解析浏览器支持语言列表
	 * @return Lang[]
	 */
	public static function parseBrowserAcceptLanguages(){
		return self::parseAcceptString($_SERVER['HTTP_ACCEPT_LANGUAGE']);
	}

	/**
	 * 解析允许语言清单
	 * @param string $accept_lang_string $_SERVER['HTTP_ACCEPT_LANGUAGE']，格式如：zh-CN,zh-TW;q=0.9,zh;q=0.8,en-US;q=0.7,en;q=0.6,de;q=0.5
	 * @return Lang[] 按照权重依次排序[lang1, lang2, ...]
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
		foreach($data as list($lang_str_list, $quality)){
			/** @var array $lang_str_list */
			$groups[(string)$quality] = array_merge($lang_str_list, $groups[$quality] ?: []);
		}
		uksort($groups, function($a, $b){
			return $a <= $b;
		});
		$tmp = [];
		foreach($groups as $ls){
			$tmp = array_merge($tmp, $ls);
		}
		return Lang::fromStringList($tmp);
	}

	/**
	 * 语言相似度匹对
	 * @param Lang[] $accepted
	 * @param Lang[] $available
	 * @return Lang[] 结果格式：['zh-CN','de', ...]
	 */
	public static function matches(array $accepted, array $available){
		$matches = [];
		foreach($available as $av){
			foreach($accepted as $acc){
				if($score = Lang::compare($av, $acc)){
					$matches[] = [
						'lang'  => $av,
						'score' => $score,
					];
				}
			}
		}
		$matches = array_orderby($matches, 'score', SORT_DESC);
		return array_unique($matches);
	}
}
