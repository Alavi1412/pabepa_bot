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
    curl_setopt($ch, CURLOPT_URL,"https://api.telegram.org/bot274622754:AAFb0_FK4ShDjOjy1KpnbRf-U9-GVBzNpVk/{$method}");
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