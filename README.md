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
    ],
    ...
],    
```

## Use

```
Yii::$app->get('unisender')->sendSms([
    'phone' => $phone,
    'sender' => $sender,
    'text' => $text,
]);
```

## API

UniSender API: http://www.unisender.com/ru/help/api/
