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
    private $gender;


    public function __construct($message_id, $text,  $user_id)
    {
        $this->db = mysqli_connect("localhost","root","root","pabepa_bot");
        $this->user_id = $user_id;
        $this->message_id = $message_id;
        $this->text = $text;
        $this->level = $this->getLevel();
        $this->helper_level = $this->getHelperLevel();
        $this->gender = $this->getGender();
    }

    private function getGender()
    {
        $result = mysqli_query($this->db, "SELECT * FROM pabepa_bot.users WHERE user_id = {$this->user_id}");
        $row = mysqli_fetch_array($result);
        return $row['gender'];
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

    private function getRow()
    {
        $result = mysqli_query($this->db, "SELECT * FROM pabepa_bot.users WHERE user_id = {$this->user_id}");
        $row = mysqli_fetch_array($result);
        return $row;
    }

    private function getHelperLevel()
    {
        $result = mysqli_query($this->db, "SELECT * FROM pabepa_bot.users WHERE user_id = {$this->user_id}");
        $row = mysqli_fetch_array($result);
        return $row['helper_level'];
    }

    private function setHelperLevel($helper_level)
    {
        mysqli_query($this->db,"UPDATE pabepa_bot.users SET helper_level = '{$helper_level}' WHERE user_id = {$this->user_id}");
    }

    private function setHelperLevelNull()
    {
        mysqli_query($this->db, "UPDATE pabepa_bot.users SET helper_level = NULL WHERE user_id = {$this->user_id}");
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
        elseif ($this->level == "fat_or_thin_showed")
            $this->showFatOrThinManager();
        elseif ($this->level == "fat_percent_showed")
            $this->showFatPercentManager();
        elseif ($this->level == "calorie_showed")
            $this->showCalorieManager();
        elseif ($this->level == "signup_showed")
            $this->showSignUpManger();
        elseif ($this->level == "code")
            $this->showCodeManager();
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
        elseif ($this->text == "Fat_Percent")
            $this->showFatPercentManager();
        elseif ($this->text == "Cal_Per_Day")
            $this->showCalorieManager();
        elseif ($this->text == "Sign_Up")
            $this->showSignUpManger();
        elseif ($this->text == "Enter_A_Code")
            $this->showCodeManager();
    }

    private function showCodeManager()
    {
        $this->setLevel("code");
        if ($this->helper_level == NULL)
        {
            $this->setHelperLevel("code_asked");
            $this->editMessageText("کد خود را وارد کنید.", []);
        }
        elseif ($this->helper_level == "code_asked")
        {
            $this->setHelperLevel("code_ended");
            $this->sendMessage("کد وارد شده صحیح نمی باشد.", [
            [
                ["text" => "منوی اصلی", "callback_data" => "Main_Menu"]
            ],
                [
                    ["text" => "ورود به کانال برای دریافت کد", "url" => "http://t.me/pabepa_ma"]
                ]
            ]);
        }
        elseif ($this->helper_level == "code_ended")
        {
            $this->setHelperLevelNull();
            if ($this->text == "Main_Menu")
                $this->showMainMenu(true);
            else
                $this->showMainMenu(false);
        }
    }

    private function showSignUpManger()
    {
        $this->setLevel("signup_showed");
        if ($this->helper_level == NULL)
            $this->askName(true);
        elseif ($this->helper_level == "name_asked")
        {
            $this->setName($this->text);
            $this->askUserAge(false);
        }
        elseif ($this->helper_level == "user_age_asked")
        {
            $this->setUserAge($this->text);
            $this->askUserGender(false);
        }
        elseif ($this->helper_level == "user_gender_asked")
        {
            $this->setUserGender($this->text);
            $this->askPhone(false);
        }
        elseif ($this->helper_level == "phone_asked")
        {
            $this->setPhone($this->text);
            $this->askEmail(false);
        }
        elseif ($this->helper_level == "email_asked")
        {
            $this->setEmail($this->text);
            $this->sendMessage("ثبت نام با موفقیت انجام شد و شما در قرعه کشی های پا به پا شرکت خواهید کرد.", []);
            $this->setHelperLevelNull();
            $this->showMainMenu(false);
        }
    }

    private function askEmail($editStatus)
    {
        $this->setHelperLevel("email_asked");
        $title = "ایمیل خود را وارد کنید";
        if ($editStatus)
            $this->editMessageText($title, []);
        else
            $this->sendMessage($title, []);
    }

    private function setEmail($email)
    {
        mysqli_query($this->db, "UPDATE pabepa_bot.users SET email = '{$email}' WHERE user_id = {$this->user_id}");
    }

    private function askPhone($editStatus)
    {
        $this->setHelperLevel("phone_asked");
        $title = "شماره ی تماس خود را وارد کنید";
        if ($editStatus)
            $this->editMessageText($title, []);
        else
            $this->sendMessage($title ,[]);
    }

    private function setPhone($phone)
    {
        mysqli_query($this->db, "UPDATE pabepa_bot.users SET phone = '{$phone}' WHERE user_id = {$this->user_id}");
    }

    private function askUserGender($editStatus)
    {
        $this->setHelperLevel("user_gender_asked");
        $title = "جنسیت خود را وارد کنید.";
        $button = [
            [
                ["text" => "مرد", "callback_data" => "male"], ["text" => "زن", "callback_data" => "female"]
            ]
        ];
        if ($editStatus)
            $this->editMessageText($title, $button);
        else
            $this->sendMessage($title, $button);
    }

    private function setUserGender($gender)
    {
        mysqli_query($this->db, "UPDATE pabepa_bot.users SET user_gender = '{$gender}' WHERE user_id = {$this->user_id}");
    }

    private function askUserAge($editStatus)
    {
        $this->setHelperLevel("user_age_asked");
        $title = "سن خود را وارد کنید";
        if ($editStatus)
            $this->editMessageText($title, []);
        else
            $this->sendMessage($title, []);
    }

    private function setUserAge($age)
    {
        mysqli_query($this->db, "UPDATE pabepa_bot.users SET user_age = '{$age}' WHERE user_id = {$this->user_id}");
    }

    private function askName($editStatus)
    {
        $this->setHelperLevel("name_asked");
        $title = "نام و نام خانوادگی خود را وارد کنید.";
        if ($editStatus)
            $this->editMessageText($title, []);
        else
            $this->sendMessage($this, []);
    }

    private function setName($name)
    {
        return mysqli_query($this->db, "UPDATE pabepa_bot.users SET name = '{$name}' WHERE user_id = {$this->user_id}");
    }

    private function showCalorieManager()
    {
        $this->setLevel("calorie_showed");
        if ($this->helper_level == NULL)
            $this->askGender(true);
        elseif ($this->helper_level == "gender_asked")
        {
            if ($this->setGender($this->text))
                $this->askAge(false);
            else
                $this->sendMessage("جنسیت وارد شده معتبر نمی باشد.", []);
        }
        elseif ($this->helper_level == "age_asked")
        {
            if ($this->setAge($this->text))
                $this->askWeight(false);
            else
                $this->sendMessage("سن وارد شده معتبر نیست", []);
        }
        elseif ($this->helper_level == "weight_asked")
        {
            if ($this->setWeight($this->text))
                $this->askHeight(false);
            else
                $this->sendMessage("وزن وارد شده معتبر نیست.", []);
        }
        elseif ($this->helper_level == "height_asked")
        {
            if ($this->setHeight($this->text))
                $this->showButtonCalorie();
            else
                $this->sendMessage("قد وارد شده معتبر نمی باشد.", []);
        }
        elseif ($this->helper_level == "button_calorie")
        {
            $this->showCaloriePerDayLastMessage();
        }
        elseif ($this->helper_level == "last_message_showed")
        {
            $this->setHelperLevelNull();
            $this->showMainMenu(true);
        }
    }

    private function showButtonCalorie()
    {
        $this->setHelperLevel("button_calorie");
        $button = [
            [
                ["text" => "فعالیت خیلی کم و محدود در وضعیت بستری", "callback_data" => "Very_Low"]
            ],
            [
                ["text" => "فعالیت کم در حد کار روزانه و زندیگ روزمره", "callback_data" => "Low_Daily"]
            ],
            [
                ["text" => "متوسط در هفته 1-2 روز ورزش میکنم", "callback_data" => "One_Or_Two_Sport"]
            ],
            [
                ["text" => "تحرکم نسبتا زیاد یا 3 روز تمرین ورزشی", "callback_data" => "Three_Sport"]
            ],
            [
                ["text" => " .فعالیت زیاد 3-4 روز ورزش", "callback_data" => "Three_Or_Four_Sport"]
            ],
            [
                ["text" => "فعالیت خیلی زیاد تقریبا هر روز ورزش میکنم", "callback_data" => "Daily_Sport"]
            ]
        ];
        $title = "میزان فعالیت روزانه ی خود را انتخاب کنید.";
        $this->sendMessage($title, $button);
    }

    private function showCaloriePerDayLastMessage()
    {
        $this->setHelperLevel("last_message_showed");
        $data = $this->getRow();
        $bmr = 0;
        if ($this->gender  == "male")
            $bmr = (10 * $data['weigth']) + (6.25 * $data['height']) - (5 * $data['age']) + 5;
        elseif ($this->gender == "female")
            $bmr = (10 * $data['weigth']) + (6.25 * $data['height']) - (5 * $data['age']) - 161;

        switch ($this->text)
        {
            case "Very_Low":
                $bmr = $bmr * 1.2;
                break;
            case "Low_Daily":
                $bmr = $bmr * 1.2;
                break;
            case "One_Or_Two_Sport":
                $bmr = $bmr * 1.375;
                break;
            case "Three_Sport":
                $bmr = $bmr * 1.55;
                break;
            case "Three_Or_Four_Sport":
                $bmr = $bmr * 1.725;
                break;
            case "Daily_Sport":
                $bmr = $bmr * 1.9;
                break;
            default:
                $bmr = $bmr * 1.5;
                break;
        }

        $this->sendMessage("میزان سوخت و ساز شما:", []);
        $this->sendMessage($bmr, []);
        $this->sendMessage("اگر میخواهید کاهش وزن داشته باشید باید کالری روزانتون کمتر از این مقدار باشد 
         و اگر هدف افزایش وزن دارید باید کالری مصرفیتون بیشتر از این مقدار باشد 
 و اگر نمیخوایین وزن بگیرید باید میزان برابری باشه. یه متخصص تغذیه و مریب تمرینیتون میتونه خیلی کمکتون کنه", [
     [
         ["text" => "منوی اصلی",  "callback_data" => "Main_Menu"], ["text" => "ورود به کانال", "url" => "http://t.me/pabepa_ma"]
     ]
        ]);
    }

    private function showFatPercentManager()
    {
        $this->setLevel("fat_percent_showed");
        if ($this->helper_level == NULL)
        {
            $this->askGender(true);
        }
        elseif ($this->helper_level == "gender_asked")
        {
            if ($this->setGender($this->text))
                $this->askAge(false);
            else
                $this->sendMessage("جنسیت وارد شده معتبر نمی باشد.", []);
        }
        elseif ($this->helper_level == "age_asked")
        {
            if ($this->setAge($this->text))
                $this->askWeight(false);
            else
                $this->sendMessage("سن وارد شده معتبر نمی باشد.", []);
        }
        elseif ($this->helper_level == "weight_asked")
        {
            if ($this->setWeight($this->text))
                $this->askHeight(false);
            else
                $this->sendMessage("وزن وارد شده معتبر نمی باشد.", []);
        }
        elseif ($this->helper_level == "height_asked")
        {
            if ($this->setHeight($this->text))
                $this->askWaist(false);
            else
                $this->sendMessage("قد وارد شده معتبر نمی باشد.", []);
        }
        elseif ($this->helper_level == "waist_asked")
        {
            if ($this->setWaist($this->text))
                $this->askNeck(false);
            else
                $this->sendMessage("دور کمر وارد شده معتبر نمی باشد.", []);
        }
        elseif ($this->helper_level == "neck_asked")
        {
            if ($this->setNeck($this->text))
            {
                if ($this->gender == "male")
                    $this->showPercentManager();
                elseif ($this->gender == "female")
                    $this->askHip(false);
            }
            else
                $this->sendMessage("دوره گردن وارد شده معتبر نمی باشد.", []);
        }
        elseif ($this->helper_level == "hip_asked")
        {
            if ($this->setHip($this->text))
                $this->showPercentManager();
            else
                $this->sendMessage("دور باسن وارد شده معتبر نمی باشد.", []);
        }
        elseif ($this->helper_level == "percent_showed")
        {
            $this->setHelperLevelNull();
            if ($this->text == "Main_Menu")
                $this->showMainMenu(true);
            else
                $this->showMainMenu(false);
        }

    }

    private function showPercentManager()
    {
        $this->setHelperLevel("percent_showed");
        if ($this->helper_level == "hip_asked" || $this->helper_level == "neck_asked")
        {
            $data = $this->getRow();
            $bmi = $data['weigth']/ ( ($data['height'] / 100) * ($data['height'] / 100) );
            $title = "";

            if ($this->gender == "male")
            {
                $formula1 = (1.2 * $bmi) + (0.23 * $data['age']) - 10.8 - 5.4;
                $formula2 = 495 / (1.29579 - 0.35004 * log10($data['waist'] - $data['neck']) + 0.22100 * log10($data['height'])) - 450;
                $percent = ($formula1 + $formula2) / 2;

                if ($percent <= 4.2)
                    $title = "درصد چریب شما در مرحله خطرناک و سوءتغذیه میباشد،برای کاهش آسیب بدین هر چه سریع تر به یک پزشک مراجعه کنید.";
                elseif ($percent > 4.2 && $percent <= 6.3)
                    $title = "درصد چربیتون خییل کم هست و در نقطه بحراین است، واگر میزان چربیتون کمتر از این میزان باشد بدنتون دچار بیماری و آسیب میشود،یه مشاور تغذیه و پزشک میتونه تو این مرحله خییل کمکتون کنه.";
                elseif ($percent > 6.3 && $percent <= 13)
                    $title = "بسیار عالی ، این درصد چریب نشون میده شما بدین ورزیش و سالیم دارید. ";
                elseif ($percent > 13 && $percent <= 14)
                    $title = "عالی، شما آدم جذایب هستین. تمریناتتون رو مرتب انجام بدین و به همین راه ادامه بدین ";
                elseif ($percent > 14 && $percent <= 17)
                    $title = "وضعیتتون خوبه، تغذیه رژییم و ورزش کمک میکنه که جذاب تر شین، اگه ورزش نمیکنید به نظرم از فردا شروع کنید،یا شدت تمرینتون رو بیشتر کنید";
                elseif ($percent > 17 && $percent <= 24)
                    $title = "وضعیتتون رضایت بخشه ، درصد چربیتون لبه مرزه ، اگه درصد چربیتون از این بیشتر شه چاقی نمایان میشه. ورزش و تغذیه مناسب کمک میکنه که همیشه فیت بمونید";
                elseif ($percent > 24 && $percent <= 25)
                    $title = "شما در نقطه بحران چاقی هستید ، اگه تغذیتون رو رعایت نکنید و درست ورزش نکنید، بیماریهای مختلف میتونه بهتون آسیب بزنه، بهترین راه حل براتون شروع تمرینات ورزیش و تغذیه رژییم از همین امروز هست . همین حاال با متخصصین ورزیش و تغذیه تماس بگیرید.";
                elseif ($percent > 25 && $percent <= 31.4)
                    $title = "شما چاقین! درصد چربیتون نشون میده که سالمتتون خییل براتون اهمیت نداره، چاقی باعث بوجود امدن خییل بیماریها میشه، تغذیه مناسب .و ورزش سبک بهترین پله برای کاهش وزنتون و چریب بدنتون هست .از همین امروز شروع کنید";
                elseif ($percent > 31.4 && $percent <= 33.3)
                    $title = "درصد چربیتون خییل زیاده و چاقیتون در مرحله خطرناک هست، از همین امروز به فکر کاهش وزن و تغذیه رژییم باشید، اگه عادات غذاییتون رو درست نکنید احتمال بیماریهای مختلف رو خییل افزایش میدین. یه مشاور تغذیه و تمریین خییل میتونه بهتون کمک کنه همین امروز باهاشون تماس بگیرین.";
                elseif ($percent > 33.3)
                    $title = "وضعیتتون خییل حاد و خطرناکه، باید هر چه سریعتر برای کاهش وزن بدنتون اقدام کنید، برنامه غذایی کم کالری روزانه، تمرینات ورزیش و هوازی بهترین راه حل برای کاهش وزن و چربیتون هست. حتما با یک متخصص تغذیه و مریب تمریین تماس بگیرید ";
            }
            elseif ($this->gender == "female")
            {
                $formula1 = (1.2 * $bmi) + (0.23 * $data['age']) - 5.4;
                $formula2 = $formula2 = 495 / (1.29579 - 0.35004 * log10($data['waist'] - $data['neck'] + $data['hip']) + 0.22100 * log10($data['height'])) - 450;
                $percent = ($formula1 + $formula2) / 2;

                if ($percent < 9.8)
                    $title = "درصد چربی شما در مرحله خطرناک و سوءتغذیه میباشد،برای کاهش آسیب بدین هر چه سریع تر به یک پزشک مراجعه کنید.";
                elseif ($percent >= 9.8 && $percent <= 12)
                    $title = "درصد چربیتون خیلی کم هست و در نقطه بحرانی است، واگر میزان چربیتون کمتر از این میزان باشد بدنتون دچار بیماری و آسیب میشود،یه مشاور تغذیه و پزشک میتونه تو این مرحله خیلی کمکتون میکنه.";
                elseif ($percent > 12 && $percent <= 14)
                    $title = "بسیار عالی ، این درصد چربی نشون میده شما بدن ورزشی و سالمی دارید. ";
                elseif ($percent > 14 && $percent <= 20)
                    $title = " عالی، شما آدم جذابی هستین. تمریناتتون رو مرتب انجام بدین و به همین راه ادامه بدین ";
                elseif ($percent > 20 && $percent <= 24)
                    $title = " وضعیتتون خوبه، تغذیه رژییم و ورزش کمک میکنه که جذاب تر شین، اگه ورزش نمیکنید به نظرم از فردا شروع کنید،یا شدت تمرینتون رو بیشتر کنید";
                elseif ($percent > 24 && $percent <= 25)
                    $title = " وضعیتتون رضایت بخشه ، درصد چربیتون لبه مرزه ، اگه درصد چربیتون از این بیشتر شه چاقی نمایان میشه. ورزش و تغذیه مناسب کمک میکنه که همیشه فیت بمونید";
                elseif ($percent > 25 && $percent <= 31.3)
                    $title = "شما در نقطه بحران چاقی هستید ، اگه تغذیتون رو رعایت نکنید و درست ورزش نکنید، بیماریهای مختلف میتونه بهتون آسیب بزنه، بهترین راه حل براتون شروع تمرینات ورزشی و تغذیه رژییم از همین امروز هست . همین حالا با متخصصین ورزشی و تغذیه تماس بگیرید.";
                elseif ($percent > 31.3 && $percent <= 38)
                    $title = "شما چاقین! درصد چربیتون نشون میده که سالمتتون خییل براتون اهمیت نداره، چاقی باعث بوجود امدن خییل بیماریها میشه، تغذیه مناسب .و ورزش سبک بهترین پله برای کاهش وزنتون و چربی بدنتون هست .از همین امروز شروع کنید ";
                elseif ($percent > 38 && $percent <= 38.9)
                    $title = " درصد چربیتون خیلی زیاده و چاقیتون در مرحله خطرناک هست، از همین امروز به فکر کاهش وزن و تغذیه رژییم باشید، اگه عادات غذاییتون رو درست نکنید احتمال بیماریهای مختلف رو خیلی افزایش میدین. یه مشاور تغذیه و تمریین خیلی میتونه بهتون کمک کنه همین امروز باهاشون تماس بگیرین.";
                elseif ($percent > 38.9)
                    $title = "وضعیتتون خییل حاد و خطرناکه، باید هر چه سریعتر برای کاهش وزن بدنتون اقدام کنید، برنامه غذایی کم کالری روزانه، تمرینات ورزیش و هوازی بهترین راه حل برای کاهش وزن و چربیتون هست. حتما با یک متخصص تغذیه و مریب تمریین تماس بگیرید ";
            }
            $this->sendMessage("درصد چربی بدن شما:", []);
            $this->sendMessage($percent, []);
            $this->sendMessage($title, [
                [
                    ["text" => "منوی اصلی", "callback_data" => "Main_Menu"], ["text" => "ورود به کانال", "url" => "http://t.me/pabepa_ma"]
                ]
            ]);

        }
    }

    private function askWaist($editStatus)
    {
        $this->setHelperLevel("waist_asked");
        $title = "دور کمر خود را وارد کنید.";
        if ($editStatus)
            $this->editMessageText($title, []);
        else
            $this->sendMessage($title, []);
    }

    private function setWaist($waist)
    {
        if (is_numeric($waist))
        {
            mysqli_query($this->db, "UPDATE pabepa_bot.users SET waist = {$waist} WHERE user_id = {$this->user_id}");
            return true;
        }
        else
            return false;
    }

    private function askHip($editStatus)
    {
        $this->setHelperLevel("hip_asked");
        $title = "دور باسن خود را وارد کنید.";
        if ($editStatus)
            $this->editMessageText($title, []);
        else
            $this->sendMessage($title, []);
    }

    private function setHip($hip)
    {
        if (is_numeric($hip))
        {
            mysqli_query($this->db, "UPDATE pabepa_bot.users SET hip = '{$hip}' WHERE user_id = {$this->user_id}");
            return true;
        }
        else
            return false;
    }

    private function askNeck($editStatus)
    {
        $this->setHelperLevel("neck_asked");
        $title = "دور گردن خود را وارد کنید";
        if ($editStatus)
            $this->editMessageText($title, []);
        else
            $this->sendMessage($title, []);
    }

    private function setNeck($neck)
    {
        if (is_numeric($neck))
        {
            mysqli_query($this->db, "UPDATE pabepa_bot.users SET neck = {$height} WHERE user_id = {$this->user_id}");
            return true;
        }
        else
            return false;
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
            if ($this->setAge($this->text))
                $this->askGender(false);
            else
                $this->sendMessage("سن وارد شده معتبر نیست",[]);
        }
        elseif ($this->helper_level == "gender_asked")
        {
            if ($this->setGender($this->text))
                $this->askWeight(false);
            else
                $this->sendMessage("جنسیت وارد شده معتبر نیست", []);
        }
        elseif ($this->helper_level == "weight_asked")
        {
            if ($this->setWeight($this->text))
                $this->askHeight(false);
            else
                $this->sendMessage("وزن وارد شده معتبر نیست", []);
        }
        elseif ($this->helper_level == "height_asked")
        {
            if ($this->setHeight($this->text))
                $this->askSport(false);
            else
                $this->sendMessage("قد وارد شده معتبر نیست", []);
        }
        elseif ($this->helper_level == "sport_asked")
        {
            $this->setHelperLevel("bmi_showed");
            $this->showBmi(false);
        }
        elseif ($this->text == "Main_Menu" && $this->helper_level == "bmi_showed")
        {

            $this->setHelperLevelNull();
            $this->showMainMenu(true);
        }
    }

    private function showBmi($editStatus)
    {
        $data = $this->getRow();
        $bmi = $data['weigth'] / ( ($data['height']/100) * ($data['height']/100) );
        $title = "";
        if ($bmi < 18.5)
            $title = "کمبود وزن شما خییل زیاد و در مرحله بحراین است، برای افزایش وزن کالری مصرفی روزانتون باید بیشتر از میزان سوختن روزانتون(BMR(
باشه ورزش هم یه عامل موثر برای افزایش وزنتون هست.برای اطالع از کالری مصرفی روزانتون روی BMRکلیک کنید/ برای آشنای با نکات تغذیه و ورزیش به کانال ما
بپیوندید. ";
        elseif ($bmi >= 18.5 && $bmi < 25)
            $title = "شما وضعیت طبیعی دارید، اگه میخواین وزنتون تغییر نکنه باید کالری مصرفی روزانتون (BMR(با کالری دریافیت از غذاتون برابر باشه.,ورزش
کمک میکنه که هم سر حالتر بشین هم خوش تیپ تر پس تو برنامه هفتگیتون بزارین. برای اطالع از کالری مصرفی روزانتون روی BMRکلیک کنید/ برای آشنای با نکات
تغذیه و ورزیش به کانال ما بپیوندید. ";
        elseif ($bmi >= 25 && $bmi < 30)
            $title = "شما اضافه وزن دارید برای کاهش وزن شما باید کالری دریافیت روزانتون از کالری مصرفیتون(BMR (کمتر باشه.فقط با 15 دقیقه ورزش مناسب
میتونید سرعت کاهش وزنتون رو افزلیش بدین.همین امروز شروع کنین. برای اطالع از کالری مصرفی روزانتون روی BMRکلیک کنید/ برای آشنای با نکات تغذیه و ورزیش
به کانال ما بپیوندید. ";
        elseif ($bmi >= 30)
            $title = "چاق شما خییل اضافه وزن دارید و چاقین،بهتره هر چه سریعتر به فکر کاهش وزنتون باشید،با یه پیاده روی ساده و برنامه غذایی مناسب
میتونید باعث کاهش وزنتون بشید. برای کاهش وزن شما باید کالری دریافیت روزانتون از کالری مصرفیتون(BMR (کمتر باشه. برای اطالع از کالری مصرفی روزانتون روی
 BMRکلیک کنید/ برای آشنای با نکات تغذیه و ورزیش به کانال ما بپیوندید.";

        $button = [
            [
                ["text" => "منوی اصلی", "callback_data" => "Main_Menu"], ["text" => "ورود به کانال", "url" => "http://t.me/pabepa_ma"]
            ]
        ];

        if ($editStatus)
            $this->editMessageText($title, $button);
        else
            $this->sendMessage($title, $button);
    }

    private function askSport($editStatus)
    {
        $this->setHelperLevel("sport_asked");
        $title = "چه مقدار ورزش میکنید؟";
        if ($editStatus)
            $this->editMessageText($title, []);
        else
            $this->sendMessage($title, []);
    }

    private function askHeight($editStatus)
    {
        $this->setHelperLevel("height_asked");
        $title = "قد خود را وارد کنید(سانتی متر)";
        if ($editStatus)
            $this->editMessageText($title, []);
        else
            $this->sendMessage($title, []);
    }

    private function setHeight($height)
    {
        if (is_numeric($height))
        {
            mysqli_query($this->db, "UPDATE pabepa_bot.users SET height = {$height} WHERE user_id = {$this->user_id}");
            return true;
        }
        else
            return false;
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

    private function setWeight($weight)
    {
        if (is_numeric($weight))
        {
            mysqli_query($this->db, "UPDATE pabepa_bot.users SET weigth = {$weight} WHERE user_id = {$this->user_id}");
            return true;
        }
        else
            return false;
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
        if (is_numeric($age))
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
                ["text" => "مرد", "callback_data" => "male"], ["text" => "زن", "callback_data" => "female"]
            ]
        ];
        if ($editStatus)
            $this->editMessageText($title, $button);
        else
            $this->sendMessage($title, $button);
    }

    private function setGender($gender)
    {
        if ($gender == "male" || $gender == "female")
        {
            mysqli_query($this->db, "UPDATE pabepa_bot.users SET gender = '{$gender}' WHERE user_id = {$this->user_id}");
            return true;
        }
        else
            return false;
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