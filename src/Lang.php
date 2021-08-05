<?php

namespace LFPhp\Pi18N;
use ArrayAccess;
use function LFPhp\Func\array_keys_exists;

class Lang implements ArrayAccess {
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
		$fields = [
			'language',
			'script',
			'region',
			'variant1',
			'variant2',
			'variant3',
			'private1',
			'private2',
			'private3',
		];
		foreach($fields as $f){
			if(isset($data[$f])){
				$this->{$f} = $data[$f];
			}
		}
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
}