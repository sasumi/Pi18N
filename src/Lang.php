<?php

namespace LFPhp\Pi18N;

use ArrayAccess;
use Locale;
use function LFPhp\Func\array_keys_exists;

class Lang implements ArrayAccess{
	public $language;
	public $script;
	public $region;
	public $variant1;
	public $variant2;
	public $variant3;
	public $private1;
	public $private2;
	public $private3;

	public function __construct($data = []){
		isset($data['language']) && $this->language = $data['language'];
		isset($data['script']) && $this->script = $data['script'];
		isset($data['region']) && $this->region = $data['region'];
		isset($data['variant1']) && $this->variant1 = $data['variant1'];
		isset($data['variant2']) && $this->variant2 = $data['variant2'];
		isset($data['variant3']) && $this->variant3 = $data['variant3'];
		isset($data['private1']) && $this->private1 = $data['private1'];
		isset($data['private2']) && $this->private2 = $data['private2'];
		isset($data['private3']) && $this->private3 = $data['private3'];
	}

	public function offsetExists($offset){
		$data = (array)$this;
		return array_keys_exists($data, $offset);
	}

	public function offsetGet($offset){
		$data = (array)$this;
		return $data[$offset];
	}

	public function offsetSet($offset, $value){
		$this->{$offset} = $value;
	}

	public function offsetUnset($offset){
		$this->{$offset} = '';
	}

	/**
	 * 语言比较
	 * @param Lang $a
	 * @param Lang $b
	 * @return float|int
	 */
	public static function compare(Lang $a, Lang $b){
		$score = 0;
		if($a->language != $b->language){
			return $score;
		}
		if($a->language == $b->language){
			$score += 10;
		}
		if($a->region && $a->region == $b->region){
			$score += 10;
		}
		if($a->script && $a->script == $b->script){
			$score += 5;
		}
		$score += $a->variant1 && $a->variant1 == $b->variant1 ? 1 : 0;
		$score += $a->variant2 && $a->variant2 == $b->variant2 ? 1 : 0;
		$score += $a->variant3 && $a->variant3 == $b->variant3 ? 1 : 0;
		$score += $a->private1 && $a->private1 == $b->private1 ? 1 : 0;
		$score += $a->private2 && $a->private2 == $b->private2 ? 1 : 0;
		$score += $a->private3 && $a->private3 == $b->private3 ? 1 : 0;
		return $score;
	}

	/**
	 * 解析语言定义字符串
	 * @param string $locale_str
	 * @return \LFPhp\Pi18N\Lang
	 */
	public static function fromString($locale_str){
		$ret = Locale::parseLocale($locale_str);
		return new self($ret);
	}

	/**
	 * @param string[] $locale_str_list
	 * @return Lang[]
	 */
	public static function fromStringList(array $locale_str_list){
		$tmp = [];
		foreach($locale_str_list as $locale_str){
			$tmp[] = self::fromString($locale_str);
		}
		return $tmp;
	}

	/**
	 * convert to locale string
	 * @return string
	 */
	public function __toString(){
		return Locale::composeLocale((array)$this);
	}

	public function compareTo(Lang $b){
		return self::compare($this, $b);
	}
}