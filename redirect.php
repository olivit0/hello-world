/******************************************************************************
	Page appelée par la page index.html en retour d'authentification PureCloud
	Enregistre dans la variable de session PureCloudToken le token renvoyé par PureCloud
	et redirige l'agent vers la page de stats
******************************************************************************/
<?php
	session_start();
	
	$token = $_GET['access_token'];
	if(strlen($token)>10){
		$_SESSION['PureCloudToken'] = $token;
	} else {
		$token = $_SESSION['PureCloudToken'];
	}
?>
<!DOCTYPE html>
<html>
	<head>
		<meta charset="UTF-8"/>
		<meta http-equiv="Content-Type" content="text/html; charset= utf-8" />
		<meta http-equiv="X-UA-Compatible" content="IE=edge"/>	
		<title>Statistiques agent sigvaris</title>
	</head>
	<script>
	window.location.replace('https://vtiger.artelab.ovh/sigvaris/statAgent.html');
	</script>
	<body>
	</body>
</html>