<?php

namespace Brandshopru\Smsc;

class Client
{
    // SMSC.RU API (smsc.ru) версия 3.8 (03.07.2019)

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
     * @return array        - массив (<id>, <количество sms>, <стоимость>, <баланс>) в случае успешной отправки
     *                          либо массив (<id>, -<код ошибки>) в случае ошибки
     */
    public function send_sms(
        $phones,
        $message,
        $translit = 0,
        $time = 0,
        $id = 0,
        $format = 0,
        $sender = false,
        $query = "",
        $files = []
    ) {
        static $formats = [
            1 => "flash=1",
            "push=1",
            "hlr=1",
            "bin=1",
            "bin=2",
            "ping=1",
            "mms=1",
            "mail=1",
            "call=1",
            "viber=1",
            "soc=1",
        ];

        return $this->_smsc_send_cmd("send", "cost=3&phones=".urlencode($phones)."&mes=".urlencode($message).
            "&translit=$translit&id=$id".($format > 0 ? "&".$formats[$format] : "").
            ($sender === false ? "" : "&sender=".urlencode($sender)).
            ($time ? "&time=".urlencode($time) : "").($query ? "&$query" : ""), $files);
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
     * @param string $query - строка дополнительных параметров, добавляемая в URL-запрос ("list=79999999999:Ваш пароль: 123\n78888888888:Ваш пароль: 456")
     *
     * @return array    - массив (<стоимость>, <количество sms>) либо массив (0, -<код ошибки>) в случае ошибки
     */
    public function get_sms_cost($phones, $message, $translit = 0, $format = 0, $sender = false, $query = "")
    {
        static $formats = [
            1 => "flash=1",
            "push=1",
            "hlr=1",
            "bin=1",
            "bin=2",
            "ping=1",
            "mms=1",
            "mail=1",
            "call=1",
            "viber=1",
            "soc=1",
        ];

        return $this->_smsc_send_cmd("send", "cost=1&phones=".urlencode($phones)."&mes=".urlencode($message).
            ($sender === false ? "" : "&sender=".urlencode($sender)).
            "&translit=$translit".($format > 0 ? "&".$formats[$format] : "").($query ? "&$query" : ""));
    }

    /**
     * Функция проверки статуса отправленного SMS или HLR-запроса
     *
     * @param $id - ID cообщения или список ID через запятую
     * @param $phone - номер телефона или список номеров через запятую
     * @param int $all - вернуть все данные отправленного SMS, включая текст сообщения (0,1 или 2)
     *
     * @return array    массив (для множественного запроса двумерный массив):
     *                  для одиночного SMS-сообщения:
     *                      (<статус>, <время изменения>, <код ошибки доставки>)
     *                  для HLR-запроса:
     *                      (<статус>, <время изменения>, <код ошибки sms>, <код IMSI SIM-карты>, <номер сервис-центра>,
     *                      <код страны регистрации>, <код оператора>, <название страны регистрации>, <название оператора>,
     *                      <название роуминговой страны>, <название роумингового оператора>)
     *                  при $all = 1 дополнительно возвращаются элементы в конце массива:
     *                      (<время отправки>, <номер телефона>, <стоимость>, <sender id>, <название статуса>, <текст сообщения>)
     *                  при $all = 2 дополнительно возвращаются элементы <страна>, <оператор> и <регион>
     *
     *                  при множественном запросе:
     *                  при $all = 0, то для каждого сообщения или HLR-запроса дополнительно возвращается <ID сообщения> и <номер телефона>
     *                  при $all = 1 или $all = 2, то в ответ добавляется <ID сообщения>
     *
     *                  либо массив (0, -<код ошибки>) в случае ошибки
     */
    public function get_status($id, $phone, $all = 0)
    {
        return $this->_smsc_send_cmd("status", "phone=".urlencode($phone)."&id=".urlencode($id)."&all=".(int)$all);
    }

    /**
     * Функция получения баланса
     *
     * @return bool - баланс в виде строки или false в случае ошибки
     */
    public function get_balance()
    {
        return $this->_smsc_send_cmd("balance");
    }

    /**
     * Получение истории отправленных sms-сообщений
     *
     * @param $phone - номер или разделенный запятыми список номеров телефонов, для которых необходимо получить историю отправленных SMS-сообщений.
     * @param $start_date - начальная дата в периоде, за который запрашивается история.
     * @param $end_date - конечная дата в периоде. Если не указана, то возвращаются данные с начальной даты.
     *
     * @return array
     */
    public function get_sms_history($phone, $start_date = null, $end_date = null) {
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
     * @return array
     */
    public function get_history($start_date, $end_date, $phone, $email = null, $format = 0, $limit = 1000, $last_id = null) {
        return $this->_smsc_send_cmd("get_messages");
    }

    /**
     * Функция вызова запроса. Формирует URL и делает 5 попыток чтения через разные подключения к сервису
     *
     * @param $cmd
     * @param string $arg
     * @param array $files
     * @return mixed
     */
    private function _smsc_send_cmd($cmd, $arg = "", $files = [])
    {
        $url = $_url = ($this->useHttps ? "https" : "http")."://smsc.ru/sys/$cmd.php?login=".urlencode($this->login)."&psw=".urlencode($this->password)."&fmt=3&charset=".$this->charset."&".$arg;

        $i = 0;
        do {
            if ($i++) {
                $url = str_replace('://smsc.ru/', '://www'.$i.'.smsc.ru/', $_url);
            }

            $ret = $this->_smsc_read_url($url, $files, 3 + $i);
        } while ($ret == "" && $i < 5);

        return $ret;
    }

    /**
     * Функция чтения URL. Для работы должно быть доступно: curl или fsockopen (только http) или включена опция allow_url_fopen для file_get_contents
     *
     * @param $url
     * @param $files
     * @param int $tm
     * @return bool|false|string
     */
    private function _smsc_read_url($url, $files, $tm = 5)
    {
        $ret = "";
        $post = $this->methodPost || strlen($url) > 2000 || $files;

        //TODO: Переписать на guzzle
        if (function_exists("curl_init")) {
            static $c = 0; // keepalive

            if (!$c) {
                $c = curl_init();
                curl_setopt_array($c, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_CONNECTTIMEOUT => $tm,
                    CURLOPT_TIMEOUT => 60,
                    CURLOPT_SSL_VERIFYPEER => 0,
                    CURLOPT_HTTPHEADER => ["Expect:"],
                ]);
            }

            curl_setopt($c, CURLOPT_POST, $post);

            if ($post) {
                list($url, $post) = explode("?", $url, 2);

                if ($files) {
                    parse_str($post, $m);

                    foreach ($m as $k => $v) {
                        $m[$k] = isset($v[0]) && $v[0] == "@" ? sprintf("\0%s", $v) : $v;
                    }

                    $post = $m;
                    foreach ($files as $i => $path) {
                        if (file_exists($path)) {
                            $post["file".$i] = function_exists("curl_file_create") ? curl_file_create($path) : "@".$path;
                        }
                    }
                }

                curl_setopt($c, CURLOPT_POSTFIELDS, $post);
            }

            curl_setopt($c, CURLOPT_URL, $url);

            $ret = curl_exec($c);

        } elseif ($files) {
            $error = "Не установлен модуль curl для передачи файлов";
        } else {
            if (!$this->useHttps && function_exists("fsockopen")) {
                $m = parse_url($url);

                if (!$fp = fsockopen($m["host"], 80, $errno, $errstr, $tm)) {
                    $fp = fsockopen("212.24.33.196", 80, $errno, $errstr, $tm);
                }

                if ($fp) {
                    stream_set_timeout($fp, 60);

                    fwrite($fp,
                        ($post ? "POST $m[path]" : "GET $m[path]?$m[query]")." HTTP/1.1\r\nHost: smsc.ru\r\nUser-Agent: PHP".($post ? "\r\nContent-Type: application/x-www-form-urlencoded\r\nContent-Length: ".strlen($m['query']) : "")."\r\nConnection: Close\r\n\r\n".($post ? $m['query'] : ""));

                    while (!feof($fp)) {
                        $ret .= fgets($fp, 1024);
                    }
                    list(, $ret) = explode("\r\n\r\n", $ret, 2);

                    fclose($fp);
                }
            } else {
                $ret = file_get_contents($url);
            }
        }

        return $ret;
    }
}