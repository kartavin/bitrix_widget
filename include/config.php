<?php
/**
 * client_id приложения
 */
define('CLIENT_ID', 'put something here');
/**
 * client_secret приложения
 */
define('CLIENT_SECRET', 'put something here');
/**
 * относительный путь приложения на сервере
 */
define('PATH', '/bitrixprod2/index.php');
/**
 * полный адрес к приложения
 */
define('REDIRECT_URI', 'http://fooderbot.ru'.PATH);
/**
 * scope приложения
 */
define('SCOPE', 'crm,log,user');

/**
 * протокол, по которому работаем. должен быть https
 */
define('PROTOCOL', "https");

/**
 * ИД пользователя, под которым будут создаваться события
 */
define('AUTH_TYPE_USER_ID', '78'); // TODO вынести в настройку

/**
 * ИД пользователя, под которым будут создаваться события
 */
define('SKIPPED_CALL_HANDLER_URI', 'http://fooderbot.ru/bitrixprod2/event.php');
?>