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

## Translate

Example for messages/ru/app.php:

```
// UniSender component
'Invalid response from the server. Please try again later.' => 'Некорректный ответ сервера. Пожалуйста, попробуйте чуть позже.',
'Error connecting to the server.' => 'Ошибка соединения с сервером.',
```
