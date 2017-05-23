<?php
/**
 * @author    jan huang <bboyjanhuang@gmail.com>
 * @copyright 2016
 *
 * @see      https://www.github.com/janhuang
 * @see      http://www.fast-d.cn/
 */

namespace FastD\Ragnar;


use Adinf\RagnarSDK\RagnarConst;
use Adinf\RagnarSDK\RagnarSDK;

/**
 * Class Ragnar
 * @package FastD\Ragnar
 */
class Ragnar
{
    /**
     * @var null
     */
    protected $startAt = null;

    /**
     * @var Ragnar
     */
    protected static $ragnar = null;

    /**
     * Ragnar constructor.
     * @param int $level
     */
    public function __construct($level = RagnarConst::LOG_TYPE_ERROR)
    {
        RagnarSDK::setLogLevel($level);
    }

    /**
     * @return $this
     */
    public function enableDevelopment()
    {
        RagnarSDK::devmode("ragnar_projectname");

        return $this;
    }

    /**
     * @param $uid
     * @param $env
     * @param $extra
     * @return $this
     */
    public function meta($uid, $env, $extra)
    {
        RagnarSDK::setMeta($uid, $env, $extra);

        return $this;
    }

    /**
     * @param $level
     * @param $file
     * @param $line
     * @param $tag
     * @param $msg
     * @return $this
     */
    public function log($level, $file, $line, $tag, $msg)
    {
        RagnarSDK::RecordLog($level, $file, $line, $tag, $msg);

        return $this;
    }

    /**
     * @param $file
     * @param $line
     * @param $tag
     * @param $msg
     * @return $this
     */
    public function info($file, $line, $tag, $msg)
    {
        $this->log(RagnarConst::LOG_TYPE_INFO, $file, $line, $tag, $msg);

        return $this;
    }

    /**
     * @param $file
     * @param $line
     * @param $tag
     * @param $msg
     * @return $this
     */
    public function debug($file, $line, $tag, $msg)
    {
        $this->log(RagnarConst::LOG_TYPE_DEBUG, $file, $line, $tag, $msg);

        return $this;
    }

    /**
     * @param $file
     * @param $line
     * @param $tag
     * @param $msg
     * @return $this
     */
    public function error($file, $line, $tag, $msg)
    {
        $this->log(RagnarConst::LOG_TYPE_ERROR, $file, $line, $tag, $msg);

        return $this;
    }

    /**
     * @param $file
     * @param $line
     * @param $tag
     * @param $msg
     * @return $this
     */
    public function performance($file, $line, $tag, $msg)
    {
        $this->log(RagnarConst::LOG_TYPE_PERFORMENCE, $file, $line, $tag, $msg);

        return $this;
    }

    /**
     * @param $file
     * @param $line
     * @param $tag
     * @param $msg
     * @return $this
     */
    public function notice($file, $line, $tag, $msg)
    {
        $this->log(RagnarConst::LOG_TYPE_NOTICE, $file, $line, $tag, $msg);

        return $this;
    }

    /**
     * @param $file
     * @param $line
     * @param $tag
     * @return $this
     */
    public function start($file, $line, $tag)
    {
        $this->startAt = RagnarSDK::digLogStart($file, $line, $tag);

        return $this;
    }

    /**
     * @param $tag
     * @return $this
     */
    public function write($tag)
    {
        RagnarSDK::digLogEnd($this->startAt, $tag);

        return $this;
    }

    /**
     * @return string
     */
    public function getTraceId()
    {
        return RagnarSDK::getTraceID();
    }

    /**
     * @return array
     */
    public function getParams()
    {
        return RagnarSDK::getChildCallParam();
    }

    /**
     * @param callable $callable
     * @return $this
     */
    public function filterCallback(callable $callable)
    {
        RagnarSDK::setUrlFilterCallback($callable);

        return $this;
    }

    /**
     * @return array
     */
    public function getLog()
    {
        return RagnarSDK::decodeTraceID($this->getTraceId());
    }

    /**
     * @param int $level
     * @return static
     */
    public static function create($level = RagnarConst::LOG_TYPE_ERROR)
    {
        if (null === static::$ragnar) {
            static::$ragnar = new static($level);
        }

        return static::$ragnar;
    }
}