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
 * @return \FastD\Ragnar\Ragnar
 */
function ragnar($level = \Adinf\RagnarSDK\RagnarConst::LOG_TYPE_ERROR)
{
    return \FastD\Ragnar\Ragnar::create($level);
}

