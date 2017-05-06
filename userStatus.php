<?php
/*----------------------------------------------------------------------------------------------------
            UserStatus.php 
 * phase initiale de connexion pour récupérer l'identifiant utilisateur
-----------------------------------------------------------------------------------------------------*/
  session_start();
  
$traceActive = false;
function logTrace($info){
  if($traceActive){
    date_default_timezone_set('Europe/Paris');
    if(($h1 = fopen("userStatus.log", 'a+'))){
      fwrite($h1, date("d-m H:i:s ", time()) . $info . "\r\n");
      fclose($h1);
    }
  }
}

/*------------------------------------------------------------------------------------------------------
          Test si une session PureCloud est ouverte avec un access_token valide 
-------------------------------------------------------------------------------------------------------*/	
if(!isset($_SESSION['PureCloudToken'])){
  ob_clean();
  header("Content-Type: application/json");
  die('{"retour":0}');
}

$access_token = $_SESSION['PureCloudToken'];
	
/*------------------------------------------------------------------------------------------------------
	Appel de methode users/me pour Lecture du code userID et sauvegarde dans la variable de session userID
*******************************************************************************************************/

$ci1 = curl_init('https://api.mypurecloud.ie/api/v2/users/me?expand=routingStatus,presence,conversationSummary');
curl_setopt($ci1, CURLOPT_HTTPHEADER, array("Authorization: Bearer " . $access_token , "Content-Type: application/json"));
curl_setopt($ci1, CURLOPT_RETURNTRANSFER, 1);
$ce1 = curl_exec($ci1); 
logTrace($ce1);
curl_close($ci1);

$t = time();
$jd1 = json_decode($ce1, true);
$userID = $jd1["id"];
$_SESSION['userID']=$userID;
$userName = $jd1["name"];
$presenceStatus = $jd1["presence"]["presenceDefinition"]["systemPresence"];
$presenceDebut = $t - strtotime($jd1["presence"]["modifiedDate"]);
$acdStatus = $jd1["routingStatus"]["status"];
$acdDebut = $t - strtotime($jd1["routingStatus"]["startTime"]);
if($acdDebut < 0){ $acdDebut = 0;}
if($presenceDebut < 0) {$presenceDebut = 0;	}
ob_clean();
header("Content-Type: application/json");
echo('{"retour":1,"user":{"nom":"' . $userName . '","presenceStatus":"' . $presenceStatus . '","presenceDebut":"' . $presenceDebut . '","acdStatus":"' . $acdStatus . '","acdDebut":"' . $acdDebut. '"}}');
?>
