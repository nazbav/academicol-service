<?php
/*
 * Файл сервиса обработки сообщений.
 */

use Krugozor\Database\Mysql\Mysql;
use VK\CallbackApi\LongPoll\VKCallbackApiLongPollExecutor;
use VK\Client\VKApiClient;

ini_set('date.timezone', 'Europe/Volgograd');
ini_set('include_path', '/mnt/sda3/bot/daemon/'); //-- указываем директорию с файлами бота.

try {
    include_once 'vendor/autoload.php';
    include_once 'config.php';
    include_once 'database.php';
    include_once 'BotHandler.php';

    $vk = new VKApiClient(API_VERSION);
    $mysql = Mysql::create(DB_HOST, DB_USER, DB_PASSWORD, DB_PORT)->setDatabaseName(DB_NAME)->setCharset('utf8');
    $database = new DataBase($mysql);
    $handler = new BotHandler($vk, $database);
    $executor = new VKCallbackApiLongPollExecutor($vk, ACCESS_TOKEN, GROUP_ID, $handler, VK_LONG_PULL_WAIT);

    if ($vk->groups()->getLongPollSettings(ACCESS_TOKEN, ['group_id' => GROUP_ID])['is_enabled'] == 0) {
        $vk->groups()->setLongPollSettings(ACCESS_TOKEN, [
            'group_id'    => GROUP_ID,
            'api_version' => API_VERSION,
            'enabled'     => 1,
            'message_new' => 1,
        ]);
    }
    do {
        $time_start = microtime(true);
        if (!$mysql->getMysqli()->ping()) {//Если база мертва
            $mysql = Mysql::create(DB_HOST, DB_USER, DB_PASSWORD, DB_PORT)->setDatabaseName(DB_NAME)->setCharset('utf8');
            $database = new DataBase($mysql);
            $handler = new BotHandler($vk, $database);
        }
        $executor->listen();
        echo microtime(true) - $time_start, ' ';
        //sleep(WAIT_FOR_VK);//пускай поспит 3 секунды.
    } while (true);
} catch (Throwable $exception) {
    ExceptionWriter($exception);
}
