<?php
require_once('include/config.php');
require_once('include/request.php');

session_start();

$error = "";

// clear auth session
if(isset($_REQUEST["clear"]) || $_SERVER["REQUEST_METHOD"] == "POST")
{
	unset($_SESSION["query_data"]);
}

if($_SERVER["REQUEST_METHOD"] == "POST")
{
/******************* get code *************************************/
	if(!empty($_POST["portal"]))
	{
		$domain = $_POST["portal"];
		$params = array(
			"response_type" => "code",
			"client_id" => CLIENT_ID,
			"redirect_uri" => REDIRECT_URI,
		);
		$path = "/oauth/authorize/";

		redirect(PROTOCOL."://".$domain.$path."?".http_build_query($params));
	}
/******************** /get code ***********************************/
}

if(isset($_REQUEST["code"]))
{
/****************** get access_token ******************************/
	$code = $_REQUEST["code"];
	$domain = $_REQUEST["domain"];
	$member_id = $_REQUEST["member_id"];

	$params = array(
		"grant_type" => "authorization_code",
		"client_id" => CLIENT_ID,
		"client_secret" => CLIENT_SECRET,
		"redirect_uri" => REDIRECT_URI,
		"scope" => SCOPE,
		"code" => $code,
	);
	$path = "/oauth/token/";

	$query_data = query("GET", PROTOCOL."://".$domain.$path, $params);

	if(isset($query_data["access_token"]))
	{
		$_SESSION["query_data"] = $query_data;
		$_SESSION["query_data"]["ts"] = time();

		redirect(PATH);
		die();
	}
	else
	{
		$error = "Произошла ошибка авторизации! ".print_r($query_data, 1);
	}
/********************** /get access_token *************************/
}
elseif(isset($_REQUEST["refresh"]))
{
/******************** refresh auth ********************************/
	$params = array(
		"grant_type" => "refresh_token",
		"client_id" => CLIENT_ID,
		"client_secret" => CLIENT_SECRET,
		"redirect_uri" => REDIRECT_URI,
		"scope" => SCOPE,
		"refresh_token" => $_SESSION["query_data"]["refresh_token"],
	);

	$path = "/oauth/token/";

	$query_data = query("GET", PROTOCOL."://".$_SESSION["query_data"]["domain"].$path, $params);

	if(isset($query_data["access_token"]))
	{
		$_SESSION["query_data"] = $query_data;
		$_SESSION["query_data"]["ts"] = time();

		redirect(PATH);
		die();
	}
	else
	{
		$error = "Произошла ошибка авторизации! ".print_r($query_data);
	}
/********************* /refresh auth ******************************/
}

require_once(dirname(__FILE__)."/include/header.php");

if(!isset($_SESSION["query_data"]))
{
/******************************************************************/
	if($error)
	{
		echo '<b>'.$error.'</b>';
	}
?>
<form action="" method="post">
	<input type="text" name="portal" placeholder="Адрес портала">
	<input type="submit" value="Авторизоваться">
</form>
<?

/******************************************************************/

}
else
{
/******************************************************************/

	if(time() > $_SESSION["query_data"]["ts"] + $_SESSION["query_data"]["expires_in"])
	{
		echo "<b>Авторизационные данные истекли</b>";
	}
	else
	{
		echo "Авторизационные данные истекут через ".($_SESSION["query_data"]["ts"] + $_SESSION["query_data"]["expires_in"] - time())." секунд";
	}
?>

<ul>
	<li><a href="<?=PATH?>?test=event.bind">Установить обработчик события</a>
	<li><a href="<?=PATH?>?test=event.get">Просмотр установленных обработчиков событий</a>
	<li><a href="<?=PATH?>?test=event.unbind">Удаление установленного обработчика события</a>
</ul>

<a href="<?=PATH?>?refresh=1">Обновить данные авторизации</a><br />
<a href="<?=PATH?>?clear=1">Очистить данные авторизации</a><br />

<?
	$test = isset($_REQUEST["test"]) ? $_REQUEST["test"] : "";
	switch($test)
	{
		case 'event.bind':
			$data = call($_SESSION["query_data"]["domain"], "event.bind", array(
				"auth" => $_SESSION["query_data"]["access_token"],
				"EVENT" => "ONVOXIMPLANTCALLEND",
				"HANDLER" => SKIPPED_CALL_HANDLER_URI,
				"auth_type" => AUTH_TYPE_USER_ID,
			));

		break;

		case 'event.get':
    		$data = call($_SESSION["query_data"]["domain"], "event.get", array(
        		"auth" => $_SESSION["query_data"]["access_token"])
    		);
		break;

		case 'event.unbind':
			$data = call($_SESSION["query_data"]["domain"], "event.unbind", array(
				"auth" => $_SESSION["query_data"]["access_token"],
				'EVENT' => 'ONVOXIMPLANTCALLEND',
				'HANDLER' => SKIPPED_CALL_HANDLER_URI
			));
		break;

		default:
			$data = $_SESSION["query_data"];

		break;
	}

	echo '<pre>'; var_export($data); echo '</pre>';

	/******************************************************************/
}

require_once(dirname(__FILE__)."/include/footer.php");
?>