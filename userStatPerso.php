<?php
/*----------------------------------------------------------------------------------------------------
            UserStatusStat.php 
 * collecte les statisques des différents temps passés par l'agent et des communications traitées
-----------------------------------------------------------------------------------------------------*/
session_start();
  
$traceActive = false;
function logTrace($info){
  if($traceActive){
    date_default_timezone_set('Europe/Paris');
    if(($h1 = fopen("userStatPerso.log", 'a+'))){
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
$userID = $_SESSION['userID'];
	
/*------------------------------------------------------------------------------------------------------
	Appel de methode users/aggregates/query pour obtenir les différents temps passés par l'agent
*******************************************************************************************************/
$ci2 = curl_init('https://api.mypurecloud.ie/api/v2/analytics/users/aggregates/query');
curl_setopt($ci2, CURLOPT_HTTPHEADER, array("Authorization: Bearer " . $access_token , "Content-Type: application/json"));
curl_setopt($ci2, CURLOPT_POST, TRUE);
curl_setopt($ci2, CURLOPT_POSTFIELDS, '{"interval":"' . date("Y-m-d") . 'T08:00:00.000+01/' . date("Y-m-d").'T20:00:00.000+01","granularity": "PT12H","groupBy": ["userId"],
"metrics":["tSystemPresence", "tAgentRoutingStatus"],"filter": {"type": "OR","predicates": [{"dimension": "userID","value":"' . $userID . '"}]}}');
	
curl_setopt($ci2, CURLOPT_RETURNTRANSFER, 1);

$ce2 = curl_exec($ci2);
logTrace($ce2);	
curl_close($ci2);
$jd2 = json_decode($ce2, true); 
foreach($result = $jd2["results"] as $rx){
  foreach($rx["data"] as $dx){
    foreach($dx["metrics"] as $mx){
      switch($mx["qualifier"]){
	case "AWAY"	:	// Temps en pause Away
          $dureeAway = round($mx["stats"]["sum"] / 1000);
	break;
	case "BREAK"	:	// Temps en pause Away
	  $dureeBreak = round($mx["stats"]["sum"]/ 1000);
	break;
	case "INTERACTING" :   // temps passé en interaction ACD Com + ACM
	  $dureeInteracting = round($mx["stats"]["sum"]/ 1000);
	break;
	case "AVAILABLE":  
	  $dureeAvailable = round($mx["stats"]["sum"]/1000);
	break;
	case "OFFLINE":
	  $dureeOffLine = round($mx["stats"]["sum"]/1000);
	break;
	case "ON_QUEUE":
	  $dureeOnQueue = round($mx["stats"]["sum"]/1000);
	break;
	case "IDLE":   // Attende d'un interaction DISPO ACD
	  $dureeIdle = round($mx["stats"]["sum"]/1000);
	break;
	case "COMMUNICATING": // Temps passé état ON_QUEUE mais en communication non ACD
	  $dureeEnCom = round($mx["stats"]["sum"]/1000);
	break;
	case "NOT_RESPONDING": 
	  $dureeNoAnswer = round($mx["stats"]["sum"]/1000);
	break;
	default:
	  logTrace(var_dump($dx["metrics"]));
	break;
      }
    }
  }
}
/*------------------------------------------------------------------------------------------------------
	Appel de methode analytics/conversations/aggregates/query pour obernir les nombres et temps de communication
-------------------------------------------------------------------------------------------------------*/
$ci3 = curl_init('https://api.mypurecloud.ie/api/v2/analytics/conversations/aggregates/query');
curl_setopt($ci3, CURLOPT_HTTPHEADER, array("Authorization: Bearer " . $access_token , "Content-Type: application/json"));
curl_setopt($ci3, CURLOPT_POST, TRUE);
curl_setopt($ci3, CURLOPT_POSTFIELDS, '{"interval":"'.date("Y-m-d").'T08:00:00.000+01/'.date("Y-m-d").'T20:00:00.000+01","groupBy": [],"filter":{"type":"or","predicates":[{"type": "dimension","dimension": "userId","operator": "matches","value": "' .$userID . '"}]}}}');
	
curl_setopt($ci3, CURLOPT_RETURNTRANSFER, 1);

$ce3 = curl_exec($ci3);
logTrace($ce3);
curl_close($ci3);
$jd3= json_decode($ce3, true); 
foreach($result = $jd3["results"] as $rx){
  foreach($rx["data"] as $dx){
    foreach($dx["metrics"] as $mx){
      switch($mx["metric"]){
        case "tAcw":
          $tAcwMax = round($mx["stats"]["max"]/1000);
	  $tAcwCount = $mx["stats"]["count"];   
	  $tAcwSum = round($mx["stats"]["sum"]/1000);
	break;
	case "tHandle":
	  $tHandleMax = round($mx["stats"]["max"]/1000);
	  $tHandleCount = $mx["stats"]["count"];
	  $tHandleSum = round($mx["stats"]["sum"]/1000);
	break;
	case "tTalkComplete":
	  $tTalkCompleteMax = round($mx["stats"]["max"]/1000);
	  $tTalkCompleteCount = $mx["stats"]["count"];
	  $tTalkCompleteSum = round($mx["stats"]["sum"]/1000);
	break;
	case "tAnswered":
	  $tAnsweredMax = round($mx["stats"]["max"]/1000);
	  $tAnsweredCount = $mx["stats"]["count"];
	  $tAnsweredSum = round($mx["stats"]["sum"]/1000);
	break;
	case "tHeldComplete":
	  $tHeldMax = round($mx["stats"]["max"]/1000);
	  $tHeldCount = $mx["stats"]["count"];
	  $tHeldSum = round($mx["stats"]["sum"]/1000);
	break;
        case "nTransferred":
          $nTransferred = $mx["stats"]["count"];
        break;
        
      }
    }
  }
}
ob_clean();
header("Content-Type: application/json");
echo('{"retour":1,"statusDuree":{"away":"' . $dureeAway . '","break":"' . $dureeBreak .'","interacting":"' . $dureeInteracting .'","available":"' . $dureeAvailable .'","onQueue":"' . $dureeOnQueue .'", "idle":"' . $dureeIdle .'","communicating":"' . $dureeEnCom .'","noAnswer":"' . $dureeNoAnswer .'"},
"conversations":{"tAcw":{"max":"' . $tAcwMax .'","count":"' .$tAcwCount .'","sum":"' .$tAcwSum .'"},"tHandle":{"max":"' . $tHandleMax .'","count":"' .$tHandleCount .'","sum":"' .$tHandleSum .'"},
                "tTalkComplete":{"max":"' . $tTalkCompleteMax .'","count":"' .$tTalkCompleteCount .'","sum":"' .$tTalkCompleteSum .'"},
		"tAnswered":{"max":"' . $tAnsweredMax .'","count":"' .$tAnsweredCount .'","sum":"' .$tAnsweredSum .'"},
		"tHeld":{"max":"' . $tHeldMax .'","count":"' .$tHeldCount .'","sum":"' .$tHeldSum .'"},
                "nTransferred":"' .$nTransferred .'"}}');
?>
