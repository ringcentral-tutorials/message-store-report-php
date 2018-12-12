#!/usr/bin/env php
<?php
require_once('_bootstrap.php');
use RingCentral\SDK\SDK;
$dotenv = new Dotenv\Dotenv(__DIR__);
$dotenv->load();

$rcsdk = null;
if (getenv('ENVIRONMENT_MODE') == "sandbox") {
    $rcsdk = new SDK(getenv('CLIENT_ID_SB'),
        getenv('CLIENT_SECRET_SB'), RingCentral\SDK\SDK::SERVER_SANDBOX);
}else{
    $rcsdk = new SDK(getenv('CLIENT_ID_PROD'),
        getenv('CLIENT_SECRET_PROD'), RingCentral\SDK\SDK::SERVER_PRODUCTION);
}
$platform = $rcsdk->platform();
$archiveFolder = "archives/";
if (is_dir($archiveFolder) === false)
    mkdir($archiveFolder, 0777, true);
login();

function CreateMessageStoreReport(){
    global $platform;
    echo ("create report ...\r\n");
    $endpoint = "/account/~/message-store-report";
    $to = date("Y-m-d\TH:i:s.00Z\Z", time());
    $lessXXDays = time() - (86400 * 30);
    $from = date("Y-m-d\TH:i:s.00Z\Z", $lessXXDays);
    try {
        $response = $platform->post($endpoint,
            array(
                'dateFrom' => $from,
                'dateTo' => $to,
            ));
        $json = $response->json();
        if ($json->status == "Completed")
          GetMessageStoreReportArchive($json->id);
        else if ($json->status == "Accepted" || $json->status == "InProgress")
          GetMessageStoreReportTask($json->id);
        else
          echo ($json->status."\r\n");
    }catch(\RingCentral\SDK\Http\ApiException $e) {
        echo($e);
    }
}

function GetMessageStoreReportTask($taskId){
    global $platform;
    echo ("polling ...\r\n");
    $endpoint = "/account/~/message-store-report/" . $taskId;
    try {
        $response = $platform->get($endpoint);
        $json = $response->json();
        if ($json->status == "Completed")
            GetMessageStoreReportArchive($json->id);
        else if ($json->status == "Accepted" || $json->status == "InProgress"){
            sleep(2);
            GetMessageStoreReportTask($taskId);
        }else
          echo ($json->status);
    }catch(\RingCentral\SDK\Http\ApiException $e) {
        echo($e);
    }
}

function GetMessageStoreReportArchive($taskId){
    global $platform;
    echo ("getting report uri ...\r\n");
    $endpoint = "/account/~/message-store-report/" . $taskId . "/archive";
    try {
        $response = $platform->get($endpoint);
        $json = $response->json();
        for ($i=0; $i < count($json->records); $i++){
            $fileName = $to = date("Y-m-d\TH:i:s.00Z\Z", time()) . "_" . $i . ".zip";
            GetMessageStoreReportArchiveContent($json->records[$i]->uri, $fileName);
        }
        echo ("Done\r\n");
    }catch(\RingCentral\SDK\Http\ApiException $e) {
        echo($e);
    }
}

function GetMessageStoreReportArchiveContent($contentUri, $fileName){
    global $platform;
    global $archiveFolder;
    echo ("Save report zip file to archives folder.\r\n");
    $uri = $platform->createUrl($contentUri, array(
        'addServer' => false,
        'addMethod' => 'GET',
        'addToken'  => true
    ));
    $dest = $archiveFolder.$fileName;
    file_put_contents($dest, fopen($uri, 'r'));
}

function login(){
    global $platform;
    global $tokens;
    try {
        if (getenv('DEV_MODE') == "sandbox")
            $platform->login(getenv('USERNAME_SB'), getenv('EXTENSION_SB'), getenv('PASSWORD_SB'));
        else
            $platform->login(getenv('USERNAME_PROD'), getenv('EXTENSION_PROD'), getenv('PASSWORD_PROD'));
        CreateMessageStoreReport();
    }catch (\RingCentral\SDK\Http\ApiException $e) {
        echo($e);
    }
}
