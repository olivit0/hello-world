<?php
/*----------------------------------------------------------------------------------------------------
            UserStatusStat.php 
 * collecte les statisques des différents filles d'attentes auquelles apartient l'agent
-----------------------------------------------------------------------------------------------------*/
session_start();
  
$traceActive = false;
function logTrace($info){
  if($traceActive){
    date_default_timezone_set('Europe/Paris');
    if(($h1 = fopen("userStatQueue.log", 'a+'))){
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
	Appel de methode /api/v2/users/{{userId}}/queues
 pour obernir la liste de queues assignées à l'agent
-------------------------------------------------------------------------------------------------------*/
$ci4 = curl_init('https://api.mypurecloud.ie/api/v2/users/' . $userID .'/queues');
curl_setopt($ci4, CURLOPT_HTTPHEADER, array("Authorization: Bearer " . $access_token , "Content-Type: application/json"));
curl_setopt($ci4, CURLOPT_RETURNTRANSFER, 1);

if(!($ce4 = curl_exec($ci4))){
  logTrace("erreur userQueues " . curl_errno($ci4));
} else{
  logTrace($ce4);
  $jd4= json_decode($ce4, true);
  $nQ=0;
  $uQ = array();
  foreach($qInfo = $jd4["entities"] as $qi){
    if($qi["joined"] === TRUE){
      $uQ[$nQ]["id"] = $qi["id"];
      $uQ[$nQ]["name"] = $qi["name"];
      $ci5 = curl_init('https://api.mypurecloud.ie/api/v2/analytics/queues/observations/query');
      curl_setopt($ci5, CURLOPT_HTTPHEADER, array("Authorization: Bearer " . $access_token , "Content-Type: application/json"));
      curl_setopt($ci5, CURLOPT_POST, TRUE);
      curl_setopt($ci5, CURLOPT_POSTFIELDS, '{"filter":{"type":"and","predicates":[{"type":"dimension","dimension": "queueId","operator": "matches","value":"' . $qi["id"] .'"},{"type":"dimension","dimension": "mediaType","operator": "matches","value": "voice"}]}}');
      curl_setopt($ci5, CURLOPT_RETURNTRANSFER, 1);
      $ce5 = curl_exec($ci5);  
      curl_close($ci5);   
      $jd5= json_decode($ce5, true); 
      foreach($result = $jd5["results"] as $rx){
        foreach($rx["data"] as $dx){
          switch($dx["metric"]){
            case "oInteracting":
              $uQ[$nQ]["nbCom"] = $dx["stats"]["count"];
            break;
            case "oWaiting":
              $uQ[$nQ]["nbAttente"] = $dx["stats"]["count"];
            break;
          }
        }
      }
      $ci6 = curl_init('https://api.mypurecloud.ie/api/v2/analytics/conversations/aggregates/query');
      curl_setopt($ci6, CURLOPT_HTTPHEADER, array("Authorization: Bearer " . $access_token , "Content-Type: application/json"));
      curl_setopt($ci6, CURLOPT_POST, TRUE);
      curl_setopt($ci6, CURLOPT_POSTFIELDS, '{"interval":"'.date("Y-m-d").'T08:00:00.000+01/'.date("Y-m-d").'T20:00:00.000+01","groupBy": [],"filter":{"type":"or","predicates":[{"type": "dimension","dimension": "queueId","operator": "matches","value": "' . $qi["id"] . '"}]}}}');
	
      curl_setopt($ci6, CURLOPT_RETURNTRANSFER, 1);

      $ce6 = curl_exec($ci6);
      logTrace($ce6);
      curl_close($ci6);
      $jd6= json_decode($ce6, true); 
      foreach($result = $jd6["results"] as $rx){
        foreach($rx["data"] as $dx){
          foreach($dx["metrics"] as $mx){
            switch($mx["metric"]){
              case "tAbandon":
                $uQ[$nQ]["abdMax"]=  round($mx["stats"]["max"]/1000);
                $uQ[$nQ]["abdCount"]=  $mx["stats"]["count"];
                $uQ[$nQ]["abdSum"]=  round($mx["stats"]["sum"]/1000);
             break;     
              case "tAcw": 
                $uQ[$nQ]["acwMax"] = round($mx["stats"]["max"]/1000);
                $uQ[$nQ]["acwCount"] = $mx["stats"]["count"];
                $uQ[$nQ]["acwSum"] = round($mx["stats"]["sum"]/1000);
              break;
              case "nOffered":
                $uQ[$nQ]["nbRec"] = $mx["stats"]["count"];
              break;
              case "nOverSla":
                $uQ[$nQ]["nbOSla"] = $mx["stats"]["count"];
              break;
              case "tHandle":
                $uQ[$nQ]["handleMax"] = round($mx["stats"]["max"]/1000);
                $uQ[$nQ]["handleCount"] = $mx["stats"]["count"];
                $uQ[$nQ]["handleSum"] = round($mx["stats"]["sum"]/1000);
              break;
              case "tTalkComplete":
                $uQ[$nQ]["talkMax"] = round($mx["stats"]["max"]/1000);
                $uQ[$nQ]["talkCount"] = $mx["stats"]["count"];
                $uQ[$nQ]["talkSum"] = round($mx["stats"]["sum"]/1000);
              break;
              case "tAnswered":
                $uQ[$nQ]["answeredMax"] = round($mx["stats"]["max"]/1000);
                $uQ[$nQ]["answeredCount"] = $mx["stats"]["count"];
                $uQ[$nQ]["answeredSum"] = round($mx["stats"]["sum"]/1000);
              break;
              case "tHeldComplete":
                $uQ[$nQ]["heldMax"] = round($mx["stats"]["max"]/1000);
                $uQ[$nQ]["heldCount"] = $mx["stats"]["count"];
                $uQ[$nQ]["heldSum"] = round($mx["stats"]["sum"]/1000);
              break;
              case "nTransferred":
                $uQ[$nQ]["transferred"] = $mx["stats"]["count"];
              break;
            }
          }
        }
      }
      $nQ++;  
    }
  }
}
curl_close($ci4);
ob_clean();
header("Content-Type: application/json");
$buf = "\"queues\":[";
for($i=0; $i<$nQ; ){
    $buf = $buf . sprintf("{\"name\":\"%s\",\"nbAttente\":\"%u\",\"nbCom\":\"%u\",\"nbRec\":\"%u\",\"nbOSla\":\"%u\",\"tAcw\":{\"max\":\"%u\",\"count\":\"%u\",\"sum\":\"%u\"},\"tHandle\":{\"max\":\"%u\",\"count\":\"%u\",\"sum\":\"%u\"},
                \"tTalkComplete\":{\"max\":\"%u\",\"count\":\"%u\",\"sum\":\"%u\"},
		\"tAnswered\":{\"max\":\"%u\",\"count\":\"%u\",\"sum\":\"%u\"},
		\"tHeld\":{\"max\":\"%u\",\"count\":\"%u\",\"sum\":\"%u\"},
		\"tAbandon\":{\"max\":\"%u\",\"count\":\"%u\",\"sum\":\"%u\"},
                \"nTransferred\":\"%u\"}",$uQ[$i]["name"],$uQ[$i]["nbAttente"],$uQ[$i]["nbCom"],$uQ[$i]["nbRec"],$uQ[$i]["nbOSla"],$uQ[$i]["acwMax"],$uQ[$i]["acwCount"],$uQ[$i]["acwSum"],$uQ[$i]["handleMax"],$uQ[$i]["handleCount"],$uQ[$i]["handleSum"],$uQ[$i]["talkMax"],$uQ[$i]["talkCount"],$uQ[$i]["talkSum"],$uQ[$i]["answeredMax"],$uQ[$i]["answeredCount"],$uQ[$i]["answeredSum"],$uQ[$i]["heldMax"],$uQ[$i]["heldCount"],$uQ[$i]["heldSum"],$uQ[$i]["abdMax"],$uQ[$i]["abdCount"],$uQ[$i]["abdSum"],$uQ[$i]["transferred"]); 
    $i++;
    if($i<$nQ){
        $buf = $buf . ",";
    }
};
$buf = $buf . "]";
echo('{"retour":1,'.$buf .'}'); 
?>
