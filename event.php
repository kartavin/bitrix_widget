<?php
require_once('include/request.php');


/*
file_put_contents(
	"event.log", 
	var_export($_REQUEST, 1)."\n", 
	FILE_APPEND
);
*/

if ($_REQUEST['event'] == 'ONVOXIMPLANTCALLEND' && $_REQUEST['data']['CALL_FAILED_REASON'] == 'Skipped call') {
	$text = '';
	if (substr($_REQUEST['data']['PHONE_NUMBER'], 0, strlen('guest')) === 'guest') {
		$_REQUEST['data']['PHONE_NUMBER'] = substr($_REQUEST['data']['PHONE_NUMBER'], strlen('guest'));
		$text .= "У телефона удалён префикс guest.\r\n";
	}

	$data = '';
	foreach (generate_phone_formats($_REQUEST['data']['PHONE_NUMBER']) as $phone) {
		$data = call($_REQUEST["auth"]["domain"], "crm.contact.list", array(
			"auth" => $_REQUEST["auth"]["access_token"],
			"filter" => array('PHONE' => $phone),
			"select" => array('ID', 'NAME', 'LAST_NAME', 'COMPANY_ID', 'ASSIGNED_BY_ID'),
		));
		if ($data['total'] > 0) {
			break; // TODO: рассматривать все найденные
		}
	}

	/*
	file_put_contents(
		"event.log", 
		"=== RESULT OF LOOKUP FOR ".$_REQUEST['data']['PHONE_NUMBER']."===\n". var_export($data, 1) ."\n", 
		FILE_APPEND
	);
	*/	

	$header = "MIME-Version: 1.0\r\n";
	$header .= "Content-type: text/plain; charset=\"utf-8\"";
	$subject = '['.$_REQUEST["auth"]["domain"].'] Пропущенный звонок с номера ' . $_REQUEST['data']['PHONE_NUMBER'];
	if (isset($data['total']) && $data['total'] > 0) {
		$text .= "Найден контакт в CRM:\r\n";
		foreach ($data['result'] as $person) {
			$companyTitle = '';
			if (isset($person['COMPANY_ID'])) {
				$companyData = call($_REQUEST["auth"]["domain"], "crm.company.get", array(
					"auth" => $_REQUEST["auth"]["access_token"],
					'id' => $person['COMPANY_ID']
				));
				if (isset($companyData['result']['TITLE']))
					$companyTitle .= ' '.$companyData['result']['TITLE'];
			}

			// TODO связь лида с контактом и компанией?
			$data = call($_REQUEST["auth"]["domain"], "crm.lead.add", array(//TODO: переменные назвать иначе
				"auth" => $_REQUEST["auth"]["access_token"],
				'fields' => array(
					"TITLE" => "Вам звонил ".$person['NAME']." ".$person['LAST_NAME'].$companyTitle,
					"NAME" => $person['NAME'],
					"LAST_NAME" => $person['LAST_NAME'], // TODO company id
					"OPENED" => "Y",
					"ASSIGNED_BY_ID" => $person['ASSIGNED_BY_ID'],
					"SOURCE_ID" => "SELF",
					"STATUS_ID" => "14",
					"PHONE" => array(array("VALUE" => $_REQUEST['data']['PHONE_NUMBER'], "VALUE_TYPE" => "WORK"))
				),
				'params' => array("REGISTER_SONET_EVENT" => "Y")
			));

			$text .= sprintf("Имя: %s    Фамилия: %s    Компания: %s\r\n", $person['NAME'], $person['LAST_NAME'], $companyTitle);
			$text .= sprintf("Ссылка на контакт: https://%s/crm/contact/show/%s/\r\n\r\n", $_REQUEST["auth"]["domain"], $person['ID']);
			$text .= sprintf("Создан лид: https://%s/crm/lead/show/%s/\r\n", $_REQUEST["auth"]["domain"], $data['result']);
		}
	} else {
		$text .= "Контакт в CRM не найден (bitrix24 автоматически создал новый лид)\r\n";

		if (isset($_REQUEST['data']['CRM_ACTIVITY_ID'])) {
			$activityId = $_REQUEST['data']['CRM_ACTIVITY_ID'];
			$text .= 'Id дела: ' . $activityId . "\r\n";

			$data = call($_REQUEST["auth"]["domain"], "crm.activity.get", array(
				"auth" => $_REQUEST["auth"]["access_token"],
				'id' => $activityId
			));

			if (isset($data['result']['OWNER_TYPE_ID']) &&
				$data['result']['OWNER_TYPE_ID'] == '1' && // checking if owner is a lead
				isset($data['result']['OWNER_ID'])
			) {
				$leadId = $data['result']['OWNER_ID'];
				$text .= 'Id лида: ' . $leadId . "\r\n";
				$text .= sprintf("Ссылка на лид: https://%s/crm/lead/show/%s/\r\n", $_REQUEST["auth"]["domain"], $leadId);

				// get the lead creation time
				$data = call($_REQUEST["auth"]["domain"], "crm.lead.get", array(
					"auth" => $_REQUEST["auth"]["access_token"],
					'id' => $leadId
				));

				$ts_created = strtotime($data['result']['DATE_CREATE']);
				$ts_now = strtotime('now');

				if (($ts_now - $ts_created) > 5 * 60) {
					$text .= 'Лид был создан более 5 минут назад: ' . $data['result']['DATE_CREATE'] . ". Название не меняем.\r\n";
				} else {
					$text .= 'Лид был создан не более 5 минут назад: ' . $data['result']['DATE_CREATE'] . ". Меняем название.\r\n";

					$data = call($_REQUEST["auth"]["domain"], "crm.lead.update", array(
						"auth" => $_REQUEST["auth"]["access_token"],
						'id' => $leadId,
						'fields' => array(
							"TITLE" => $_REQUEST['data']['PHONE_NUMBER'] . " - Пропущенный звонок"
						),
						'params' => array("REGISTER_SONET_EVENT" => "Y")
					));

					$text .= 'Название лида изменено ' . ($data['result'] ? 'успешно' : 'неуспешно') . "\r\n";
				}
			}
		}
	}

	$to = 'email@example.com'; // comma separated
	$res = mail($to, '=?UTF-8?B?'.base64_encode($subject).'?=', $text, $header);

	file_put_contents(
		"event.log", 
		"Sent mail:\nTo: $to\nSubject: $subject\nText: $text\nResult: $res\n\n\n", 
		FILE_APPEND
	);
}
?>