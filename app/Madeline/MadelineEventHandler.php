<?php

namespace App\Madeline;

use App\Models\Status;
use App\Models\VPNUser;
use danog\MadelineProto\EventHandler;
use danog\MadelineProto\Tools;
use danog\MadelineProto\API;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Settings;
use danog\MadelineProto\RPCErrorException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use PhpParser\Builder\Class_;
use phpseclib3\Math\BigInteger\Engines\PHP;


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

//        yield $this->messages->sendMessage(['peer' => $update, 'message' => "This userbot is powered by MadelineProto!", 'reply_to_msg_id' => isset($update['message']['id']) ? $update['message']['id'] : null, 'parse_mode' => 'HTML']);
//        if (isset($update['message']['media']) && $update['message']['media']['_'] !== 'messageMediaGame') {
//            yield $this->messages->sendMedia(['peer' => $update, 'message' => $update['message']['message'], 'media' => $update]);
//        }


//        yield $this->sendMessageToUpdateOwner($res);

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
                yield $this->sendMessageWithMenuToUpdateOwner('select form menu','main');
                $this->changeUpdateUserStatus('menu');
                goto Done;
            }
            case 'menu':{

                switch ($this->getUpdateMessage()){
                    case 'create new user':{

                        $this->changeUpdateUserStatus('create new user');
                        $this->changeUpdateUserSubStatus('create user get phone number');

                        yield $this->sendMessageToUpdateOwner('send me new user phone number');

                        goto Done;
                    }
                    case 'delete user':{

                        $this->changeUpdateUserStatus('delete user');
                        $this->changeUpdateUserSubStatus('delete user get phone number');

                        $vpnUsers = VPNUser::all();

                        $phones = [];
                        foreach ($vpnUsers as $vpnUser){
                            $phones[] = ''
                                .$vpnUser->phone
                                .' '
                                .$vpnUser->first_name
                                .' '
                                .$vpnUser->last_name
                                .PHP_EOL
                                .$vpnUser->days_to_expire;
                        }

                        yield $this->sendMessageWithDynamicMenuToUpdateOwner('send me new user phone number',$phones);

                        goto Done;
                    }
                    default:{
                        yield $this->sendMessageWithMenuToUpdateOwner('wrong answer select from menu','main');
                        goto Done;
                    }
                }


//                goto Done;
            }
            case 'create new user':{

                switch ($status->sub_status){
                    case 'create user get phone number':{

                        $phone_number = $this->getUpdateMessage();

                        $validator = Validator::make(['phone_number'=>$phone_number],[
                            'phone_number' => [
                                'required',
                                'numeric',
                                'max_digits:16',
                                'min_digits:12',
                                'doesnt_start_with:+',
                                'doesnt_start_with:0',
                                'doesnt_start_with:00',
                                'doesnt_start_with:000',

                            ]
                        ]);

                        if ($validator->fails()) {

                            $this->sendMessageToUpdateOwner('validation message: '.PHP_EOL.$validator->messages());

                            $this->backToMainMenu();

                            goto Done;
                        }

                        if (VPNUser::where('phone','=',$phone_number)->exists()){

                            $this->sendMessageToUpdateOwner('this phone number already exists');

                            $this->backToMainMenu();

                            goto Done;
                        }


                        $vpnUser = new VPNUser();
                        $vpnUser->phone = $phone_number;
                        $vpnUser->statuses_id = $this->getUpdateUserId();
                        $vpnUser->save();

                        $this->setNewTempString($phone_number);

                        $this->changeUpdateUserSubStatus('create user get first name');

                        yield $this->sendMessageToUpdateOwner('send me new user first name');

                        goto Done;
                    }

                    case 'create user get first name':{

                        $first_name = $this->getUpdateMessage();

                        $validator = Validator::make(['first_name'=>$first_name],[
                            'first_name' => [
                                'required',
                                'string',
                                'max:255',
                                'min:3',
                            ]
                        ]);

                        if ($validator->fails()) {

                            $this->sendMessageToUpdateOwner('validation message: '.PHP_EOL.$validator->messages());

                            goto Done;
                        }

                        VPNUser::where('phone','=',$status->temp)->update([
                            'first_name' => $first_name,
                        ]);

                        $this->changeUpdateUserSubStatus('create user get last name');

                        yield $this->sendMessageToUpdateOwner('send me new user last name');

                        goto Done;
                    }
                    case 'create user get last name':{

                        $last_name = $this->getUpdateMessage();

                        $validator = Validator::make(['last_name'=>$last_name],[
                            'last_name' => [
                                'required',
                                'string',
                                'max:255',
                                'min:3',
                            ]
                        ]);

                        if ($validator->fails()) {

                            $this->sendMessageToUpdateOwner('validation message: '.PHP_EOL.$validator->messages());

                            goto Done;
                        }

                        VPNUser::where('phone','=',$status->temp)->update([
                            'last_name' => $last_name,
                        ]);

                        $this->changeUpdateUserSubStatus('create user get password');

                        yield $this->sendMessageToUpdateOwner('send me new user password');

                        goto Done;
                    }
                    case 'create user get password':{

                        $password = $this->getUpdateMessage();

                        $validator = Validator::make(['password'=>$password],[
                            'password' => [
                                'required',
                                'string',
                                'max:255',
                                'min:8',
                            ]
                        ]);

                        if ($validator->fails()) {

                            $this->sendMessageToUpdateOwner('validation message: '.PHP_EOL.$validator->messages());

                            goto Done;
                        }

                        VPNUser::where('phone','=',$status->temp)->update([
                            'password' => $password,
                        ]);

                        $this->changeUpdateUserSubStatus('create user get expire days');

                        yield $this->sendMessageToUpdateOwner('send me days until this user should lost access zero means user lost access tomorrow');

                        goto Done;
                    }
                    case 'create user get expire days':{

                        $days_to_expire = $this->getUpdateMessage();

                        $validator = Validator::make(['days_to_expire'=>$days_to_expire],[
                            'days_to_expire' => [
                                'required',
                                'numeric',
                                'max:364',
                                'min:0',
                            ]
                        ]);

                        if ($validator->fails()) {

                            $this->sendMessageToUpdateOwner('validation message: '.PHP_EOL.$validator->messages());

                            goto Done;
                        }

                        VPNUser::where('phone','=',$status->temp)->update([
                            'days_to_expire' => $days_to_expire,
                        ]);

                        $this->changeUpdateUserSubStatus('create user confirm information');

                        $vpnUser = VPNUser::where('phone','=',$status->temp)->first();

                        yield $this->sendMessageWithMenuToUpdateOwner(
                            'ok please confirm information:'.PHP_EOL
                            .'First Name: '.$vpnUser->first_name.PHP_EOL
                            .'Last Name: '.$vpnUser->last_name.PHP_EOL
                            .'phone: '.$vpnUser->phone.PHP_EOL
                            .'password: '.$vpnUser->password.PHP_EOL
                            .'days until expires: '. $vpnUser->days_to_expire.PHP_EOL
                            .'create time: '.$vpnUser->created_at.PHP_EOL
                            .'last modified: '.$vpnUser->updated_at.PHP_EOL
                            .'created by: '.$vpnUser->Status->id.PHP_EOL

                        ,'confirm');

                        goto Done;

                    }
                    case 'create user confirm information':{

                        switch ($this->getUpdateMessage()){
                            case 'correct':{

                                // todo create unix user

                                $vpnUser = VPNUser::where('phone','=',$status->temp)->first();

                                $result = Artisan::call('createUnixUser '.$vpnUser->phone.' '.$vpnUser->password.' ');
                                if ($result == 'already exists.'){

                                    $this->sendMessageToUpdateOwner('unix user already exists.');
                                }
                                else if ($result == 'something went wrong!'){
                                    $this->sendMessageToUpdateOwner('something went wrong!');
                                }
                                else{
                                    $this->sendMessageToUpdateOwner('user created successfully.');
                                }

                                $this->backToMainMenu();

                                goto Done;
                            }
                            case 'incorrect':{

                                VPNUser::where('phone','=',$status->temp)->delete();

                                $this->backToMainMenu();

                                goto Done;
                            }
                            default:{
                                yield $this->sendMessageWithMenuToUpdateOwner('wrong answer please use keyboard','confirm');
                                goto Done;
                            }
                        }
//                        goto Done;
                    }

                }
            }

            case 'delete user':{

                switch ($status->sub_status){
                    case 'delete user get phone number':{

                        $message = $this->getUpdateMessage();

                        $temp = explode(' ',$message);

                        $phone_number = $temp[0];

                        $validator = Validator::make(['phone_number'=>$phone_number],[
                            'phone_number' => [
                                'required',
                                'numeric',
                                'max_digits:16',
                                'min_digits:12',
                                'doesnt_start_with:+',
                                'doesnt_start_with:0',
                                'doesnt_start_with:00',
                                'doesnt_start_with:000',
                                'exists:v_p_n_users,phone'

                            ]
                        ]);

                        if ($validator->fails()) {

                            $this->sendMessageToUpdateOwner('validation message: '.PHP_EOL.$validator->messages());

                            $this->backToMainMenu();

                            goto Done;
                        }

                        $vpnUser = VPNUser::where('phone','=',$phone_number)->first();

                        $this->setNewTempString($vpnUser->phone);
                        $this->changeUpdateUserSubStatus('delete user confirm phone number');

                        yield $this->sendMessageWithMenuToUpdateOwner(
                            'ok please confirm information to delete:'.PHP_EOL
                            .'First Name: '.$vpnUser->first_name.PHP_EOL
                            .'Last Name: '.$vpnUser->last_name.PHP_EOL
                            .'phone: '.$vpnUser->phone.PHP_EOL
                            .'password: '.$vpnUser->password.PHP_EOL
                            .'days until expires: '. $vpnUser->days_to_expire.PHP_EOL
                            .'create time: '.$vpnUser->created_at.PHP_EOL
                            .'last modified: '.$vpnUser->updated_at.PHP_EOL
                            .'created by: '.$vpnUser->Status->id.PHP_EOL

                            ,'confirm');

                        goto Done;
                    }
                    case 'delete user confirm phone number':{

                        switch ($this->getUpdateMessage()){

                            case 'correct':{

                                // todo delete unix user

                                $vpnUser = VPNUser::where('phone','=',$status->temp)->first();

                                $result = Artisan::call('deleteUnixUser '.$vpnUser->phone.' ');
                                if ($result == 'does not exist'){

                                    $this->sendMessageToUpdateOwner('unix user does not exist');
                                }
                                else if ($result == 'something went wrong!'){
                                    $this->sendMessageToUpdateOwner('something went wrong!');
                                }
                                else{
                                    $this->sendMessageToUpdateOwner('user deleted successfully.');

                                    VPNUser::where('phone','=',$status->temp)->delete();
                                }


                                $this->backToMainMenu();

                                goto Done;
                            }

                            case 'incorrect':{

                                $this->backToMainMenu();

                                goto Done;
                            }
                        }

                    }
                }




                goto Done;
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

    public function setNewTempString($s){

        Status::where('id','=',$this->getUpdateUserId())->update([
           'temp' => $s,
        ]);
    }

    public function getUpdateUserId(){

        return $this->update['message']['peer_id']['user_id'];
    }

    public function backToMainMenu(){

        $this->changeUpdateUserStatus('menu');
        $this->changeUpdateUserSubStatus('');
        $this->setNewTempString('');

        $this->sendMessageWithMenuToUpdateOwner('getting back to main menu','main');

    }


    public function getUpdateMessage(){

        return $this->update['message']['message'];
    }

    public function changeUpdateUserStatus($newStatus){

        return Status::where('id','=',$this->getUpdateUserId())->update([
            'status' => $newStatus,
        ]);

    }

    public function changeUpdateUserSubStatus($newSubStatus){

        return Status::where('id','=',$this->getUpdateUserId())->update([
            'sub_status' => $newSubStatus,
        ]);

    }

    public function sendMessageWithMenuToUpdateOwner($m,$menuName){

        return $this->messages->sendMessage([
            'peer' =>$this->update,
            'message' => $m,
            'reply_markup' => $this->getMenuMarkupByName($menuName)
        ]);
    }

    public function sendMessageWithDynamicMenuToUpdateOwner($m,$menuValues){

        return $this->messages->sendMessage([
            'peer' =>$this->update,
            'message' => $m,
            'reply_markup' => $this->makeKeyboardMarkup($menuValues)
        ]);
    }

    public function getMenuMarkupByName($menuName){

        return $this->makeKeyboardMarkup($this->getMenuByName($menuName));
    }

    public function makeKeyboardMarkup($values){

        $keyboard = [];
        foreach ($values as $value){

            $keyboard[] = [['text'=>$value]];
        }

        $markup = [
            'keyboard' => $keyboard,
            'resize' => true
        ];

//        $this->sendMessageToUpdateOwner(json_encode($markup,JSON_PRETTY_PRINT));

        return $markup;
    }

    public function getMenuByName($menuName){

        $menus = [
            'main' =>[
                'create new user',
                'delete user',
            ],
            'confirm'=>[
                'correct',
                'incorrect'
            ]
        ];

        return $menus[$menuName];
    }
}

