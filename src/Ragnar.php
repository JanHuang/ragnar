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
use Adinf\RagnarSDK\Util;
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

    protected $seg = 0;

    protected $idc;

    protected $ip;

    protected $startAt;

    protected $logs = [];

    protected $headers = [];

    /**
     * Ragnar constructor.
     * @param $name
     * @param int $level
     * @param ServerRequestInterface $serverRequest
     */
    public function __construct($name, ServerRequestInterface $serverRequest = null, $level = RagnarInterface::LOG_TYPE_INFO)
    {
        $this->name = $name;
        $this->level = $level;

        if (null !== $serverRequest) {
            $this->server = $serverRequest;
            $this->idc = !$serverRequest->hasHeader('RAGNAR_IDC') ? 0 : (int) $serverRequest->getHeaderLine('RAGNAR_IDC');
            $this->rpcId = !$serverRequest->hasHeader('HTTP_X_RAGNAR_RPC_ID') ? 0 : (int) $serverRequest->getHeaderLine('HTTP_X_RAGNAR_RPC_ID');
            $this->ip = !$serverRequest->hasHeader('RAGNAR_IP') ? '127.0.0.1' : (int) $serverRequest->getHeaderLine('RAGNAR_IP');
            $this->traceId = !$serverRequest->hasHeader('HTTP_X_RAGNAR_TRACE_ID') ? $this->getTraceId() : (int) $serverRequest->getHeaderLine('HTTP_X_RAGNAR_TRACE_ID');
        } else {
            $this->idc = 0;
            $this->rpcId = 0;
            $this->ip = '127.0.0.1';
            $this->traceId = $this->getTraceId();
        }
        $this->startAt = microtime(true);
    }

    /**
     * @return ServerRequestInterface
     */
    public function getServer()
    {
        return $this->server;
    }

    /**
     * @param $type
     * @param $file
     * @param $line
     * @param $tag
     * @param $content
     * @return $this
     */
    public function log($type, $file, $line, $tag, $content)
    {
        if ($type >= $this->level) {
            $this->logs[] = [
                "r" => $this->getChildRPCID(),
                "t" => $type,
                "e" => microtime(true),
                "g" => $tag,
                "p" => $file,
                "l" => $line,
                "m" => $content,
            ];
        }

        return $this;
    }

    /**
     * @param $callback
     * @return $this
     */
    public function flow($callback)
    {
        $startPoint = $this->digLogStart(__FILE__, __LINE__, 'flow');
        call_user_func_array($callback, []);
        $this->digLogEnd($startPoint, 'flow_end');
        return $this;
    }

    /**
     * @param $file
     * @param $line
     * @param string $tag
     * @return array
     */
    public function digLogStart($file, $line, $tag = '')
    {
        return [
            "file" => $file,
            "line" => $line,
            "tag" => $tag,
            "start" => microtime(true),
            "rpcid" => $this->getChildNextRPCID(),
        ];
    }

    /**
     * @param $startPoint
     * @param string $msg
     * @return $this
     */
    public function digLogEnd($startPoint, $msg = '')
    {
        $this->logs[] =
            [
                "t" => static::LOG_TYPE_PERFORMANCE,
                "e" => microtime(true),
                "g" => $startPoint["tag"],
                "p" => $startPoint["file"],
                "l" => $startPoint["line"],
                "c" => bcsub(microtime(true), $startPoint["start"], 4),
                "m" => is_array($msg) ? $msg : [$msg],
                "r" => $startPoint["rpcid"],
            ];

        return $this;
    }

    /**
     * @return string
     */
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
        return $this->rpcId . "." . $this->seg;
    }

    /**
     * 获取下一个子请求的RPCID，getChildRPCID也会跟随变化，发送请求的时候用这个
     * @return string
     */
    public function getChildNextRPCID()
    {
        $this->seg++;
        return $this->rpcId . "." . $this->seg;
    }

    /**
     * 获取子请求的header参数，已经包含了getChildNextRPCID
     * @return array
     */
    public function getChildCallParam()
    {
        return [
            "X-RAGNAR-RPCID" => $this->getChildNextRPCID(),
            "X-RAGNAR-TRACEID" => $this->getTraceID(),
            "X-RAGNAR-LOGLEVEL" => $this->level,
        ];
    }

    /**
     * @param array $point
     * @return array
     */
    public function getCurlChildCallParam($point = [])
    {
        return [
            "X-RAGNAR-TRACEID" => $this->getTraceId(),
            "X-RAGNAR-LOGLEVEL" => $this->level,
            "X-RAGNAR-RPCID" => isset($point["rpcid"]) ? $point["rpcid"] : $this->getChildNextRPCID(),
        ];
    }

    /**
     * @return array
     */
    public function getHeaders()
    {
        return [
            "X-RAGNAR-RPCID" => $this->getChildNextRPCID(),
            "X-RAGNAR-TRACEID" => $this->getTraceID(),
            "X-RAGNAR-LOGLEVEL" => $this->level,
        ];
    }

    /**
     * @return string
     */
    public function getLogPath()
    {
        return static::LOG_PTAH . "/" . trim($this->name . '_log') . "/" . date("Ym");
    }

    /**
     * @param bool $output
     * @return null|string
     */
    public function dump($output = true)
    {
        $log = '';
        foreach ($this->logs as $k => $v) {
            $msg = json_encode($v["m"]);
            if (strlen($msg) > 20480) {
                $this->logs[$k]["m"] = substr($msg, 0, 20480);
            }
        }

        if (count($this->logs) > 30) {
            $list = array_chunk($this->logs, 30);

            $result = [
                [
                    "key" => $this->getTraceID(),
                    "rpcid" => $this->getCurrentRPCID(),
                    "val" => "",
                    "timestamp" => time(),
                ],
            ];

            foreach ($list as $item) {
                $result[0]["val"] = $item;
                $log = json_encode($result);
            }
        } else {
            $result = [
                [
                    "key" => $this->getTraceID(),
                    "rpcid" => $this->getCurrentRPCID(),
                    "val" => $this->logs,
                    "timestamp" => time(),
                ],
            ];

            $log = json_encode($result);
        }

        if (!$output) {
            return $log;
        }

        echo json_encode(json_decode($log, true), JSON_PRETTY_PRINT);
        return null;
    }

    /**
     * @return $this
     */
    public function clean()
    {
        $this->logs = [];

        return $this;
    }

    /**
     * @param $path
     * @return bool
     */
    protected function targetDir($path)
    {
        return !is_dir($path) ? mkdir($path, 0777, true) : true;
    }

    /**
     * @return string
     */
    public function getLogFile()
    {
        return $this->getLogPath() . '/' . date("d") . "-" . getmypid() . ".log";
    }

    /**
     * @return int
     */
    public function persist()
    {
        $log = $this->dump(false);

        $path = $this->getLogPath();

        $this->targetDir($path);

        return file_put_contents($this->getLogFile(), trim($log) . "\n", FILE_APPEND);
    }
}