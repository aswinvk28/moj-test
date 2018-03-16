<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

trait BankHolidays {
    static function get_bank_holidays($url)
    {
        return json_decode(file_get_contents($url));
    }
}