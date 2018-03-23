<?php
require_once('include/config.php');

/**
 * Производит перенаправление пользователя на заданный адрес
 *
 * @param string $url адрес
 */
function redirect($url)
{
	Header("HTTP 302 Found");
	Header("Location: ".$url);
	die();
}

/**
 * Совершает запрос с заданными данными по заданному адресу. В ответ ожидается JSON
 *
 * @param string $method GET|POST
 * @param string $url адрес
 * @param array|null $data POST-данные
 *
 * @return array
 */
function query($method, $url, $data = null)
{
	$query_data = "";

	$curlOptions = array(
		CURLOPT_RETURNTRANSFER => true
	);

	if($method == "POST")
	{
		$curlOptions[CURLOPT_POST] = true;
		$curlOptions[CURLOPT_POSTFIELDS] = http_build_query($data);
	}
	elseif(!empty($data))
	{
		$url .= strpos($url, "?") > 0 ? "&" : "?";
		$url .= http_build_query($data);
	}

	$curl = curl_init($url);
	curl_setopt_array($curl, $curlOptions);
	$result = curl_exec($curl);

	return json_decode($result, 1);
}

/**
 * Вызов метода REST.
 *
 * @param string $domain портал
 * @param string $method вызываемый метод
 * @param array $params параметры вызова метода
 *
 * @return array
 */
function call($domain, $method, $params)
{
	return query("POST", PROTOCOL."://".$domain."/rest/".$method, $params);
}

/*
 * Генерация массива различных форматов телефонного номера их исходно 79161234567
 * 79161234567
 * +79161234567
 * +7 (916) 123-45-67
 * 89161234567
 */
function generate_phone_formats($phone)
{
    $code = substr($phone, 1, 3);
    $number = substr($phone, 4, 7);
    return array(
        $phone,
        '+' . $phone,
        '+7 (' . $code . ') ' . substr($number, 0, 3) . '-' . substr($number, 3, 2) . '-' . substr($number, 5, 2),
        '8' . $code . $number
    );
}

?>