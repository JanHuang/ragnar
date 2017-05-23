<?php
include __DIR__ . '/vendor/autoload.php';

use \Adinf\RagnarSDK\RagnarConst as RagnarConst;

error_reporting(E_ALL);
ini_set("display_errors", "On");


ragnar(RagnarConst::LOG_TYPE_INFO)->enableDevelopment();

ragnar()->info(__FILE__, __LINE__, "module1_msg", "i wish i can fly!");
ragnar()->debug(__FILE__, __LINE__, "module2_msg", "i wish i'm rich!");

ragnar()->start(__FILE__, __LINE__, 'start')->write('happy');

//url 内包含变量替换注册函数演示
$url = "http://dev.weibo.c1om/v1/log/12312312/lists.json?a=1";

$filterURL = function ($url, $hashquery) {
    return $url;
};

var_dump(ragnar()->getLog());
