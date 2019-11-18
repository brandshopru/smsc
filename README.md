# PHP клиент для API сервиса оповещения [SMS Центр](http://smsc.ru).
Пакет предоставляет удобный интерфейс для интеграции с сервисом оповещения SMS Центр через [API](https://smsc.ru/api/). 
## Требования
* php ^7.1
* guzzlehttp/guzzle ^6.0.0

## Установка
Вы можете установить данный пакет с помощью сomposer:

```
composer require brandshopru/smsc
```

## Использование
Допустим нам необходимо отправить смс-сообщение:
```php
use Brandshopru\Smsc\Client;

    $login = 'smscLogin';                       //логин клиента
    $password = 'smscPassword';                 //пароль
    $useMethodPost = TRUE;                      //использовать метод POST
    $useHttps = TRUE;                           //использовать HTTPS протокол
    $charset = 'utf-8';                         //кодировка сообщения: utf-8, koi8-r или windows-1251 (по умолчанию)
    $emailSender = 'account@yoursite.domain';   //e-mail адрес отправителя
    
    $SmsCenterClient = new Client($login, $password, $useMethodPost, $useHttps, $charset, $emailSender);
    
    $phone = "+76543210987";
    $message = "Привет, нам не хватает только тебя ;)";
    
    try {
        $result = $SmsCenterClient->send_sms($phone, $message);
        if ($result->isOk()) {
            // сообщение отправлено
            $details = $result->getContent();
        } else {
            // что-то пошло не так
        }
    } catch (Exception $error) {
        //обрабатываем исключение
    }
```
