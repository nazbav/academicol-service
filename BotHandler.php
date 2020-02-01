<?php

use VK\CallbackApi\VKCallbackApiHandler;
use VK\Client\VKApiClient as VKApiClient;
use VK\Exceptions\Api\VKApiMessagesCantFwdException;
use VK\Exceptions\Api\VKApiMessagesChatBotFeatureException;
use VK\Exceptions\Api\VKApiMessagesChatUserNoAccessException;
use VK\Exceptions\Api\VKApiMessagesContactNotFoundException;
use VK\Exceptions\Api\VKApiMessagesDenySendException;
use VK\Exceptions\Api\VKApiMessagesKeyboardInvalidException;
use VK\Exceptions\Api\VKApiMessagesPrivacyException;
use VK\Exceptions\Api\VKApiMessagesTooLongForwardsException;
use VK\Exceptions\Api\VKApiMessagesTooLongMessageException;
use VK\Exceptions\Api\VKApiMessagesTooManyPostsException;
use VK\Exceptions\Api\VKApiMessagesUserBlockedException;
use VK\Exceptions\VKApiException;
use VK\Exceptions\VKClientException;


class BotHandler extends VKCallbackApiHandler
{
    private $vk;
    private $database;

    /**
     * BotHandler constructor.
     * @param VKApiClient $vk
     * @param DataBase $db
     */
    public function __construct(VKApiClient $vk, DataBase $db)
    {
        $this->vk = $vk;
        $this->database = $db;
    }

    /**
     * @param int $group_id
     * @param string|null $secret
     * @param array $object
     * @throws VKApiMessagesCantFwdException
     * @throws VKApiMessagesChatBotFeatureException
     * @throws VKApiMessagesChatUserNoAccessException
     * @throws VKApiMessagesContactNotFoundException
     * @throws VKApiMessagesDenySendException
     * @throws VKApiMessagesKeyboardInvalidException
     * @throws VKApiMessagesPrivacyException
     * @throws VKApiMessagesTooLongForwardsException
     * @throws VKApiMessagesTooLongMessageException
     * @throws VKApiMessagesTooManyPostsException
     * @throws VKApiMessagesUserBlockedException
     * @throws VKApiException
     * @throws VKClientException
     * @throws Exception
     */
    public function messageNew(int $group_id, ?string $secret, array $object)
    {
        if (GROUP_ID == $group_id) {
            $_M = include LANG_BOT;
            $attachments = [];
            //$user_id = (int)$object['from_id'];
            $use_object = preg_replace('/\[club' . GROUP_ID . '\|.*\]/ui', '', strtr($object['text'], ["\r" => '', "\n" => '']));
            if (isset($object['action']['type']) && $object['action']['type'] == "chat_invite_user" && $object['action']['member_id'] == -GROUP_ID) {
                $message = $_M['SELF_BOT_JOIN_MESSAGE'];
            } else {
                $message = '';
            }

            //Command 1
            if (preg_match('/\/групп((ы|а) | )(.*)/iu', $use_object, $matches1, PREG_OFFSET_CAPTURE, 0)) {
                $matches1[0][0] = strtr($matches1[0][0], ["	" => '', ' ' => '']);
                $groups = SPECIAL;
                if (preg_match_all('/((([1-5]{1})(' . $groups . ')(\-(11|9))(\-[0-9]{1})|([1-5]{1})(' . $groups . ')(\-(11|9)))(,)(([1-5]{1})(' . $groups . ')(\-[0-9]{1,2})(\-[0-9]{1})|([1-5]{1})(' . $groups . ')(\-(11|9))))|(([1-5]{1})(' . $groups . ')(\-[0-9]{1,2})(\-[0-9]{1})|([1-5]{1})(' . $groups . ')(\-(11|9)))/', $matches1[0][0], $matches)) {
                    $this->database->peers_add($object['peer_id'], (string)$matches[0][0]);
                    $message = sprintf($_M['GROUP_ADD_MESSAGE'], $matches[0][0]);
                } elseif (isset($matches1[3][0]) && $matches1[3][0] == '0') {
                    $message = $_M['DISABLE_NOTIFY'];
                    $this->database->peers_delete($object['peer_id']);
                } else {
                    $message = $_M['GROUP_NOT_MATCH'];
                }
            }
            //Command 1
            if (preg_match('/\/отключить/iu', $use_object, $matches1, PREG_OFFSET_CAPTURE, 0)) {
                $matches1[0][0] = strtr($matches1[0][0], ["	" => '', ' ' => '']);
                $message = $_M['DISABLE_NOTIFY'];
                $this->database->peers_update($object['peer_id'], 0);
            }
            //Command 1
            if (preg_match('/\/включить/iu', $use_object, $matches1, PREG_OFFSET_CAPTURE, 0)) {
                $matches1[0][0] = strtr($matches1[0][0], ["	" => '', ' ' => '']);
                $message = $_M['ENABLE_NOTIFY'];
                $this->database->peers_update($object['peer_id'], 1);
            }
            //Command 1
            if (preg_match('/\/(преподаватель|препод) (.*)/iu', $use_object, $matches1, PREG_OFFSET_CAPTURE, 0)) {
                $matches1[0][0] = mb_convert_encoding($matches1[2][0], 'utf-8', mb_detect_encoding($matches1[2][0]));
                $search_teachers = '%[А-Я]([а-я]+)\s[А-Я]\.[А-Я]\.%u';
                if (preg_match_all($search_teachers, $matches1[0][0], $matches)) {
                    $this->database->peers_add($object['peer_id'], $matches[0][0]);
                    $message = sprintf($_M['TEACHER_ADD_MESSAGE'], $matches[0][0]);
                } elseif (isset($matches1[2][0]) && $matches1[2][0] == '0') {
                    $message = $_M['DISABLE_NOTIFY'];
                    $this->database->peers_delete($object['peer_id']);
                } else {
                    $message = $_M['TEACHER_NOT_MATCH'];
                }
            }
            //Command 2
            if (preg_match('/\/замены/iu', $use_object, $matches1, PREG_OFFSET_CAPTURE, 0)) {
                $replaces = $this->database->replaces_get_tag($object['peer_id']);
                if ($replaces) {
                    $tag = '';
                    $message2 = '';
                    foreach ($replaces as $replace) {
                        if ($replace['date'] >= time()) {//search
                            $message2 .= $replace['name'] . ' -- ' . $replace['url'] . "\r\n";
                        }
                        $tag = $replace['tag'];
                    }
                    $message = sprintf($_M['REPLACES_TEMPLATE_MASS'], $tag);
                    if (empty($message2)) $message2 = $_M['NO_REPLACES_TEMPLATE'];
                    $message .= $message2 . $_M['TIMETABLE_LINK'];
                } else {
                    $message = $_M['NO_REPLACES_TEMPLATE'];
                }
            }

            if ($message || $attachments) {
                $request = ['peer_id' => $object['peer_id'], 'random_id' => rand(0, 2000000), 'message' => $message, 'attachment' => $attachments ? implode(',', $attachments) : false];
                $this->vk->messages()->send(ACCESS_TOKEN, $request);
            }
        }
    }
}