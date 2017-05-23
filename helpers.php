<?php
/**
 * @author    jan huang <bboyjanhuang@gmail.com>
 * @copyright 2016
 *
 * @see      https://www.github.com/janhuang
 * @see      http://www.fast-d.cn/
 */

/**
 * @param int $level
 */
function ragnar($level = \Adinf\RagnarSDK\RagnarConst::LOG_TYPE_INFO)
{

}

function info($file, $line, $tag, $msg)
{
    \Adinf\RagnarSDK\RagnarSDK::RecordLog(\Adinf\RagnarSDK\RagnarConst::LOG_TYPE_INFO, $file, $line, $tag, $msg);
}

function debug($file, $line, $tag, $msg)
{
    \Adinf\RagnarSDK\RagnarSDK::RecordLog(\Adinf\RagnarSDK\RagnarConst::LOG_TYPE_DEBUG, $file, $line, $tag, $msg);
}


