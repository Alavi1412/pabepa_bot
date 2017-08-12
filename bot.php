<?php
/**
 * Created by PhpStorm.
 * User: alavi
 * Date: 8/1/17
 * Time: 10:59 PM
 */

function makeCurl($method,$datas=[])    //make and receive requests to bot
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL,"https://api.telegram.org/bot365628024:AAH8l35wfRQacP6GZRi586lRu0UiJXSFrCM/{$method}");
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS,http_build_query($datas));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $server_output = curl_exec ($ch);
    curl_close ($ch);
    return $server_output;
}

$last_updated_id = 0;           //should be removed
$db = mysqli_connect("localhost","root","root","pabepa_bot");
$level;                         //user level
$user_id;                       //user unique user id.find in main function in each update
$text;                          //text that user sent.sometimes the callback data of inline keyboard
$username;                      //user name
$message_id;

require "User.php";

function levelFinder()
{
    global $user_id;
    global $level;
    global $db;
    global $username;

    $b = 0;
    $result = mysqli_query($db,"SELECT * FROM pabepa_bot.users WHERE user_id={$user_id}");
    while($row = mysqli_fetch_array($result))
    {
        if($row['level'])
        {
            $level = $row['level'];
            $b = 1;
        }
    }
    if($b == 0)
    {
        $level = "begin";
        mysqli_query($db, "INSERT INTO pabepa_bot.users (user_id, username, level) VALUE ({$user_id}, '{$username}', 'begin') ");
    }
}

function askGender()
{
    global $user_id;
    global $level;
    global $db;
    global $text;
    makeCurl("sendMessage", ["text" => "جنسیت خود را انتخاب کنید", "chat_id" => $user_id, "reply_markup" => json_encode([
        "inline_keyboard" => [
            [
                ["text" => "مرد", "callback_data" => "ManMfqnamfe"], ["text" => "زن", "callback_data" => "woMEannnsn"]
            ]
        ]
    ])]);
    mysqli_query($db, "UPDATE pabepa_bot.users SET level = 'gender_asked' WHERE user_id = {$user_id}");
}

function askAge()
{
    global $message_id;
    global $user_id;
    global $text;
    global $db;
    if ($text == "ManMfqnamfe")
    {
        $gender = "man";
        mysqli_query($db, "UPDATE pabepa_bot.users SET gender = \"{$gender}\", level = 'age_asked' WHERE user_id = {$user_id}");
        makeCurl("editMessageText", ["chat_id" => $user_id, "text" => "سن خود را وارد کنید(به لاتین)", "message_id" => $message_id]);
    }
    elseif ($text == "woMEannnsn")
    {
        $gender = "woman";
        mysqli_query($db, "UPDATE pabepa_bot.users SET gender = \"{$gender}\", level = 'age_asked' WHERE user_id = {$user_id}");
        makeCurl("editMessageText", ["chat_id" => $user_id, "text" => "سن خود را وارد کنید(به لاتین)", "message_id" => $message_id]);
    }
    else
    {
        makeCurl("sendMessage", ["chat_id" => $user_id, "text" => "جنسیت معتبر نیست.
دوباره سعی کنید:", "reply_markup" => json_encode([
            "inline_keyboard" => [
                [
                    ["text" => "مرد", "callback_data" => "ManMfqnamfe"], ["text" => "زن", "callback_data" => "woMEannnsn"]
                ]
            ]
        ])]);
        mysqli_query($db, "UPDATE pabepa_bot.users SET level = 'no_valid_gender' WHERE user_id = {$user_id}");
    }

}

function askHeight()
{
    global $user_id;
    global $text;
    global $db;
    if (is_numeric($text))
    {
        mysqli_query($db, "UPDATE pabepa_bot.users SET age = {$text}, level = 'height_asked' WHERE user_id = {$user_id}");
        makeCurl("sendMessage", ["chat_id" => $user_id, "text" => "قد خود را وارد کنید(سانتی متر):"]);
    }
    else
        makeCurl("sendMessage", ["chat_id" => $user_id, "text" => "سن وارد شده معتبر نیست.
        دوباره سعی کنید:"]);
}

function askWeight()
{
    global $user_id;
    global $text;
    global $db;
    if (is_numeric($text))
    {
        mysqli_query($db, "UPDATE pabepa_bot.users SET height = {$text}, level = 'weight_asked' WHERE user_id = {$user_id}");
        makeCurl("sendMessage", ["chat_id" => $user_id, "text" => "وزن خود را وارد کنید(کیلو گرم):"]);
    }
    else
        makeCurl("sendMessage", ["chat_id" => $user_id, "text" => "قد وارد شده معتبر نیست.
        دوباره سعی کنید:"]);
}

function askWaist()
{
    global $user_id;
    global $text;
    global $db;
    if (is_numeric($text))
    {
        mysqli_query($db, "UPDATE pabepa_bot.users SET weigth = {$text}, level = 'waist_asked' WHERE user_id = {$user_id}");
        makeCurl("sendMessage", ["chat_id" => $user_id, "text" => "دور مچ خود را وارد کنید(سانتی متر):"]);
    }
    else
        makeCurl("sendMessage", ["chat_id" => $user_id, "text" => "وزن وارد شده معتبر نیست.
        دوباره سعی کنید:"]);
}

function askNeck()
{
    global $user_id;
    global $text;
    global $db;
    if (is_numeric($text))
    {
        mysqli_query($db, "UPDATE pabepa_bot.users SET waist = {$text}, level = 'neck_asked' WHERE user_id = {$user_id}");
        makeCurl("sendMessage", ["chat_id" => $user_id, "text" => "دور گردن خود را وارد کنید(سانتی متر):"]);
    }
    else
        makeCurl("sendMessage", ["chat_id" => $user_id, "text" => "دور مچ وارد شده معتبر نیست.
        دوباره سعی کنید:"]);
}

function askHip()
{
    global $user_id;
    global $text;
    global $db;
    if (is_numeric($text))
    {
        mysqli_query($db, "UPDATE pabepa_bot.users SET neck = {$text}, level = 'hip_asked' WHERE user_id = {$user_id}");
        makeCurl("sendMessage", ["chat_id" => $user_id, "text" => "دور باسن خود را وارد کنید(سانتی متر):"]);
    }
    else
        makeCurl("sendMessage", ["chat_id" => $user_id, "text" => "دور گردن وارد شده معتبر نیست.
        دوباره سعی کنید:"]);
}

function askEmail()
{
    global $user_id;
    global $text;
    global $db;
    $result = mysqli_query($db, "SELECT * FROM pabepa_bot.users WHERE user_id = {$user_id}");
    while($row = mysqli_fetch_array($result))
    {
        if ($row['email'] == NULL)
            $var = 0;
        else
            $var = 1;
    }
    if (is_numeric($text))
    {
        if ($var == 0)
        {
            mysqli_query($db, "UPDATE pabepa_bot.users SET hip = {$text}, level = 'email_asked' WHERE user_id = {$user_id}");
            makeCurl("sendMessage", ["chat_id" => $user_id, "text" => "برای دیدن اطلاعات، ایمیل خود را وارد کنید:"]);
        }
        else if ($var == 1)
            finish(1);
    }
    else
        makeCurl("sendMessage", ["chat_id" => $user_id, "text" => "دور باسن وارد شده معتبر نیست.
        دوباره سعی کنید:"]);

}

function finish($var)
{
    global $user_id;
    global $text;
    global $db;
    if ($var == 0)
        mysqli_query($db, "UPDATE pabepa_bot.users SET email = \"{$text}\", level = 'finish' WHERE user_id = {$user_id}");
    elseif($var == 1)
        mysqli_query($db, "UPDATE pabepa_bot.users SET level = 'finish' WHERE user_id = {$user_id}");
    $result = mysqli_query($db, "SELECT * FROM pabepa_bot.users WHERE user_id = {$user_id}");
    while($row = mysqli_fetch_array($result))
    {
        $gender = $row['gender'];
        $age = $row['age'];
        $height = $row['height'];
        $waist = $row['waist'];
        $weight = $row['weigth'];
        $neck = $row['neck'];
        $hip = $row['hip'];
        $count = $row['count'];
        $count = $count + 1;
        mysqli_query($db, "UPDATE pabepa_bot.users SET count = {$count} WHERE user_id = {$user_id}");
        $bmi = ($weight)/($height * $height);
        if ($gender == "man")
        {
            $gender_help = 1;
            $bmr = (10 * $weight) + (6.25 * $height) - (5 * $age) + 5;
        }
        elseif ($gender == "women")
        {
            $gender_help = 0;
            $bmr = (10 * $weight) + (6.25 * $height) - (5 * $age) - 161;
        }
        if ($age < 15)
            $fat = (1.51 * $bmi) - (0.7 * $age) - (3.6 * $gender_help) + 1.4;
        else
            $fat = (1.2 * $bmi) + (0.23 * $age) - (10.8 * $gender_help) - 5.4;
       makeCurl("sendMessage", ["chat_id" => $user_id, "text" => "BMR شما  : {$bmr}
BMI شما:  {$bmi}
درصد چربی شما: {$fat}", "reply_markup" => json_encode([
            "inline_keyboard" => [
                [
                    ["text" => "محاسبه مجدد", "callback_data" => "Agingnannn"]
                ]
            ]
        ])]);

    }
}

function main()
{
    global $level;
    global $user_id;
    global $text;
    global $username;
    global $user_firstname;
    global $message_id;
    global $last_updated_id;
    global $db;
//    $update = json_decode(file_get_contents("php://input"));          //should not be comment
    $updates = json_decode(makeCurl("getUpdates",["offset"=>($last_updated_id+1)]));        //should be removed
    if($updates->ok == true && count($updates->result) > 0) {               //should be removed
        foreach ($updates->result as $update) {                             //should be removed
            if ($update->callback_query) {
                makeCurl("answerCallbackQuery", ["callback_query_id" => $update->callback_query->id]);
                $text = $update->callback_query->data;
                $user_id = $update->callback_query->from->id;
                $user_firstname = $update->callback_query->from->first_name;
                $username = $update->callback_query->from->username;
                $message_id = $update->callback_query->message->message_id;
            } else {
                $text = $update->message->text;
                $user_id = $update->message->chat->id;
                $username = $update->message->from->username;
                $user_firstname = $update->message->from->first_name;
            }
            $User = new User($message_id, $text, $user_id);
            $User->process();
            $last_updated_id = $update->update_id;              //should be removed
        }           //should be removed
    }               //should be removed
}


while(1) {
    main();
}