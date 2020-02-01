<?php

require "../vendor/autoload.php";

use SMITExecute\Library\ExecuteRequest;
use VK\Client\VKApiClient;
use Monolog\Logger;

$builder = new ExecuteRequest();
$logger = new Logger("execute.builder");

$builder->add(
        $builder->create()
        ->setMainMethod("messages")
        ->setSubMethod("getConversations")
        ->setParams([
            "user_id" => 89481221
        ])
);

$builder->add(
    $builder->create()
        ->setMainMethod("users")
        ->setSubMethod("get")
        ->setParams([
            "user_id" => 89481221,
        ])
);

$code_strings = $builder->convertToJS();
$code = implode(PHP_EOL, $code_strings);

$logger->info("Converted code to javascript", ["code" => $code]);

$vk = new VKApiClient('5.00');

$response = $vk->getRequest()->post('execute', "token", [
    'code' => $code,
]);

$logger->info("Execute response from vk", $response);