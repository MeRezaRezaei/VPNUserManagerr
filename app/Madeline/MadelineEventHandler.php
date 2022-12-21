<?php

namespace App\Madeline;

use App\Models\Status;
use danog\MadelineProto\EventHandler;
use danog\MadelineProto\Tools;
use danog\MadelineProto\API;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Settings;
use danog\MadelineProto\RPCErrorException;



class MadelineEventHandler extends EventHandler
{
    /**
     * @var int|string Username or ID of bot admin
     */
    const Programmer = 501558149; // Change this

    const ADMINS = [501558149,];

    /**
     * List of properties automatically stored in database (MySQL, Postgres, redis or memory).
     * @see https://docs.madelineproto.xyz/docs/DATABASE.html
     * @var array
     */
    protected static array $dbProperties = [
        'dataStoredOnDb' => 'array'
    ];

    /**
     * @var DbArray<array>
     */
    protected $dataStoredOnDb;

    /**
     * Get peer(s) where to report errors
     *
     * @return int|string|array
     */
    public function getReportPeers()
    {
        return [self::Programmer];
    }
    /**
     * Called on startup, can contain async calls for initialization of the bot
     */
    public function onStart()
    {
    }
    /**
     * Handle updates from supergroups and channels
     *
     * @param array $update Update
     */
    public function onUpdateNewChannelMessage(array $update): \Generator
    {
        return $this->onUpdateNewMessage($update);
    }
    /**
     * Handle updates from users.
     *
     * @param array $update Update
     *
     * @return \Generator
     */
    public function onUpdateNewMessage(array $update): \Generator
    {
        if ($update['message']['_'] === 'messageEmpty' || $update['message']['out'] ?? false) {
            return;
        }

        $this->update = $update;

        $res = \json_encode($update, JSON_PRETTY_PRINT);

        yield $this->messages->sendMessage(['peer' => $update, 'message' => "This userbot is powered by MadelineProto!", 'reply_to_msg_id' => isset($update['message']['id']) ? $update['message']['id'] : null, 'parse_mode' => 'HTML']);
        if (isset($update['message']['media']) && $update['message']['media']['_'] !== 'messageMediaGame') {
            yield $this->messages->sendMedia(['peer' => $update, 'message' => $update['message']['message'], 'media' => $update]);
        }


        yield $this->sendMessageToUpdateOwner($res);

        if (!Status::where('id','=',$this->getUpdateUserId())->exists()){

            $status = new Status();

            $status->id = $this->getUpdateUserId();
            $status->status = 'new user';

            $status->save();
        }

        $status = Status::find($this->getUpdateUserId());

        switch ($status->status){
            case 'new user':{

                yield $this->sendMessageToUpdateOwner('welcome new user');
                break;
            }

        }

        Done:
    }

    public function sendMessageToUpdateOwner($m){

        return $this->messages->sendMessage([
            'peer' => $this->update,
            'message' => $m,
            //'reply_to_msg_id' => isset($update['message']['id']) ? $update['message']['id'] : null,
            'parse_mode' => 'HTML'
        ]);

    }

    public function getUpdateUserId(){

        return $this->update['message']['peer_id']['user_id'];
    }
}

