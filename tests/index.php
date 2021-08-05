<?php

use LFPhp\Pi18N\Service;
use LFPhp\Pi18N\Translate;
use function LFPhp\Func\dump;

include __DIR__.'/../vendor/autoload.php';

echo "<PRE>";
Service::register('litephp', __DIR__.'/litephp_lang', ['zh-CN']);
Service::setCurrentLanguageFromBrowser();
$s = Translate::getText('UPLOAD_ERR_INI_SIZE');

dump(Service::getCurrentDomain());
\LFPhp\Func\dump($s);