<?php

namespace Brandshopru\Smsc;

use Brandshopru\Smsc\Response;

class Client
{
    private $login;
    private $password;
    private $methodPost;
    private $useHttps;
    private $charset;
    private $from;

    public function __construct(string $login, string $password, bool $useMethodPost, bool $useHttps, string $charset, string $emailSender)
    {
        $this->login = $login;              //логин клиента
        $this->password = $password;        //пароль
        $this->methodPost = $useMethodPost; //использовать метод POST
        $this->useHttps = $useHttps;        //использовать HTTPS протокол
        $this->charset = $charset;          //кодировка сообщения: utf-8, koi8-r или windows-1251 (по умолчанию)
        $this->from = $emailSender;         //e-mail адрес отправителя
    }

    /**
     * Функция отправки SMS
     *
     * обязательные параметры:
     * @param $phones - список телефонов через запятую или точку с запятой
     * @param $message - отправляемое сообщение
     * необязательные параметры:
     * @param int $translit - переводить или нет в транслит (1,2 или 0)
     * @param int $time - необходимое время доставки в виде строки (DDMMYYhhmm, h1-h2, 0ts, +m)
     * @param int $id - идентификатор сообщения. Представляет собой 32-битное число в диапазоне от 1 до 2147483647.
     * @param int $format - формат сообщения (0 - обычное sms, 1 - flash-sms, 2 - wap-push, 3 - hlr, 4 - bin, 5 - bin-hex, 6 - ping-sms, 7 - mms, 8 - mail, 9 - call, 10 - viber, 11 - soc)
     * @param bool $sender - имя отправителя (Sender ID).
     * @param string $query - строка дополнительных параметров, добавляемая в URL-запрос ("valid=01:00&maxsms=3&tz=2")
     * @param array $files - массив путей к файлам для отправки mms или e-mail сообщений
     *
     * @return \Brandshopru\Smsc\Response
     * @throws \Exception
     */
    public function send_sms($phones, $message, $translit = 0, $time = 0, $id = 0, $format = 0, $sender = false, $query = [], $files = [])
    {
        if ($format) {
            $query = array_merge($query, $this->getFormat($format));
        }
        if ($sender) {
            $query['sender'] = $sender;
        }
        if ($time) {
            $query['time'] = $time;
        }
        return $this->request("send", array_merge($query, [
            "cost" => 3,
            "phones" => $phones,
            "mes" => $message,
            "translit" => $translit,
            "id" => $id,
        ], $files));
    }

    /**
     * SMTP версия функции отправки SMS
     *
     * обязательные параметры:
     * @param $phones - список телефонов через запятую или точку с запятой
     * @param $message - отправляемое сообщение
     * необязательные параметры:
     * @param int $translit - переводить или нет в транслит (1,2 или 0)
     * @param int $time - необходимое время доставки в виде строки (DDMMYYhhmm, h1-h2, 0ts, +m)
     * @param int $id - идентификатор сообщения. Представляет собой 32-битное число в диапазоне от 1 до 2147483647.
     * @param int $format - формат сообщения (0 - обычное sms, 1 - flash-sms, 2 - wap-push, 3 - hlr, 4 - bin, 5 - bin-hex, 6 - ping-sms, 7 - mms, 8 - mail, 9 - call, 10 - viber, 11 - soc)
     * @param bool $sender - имя отправителя (Sender ID)
     *
     * @return bool
     */
    public function send_sms_mail($phones, $message, $translit = 0, $time = 0, $id = 0, $format = 0, $sender = "")
    {
        return mail("send@send.smsc.ru", "",
            $this->login.":".$this->password.":$id:$time:$translit,$format,$sender:$phones:$message",
            "From: ".$this->from."\nContent-Type: text/plain; charset=".$this->charset."\n");
    }

    /**
     * Функция получения стоимости SMS
     *
     * обязательные параметры:
     * @param $phones - список телефонов через запятую или точку с запятой
     * @param $message - отправляемое сообщение
     * необязательные параметры:
     * @param int $translit - переводить или нет в транслит (1,2 или 0)
     * @param int $format - формат сообщения (0 - обычное sms, 1 - flash-sms, 2 - wap-push, 3 - hlr, 4 - bin, 5 - bin-hex, 6 - ping-sms, 7 - mms, 8 - mail, 9 - call, 10 - viber, 11 - soc)
     * @param bool $sender - имя отправителя (Sender ID)
     * @param array $query - строка дополнительных параметров, добавляемая в URL-запрос ("list=79999999999:Ваш пароль: 123\n78888888888:Ваш пароль: 456")
     *
     * @return \Brandshopru\Smsc\Response
     * @throws \Exception
     */
    public function get_sms_cost($phones, $message, $translit = 0, $format = 0, $sender = false, $query = [])
    {
        if ($format) {
            $query = array_merge($query, $this->getFormat($format));
        }
        if ($sender) {
            $query['sender'] = $sender;
        }

        return $this->request("send", array_merge($query, [
            "cost" => 1,
            "phones" => $phones,
            "mes" => $message,
            "translit" => $translit,
        ]));
    }

    /**
     * Функция проверки статуса отправленного SMS или HLR-запроса
     *
     * @param $id - ID cообщения или список ID через запятую
     * @param $phone - номер телефона или список номеров через запятую
     * @param int $all - вернуть все данные отправленного SMS, включая текст сообщения (0,1 или 2)
     *
     * @return \Brandshopru\Smsc\Response
     * @throws \Exception
     */
    public function get_status($id, $phone, $all = 0)
    {
        return $this->request("status", [
            "phone" => $phone,
            "id" => $id,
            "all" => (int)$all,
        ]);
    }

    /**
     * Функция получения баланса
     *
     * @return \Brandshopru\Smsc\Response
     * @throws \Exception
     */
    public function get_balance()
    {
        return $this->request("balance");
    }

    /**
     * Получение истории отправленных sms-сообщений
     *
     * @param $phone
     * @param null $start_date
     * @param null $end_date
     *
     * @return \Brandshopru\Smsc\Response
     * @throws \Exception
     */
    public function get_sms_history($phone = null, $start_date = null, $end_date = null) {
        if (!$start_date) {
            $start_date = date('d.m.Y', strtotime('-6 month'));
        }
        if (!$end_date) {
            $end_date = date('d.m.Y');
        }
        return $this->get_history($start_date, $end_date, $phone);
    }

    /**
     * Получение истории отправленных сообщений
     *
     * @param $start_date - начальная дата в периоде, за который запрашивается история. Формат: 'дд.мм.гггг'.
     * @param $end_date - конечная дата в периоде. Если не указана, то возвращаются данные с начальной даты. Формат: 'дд.мм.гггг'.
     * @param $phone - номер или разделенный запятыми список номеров телефонов, для которых необходимо получить историю отправленных SMS-сообщений.
     * @param $email - e-mail адрес или разделенный запятыми список адресов, для которых необходимо получить историю отправленных e-mail сообщений.
     * @param $format - признак запроса e-mail сообщений.
     *              0 (по умолчанию) – запрос SMS-сообщений.
     *              8 – запрос e-mail сообщений.
     * @param $limit - количество возвращаемых в ответе сообщений. Максимальное значение равно 1000.
     * @param $last_id - глобальный идентификатор сообщения (параметр int_id в ответе Сервера), назначаемый Сервером автоматически. Используется для запроса списка сообщений, отправленных до сообщения с указанным идентификатором.
     *
     * @return \Brandshopru\Smsc\Response
     * @throws \Exception
     */
    public function get_history($start_date, $end_date, $phone = null, $email = null, $format = 0, $limit = 1000, $last_id = null) {
        return $this->request( "get", [
            "get_messages" => 1,
            "start" => $start_date,
            "end" => $end_date,
            "phone" => $phone,
            "email" => $email,
            "format" => $format,
            "cnt" => $limit,
            "prev_id" => $last_id,
        ]);
    }

    /**
     * Функция вызова запроса. Формирует URL и делает 5 попыток чтения через разные подключения к сервису
     *
     * @param $cmd
     * @param array $data
     * @param array $files
     *
     * @return \Brandshopru\Smsc\Response
     * @throws \Exception
     */
    private function request($cmd, $data = [], $files = [])
    {
        $base_url = ($this->useHttps ? "https" : "http")."://smsc.ru/sys/$cmd.php";
        $data = array_merge($data, [
            "login" => $this->login,
            "psw" => $this->password,
            "fmt" => 3,
            "charset" => $this->charset,
        ]);

        $postData = [];

        $usePost = $this->methodPost || strlen($base_url."?".http_build_query($data)) > 2000 || $files;
        if ($usePost) {
            foreach ($data as $key => $value) {
                $postData[] = [
                    "name"     => $key,
                    "contents" => $value,
                ];
            }
            foreach ($files as $file_id => $path) {
                if (file_exists($path)) {
                    $postData[] = [
                        "name"     => "file".$file_id,
                        "contents" => fopen($path, "r"),
                    ];
                }
            }
        }

        $client = new \GuzzleHttp\Client([
            "headers" => ["Expect"=>""],
            "timeout" => 20,
            "verify" => false,
        ]);

        $i = 0;
        do {
            $url = $base_url;
            if ($i++) {
                $url = str_replace("://smsc.ru/", "://www".$i.".smsc.ru/", $url);
            }
            $options = [
                "connect_timeout" => (2 + $i),
            ];
            if ($usePost) {
                $httpResponse = $client->request("POST", $url, array_replace_recursive($options, [
                    "multipart" => $postData,
                ]));
            } else {
                $httpResponse = $client->request("GET", $url, array_replace_recursive($options, [
                    "query" => $data,
                ]));
            }
            $response = new Response($httpResponse);
        } while (!$response->isOk() && $i < 5);

        return $response;
    }

    private function getFormat($id) {
        $formats = [
            1 => ["flash" => 1],
            2 => ["push" => 1],
            3 => ["hlr" => 1],
            4 => ["bin" => 1],
            5 => ["bin" => 2],
            6 => ["ping" => 1],
            7 => ["mms" => 1],
            8 => ["mail" => 1],
            9 => ["call" => 1],
            10 => ["viber" => 1],
            11 => ["soc" => 1],
        ];
        return isset($formats[$id]) ? $formats[$id] : false;
    }
}