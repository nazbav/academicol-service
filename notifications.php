<?php
/*
 * Файл сервиса отрправки сообщений.
 */

use Krugozor\Database\Mysql\Mysql;
use SMITExecute\Library\ExecuteRequest;
use VK\Client\VKApiClient;

ini_set('date.timezone', 'Europe/Volgograd');
ini_set('include_path', '/mnt/sda3/bot/daemon/'); //-- указываем директорию с файлами бота.
try {
    include_once('vendor/autoload.php');
    include_once('config.php');
    include_once('database.php');
    include_once('parser.php');

    $vk = new VKApiClient(API_VERSION);
    $_M = include LANG_BOT;
    $sleep = WAIT_FOR_VK;
    $run = true;
    $mysql = Mysql::create(DB_HOST, DB_USER, DB_PASSWORD, DB_PORT)->setDatabaseName(DB_NAME)->setCharset("utf8");
    $database = new DataBase($mysql);
    do {
        $time_start2 = microtime(true);
        if (!$mysql->getMysqli()->ping()) {//Если база мертва
            $mysql = Mysql::create(DB_HOST, DB_USER, DB_PASSWORD, DB_PORT)->setDatabaseName(DB_NAME)->setCharset("utf8");
            $database = new DataBase($mysql);
        }
        $site = json_decode(_curl(REPLACES_URL), true);
        $time_start = time();
        if (is_array($site)) {
            foreach ($site as $replaces) {
                if ($replaces['date'] >= time() && !$database->replaces_file_name($replaces['name'])) {
                    $doc_text = read_doc($replaces['url']);
                    $replaces['url'] = 'https://docs.google.com/viewer?url=' . urlencode($replaces['url']);
                    $replaces['url'] = $vk->utils()->getShortLink(ACCESS_TOKEN, ['url' => $replaces['url']])['short_url'];
                    if ($doc_text) {
                        $tags = [];
                        $doc_text = mb_convert_encoding($doc_text, 'utf-8', mb_detect_encoding($doc_text));
                        if (preg_match_all(TEACHER_NAMES_MASK, $doc_text, $matches)) {
                            $matches[0] = array_unique($matches[0]);
                            foreach ($matches[0] as $tag) {
                                $tags [] = $tag;
                            }
                        }
                        $doc_text = strtr($doc_text, ["\r\n" => '', "\r" => '', "\n" => '', "	" => '', ' ' => '']);
                        $search_groups = '((([1-5]{1})(' . SPECIAL . ')(\-[0-9]{1,2})(\-[0-9]{1})|([1-5]{1})(' . SPECIAL . ')(\-(11|9)))(,)(([1-5]{1})(' . SPECIAL . ')(\-[0-9]{1,2})(\-[0-9]{1})|([1-5]{1})(' . SPECIAL . ')(\-(11|9))))|(([1-5]{1})(' . SPECIAL . ')(\-[0-9]{1,2})(\-[0-9]{1})|([1-5]{1})(' . SPECIAL . ')(\-(11|9)))';
                        if (preg_match_all('/' . $search_groups . '/', $doc_text, $matches)) {
                            $matches[0] = array_unique($matches[0]);
                            foreach ($matches[0] as $tag) {
                                $tags [] = $tag;
                            }
                        }
                        $tags = array_unique($tags);
                        $replace_file = $database->replaces_add($replaces, $tags);
                        $database->tasks_add($replace_file);
                    }
                }
            }

            //Авто удаление старых замен. Вроде пока работает. берет замены с сайта и удаляет все кроме тех которые 1 на сайте, 2 актуальны.
            $day_clear = date('N', time());
            $time_clear = date('H:i');
            if (($day_clear == DAY_CLEAR) && ($time_clear >= TIME_CLEAR_MIN && TIME_CLEAR_MAX >= $time_clear)) {
                $replaces2clean = ['28 декабря 2019'];//Список замен которые нельзя удалять из базы.
                foreach ($site as $replaces) {
                    if ($replaces['date'] >= time()) {
                        $replaces2clean[] = $replaces['name'];
                    }
                }
                $database->database_clear($replaces2clean);
            }
        }

        $task_count = $database->task_count();
        if ($task_count) {
            $task_to_delete = [];
            $task_limit = (int)($task_count / TASK_LIMIT);
            for ($i = 0; $i <= $task_limit; $i++) { //бъем на пакеты по $task_limit шт.
                $tasks = $database->task_get(TASK_LIMIT);
                if (!empty($tasks)) {
                    $execute = false;//Если есть запросы для вк включаем режим слияния запросов
                    $builder = new ExecuteRequest();
                    foreach ($tasks as $task) {
                        switch ($task['type']) {//внешнее поле
                            case 1:
                                $execute = true;
                                $message = sprintf($_M['REPLACES_TEMPLATE'], $task['tag'], $task['name'], $task['url']) . "\r\n" . $_M['TIMETABLE_LINK'];
                                $builder->add(
                                    $builder->create()
                                        ->setMainMethod("messages")
                                        ->setSubMethod("send")
                                        ->setParams(['peer_id' => $task['peer_id'], 'random_id' => rand(0, 2000000), 'message' => $message, 'v' => API_VERSION])
                                );
                                $task_to_delete[] = $task['id'];
                                break;
                            default:
                                $task_to_delete[] = $task['id'];
                                break;
                        }
                    }
                    if ($execute) {//если есть отправка на вк.
                        $code_strings = $builder->convertToJS();
                        $code = implode(PHP_EOL, $code_strings);
                        $vk->getRequest()->post('execute', ACCESS_TOKEN, ['v' => API_VERSION,
                            'code' => $code]);
                    }
                }
                sleep(WAIT_FOR_VK);
            }
            $database->task_delete($task_to_delete);
        }
        $sleep = (SLEEP_TIME - ((time() - $time_start) > WAIT_FOR_VK) ? (SLEEP_TIME - ((time() - $time_start))) : WAIT_FOR_VK);//Сложно.
        echo microtime(true) - $time_start, ' ';
        sleep($sleep);
    } while ($run);

} catch (Throwable $exception) {
    ExceptionWriter($exception);
}