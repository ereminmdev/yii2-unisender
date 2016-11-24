# yii2-unisender

UniSender API component for Yii framework.

## Install

``composer require ereminmdev/yii2-unisender``

## Configure

Add this component to config file:

```
'components' => [
    'unisender' => [
        'class' => 'ereminmdev\yii2\unisender\Unisender',
        'apiKey' => 'YOUR_UNISENDER_API_KEY',
        'retryCount' => 5,
        'httpClientConfig' => [
            ...
        ],
        'httpClientClass' => 'yii\httpclient\Client',
    ],
    ...
],    
```

To configure http client see: https://github.com/yiisoft/yii2-httpclient

## Use

```
$response = Yii::$app->get('unisender')->sendSms([
    'phone' => $phone,
    'sender' => $sender,
    'text' => $text,
]);

if ($response && $response->isOk) {
    ...
} else {
    ...
}
```

To simple process response and get returned data use processResponse() function:

```
$data = $sender->processResponse($sender->sendSms([
    'phone' => $phone,
    'sender' => Yii::$app->params['sender.sms.sender'],
    'text' => $text,
]));
```

## API

UniSender API: http://www.unisender.com/ru/help/api/

## Log and profile

See: https://github.com/yiisoft/yii2-httpclient/blob/master/docs/guide/usage-logging.md
