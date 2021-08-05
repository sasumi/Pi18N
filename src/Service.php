<?php
namespace LFPhp\Pi18N;

use LFPhp\Pi18N\Exception\LangException;
use LFPhp\Pi18N\Exception\LangNoSupportedException;
use function LFPhp\Func\dump;
use function LFPhp\Func\server_in_windows;

abstract class Service {
	private static $domain_list;
	private static $current_language;

	/**
	 * 设置当前域
	 * @param string $domain
	 * @return string
	 */
	public static function setCurrentDomain($domain){
		return textdomain($domain);
	}

	/**
	 * 获取当前域
	 * @return string
	 */
	public static function getCurrentDomain(){
		return textdomain(null);
	}

	/**
	 * 设置当前环境语言
	 * @param string $language 语言名称，必须在support_language_list里面
	 * @param int $category 类目，缺省为所有类目：LC_ALL
	 * @param bool $force_check_all_domain_support 是否强制检查所有域必须支持
	 * @return string
	 * @throws \LFPhp\Pi18N\Exception\LangException
	 */
	public static function setCurrentLanguage($language, $category = LC_ALL, $force_check_all_domain_support = false){
		if(server_in_windows()){
			return self::setCurrentLanguageInWindows($language, $category);
		}

		$force_check_all_domain_support && self::checkLanguageSupportAllDomain($language);

		//try difference language case ...
		$locale_set = setlocale($category, $language.'.utf8', $language.'.UTF8', $language.'.utf-8', $language.'.UTF-8');
		if($language && stripos($locale_set,$language) === false){
			throw new LangException(sprintf('Language set %s failure:%s, return:%s', $category, $language, $locale_set));
		}
		self::$current_language = $language;
		return $locale_set;
	}

	/**
	 * 根据浏览器（HTTP）头支持语言，设置当前翻译语言
	 * 该函数如果没有传递支持语言列表，需要在注册语言动作之后执行。
	 * @param string[] $support_language_list
	 * @return string
	 * @throws \LFPhp\Pi18N\Exception\LangException
	 */
	public static function setCurrentLanguageFromBrowser($support_language_list = []){
		$accepted = Parser::parseBrowserAcceptLanguages();
		$language_list = Parser::matches($accepted, $support_language_list ?: self::getAllLanguageList());
		return self::setCurrentLanguage($language_list[0]);
	}

	/**
	 * 获取绑定的所有域的支持的语言列表
	 * @return array
	 */
	private static function getAllLanguageList(){
		$ls = [];
		foreach(self::$domain_list as $domain => list($language_list)){
			$ls = array_merge($ls, $language_list);
		}
		return array_unique($ls);
	}

	/**
	 * 翻译，如果当前域不支持当前语言，则使用缺省语言
	 * @param string $text
	 * @param array $param
	 * @param string $domain
	 * @return string
	 * @throws \LFPhp\Pi18N\Exception\LangException
	 */
	public static function getTextSoft($text, $param, $domain = ''){
		$current_language = self::getCurrentLanguage();
		$domain = $domain ?: self::getCurrentDomain();
		if(!in_array($current_language, self::$domain_list[$domain][0])){
			$current_language = self::$domain_list[$domain][1];
			return self::getTextInLanguageTemporary($text, $param, $current_language, $domain);
		}
		return Translate::getText($text, $param, $domain);
	}

	/**
	 * 获取当前设置语言
	 * @return string
	 */
	public static function getCurrentLanguage(){
		return self::$current_language;
	}

	/**
	 * 临时以指定语种翻译翻译
	 * @param $text
	 * @param array $param
	 * @param string $language
	 * @param string $domain
	 * @return string
	 * @throws \LFPhp\Pi18N\Exception\LangException
	 */
	private static function getTextInLanguageTemporary($text, $param, $language, $domain = ''){
		$old_language = self::getCurrentLanguage();
		self::setCurrentLanguage($language);
		$text = Translate::getText($text, $param, $domain);
		self::setCurrentLanguage($old_language);
		return $text;
	}

	/**
	 * 设置Windows环境语言
	 * @param string $language
	 * @param int $category
	 * @param bool $force_check_all_domain_support 是否强制检查所有域必须支持
	 * @return string
	 * @throws \LFPhp\Pi18N\Exception\LangException
	 */
	private static function setCurrentLanguageInWindows($language, $category = LC_ALL, $force_check_all_domain_support = false){
		$force_check_all_domain_support && self::checkLanguageSupportAllDomain($language);

		$win_lang = str_replace('_', '-', $language);

		static $win_lang_list;
		if(!$win_lang_list){
			$win_lang_list = include __DIR__.'/assert/windows.lang.php';
			$win_lang_list = array_map('strtolower', $win_lang_list);
		}

		if(!in_array(strtolower($win_lang), $win_lang_list)){
			throw new LangNoSupportedException("Language no support in windows:$win_lang");
		}

		if(false == putenv("LANGUAGE=".$win_lang)){
			throw new LangException(sprintf("Could not set the ENV variable LANGUAGE = $win_lang"));
		}

		// set the LANG environmental variable
		if(false == putenv("LANG=".$win_lang)){
			throw new LangException(sprintf("Could not set the ENV variable LANG = $win_lang"));
		}

		//try difference language case ...
		$locale_set = setlocale($category, $win_lang);
		if($win_lang && stripos($locale_set,$win_lang) === false){
			throw new LangException(sprintf('Language set %s failure:%s, return:%s', $category, $language, $locale_set));
		}
		self::$current_language = $language;
		return $locale_set;
	}

	/**
	 * 检查语言是否被所有域支持
	 * @param string $language
	 * @throws \LFPhp\Pi18N\Exception\LangNoSupportedException
	 */
	public static function checkLanguageSupportAllDomain($language){
		foreach(self::$domain_list as $domain => list($language_list)){
			if(!in_array($language, $language_list)){
				throw new LangNoSupportedException('Language no support in domain:'.$domain);
			}
		}
	}

	/**
	 * 注册域
	 * @param string $domain 域
	 * @param string $path 翻译件路径
	 * @param array $support_language_list 支持语言列表
	 * @param string $default_language 缺省支持语言，默认为支持语言列表第一个。当当前域不支持设定语言时，可使用缺省语言显示
	 * @param string $code_set 编码，缺省为UTF-8（windows暂不支持设定）
	 * @throws \LFPhp\Pi18N\Exception\LangException
	 */
	public static function register($domain, $path, $support_language_list, $default_language = null, $code_set = 'UTF-8'){
		if(!bindtextdomain($domain, $path)){
			throw new LangException("Bind text domain fail, domain:$domain, path:$path");
		}
		if($code_set){
			bind_textdomain_codeset($domain, $code_set);
		}
		if(!$default_language){
			$default_language = current($support_language_list);
		}
		if(!self::getCurrentDomain()){
			self::setCurrentDomain($domain);
		}
		if(!self::$current_language){
			self::$current_language = $default_language;
		}
		self::$domain_list[$domain] = [$support_language_list, $default_language];
	}
}