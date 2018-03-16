<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class CallBackTimeModule {
    
    use BankHolidays;
    
    public $registrationTime;
    public $callBackTime;
    
    private $bankHolidays;
    private $slots;
    
    public function __construct($regTime, $callTime)
    {
        $this->registrationTime = new DateTime();
        $this->registrationTime->setTimestamp($regTime);
        $this->callBackTime = new DateTime();
        $this->callBackTime->setTimestamp($callTime);
        
        $this->bankHolidays = array();
        $this->slots = array();
    }
    
    public function setBankHolidays()
    {
        $jsonData = BankHolidays::get_bank_holidays(HOLIDAYS_URL);
        if(count($jsonData->events) > 0) {
            foreach($jsonData->events as $event) {
                array_push($this->bankHolidays, DateTime::createFromFormat("YYYY-m-d", $event->date));
            }
        }
    }
    
    public function setBasicRules($date, $bankHoliday)
    {
        $sunday = $date->format('l') == "Sunday";
        $bank_holiday = $date->format("YYY-m-d") == $bankHoliday;
        return $sunday || $bank_holiday;
    }
    
    public function setAdvancedRules($date)
    {
        $weekday = $date->format('l') != "Saturday" && $date->format('l') != "Sunday";
        $weekdayTime = $date->format('H') >= 9 && $date->format('H') <= 20;
        $weekend = $date->format('l') == "Saturday";
        
        if($weekday && $weekdayTime) {
            return true;
        } else if($weekend) {
            if($date->format('H') >= 9 && $date->format('H') <= 12) {
                return true;
            } else if($date->format('H') == 12 && $date->format('i') <= 30) {
                return true;
            } else {
                return false;
            }
        }
        return false;
    }
    
    public function validateBasic($callBackTime)
    {
        if(count($this->bankHolidays) > 0) {
            foreach($this->bankHolidays as $bankHoliday) {
                $result = $this->setBasicRules($callBackTime, $bankHoliday);
                if($result == true) {
                    return false;
                } 
            }
            return true;
        }
        return NULL;
    }
    
    public function validateBasicAdvanced($callBackTime)
    {
        if(count($this->bankHolidays) > 0) {
            foreach($this->bankHolidays as $bankHoliday) {
                $result = $this->setAdvancedRules($callBackTime);
                if($result == true) {
                    return false;
                } 
            }
            return true;
        }
        return NULL;
    }
    
    public function validateDisallowed(DateInterval $interval)
    {
        $callBackTime = $this->registrationTime->add($interval);
        $validate = $this->validateBasic($callBackTime) || $this->validateBasicAdvanced($callBackTime);
        $map = array(
            "Sunday", "Saturday", "Friday", "Thursday", "Wednesday", "Tuesday", "Monday"
        );
        if((int) $interval->format("%d") == 6 || !$validate) {
            $daysAdd = array_search($callBackTime->format('l'), $map);
            $hours = (int) $callBackTime->format('H');
            $minutes = (int) $callBackTime->format('i');
            if($minutes != 0) {
                $hoursAdd = 23-$hours+9;
                $minutesAdd = 60-$minutes;
            } else {
                $hoursAdd = 24-$hours+9;
                $minutesAdd = 0;
            }
            $callBackTime = $callBackTime->add(new DateInterval("P" . $daysAdd . "D" . "T" . $hoursAdd . "H" . $minutesAdd . "M"));
            if($this->callBackTime <= $callBackTime) {
                return $callBackTime;
            }
            return false;
        } else {
            return $this->validateDisallowedEarlier($this->registrationTime, $callBackTime);
        }
        return true;
    }
    
    private function validateDisallowedEarlier($registrationTime, $callBackTime)
    {
        $interval = new DateInterval("PT2H");
        $getHourSlots = $this->getHourSlots($registrationTime, $callBackTime);
        if((int) $interval->format("%h") == 2) {
            return count($getHourSlots) == 4;
        }
        return false;
    }
    
    public function getHourSlots($registrationTime, $callBackTime)
    {
        $interval = new DateInterval("PT30M");
        $initial_date = $registrationTime->sub($interval);
        $date = $registrationTime;
        if(count($this->bankHolidays) > 0) {
            foreach($this->bankHolidays as $bankHoliday) {
                while($date < $callBackTime) {
                    $initial_date = $initial_date->add($interval);
                    $date = $initial_date->add($interval);
                    $validate = $this->setBasicRules($date, $bankHoliday) && $this->setAdvancedRules($date);
                    $validate_initial = $this->setBasicRules($initial_date, $bankHoliday) && $this->setAdvancedRules($initial_date);
                    if($validate_date && $validate_initial) {
                        array_push($this->slots, array($initial_date, $date));
                    }
                }
            }
        }
        return $this->slots;
    }
    
}