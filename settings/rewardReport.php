<?php
include_once '../baseInfo.php';
include_once '../config.php';

$sellState = $botState['sellState'] == "off" ? "Выключено ❌" : "Включено ✅";
$searchState = $botState['searchState'] == "off" ? "Выключено ❌" : "Включено ✅";
$rewaredTime = ($botState['rewaredTime'] ?? 0);
$rewaredChannel = $botState['rewardChannel'];

if ($rewaredTime > 0 && $rewaredChannel != null) {
    $lastTime = $botState['lastRewardMessage'] ?? 0;
    if (time() > $lastTime) {
        $time = time() - ($rewaredTime * 60 * 60);

        $stmt = $connection->prepare("SELECT SUM(price) as total FROM `pays` WHERE `request_date` > ? AND (`state` = 'paid' OR `state` = 'approved')");
        $stmt->bind_param("i", $time);
        $stmt->execute();
        $totalRewards = number_format($stmt->get_result()->fetch_assoc()['total']);
        $stmt->close();

        $botState['lastRewardMessage'] = time() + ($rewaredTime * 60 * 60);

        $stmt = $connection->prepare("SELECT * FROM `setting` WHERE `type` = 'BOT_STATES'");
        $stmt->execute();
        $isExists = $stmt->get_result();
        $stmt->close();
        if ($isExists->num_rows > 0) $query = "UPDATE `setting` SET `value` = ? WHERE `type` = 'BOT_STATES'";
        else $query = "INSERT INTO `setting` (`type`, `value`) VALUES ('BOT_STATES', ?)";
        $newData = json_encode($botState);

        $stmt = $connection->prepare($query);
        $stmt->bind_param("s", $newData);
        $stmt->execute();
        $stmt->close();

        $txt = "⁮⁮ ⁮⁮ ⁮⁮ ⁮⁮
🔰 Мой доход за последние $rewaredTime часов

💰 Сумма: $totalRewards руб.

☑️ $channelLock

";
        sendMessage($txt, null, null, $rewaredChannel);
    }
}
?>
