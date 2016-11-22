<?php

namespace ereminmdev\yii2\unisender;

use yii;
use yii\base\Object;

/**
 * UniSender API component for Yii framework.
 *
 * @link https://github.com/unisender-dev/php-api-wrapper
 * @link http://www.unisender.com/ru/help/api/
 */
class Unisender extends Object
{
    /**
     * @var string UniSender API key
     */
    public $apiKey;
    /**
     * @var int
     */
    public $retryCount = 0;
    /**
     * @var int
     */
    public $timeout;
    /**
     * @var bool
     */
    public $compression = false;


    /**
     * @param string $name
     * @param array $arguments
     * @return string
     */
    public function __call($name, $arguments)
    {
        if (!is_array($arguments) || 0 === count($arguments)) {
            $params = [];
        } else {
            $params = $arguments[0];
        }

        return $this->callMethod($name, $params);
    }

    /**
     * @param array $params
     * @return string
     */
    public function subscribe($params)
    {
        $params = (array)$params;

        if (empty($params['request_ip'])) {
            $params['request_ip'] = Yii::$app->request->userIP;
        }

        return $this->callMethod('subscribe', $params);
    }

    /**
     * @param string $result
     * @return bool
     */
    public function getResult($result)
    {
        if ($result) {
            $jsonObj = json_decode($result);
            if (null === $jsonObj) {
                Yii::$app->session->addFlash('danger', Yii::t('app', 'Invalid response from the server. Please try again later.'));
                return false;
            } elseif (!empty($jsonObj->error)) {
                Yii::$app->session->addFlash('danger', $jsonObj->error . ' (' . $jsonObj->code . ')');
                return false;
            } else {
                return $jsonObj;
            }
        } else {
            Yii::$app->session->addFlash('danger', 'Error connecting to the server.');
            return false;
        }
    }

    /**
     * @param string $methodName
     * @param array $params
     * @return array
     */
    protected function callMethod($methodName, $params = [])
    {
        $url = $methodName . '?format=json';

        if ($this->compression) {
            $url .= '&api_key=' . $this->apiKey . '&request_compression=bzip2';
            $content = bzcompress(http_build_query($params));
        } else {
            $params = array_merge((array)$params, ['api_key' => $this->apiKey]);
            $content = http_build_query($params);
        }

        $contextOptions = [
            'http' => [
                'method' => 'POST',
                'header' => 'Content-type: application/x-www-form-urlencoded',
                'content' => $content,
            ],
        ];

        if ($this->timeout) {
            $contextOptions['http']['timeout'] = $this->timeout;
        }

        $retryCount = 0;
        $context = stream_context_create($contextOptions);

        $result = false;
        do {
            $host = $this->getApiHost($retryCount);
            $result = @file_get_contents($host . $url, false, $context);
            ++$retryCount;
        } while (($result === false) && ($retryCount < $this->retryCount));

        return $result;
    }

    /**
     * @param int $retryCount
     * @return string
     */
    protected function getApiHost($retryCount = 0)
    {
        if ($retryCount % 2 === 0) {
            return 'http://api.unisender.com/ru/api/';
        } else {
            return 'http://www.api.unisender.com/ru/api/';
        }
    }
}
