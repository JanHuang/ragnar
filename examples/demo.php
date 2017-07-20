<?php
/**
 * Created by PhpStorm.
 * User: jan
 * Date: 17-7-20
 * Time: 下午4:15
 */

use FastD\Ragnar\Ragnar;

include __DIR__ . '/../vendor/autoload.php';

$ragnar = new Ragnar('ragnar_dev');
$ragnar->log(Ragnar::LOG_TYPE_INFO, __FILE__, __LINE__, "module1_msg", "i wish i can fly!");

//输出debug级别日志
$ragnar->log(Ragnar::LOG_TYPE_DEBUG, __FILE__, __LINE__, "module2_msg", "i wish i'm rich!");

//自定义性能埋点示范
$digpooint = $ragnar->digLogStart(__FILE__, __LINE__, "test");
//性能测试内容
//自定义性能埋点结束
$ragnar->digLogEnd($digpooint, "happy");
echo $ragnar->getTraceId() . PHP_EOL;
$ragnar->persist();

