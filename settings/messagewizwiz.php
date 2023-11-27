<?php 
$msgInfo = json_decode(file_get_contents("messagewizwiz.json"),true);
$offset = $msgInfo['offset']??-1;
$messageParam = json_decode($msgInfo['text']);
$rateLimit = $msgInfo['rateLimit']??0;

include_once '../baseInfo.php';
include_once '../config.php';
include_once 'jdf.php';

if(time() > $rateLimit){
    #$rate = json_decode(file_get_contents("https://api.changeto.technology/api/rate"),true)['result'];
    #if(!empty($rate['USD'])) $botState['USDRate'] = $rate['USD'];
    #if(!empty($rate['TRX'])) $botState['TRXRate'] = $rate['TRX'];


    # trx $ usd rates in rub
    #$tron_usd_rate =  $rate['TRX'] / $rate['USD'];  # 0.101

    $rate_rub_usd = json_decode(file_get_contents("https://v6.exchangerate-api.com/v6/055b2e5d0cdff58c1879fe9d/latest/RUB"),true)['conversion_rates'];
    if(!empty($rate_rub_usd['USD'])) {
         $botState['USDRate'] = $rate_rub_usd['USD']; #0.01131
    }

    $rate_trx_usd = json_decode(file_get_contents("https://api.diadata.org/v1/assetQuotation/Tron/0x0000000000000000000000000000000000000000"),true)['Price']; # 0.1002
    
    
    if(!empty($rate_trx_usd)) {
    $botState['TRXRate'] = $rate_trx_usd / $rate_rub_usd['USD']; # rate trx/rub
    }
    
    # **********************







    
    $stmt = $connection->prepare("SELECT * FROM `setting` WHERE `type` = 'BOT_STATES'");
    $stmt->execute();
    $isExists = $stmt->get_result();
    $stmt->close();
    if($isExists->num_rows>0) $query = "UPDATE `setting` SET `value` = ? WHERE `type` = 'BOT_STATES'";
    else $query = "INSERT INTO `setting` (`type`, `value`) VALUES ('BOT_STATES', ?)";
    $newData = json_encode($botState);
    
    $stmt = $connection->prepare($query);
    $stmt->bind_param("s", $newData);
    $stmt->execute();
    $stmt->close();
    
    $msgInfo['rateLimit'] = strtotime("+1 hour");





    file_put_contents("messagewizwiz.json",json_encode($msgInfo));

}


if($offset == '-1') exit;

if ($offset == '0') {
    if ($messageParam->type == "forwardall") $msg = "Началась операция пересылки для всех";
    else $msg = "Началась операция отправки сообщений всем";

    bot('sendMessage', [
        'chat_id' => $admin,
        'text' => $msg
    ]);
}

$stmt = $connection->prepare("SELECT * FROM `users`ORDER BY `id` LIMIT 50 OFFSET ?");
$stmt->bind_param("i", $offset);
$stmt->execute();
$usersList = $stmt->get_result();
$stmt->close();

if( $usersList->num_rows > 1 ) {
    while($user = $usersList->fetch_assoc()){
        if($messageParam->type == 'text'){
            sendMessage($messageParam->value,json_encode([
                        'inline_keyboard' => [
                            [['text'=>$buttonValues['start_bot'],'callback_data'=>"mainMenu"]]
                            ]
                    ]),null,$user['userid']);
        }elseif($messageParam->type == 'music'){
            bot('sendAudio',[
                'chat_id' => $user['userid'],
                'audio' => $messageParam->value->fileid,
                'caption' => $messageParam->value->caption,
                'reply_markup'=>json_encode([
                        'inline_keyboard' => [
                            [['text'=>$buttonValues['start_bot'],'callback_data'=>"mainMenu"]]
                            ]
                    ])
            ]);
        }elseif($messageParam->type == 'video'){
            bot('sendVideo',[
                'chat_id' => $user['userid'],
                'video' => $messageParam->value->fileid,
                'caption' => $messageParam->value->caption,
                'reply_markup'=>json_encode([
                        'inline_keyboard' => [
                            [['text'=>$buttonValues['start_bot'],'callback_data'=>"mainMenu"]]
                            ]
                    ])
            ]);
        }elseif($messageParam->type == 'voice'){
            bot('sendVoice',[
                'chat_id' => $user['userid'],
                'voice' => $messageParam->value->fileid,
                'caption' => $messageParam->value->caption,
                'reply_markup'=>json_encode([
                        'inline_keyboard' => [
                            [['text'=>$buttonValues['start_bot'],'callback_data'=>"mainMenu"]]
                            ]
                    ])
            ]);
        }elseif($messageParam->type == 'document'){
            bot('sendDocument',[
                'chat_id' => $user['userid'],
                'document' => $messageParam->value->fileid,
                'caption' => $messageParam->value->caption,
                'reply_markup'=>json_encode([
                        'inline_keyboard' => [
                            [['text'=>$buttonValues['start_bot'],'callback_data'=>"mainMenu"]]
                            ]
                    ])
            ]);
        }elseif($messageParam->type == 'photo'){
            bot('sendPhoto', [
                'chat_id' => $user['userid'],
                'photo' => $messageParam->value->fileid,
                'caption' => $messageParam->value->caption,
                'reply_markup'=>json_encode([
                        'inline_keyboard' => [
                            [['text'=>$buttonValues['start_bot'],'callback_data'=>"mainMenu"]]
                            ]
                    ])
            ]); 
        }elseif($messageParam->type == "forwardall"){
            forwardmessage($user['userid'], $messageParam->chat_id, $messageParam->message_id);
        }
        else {
            bot('sendDocument',[
                'chat_id' => $user['userid'],
                'document' => $messageParam->value->fileid,
                'caption' => $messageParam->value->caption,
                'reply_markup'=>json_encode([
                        'inline_keyboard' => [
                            [['text'=>$buttonValues['start_bot'],'callback_data'=>"mainMenu"]]
                            ]
                    ])
            ]);
        }
        $offset++;
    }
    $msgInfo['offset'] = $offset;
    file_put_contents("messagewizwiz.json",json_encode($msgInfo));
}else{
    if ($messageParam->type == "forwardall") $msg = "Операция пересылки для всех успешно выполнена";
    else $msg = "Операция отправки сообщений всем успешно выполнена";
    
    bot('sendMessage', [
        'chat_id' => $admin,
        'text' => $msg . "\nЯ отправил твое сообщение " . $offset . " пользователям"
    ]);
    $msgInfo['offset'] = -1;
    file_put_contents("messagewizwiz.json", json_encode($msgInfo));
    
}

