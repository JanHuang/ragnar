<?php
/**
 * @author    jan huang <bboyjanhuang@gmail.com>
 * @copyright 2016
 *
 * @see      https://www.github.com/janhuang
 * @see      http://www.fast-d.cn/
 */

namespace FastD\Ragnar;

use Adinf\RagnarSDK\MidTool;
use Adinf\RagnarSDK\Traceid;
use LogicException;
use Psr\Http\Message\ServerRequestInterface;


/**
 * Class Ragnar
 * @package FastD\Ragnar
 */
class Ragnar implements RagnarInterface
{
    protected $name;

    protected $level;

    protected $server;

    protected $traceId;

    protected $rpcId;

    protected $idc;

    protected $ip;

    protected $startAt;

    protected $logs = [];

    /**
     * Ragnar constructor.
     * @param $name
     * @param int $level
     * @param ServerRequestInterface $serverRequest
     */
    public function __construct($name, ServerRequestInterface $serverRequest, $level = RagnarInterface::LOG_TYPE_INFO)
    {
        $this->name = $name;
        $this->level = $level;
        $this->server = $serverRequest;

        if (($idc = (int)$serverRequest->getHeaderLine('RAGNAR_IDC')) !== "" && $idc >= 0 && $idc <= 3) {
            $this->idc = $idc;
        } else {
            throw new LogicException("Ragnar:RAGNAR_IDC取值 0~3 ，请检查 Nginx 配置选项", 1000);
        }

        $this->startAt = microtime(true);

        if ( ! empty($serverRequest->getHeaderLine("HTTP_X_RAGNAR_RPC_ID"))) {
            $this->rpcId = $serverRequest->getHeaderLine("HTTP_X_RAGNAR_RPC_ID");
        }

        if ( ! empty($serverRequest->getHeaderLine("HTTP_X_RAGNAR_TRACE_ID"))) {
            $this->traceId = $serverRequest->getHeaderLine("HTTP_X_RAGNAR_TRACE_ID");
        } else {
            $this->getTraceID();
        }

        header("X-RAGNAR-TRACE-ID: ".$this->traceId);
        header("X-RAGNAR-RPC-ID: ".$this->rpcId);
    }

    public function log($type, $file, $line, $tag, $content)
    {
        if ($type > $this->level) {
            $this->logs[] = array(
                "r" => self::getChildRPCID(),
                "t" => $type,
                "e" => microtime(true),
                "g" => $tag,
                "p" => $file,
                "l" => $line,
                "m" => $content,
            );
        }

        return $this;
    }

    public function setMeta($uid, $env, $extra)
    {
        self::$_uid = $uid;
        self::$_env = $env;
        self::$_extra = json_encode($extra);
    }

    public function digLogStart($file, $line, $tag = '')
    {
        return array(
            "file" => $file,
            "line" => $line,
            "tag" => $tag,
            "start" => microtime(true),
            "rpc_id" => $this->getNextId(),
        );
    }

    public function digLogEnd()
    {
        $this->logs[] =
            array(
                "t" => static::LOG_TYPE_PERFORMANCE,
                "e" => microtime(true),
                "g" => $config["tag"],
                "p" => $config["file"],
                "l" => $config["line"],
                "c" => bcsub(microtime(true), $config["start"], 4),
                "m" => $msg,
                "r" => $config["rpcid"],
            );
    }

    public function getTraceId()
    {
        if (empty($this->traceId)) {
            //prepare parameter
            $idc = $this->idc;//2bit

            $ip = $this->ip;//16bit
            $ip = explode(".", $ip);
            $ip = $ip[2] * 256 + $ip[3];

            $time = microtime();//28bit + 10bit
            $time = explode(" ", $time);

            $ms = intval($time[0] * 1000);
            $time = $time[1] - strtotime("2017-1-1");

            $rand = mt_rand(0, 255);//4

            $key = Traceid::encode($idc, $ip, $time, $ms, $rand);
            $key = MidTool::encode($key);

            $this->traceId = $key;

        }

        return $this->traceId;
    }

    public function decodeTraceID($traceid)
    {
        $traceid = MidTool::decode($traceid);

        $result = Traceid::decode($traceid);
        $result["time"] = strtotime("2017-01-01") + $result["time"];

        $ip1 = (int)($result["ip"] / 256);
        $ip2 = (int)($result["ip"] % 256);

        $result["ip"] = $ip1 . "." . $ip2;
        return $result;
    }

    /**
     * 获取当前请求的RPCid
     * @return string
     */
    public function getCurrentRPCID()
    {
        return $this->rpcId;
    }

    /**
     * 获取当前子请求的RPCID，发送请求用，请不要使用这个
     * @return string
     */
    public function getChildRPCID()
    {
        return self::$_rpcid . "." . self::$_seq;
    }

    /**
     * 获取下一个子请求的RPCID，getChildRPCID也会跟随变化，发送请求的时候用这个
     * @return string
     */
    public function getChildNextRPCID()
    {
        self::$_seq++;
        return self::$_rpcid . "." . self::$_seq;
    }

    /**
     * 获取子请求的header参数，已经包含了getChildNextRPCID
     * @return array
     */
    public function getChildCallParam()
    {
        $headers = array(
            "X-RAGNAR-RPCID" => self::getChildNextRPCID(),
            "X-RAGNAR-TRACEID" => self::getTraceID(),
            "X-RAGNAR-LOGLEVEL" => self::$_log_level,
        );
        return $headers;
    }

    /**
     * 获取子请求的curl header参数，已经包含了getChildNextRPCID
     * 通过这个函数获取下一次Curl请求所需的Header值
     * 如果指定了digstart返回的数组会使用当前rpciid
     * @param $digpoint array digpoint埋点
     * @return array
     */
    public function getCurlChildCallParam($digpoint = array())
    {
        //检测是否初始化并且未禁用
        if (!self::isEnable()) {
            return array();
        }

        $headers = array(
            "X-RAGNAR-TRACEID: " . self::getTraceID(),
            "X-RAGNAR-LOGLEVEL: " . self::$_log_level,
        );

        if (isset($digpoint["rpcid"])) {
            $headers[] = "X-RAGNAR-RPCID: " . $digpoint["rpcid"];
        } else {
            $headers[] = "X-RAGNAR-RPCID: " . self::getChildNextRPCID();
        }

        return $headers;
    }

    public function getHeaders()
    {
        return [
            "X-RAGNAR-RPC-ID" => self::getNextId(),
            "X-RAGNAR-TRACE-ID" => self::getTraceID(),
            "X-RAGNAR-LOG-LEVEL" => $this->level,
        ];
    }
}