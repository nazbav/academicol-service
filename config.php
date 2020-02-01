<?php
const WRITE_LOGS = true;//Логирование всех уровней ошибок
const LOGS_DIRECTORY = 'logs/';//Папка логов
const LANG_BOT = 'lang/RU_ru.php';//Язык бота
const GROUP_ID = 1;//id
const ACCESS_TOKEN = 'jkhughuiuiRWEQRQW798UIYui786987hg';//Токен сообщества
const API_VERSION = '5.95';
const DB_HOST = 'localhost';
const DB_PORT = 3306;
const DB_NAME = 'bot';
const DB_USER = 'usr';
const DB_PASSWORD = '1337';
const SLEEP_TIME = 30 * 60;//Сканирование замен раз в 30 минут.
const SPECIAL = 'ПСО|ПД|ИСП|ТОП|БД|ГД|ПСО|ПКС|ЗИО|Б|К';//Сокращение специальностей
const TEACHER_NAMES_MASK = '%[А-Я]([а-я]+)\s[А-Я]\.[А-Я]\.%u'; //Поиск преподавателей
const REPLACES_URL = 'https://blat.ml/zameny.php'; //адрес замен
const WAIT_FOR_VK = 3;//ожидание между запросами к вк.
const TASK_LIMIT = 20;//Количество обрабатываемых заданий за раз.
const VK_LONG_PULL_WAIT = 55;//Ожмдание ответа от вк.
/*
 * Автоматическая отчистка, заданна периодом из за возможного нахождения бота в момент очистки в состоянии сна (sleep()).
 */
const DAY_CLEAR = 7; //Очистка базы от мусара по воскресеньям
const TIME_CLEAR_MIN = '23:00';//Время начала очистки ЧЧ.ММ
const TIME_CLEAR_MAX = '23:59';//Время конца очистки ЧЧ.ММ


/**
 * @param $exception
 */
function ExceptionWriter(Throwable $exception): void
{
    if (WRITE_LOGS) {
        $_M = include LANG_BOT;
        $error = $_M['EXCEPTION']['DATE'] . date('d-m-Y h:i:s') . ":\r\n";
        $error .= $_M['EXCEPTION']['TEXT'] . $exception->getMessage() . "\r\n";
        $error .= $_M['EXCEPTION']['CODE'] . $exception->getCode() . "\r\n";
        $error .= $_M['EXCEPTION']['FILE'] . $exception->getFile() . ":" . $exception->getLine() . "\r\n";
        $error .= $_M['EXCEPTION']['PATH'] . $exception->getTraceAsString() . "\r\n";
        $file_log = fopen(LOGS_DIRECTORY . 'error_log_' . basename(__FILE__, '.php') . '_' . date('d-m-Y_h') . '.log', 'a');
        fwrite($file_log, $error);
        fclose($file_log);
    }
}

/**
 * @param string $url
 * @return bool|string
 * @throws Exception
 */
function _curl(string $url)
{
    $curl_init = curl_init($url);
    curl_setopt_array($curl_init, [CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HEADER => false,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_RETURNTRANSFER => true,
    ]);
    $response = curl_exec($curl_init);
    $curl_error_code = curl_errno($curl_init);
    $curl_error = curl_error($curl_init);
    curl_close($curl_init);
    if ($curl_error || $curl_error_code) {
        $error_msg = "Failed curl request. Curl error {$curl_error_code}";
        if ($curl_error) {
            $error_msg .= ": {$curl_error}";
        }
        $error_msg .= '.';
        throw new Exception($error_msg);
    }
    return $response;
}