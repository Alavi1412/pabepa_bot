<?php

/**
 * Created by PhpStorm.
 * User: alavi
 * Date: 8/11/17
 * Time: 10:12 PM
 */
class User
{
    private $user_id;
    private $level;
    private $message_id;
    private $text;
    private $db;
    private $helper_level;


    public function __construct($message_id, $text,  $user_id)
    {
        $this->db = mysqli_connect("localhost","root","root","pabepa_bot");
        $this->user_id = $user_id;
        $this->message_id = $message_id;
        $this->text = $text;
        $this->level = $this->getLevel();
        $this->helper_level = $this->getHelperLevel();
    }

    private function getLevel()
    {
        $result = mysqli_query($this->db, "SELECT * FROM pabepa_bot.users WHERE user_id = {$this->user_id}");
        if ($row = mysqli_fetch_array($result))
            return $row['level'];
        else
        {
            mysqli_query($this->db, "INSERT INTO pabepa_bot.users (user_id, level) VALUES ({$this->user_id}, 'begin')");
            return 'begin';
        }
    }

    private function getHelperLevel()
    {
        $result = mysqli_query($this->db, "SELECT * FROM pabepa_bot.users WHERE user_id = {$this->user_id}");
        return $result['helper_level'];
    }

    private function setHelperLevel($helepr_level)
    {
        mysqli_query($this->db,"UPDATE pabepa_bot.users SET helper_level = '{$helepr_level}' WHERE user_id = {$this->user_id}");
    }

    private function setLevel($level)
    {
        mysqli_query($this->db,"UPDATE pabepa_bot.users SET level = '{$level}' WHERE user_id = {$this->user_id}");
    }

    private function sendMessage($text, $inline)
    {
        return $this->makeCurl("sendMessage", ["chat_id" => $this->user_id, "text" => $text, "reply_markup" => json_encode([
            "inline_keyboard" =>
                $inline
        ])]);
    }

    private function sendPhoto($url)
    {
        return $this->makeCurl("sendPhoto", ["chat_id" => $this->user_id, "photo" => $url]);
    }

    private function editMessageText($text, $inline)
    {
        return $this->makeCurl("editMessageText", ["message_id" => $this->message_id ,"chat_id" => $this->user_id, "text" => $text, "reply_markup" => json_encode([
            "inline_keyboard" =>
                $inline
        ])]);
    }

    public function process()
    {
        if ($this->text == "/start")
            $this->showMainMenu(false);
        elseif ($this->level == "main_menu_showed")
            $this->mainMenuManager();

    }

    private function showMainMenu($editBool)
    {
        $this->setLevel("main_menu_showed");
        $title = " سالم چطور میتونم کمکتون کنم، لطفا گزینه مورد نظرتون رو انتخاب کنید ؟";
        $button = [
            [
                ["text" => "اضافه وزن دارم یا لاغرم ؟", "callback_data" => "If_Fat_OR_Thin"]
            ],
            [
                ["text" => "درصد چریب بدنم چقدر هست؟ ", "callback_data" => "Fat_Percent"]
            ],
            [
                ["text" => "چه میزان کالری در طول روز مصرف میکنم؟ ", "callback_data" => "Cal_Per_Day"]
            ],
            [
                ["text" => " .ثبت نام و استفاده از جوایز مختلف پا به پا ☺", "callback_data" => "Sign_Up"]
            ],
            [
                ["text" => "وارد کردن یه کد رمز", "callback_data" => "Enter_A_Code"]
            ]
        ];
        if ($editBool)
            $this->editMessageText($title, $button);
        else
            $this->sendMessage($title, $button);
    }

    private function mainMenuManager()
    {
        if ($this->text == "If_Fat_OR_Thin")
            $this->showFatOrThinManager();
    }

    private function showFatOrThinManager()
    {
        $this->setLevel("fat_or_thin_showed");
        if ($this->helper_level == NULL)
        {
            $this->askAge(true);
        }
        elseif ($this->helper_level == "age_asked")
        {

            $this->askGender(false);
        }
        elseif ($this->helper_level == "gender_asked")
        {
            $this->askWeight(false);
        }
    }

    private function askWeight($editStatus)
    {
        $this->setHelperLevel("weight_asked");
        $title = "چند کیلو هستین";
        if ($editStatus)
            $this->editMessageText($title, []);
        else
            $this->sendMessage($title, []);
    }

    private function askAge($editStatus)
    {
        $this->setHelperLevel("age_asked");
        $title = "چند سالتونه؟";
        if ($editStatus)
            $this->editMessageText($title, []);
        else
            $this->sendMessage($title, []);
    }

    private function setAge($age)
    {
        if (is_int($age))
        {
            mysqli_query($this->db, "UPDATE pabepa_bot.users SET age = {$age} WHERE user_id = {$this->user_id}");
            return true;
        }
        else
            return false;
    }

    private function askGender($editStatus)
    {
        $this->setHelperLevel("gender_asked");
        $title = "جنسیتتون رو مشخص کنید؟";
        $button = [
            [
                ["text" => "مرد", "callback_data" => "Male"], ["text" => "زن", "callback_data" => "Female"]
            ]
        ];
        if ($editStatus)
            $this->editMessageText($title, $button);
        else
            $this->sendMessage($title, $button);
    }

    private function makeCurl($method,$datas=[])    //make and receive requests to bot
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
}