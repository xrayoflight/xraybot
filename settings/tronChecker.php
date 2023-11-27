<?php

require '../config.php';
$stmt = $connection->prepare("SELECT * FROM `pays` WHERE `payid` IS NOT NULL AND `state` REGEXP '^[0-9]+$'");
$stmt->execute();
$paysList = $stmt->get_result();
$stmt->close();
if(empty($paymentKeys['tronwallet'])) exit();
$wallet = $paymentKeys['tronwallet'];

while($payParam = $paysList->fetch_assoc()){
    $rowId = $payParam['id'];
    $amount = $payParam['price'];
    $user_id = $payParam['user_id'];
    $payType = $payParam['type'];
    $hash_id = $payParam['payid'];
    $tronPrice = $payParam['tron_price'];
    $state = $payParam['state'];
    
    if($payType == "BUY_SUB") 
    $payDescription = "Покупка подписки";
    elseif($payType == "RENEW_ACCOUNT") 
    $payDescription = "Продление аккаунта";
    elseif($payType == "INCREASE_WALLET") 
    $payDescription = "Пополнение кошелька";
    elseif(preg_match('/^INCREASE_DAY_(\d+)_(\d+)/', $payType)) 
    $payDescription = "Увеличение времени аккаунта";
    elseif(preg_match('/^INCREASE_VOLUME_(\d+)_(\d+)/', $payType)) 
    $payDescription = "Увеличение объема аккаунта";

    
    $result = json_decode(getWebsite($hash_id),true);
    $success = $result['contractRet'];
    
    $stmt = $connection->prepare("SELECT * FROM `users` WHERE `userid` = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $userInfo = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if($success = "SUCCESS" && isset($success)){
        $firstTime = $result['timestamp']/1000;
    	$secondTime = time();
    
    	$dayStamp = 24 * 60 * 60;
        $dateDiff = $secondTime - $firstTime;
        
        
        $transferInfo = $result['tokenTransferInfo']??$result['contractData'];
        
        $to_address = $transferInfo['to_address'];
        $amount = ($transferInfo['amount_str']??$transferInfo['amount'])/1000000;
        
        if($dayStamp > $dateDiff && $wallet == $to_address){
            if($amount >= $tronPrice && $amount <= $tronPrice+1 ){
                
                $price = $payParam['price'];
                $description = $payParam['description'];
                
                $plan_id = $payParam['plan_id'];
                $volume = $payParam['volume'];
                $days = $payParam['day'];
                $agentBought = $payParam['agent_bought'];
                
                $stmt = $connection->prepare("UPDATE `pays` SET `state` = 'approved' WHERE `id` =?");
                $stmt->bind_param("i", $rowId);
                $stmt->execute();
                $stmt->close();
                
                if($payType == "BUY_SUB"){
                    $fid = $plan_id;
                    $acctxt = '';
                    
                    $stmt = $connection->prepare("SELECT * FROM `users` WHERE `userid` = ?");
                    $stmt->bind_param("i", $user_id);
                    $stmt->execute();
                    $userinfo = $stmt->get_result()->fetch_assoc();
                    $stmt->close();
                    
                    
                    $stmt = $connection->prepare("SELECT * FROM `server_plans` WHERE `id`=?");
                    $stmt->bind_param("i", $fid);
                    $stmt->execute();
                    $file_detail = $stmt->get_result()->fetch_assoc();
                    $stmt->close();
                
                    $days = $file_detail['days'];
                    $date = time();
                    $expire_microdate = floor(microtime(true) * 1000) + (864000 * $days * 100);
                    $expire_date = $date + (86400 * $days);
                    $type = $file_detail['type'];
                    $volume = $file_detail['volume'];
                    $protocol = $file_detail['protocol'];
                
                    $server_id = $file_detail['server_id'];
                    $netType = $file_detail['type'];
                    $acount = $file_detail['acount'];
                    $inbound_id = $file_detail['inbound_id'];
                    $limitip = $file_detail['limitip'];
                    $rahgozar = $file_detail['rahgozar'];
                    $customPath = $file_detail['custom_path'];
                    $customPort = $file_detail['custom_port'];
                    $customSni = $file_detail['custom_sni'];
                
                    $accountCount = $payParam['agent_count'] != 0?$payParam['agent_count']:1;
                    $eachPrice = $price / $accountCount;
                
                    $stmt = $connection->prepare("SELECT * FROM `server_info` WHERE `id`=?");
                    $stmt->bind_param("i", $server_id);
                    $stmt->execute();
                    $serverInfo = $stmt->get_result()->fetch_assoc();
                    $srv_remark = $serverInfo['remark'];
                    $stmt->close();
                
                    $stmt = $connection->prepare("SELECT * FROM `server_config` WHERE `id`=?");
                    $stmt->bind_param("i", $server_id);
                    $stmt->execute();
                    $portType = $stmt->get_result()->fetch_assoc()['port_type'];
                    $stmt->close();
                    include '../phpqrcode/qrlib.php';
                    define('IMAGE_WIDTH',540);
                    define('IMAGE_HEIGHT',540);
                    sendMessage("Ваш платеж с идентификатором транзакции $hash_id успешно выполнен 🚀 | 😍 Отправка конфигурации на ваш Telegram...", null, null, $user_id);

                    for($i =1; $i<= $accountCount; $i++){
                        $uniqid = generateRandomString(42,$protocol); 
                    
                        $savedinfo = file_get_contents('../settings/temp.txt');
                        $savedinfo = explode('-',$savedinfo);
                        $port = $savedinfo[0];
                        $last_num = $savedinfo[1] + 1;
                        
                        if($portType == "auto"){
                            $port++;
                            file_put_contents('../settings/temp.txt',$port.'-'.$last_num);
                        }else{
                            $port = rand(1111,65000);
                        }
                    
                        if($botState['remark'] == "digits"){
                            $rnd = rand(10000,99999);
                            $remark = "{$srv_remark}-{$rnd}";
                        }
                        elseif($botState['remark'] == "manual"){
                            $remark = $description;
                        }
                        else{
                            $rnd = rand(1111,99999);
                            $remark = "{$srv_remark}-{$user_id}-{$rnd}";
                        }
                        if(!empty($description)) $remark = $description;
                        
                        if($inbound_id == 0){    
                            $response = addUser($server_id, $uniqid, $protocol, $port, $expire_microdate, $remark, $volume, $netType, 'none', $rahgozar, $fid); 
                            if(! $response->success){
                                $response = addUser($server_id, $uniqid, $protocol, $port, $expire_microdate, $remark, $volume, $netType, 'none', $rahgozar, $fid);
                            } 
                        }else {
                            $response = addInboundAccount($server_id, $uniqid, $inbound_id, $expire_microdate, $remark, $volume, $limitip, null, $fid); 
                            if(! $response->success){
                                $response = addInboundAccount($server_id, $uniqid, $inbound_id, $expire_microdate, $remark, $volume, $limitip, null, $fid);
                            } 
                        }
                        
                        if(is_null($response)){
                            $stmt = $connection->prepare("UPDATE `users` SET `wallet` = `wallet` + ? WHERE `userid` = ?");
                            $stmt->bind_param("ii", $price, $user_id);
                            $stmt->execute();
                            $stmt->close();
                            sendMessage("Ваша транзакция с идентификатором транзакции $hash_id подтверждена, но соединение с сервером не установлено. Пожалуйста, уведомите администратора\n✅ Сумма " . number_format($price) . " руб. ($tronPrice тон) была добавлена на ваш счет", null, null, $user_id);
                            sendMessage("✅ Сумма " . number_format($price) . " руб. была добавлена на кошелек пользователя $user_id через  Tron. Он хотел приобрести конфигурацию, но соединение с сервером не установлено", null, null, $admin);
                    
                            exit;
                        }
                        if($response == "inbound not Found"){
                            $stmt = $connection->prepare("UPDATE `users` SET `wallet` = `wallet` + ? WHERE `userid` = ?");
                            $stmt->bind_param("ii", $price, $user_id);
                            $stmt->execute();
                            $stmt->close();
                            sendMessage("Ваша транзакция с идентификатором транзакции $hash_id подтверждена, но запись с идентификатором $inbound_id не найдена на сервере. Пожалуйста, уведомите администратора\n✅ Сумма " . number_format($price) . " руб. ($tronPrice тон) была добавлена на ваш счет", null, null, $user_id);
                            sendMessage("✅ Сумма " . number_format($price) . " руб. была добавлена на кошелек пользователя $user_id через  Tron. Он хотел приобрести конфигурацию, но запись не найдена", null, null, $admin);
                            exit;
                        }
                        
                        if(!$response->success){
                            sendMessage("Ошибка сервера {$serverInfo['title']}:\n\n" . $response['msg'], null, null, $admin);
                            $stmt = $connection->prepare("UPDATE `users` SET `wallet` = `wallet` + ? WHERE `userid` = ?");
                            $stmt->bind_param("ii", $price, $user_id);
                            $stmt->execute();
                            $stmt->close();
                            sendMessage("Ваша транзакция с идентификатором транзакции $hash_id подтверждена, но произошла ошибка. Пожалуйста, сообщите администратору\n✅ Сумма " . number_format($price) . " руб. ($tronPrice тон) была добавлена на ваш счет", null, null, $user_id);
                            sendMessage("✅ Сумма " . number_format($price) . " руб. была добавлена на кошелек пользователя $user_id через  Tron. Он хотел приобрести конфигурацию, но произошла ошибка", null, null, $admin);
                            exit;
                        }
                                            
                        $vraylink = getConnectionLink($server_id, $uniqid, $protocol, $remark, $port, $netType, $inbound_id, $rahgozar, $customPath, $customPort, $customSni);
                        $token = RandomString(30);
                        $subLink = $botState['subLinkState']=="on"?$botUrl . "settings/subLink.php?token=" . $token:"";
                
                        foreach($vraylink as $vray_link){
                            $acc_text = "
                            😍 Ваш новый заказ
                            📡 Протокол: $protocol
                            🔮 Название услуги: $remark
                            🔋 Объем услуги: $volume гб
                            ⏰ Срок услуги: $days дней
                            ";
                            
                ($botState['configLinkState'] != "off"?
                "
                💝 config : <code>$vray_link</code>":"").
                ($botState['subLinkState']=="on"?
                "
                
                🔋 Volume web: <code> $botUrl"."search.php?id=".$uniqid."</code>
                
                
                🌐 subscription : <code>$subLink</code>
                    
                            ":"");
                        
                            $file = RandomString() .".png";
                            $ecc = 'L';
                            $pixel_Size = 11;
                            $frame_Size = 0;
                            
                            QRcode::png($vray_link, $file, $ecc, $pixel_Size, $frame_Size);
                        	addBorderImage($file);
                        	
                	        $backgroundImage = imagecreatefromjpeg("../settings/QRCode.jpg");
                            $qrImage = imagecreatefrompng($file);
                            
                            $qrSize = array('width' => imagesx($qrImage), 'height' => imagesy($qrImage));
                            imagecopy($backgroundImage, $qrImage, 300, 300 , 0, 0, $qrSize['width'], $qrSize['height']);
                            imagepng($backgroundImage, $file);
                            imagedestroy($backgroundImage);
                            imagedestroy($qrImage);
                
                        	sendPhoto($botUrl . "settings/" . $file, $acc_text,json_encode(['inline_keyboard'=>[[['text'=>"صفحه اصلی 🏘",'callback_data'=>"mainMenu"]]]]),"HTML", $user_id);
                            unlink($file);
                        }
                        $vray_link = json_encode($vraylink);
                        $date = time();
                        
                    	$stmt = $connection->prepare("INSERT INTO `orders_list` 
                    	    (`userid`, `token`, `transid`, `fileid`, `server_id`, `inbound_id`, `remark`, `uuid`, `protocol`, `expire_date`, `link`, `amount`, `status`, `date`, `notif`, `rahgozar`, `agent_bought`)
                    	    VALUES (?, ?, '', ?, ?, ?, ?, ?, ?, ?, ?, ?,1, ?, 0, ?, ?);");
                        $stmt->bind_param("ssiiisssisiiii", $user_id, $token, $fid, $server_id, $inbound_id, $remark, $uniqid, $protocol, $expire_date, $vray_link, $eachPrice, $date, $rahgozar, $agentBought);        
                        $stmt->execute();
                        $order = $stmt->get_result(); 
                        $stmt->close();
                        
                    }
                
                    
                    
                    $stmt = $connection->prepare("SELECT * FROM `users` WHERE `userid` = ?");
                    $stmt->bind_param("i", $user_id);
                    $stmt->execute();
                    $user_info = $stmt->get_result()->fetch_assoc();
                    $stmt->close();
                
                    if($inbound_id == 0) {
                        $stmt = $connection->prepare("UPDATE `server_info` SET `ucount` = `ucount` - ? WHERE `id`=?");
                        $stmt->bind_param("ii", $accountCount, $server_id);
                        $stmt->execute();
                        $stmt->close();
                    }else{
                        $stmt = $connection->prepare("UPDATE `server_plans` SET `acount` = `acount` - ? WHERE id=?");
                        $stmt->bind_param("ii", $accountCount, $fid);
                        $stmt->execute();
                        $stmt->close();
                    }
                    
                    if($user_info['refered_by'] != null){
                        $stmt = $connection->prepare("SELECT * FROM `setting` WHERE `type` = 'INVITE_BANNER_AMOUNT'");
                        $stmt->execute();
                        $inviteAmount = $stmt->get_result()->fetch_assoc()['value']??0;
                        $stmt->close();
                        $inviterId = $user_info['refered_by'];
                        
                        $stmt = $connection->prepare("UPDATE `users` SET `wallet` = `wallet` + ? WHERE `userid` = ?");
                        $stmt->bind_param("ii", $inviteAmount, $inviterId);
                        $stmt->execute();
                        $stmt->close();
                         
                        sendMessage("تبریک یکی از زیر مجموعه های شما خرید انجام داد شما مبلغ " . number_format($inviteAmount) . " Руб. جایزه دریافت کردید",null,null,$inviterId);
                    }
                
                    $user_info = Bot('getChat',['chat_id'=>$user_id])->result;
                    $first_name = $user_info->first_name;
                    $username = $user_info->username;
                    
                    $keys = json_encode(['inline_keyboard'=>[
                        [
                            ['text'=>"خرید از درگاه ترون 💞",'callback_data'=>'wizwizch'],
                            ],
                        ]]);
                        sendMessage("
                        👨‍👦‍👦 Покупка ( Tron)
                        
                        Идентификатор транзакции: $hash_id
                        
                        🧝‍♂️ Идентификатор пользователя: $user_id
                        🛡 Имя пользователя: $first_name
                        🔖 Имя пользователя: @$username
                        💰 Сумма платежа: $price руб. ($tronPrice тон)
                        🔮 Название услуги: $remark
                        🔋 Объем услуги: $volume гб
                        ⏰ Срок услуги: $days дней
                        ⁮⁮ 
                        ", $keys, "html", $admin);
                        
                }
                elseif($payType == "INCREASE_WALLET"){
                    $stmt = $connection->prepare("UPDATE `users` SET `wallet` = `wallet` + ? WHERE `userid` = ?");
                    $stmt->bind_param("ii", $price, $user_id);
                    $stmt->execute(); 
                    $stmt->close(); 
                    sendMessage("Ваша транзакция с идентификатором транзакции $hash_id подтверждена\n ✅ Сумма " . number_format($price) . " руб. была добавлена на ваш счет", null, null, $user_id);
                    sendMessage("✅ Сумма " . number_format($price) . " руб. ($tronPrice тон) была добавлена на кошелек пользователя $user_id через  Tron", null, null, $admin);
                                    
                }
                elseif($payType == "RENEW_ACCOUNT"){
                    $oid = $plan_id;
                    $stmt = $connection->prepare("SELECT * FROM `orders_list` WHERE `id` = ?");
                    $stmt->bind_param("i", $oid);
                    $stmt->execute();
                    $order = $stmt->get_result()->fetch_assoc();
                    $stmt->close();
                    $fid = $order['fileid'];
                    $remark = $order['remark'];
                    $uuid = $order['uuid']??"0";
                    $server_id = $order['server_id'];
                    $inbound_id = $order['inbound_id'];
                    $expire_date = $order['expire_date'];
                    $expire_date = ($expire_date > $time) ? $expire_date : $time;
                    
                    $stmt = $connection->prepare("SELECT * FROM `server_plans` WHERE `id` = ? AND `active` = 1");
                    $stmt->bind_param("i", $fid);
                    $stmt->execute();
                    $respd = $stmt->get_result()->fetch_assoc();
                    $stmt->close();
                    $name = $respd['title'];
                    $days = $respd['days'];
                    $volume = $respd['volume'];
                
                    if($inbound_id > 0)
                        $response = editClientTraffic($server_id, $inbound_id, $uuid, $volume, $days, "renew");
                    else
                        $response = editInboundTraffic($server_id, $uuid, $volume, $days, "renew");
                
                        if(is_null($response)){
                            $stmt = $connection->prepare("UPDATE `users` SET `wallet` = `wallet` + ? WHERE `userid` = ?");
                            $stmt->bind_param("ii", $price, $user_id);
                            $stmt->execute();
                            $stmt->close();
                            sendMessage("Ваша транзакция с идентификатором транзакции $hash_id подтверждена, но возникла техническая проблема с подключением к серверу\n✅ Сумма " . number_format($price). " руб. была добавлена на ваш счет",null,null,$user_id);
                            sendMessage("✅ Сумма " . number_format($price) . " Руб. ($tronPrice тон) была добавлена на кошелек пользователя $user_id. Он хотел продлить свою конфигурацию, но подключение к серверу не удалось",null,null,$admin);
                            exit;
                        }
                        
                	$stmt = $connection->prepare("UPDATE `orders_list` SET `expire_date` = ?, `notif` = 0 WHERE `id` = ?");
                	$newExpire = $time + $days * 86400;
                	$stmt->bind_param("ii", $newExpire, $oid);
                	$stmt->execute();
                	$stmt->close();
                	$stmt = $connection->prepare("INSERT INTO `increase_order` VALUES (NULL, ?, ?, ?, ?, ?, ?);");
                	$stmt->bind_param("iiisii", $user_id, $server_id, $inbound_id, $remark, $price, $time);
                	$stmt->execute();
                	$stmt->close();
                	
                    sendMessage("✅ Услуга $remark успешно продлена", null, null, $user_id);

                    $keys = json_encode(['inline_keyboard'=>[
                        [
                            ['text'=>"Купить через  Tron 💞",'callback_data'=>'wizwizch'],
                        ],
                    ]]);
                    
                    $user_info = Bot('getChat',['chat_id'=>$user_id])->result;
                    $first_name = $user_info->first_name;
                    $username = $user_info->username;
                
                    sendMessage("
                    💚 Продление аккаунта (через  Tron)
                    
                    🧝‍♂️ Идентификатор пользователя: $user_id
                    🛡 Имя пользователя: $first_name
                    🔖 Имя пользователя: @$username
                    💰 Сумма платежа: $price руб. ($tronPrice тон)
                    🔮 Название услуги: $remark
                    ⁮⁮ ⁮⁮
                    ", $keys, "html", $admin);
                    
                exit;
                
                }
                elseif(preg_match('/^INCREASE_DAY_(\d+)_(\d+)/',$payType,$match)){
                    $orderId = $match[1];
                    
                    $stmt = $connection->prepare("SELECT * FROM `orders_list` WHERE `id` = ?");
                    $stmt->bind_param("i", $orderId);
                    $stmt->execute();
                    $orderInfo = $stmt->get_result()->fetch_assoc();
                    $stmt->close();
                    
                    $server_id = $orderInfo['server_id'];
                    $inbound_id = $orderInfo['inbound_id'];
                    $remark = $orderInfo['remark'];
                    $uuid = $orderInfo['uuid']??"0";
                    $planid = $match[2];
                
                    
                    $stmt = $connection->prepare("SELECT * FROM `increase_day` WHERE `id` = ?");
                    $stmt->bind_param("i", $planid);
                    $stmt->execute();
                    $res = $stmt->get_result()->fetch_assoc();
                    $stmt->close();
                    $volume = $res['volume'];
                
                    if($inbound_id > 0)
                        $response = editClientTraffic($server_id, $inbound_id, $uuid, 0, $volume);
                    else
                        $response = editInboundTraffic($server_id, $uuid, 0, $volume);
                        
                    if($response->success){
                        $stmt = $connection->prepare("UPDATE `orders_list` SET `expire_date` = `expire_date` + ?, `notif` = 0 WHERE `uuid` = ?");
                        $newVolume = $volume * 86400;
                        $stmt->bind_param("is", $newVolume, $uuid);
                        $stmt->execute();
                        $stmt->close();
                        
                        $time = time();
                        $stmt = $connection->prepare("INSERT INTO `increase_order` VALUES (NULL, ?, ?, ?, ?, ?, ?);");
                        $newVolume = $volume * 86400;
                        $stmt->bind_param("iiisii", $user_id, $server_id, $inbound_id, $remark, $price, $time);
                        $stmt->execute();
                        $stmt->close();
                        
                        sendMessage("Ваш платеж с идентификатором транзакции $hash_id успешно выполнен. $volume дней было добавлено к сроку действия вашей услуги", null, null, $user_id);
                        $keys = json_encode(['inline_keyboard'=>[
                            [
                                ['text'=>"Купить через  Tron 💞",'callback_data'=>'wizwizch'],
                            ],
                        ]]);
                        
                                    $user_info = Bot('getChat',['chat_id'=>$user_id])->result;
                    $first_name = $user_info->first_name;
                    $username = $user_info->username;
                
                    sendMessage("
                    💜 Увеличение времени услуги (через  Tron)
                    
                    🧝‍♂️ Идентификатор пользователя: $user_id
                    🛡 Имя пользователя: $first_name
                    🔖 Имя пользователя: @$username
                    💰 Сумма платежа: $price руб. ($tronPrice тон)
                    🔮 Название услуги: $remark
                    ⁮⁮ ⁮⁮
                    ", $keys, "html", $admin);
                    
                exit;
                    }else {
                        $stmt = $connection->prepare("UPDATE `users` SET `wallet` = `wallet` + ? WHERE `userid` = ?");
                        $stmt->bind_param("ii", $price, $user_id);
                        $stmt->execute();
                        $stmt->close();
                        sendMessage("Ваша транзакция с идентификатором транзакции $hash_id подтверждена, но из-за технических проблем невозможно увеличить объем. Пожалуйста, сообщите администратору\n✅ Сумма " . number_format($price). " руб. была добавлена на ваш счет",null,null,$user_id);
                        sendMessage("✅ Сумма " . number_format($price) . " Руб. была добавлена на кошелек пользователя $user_id. Он хотел увеличить объем своей услуги, но из-за технических проблем это не удалось",null,null,$admin);
                        exit;
                        
                    }
                }
                elseif(preg_match('/^INCREASE_VOLUME_(\d+)_(\d+)/',$payType, $match)){
                    $orderId = $match[1];
                    
                    $stmt = $connection->prepare("SELECT * FROM `orders_list` WHERE `id` = ?");
                    $stmt->bind_param("i", $orderId);
                    $stmt->execute();
                    $orderInfo = $stmt->get_result()->fetch_assoc();
                    $stmt->close();
                    
                    $server_id = $orderInfo['server_id'];
                    $inbound_id = $orderInfo['inbound_id'];
                    $remark = $orderInfo['remark'];
                    $uuid = $orderInfo['uuid']??"0";
                    $planid = $match[2];
                
                    $stmt = $connection->prepare("SELECT * FROM `increase_plan` WHERE `id` = ?");
                    $stmt->bind_param("i",$planid);
                    $stmt->execute();
                    $res = $stmt->get_result()->fetch_assoc();
                    $stmt->close();
                    $volume = $res['volume'];
                
                    $acctxt = '';
                
                    
                    if($inbound_id > 0)
                        $response = editClientTraffic($server_id, $inbound_id, $uuid, $volume, 0);
                    else
                        $response = editInboundTraffic($server_id, $uuid, $volume, 0);
                    if($response->success){
                        $stmt = $connection->prepare("UPDATE `orders_list` SET `notif` = 0 WHERE `uuid` = ?");
                        $stmt->bind_param("s", $uuid);
                        $stmt->execute();
                        $stmt->close();
                        sendMessage("Ваш платеж с идентификатором транзакции $hash_id успешно выполнен. $volume гб было добавлено к объему вашей услуги", null, null, $user_id);
                        $keys = json_encode(['inline_keyboard'=>[
                            [
                                ['text'=>"Купить через  Tron 💞",'callback_data'=>'wizwizch'],
                            ],
                        ]]);
                        
                                    $user_info = Bot('getChat',['chat_id'=>$user_id])->result;
                    $first_name = $user_info->first_name;
                    $username = $user_info->username;
                
                    sendMessage("
                    🤎 Увеличение объема услуги (через  Tron)
                    
                    🧝‍♂️ Идентификатор пользователя: $user_id
                    🛡 Имя пользователя: $first_name
                    🔖 Имя пользователя: @$username
                    💰 Сумма платежа: $price руб. ($tronPrice тон)
                    🔮 Название услуги: $remark
                    ⁮⁮ ⁮⁮
                    ", $keys, "html", $admin);
                    
                exit;
                    }else {
                        $stmt = $connection->prepare("UPDATE `users` SET `wallet` = `wallet` + ? WHERE `userid` = ?");
                        $stmt->bind_param("ii", $price, $user_id);
                        $stmt->execute();
                        $stmt->close();
                        sendMessage("Ваша транзакция с идентификатором транзакции $hash_id подтверждена, но из-за технических проблем невозможно увеличить. Пожалуйста, сообщите администратору\n✅ Сумма " . number_format($price). " руб. была добавлена на ваш счет",null,null,$user_id);
                        sendMessage("✅ Сумма " . number_format($price) . " Руб. была добавлена на кошелек пользователя $user_id. Он хотел увеличить объем своей конфигурации, но из-за технических проблем это не удалось",null,null,$admin);
                        exit;
                        
                    }
                }
                elseif($payType == "RENEW_SCONFIG"){
                    $user_id = $user_id;
                    $fid = $plan_id;
                    $acctxt = '';
                    
                    $stmt = $connection->prepare("SELECT * FROM `users` WHERE `userid` = ?");
                    $stmt->bind_param("i", $user_id);
                    $stmt->execute();
                    $userinfo = $stmt->get_result()->fetch_assoc();
                    $stmt->close();
                    
                    
                    $stmt = $connection->prepare("SELECT * FROM `server_plans` WHERE `id`=?");
                    $stmt->bind_param("i", $fid);
                    $stmt->execute();
                    $file_detail = $stmt->get_result()->fetch_assoc();
                    $stmt->close();
                
                    $days = $file_detail['days'];
                    $volume = $file_detail['volume'];
                    $server_id = $file_detail['server_id'];
                    
                    $configInfo = json_decode($payParam['description'],true);
                    $uuid = $configInfo['uuid'];
                    $remark = $configInfo['remark'];
                
                    $uuid = $payParam['description'];
                    $inbound_id = $payParam['volume']; 
                    
                    if($inbound_id > 0)
                        $response = editClientTraffic($server_id, $inbound_id, $uuid, $volume, $days, "renew");
                    else
                        $response = editInboundTraffic($server_id, $uuid, $volume, $days, "renew");
                    
                	if(is_null($response)){
                        sendMessage('🔻 Техническая проблема с подключением к серверу. Пожалуйста, сообщите администратору',null,null,$user_id);

                        $stmt = $connection->prepare("UPDATE `users` SET `wallet` = `wallet` + ? WHERE `userid` = ?");
                        $stmt->bind_param("ii", $price, $user_id);
                        $stmt->execute();
                        $stmt->close();
                        sendMessage("Ваш платеж с идентификатором транзакции $hash_id подтвержден, но возникла техническая проблема с подключением к серверу. Пожалуйста, сообщите администратору\n✅ Сумма " . number_format($price). " руб. была добавлена на ваш счет",null,null,$user_id);
                        sendMessage("✅ Сумма " . number_format($price) . " Руб. была добавлена на кошелек пользователя $user_id. Он хотел продлить свою конфигурацию",null,null,$admin);
                        exit;
                        
                	}
                	$stmt = $connection->prepare("INSERT INTO `increase_order` VALUES (NULL, ?, ?, ?, ?, ?, ?);");
                	$stmt->bind_param("iiisii", $user_id, $server_id, $inbound_id, $remark, $price, $time);
                	$stmt->execute();
                	$stmt->close();
                    sendMessage("Ваша транзакция с идентификатором транзакции $hash_id подтверждена\n✅ Услуга $remark успешно продлена",null,null,$user_id);
                
                }
            }else{
                sendMessage(str_replace(["TYPE", "USERID", "TAXID", "USERNAME", "NAME"], [$payDescription, $user_id, $hash_id, $userInfo['username'], $userInfo['name']], $mainValues['partially_paid_user_taxid']), null, "html", $admin);
                sendMessage(str_replace(["TYPE", "TAXID"], [$payDescription, $hash_id], $mainValues['you_have_partially_paid']), null, "html", $user_id);
                $stmt = $connection->prepare("UPDATE `pays` SET `state` = 'partially_paid' WHERE `payid` = ?");
                $stmt->bind_param("s", $hash_id);
                $stmt->execute();
                $stmt->close();
            }
        }else{
            sendMessage(str_replace(["TYPE", "USERID", "TAXID", "USERNAME", "NAME"], [$payDescription, $user_id, $hash_id, $userInfo['username'], $userInfo['name']], $mainValues['incorrect_user_taxid_rejected']), null, "html", $admin);
            sendMessage(str_replace(["TYPE", "TAXID"], [$payDescription, $hash_id], $mainValues['your_incorrect_taxid_rejected']), null, "html", $user_id);
            $stmt = $connection->prepare("UPDATE `pays` SET `state` = 'declined' WHERE `payid` = ?");
            $stmt->bind_param("s", $hash_id);
            $stmt->execute();
            $stmt->close();
        }
    }else{
        if($state >= 5){
            sendMessage(str_replace(["TYPE", "USERID", "TAXID", "USERNAME", "NAME"], [$payDescription, $user_id, $hash_id, $userInfo['username'], $userInfo['name']], $mainValues['user_taxid_rejected']), null, "html", $admin);
            sendMessage(str_replace(["TYPE", "TAXID"], [$payDescription, $hash_id], $mainValues['your_taxid_rejected']), null, "html", $user_id);
            $stmt = $connection->prepare("UPDATE `pays` SET `state` = 'declined' WHERE `payid` = ?");
            $stmt->bind_param("s", $hash_id);
            $stmt->execute();
            $stmt->close();
        }else{
            $newState = $state+1;
            $stmt = $connection->prepare("UPDATE `pays` SET `state` = ? WHERE `payid` = ?");
            $stmt->bind_param("is", $newState, $hash_id);
            $stmt->execute();
            $stmt->close();
        }
    }
}


function getWebsite($hash_id){

    $ch = curl_init();
    $user_agent='Mozilla/5.0 (Windows NT 6.1; rv:8.0) Gecko/20100101 Firefox/'.rand(8,100).'.0';
    curl_setopt($ch, CURLOPT_URL, "https://apilist.tronscan.org/api/transaction-info?hash=$hash_id");
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_AUTOREFERER, false);
    curl_setopt($ch, CURLOPT_VERBOSE, 1);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    
    curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSLVERSION,CURL_SSLVERSION_DEFAULT);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $webcontent= curl_exec ($ch);
    $error = curl_error($ch); 
    curl_close ($ch);
    return  $webcontent;

}

?>
