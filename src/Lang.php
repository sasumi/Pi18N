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
}