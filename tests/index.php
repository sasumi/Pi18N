<?php

use LFPhp\Pi18N\Service;
use LFPhp\Pi18N\Translate;

include __DIR__.'/../vendor/autoload.php';

echo "<PRE>";
Service::registerDomain('litephp', __DIR__.'/litephp_lang', ['zh-CN']);
Service::setCurrentLanguageFromBrowser();

$s = Translate::getText('UPLOAD_ERR_INI_SIZE');
\LFPhp\Func\dump($s);