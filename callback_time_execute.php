<?php

print("\nEnter the date in the format: " . "YYYY-m-d H:i:s\n");

define('TICKET_REGISTRATION', time());
define('HOLIDAYS_URL', "https://www.gov.uk/bank-holidays/england-and-wales.json");

define('CALLBACK_TIME', date_create_from_format("YYYY-m-d H:i:s", $argv[1]));

require_once 'utils.php';
require_once 'module.php';

$callBackTimeModule = new CallBackTimeModule(TICKET_REGISTRATION, CALLBACK_TIME);

$callBackTimeModule->setBankHolidays();

// disallowed interval of at least 2 hrs gap
var_dump($callBackTimeModule->validateDisallowed(new DateInterval("PT2H")));

// disallowed interval of 6 working days
var_dump($callBackTimeModule->validateDisallowed(new DateInterval("P6D")));

print_r($callBackTimeModule->getHourSlots($callBackTimeModule->registrationTime, $callBackTimeModule->callBackTime));