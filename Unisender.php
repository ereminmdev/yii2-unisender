<?php

namespace ereminmdev\yii2\unisender;

use yii;
use yii\base\Object;
use yii\helpers\ArrayHelper;
use yii\httpclient\Client as HttpClient;
use yii\httpclient\Response as HttpResponse;

/**
 * UniSender component for Yii framework.
 *
 * @link https://www.unisender.com/en/support/integration/api/
 * @link https://www.unisender.com/ru/support/integration/api/
 *
 * @method sendSms
 * @method sendEmail
 * @method getLists
 * @method createList
 * @method updateList
 * @method deleteList
 * @method exclude
 * @method unsubscribe
 * @method importContacts
 * @method exportContacts
 * @method getTotalContactsCount
 * @method getContactCount
 * @method createEmailMessage
 * @method createSmsMessage
 * @method createCampaign
 * @method getActualMessageVersion
 * @method checkSms
 * @method sendTestEmail
 * @method checkEmail
 * @method updateOptInEmail
 * @method getWebVersion
 * @method deleteMessage
 * @method createEmailTemplate
 * @method updateEmailTemplate
 * @method deleteTemplate
 * @method getTemplate
 * @method getTemplates
 * @method listTemplates
 * @method getCampaignDeliveryStats
 * @method getCampaignAggregateStats
 * @method getVisitedLinks
 * @method getCampaigns
 * @method getCampaignStatus
 * @method getMessages
 * @method getMessage
 * @method listMessages
 * @method getFields
 * @method createField
 * @method updateField
 * @method deleteField
 * @method getTags
 * @method deleteTag
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
     * @var array|\Closure the default configuration used when creating client object
     * @see yii\httpclient\Client
     */
    public $httpClientConfig = [];
    /**
     * @var string the default client class name when creating client object
     * @see httpClientConfig
     */
    public $httpClientClass = 'yii\httpclient\Client';
    /**
     * @var string
     */
    public $platform = '';


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
     * @param string $methodName
     * @param array $params
     * @return HttpResponse|false
     */
    protected function callMethod($methodName, $params = [])
    {
        if ($this->platform !== '') {
            $params['platform'] = $this->platform;
        }

        $params = ArrayHelper::merge((array)$params, ['api_key' => $this->apiKey]);

        $retryCount = 0;
        do {
            $response = false;
            try {
                $response = $this->getHttpClient()->createRequest()
                    ->setUrl($this->getApiHost() . $methodName . '?format=json')
                    ->setMethod('post')
                    ->setFormat(HttpClient::FORMAT_RAW_URLENCODED)
                    ->setData($params)
                    ->send();
            } catch (\ErrorException $e) {
                Yii::error($e, __METHOD__);
            }
        } while ((++$retryCount < $this->retryCount) && (!($response instanceof HttpResponse) || !$response->isOk));

        return $response;
    }

    /**
     * @return string
     */
    protected function getApiHost()
    {
        return 'https://api.unisender.com/ru/api/';
    }

    private $_httpClient;

    /**
     * @return HttpClient
     */
    public function getHttpClient()
    {
        if ($this->_httpClient === null) {
            $config = $this->httpClientConfig;
            if ($config instanceof \Closure) {
                $config = call_user_func($config);
            }
            if (!isset($config['class'])) {
                $config['class'] = $this->httpClientClass;
            }
            $this->_httpClient = Yii::createObject($config);
        }

        return $this->_httpClient;
    }

    /**
     * @param HttpResponse $response
     * @return array|false
     */
    public function processResponse($response)
    {
        if (!($response instanceof HttpResponse) || !$response->isOk) {
            Yii::$app->session->addFlash('danger', Yii::t('app', 'Invalid response from the server. Please try again later.'));
            return false;
        }

        $responseData = $response->getData();

        if (YII_DEBUG) {
            Yii::$app->session->addFlash('info', var_export($responseData, true));
        }

        $result = ArrayHelper::getValue($responseData, 'result', []);

        if (array_key_exists(0, $result)) {
            $isOk = false;
            foreach ($result as $data) {
                $isOk = $isOk || $this->checkResponseData($data);
            }
        } else {
            $isOk = $this->checkResponseData($result);
        }

        return $isOk ? $result : false;
    }

    /**
     * @param array $data
     * @return bool
     */
    public function checkResponseData($data)
    {
        if (empty($data)) {
            Yii::$app->session->addFlash('danger', Yii::t('app', 'Invalid response from the server. Please try again later.'));
            return false;
        }

        $warnings = ArrayHelper::getValue($data, 'warnings', []);
        foreach ($warnings as $warning) {
            Yii::$app->session->addFlash('warning', $warning);
        }

        $errors = ArrayHelper::getValue($data, 'errors', []);
        if (isset($data['error'])) {
            $errors[] = $data['error'];
        }

        if (!empty($errors)) {
            foreach ($errors as $error) {
                $this->processError($error);
            }
            return false;
        }

        return true;
    }

    /**
     * @param array $errorData
     */
    public function processError($errorData)
    {
        if (is_array($errorData)) {
            $error = Yii::t('yii', 'Error') . ': ' . $errorData['error'];
            $error .= isset($errorData['code']) ? '<br>' . ArrayHelper::getValue(static::errorCodes(), $errorData['code'], '') : '';
            Yii::$app->session->addFlash('error', $error);
        }
    }

    /**
     * @return array UniSender API error codes
     */
    public static function errorCodes()
    {
        return [
            'unspecified' => 'Тип ошибки не указан. Подробности смотрите в сообщении.',
            'invalid_api_key' => 'Указан неправильный ключ доступа к API. Проверьте, совпадает ли значение api_key со значением, указанным в личном кабинете.',
            'access_denied' => 'Доступ запрещён. Проверьте, включён ли доступ к API в личном кабинете и не обращаетесь ли вы к методу, прав доступа к которому у вас нет.',
            'unknown_method' => 'Указано неправильное имя метода',
            'invalid_arg' => 'Указано неправильное значение одного из аргументов метода',
            'not_enough_money' => 'Не хватает денег на счету для выполнения метода',
            'retry_later' => 'Временный сбой. Попробуйте ещё раз позднее.',
            'api_call_limit_exceeded_for_api_key' => 'Сработало ограничение по вызову методов API в единицу времени. На данный момент это 300 вызовов в минуту.',
            'api_call_limit_exceeded_for_ip' => 'Сработало ограничение по вызову методов API в единицу времени. На данный момент это 300 вызовов в минуту.',
            'dest_invalid' => 'Доставка невозможна, телефон получателя некорректен',
            'src_invalid' => 'Доставка невозможна, аргумент sender (поле «отправитель») некорректен',
            'has_been_sent' => 'SMS данному адресату уже был отправлен. Допустимый интервал между двумя отправками - 1 минута.',
            'unsubscribed_globally' => 'Адресат глобально отписан от рассылок',
            'attachment_is_not_bytestring' => 'Содержимое вложения не является скалярным значением.',
            'attachment_quota_error' => 'Превышен допустимый размер вложения.',
            'body_empty' => 'Отсутствует тело письма.',
            'body_exceeds_length' => 'Тело письма превышает допустимый размер.',
            'empty_subject' => 'Не указана тема письма.',
            'subject_exceeds_length' => 'Тема письма превышает допустимый размер.',
            'wrong_header_parameter' => 'Не указан обязательный параметр заголовка.',
            'header_not_allowed' => 'Указанный заголовок не поддерживается.',
            'invalid_email' => 'Недопустимый Email-адрес.',
            'empty_sender_name' => 'Не указано имя отправителя',
            'invalid_sender_email' => 'Недопустимый Email-адрес отправителя.',
            'unchecked_sender_email' => 'Email-адрес отправителя не подтвержден.',
            'unsupported_lang' => 'Указанный язык не поддерживается системой.',
            'unsubscribe_link_missing' => 'Вы забыли добавить ссылку отписки: {{_UnsubscribeUrl}}',
        ];
    }
}
