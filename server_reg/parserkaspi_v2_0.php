<?php
// ! Парсинг Kaspi.php
$ver = "_d06_30";

set_time_limit(0);
header('Content-type: text/html; charset=utf-8');
// устанавливаем московское время
date_default_timezone_set("Europe/Moscow");

// настройки прокси
$proxy = array(
	// Proxy proxymania.ru (для авторизации)
	// Proxy AstroProxy (dinamic)
	[
		'proxy_ip' => '109.248.7.64',
		'proxy_port' => '10299',
		'proxy_user' => 'telcop3543',
		'proxy_pwd' => '9ee096',
		'proxy_type' => 'CURLPROXY_HTTP',
		'proxy_provider' => 'AstroProxy',
		'proxy_ip_restart' => 'node-ru-281.astroproxy.com:10299/api/changeIP?apiToken=c39eda75f9269f0e'
	],
	[
		'proxy_ip' => '185.102.73.43',
		'proxy_port' => '10261',
		'proxy_user' => 'telcop3543',
		'proxy_pwd' => '9ee096',
		'proxy_type' => 'CURLPROXY_HTTP',
		'proxy_provider' => 'AstroProxy',
		'proxy_ip_restart' => 'node-kz-5.astroproxy.com:10261/api/changeIP?apiToken=c39eda75f9269f0e'
	]
);


// Сохраняем время начала парсинга
$time_start = time();

include 'simple_html_dom.php';

/* Загрузка страницы при помощи cURL */
function curl_get_contents($page_url, $base_url, $pause_time = 0, $retry = 0)
{
	/*
	$page_url - адрес страницы-источника
	$base_url - адрес страницы для поля REFERER
	$pause_time - пауза между попытками парсинга
	$retry - 0 - не повторять запрос, 1 - повторить запрос при неудаче
	*/
	global $proxy;
	$proxy_item = $proxy[1];

	$error_page = array();
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxy_item['proxy_user'] . ":" . $proxy_item['proxy_pwd']);
	curl_setopt($ch, CURLOPT_PROXY, $proxy_item['proxy_ip'] . ":" . $proxy_item['proxy_port']);
	curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.1; WOW64; rv:38.0) Gecko/20100101 Firefox/38.0");
	curl_setopt($ch, CURLOPT_COOKIEJAR, str_replace("\\", "/", getcwd()) . '/kaspi.txt');
	curl_setopt($ch, CURLOPT_COOKIEFILE, str_replace("\\", "/", getcwd()) . '/kaspi.txt');
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); // Автоматом идём по редиректам
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); // Не проверять SSL сертификат
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0); // Не проверять Host SSL сертификата
	curl_setopt($ch, CURLOPT_URL, $page_url); // Куда отправляем
	curl_setopt($ch, CURLOPT_REFERER, $base_url); // Откуда пришли
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // Возвращаем, но не выводим на экран результат
	$response['html'] = curl_exec($ch);
	$info = curl_getinfo($ch);
	if ($info['http_code'] != 200 && $info['http_code'] != 404) {
		$error_page[] = array(1, $page_url, $info['http_code']);
		if ($retry) {
			sleep($pause_time);
			$response['html'] = curl_exec($ch);
			$info = curl_getinfo($ch);
			if ($info['http_code'] != 200 && $info['http_code'] != 404)
				$error_page[] = array(2, $page_url, $info['http_code']);
		}
	}
	$response['code'] = $info['http_code'];
	$response['errors'] = $error_page;
	curl_close($ch);
	return $response;
}

// Инициализация библиотеки simple_html_dom.php без использования команды file_get_html
function init_html($page)
{
	// Создаем класс для работы с библиотекой simple_html_dom.php
	$dom = new simple_html_dom(
		null,
		true,
		true,
		DEFAULT_TARGET_CHARSET,
		true,
		DEFAULT_BR_TEXT,
		DEFAULT_SPAN_TEXT
	);
	$dom->load($page, true, true);
	return $dom;
}


$categories = array(); // ассоциативный массив с категориями
$url_products = array(); // ссылки на товары
$offers = array(); // ассоциативный массив с товарами
$url = 'https://kaspi.kz/shop/search/?q=%3AallMerchants%3ARautel&at=1';
$namepage  = '&page=';
$url_base = "https://kaspi.kz/shop";

$url_products = array();
// Перебираем все страницы page
//! убрать $i <= 4 после тестирования
for ($i = 1;; $i++) {
	$url_page = $url . $namepage . $i;
	// Парсим страницы с ссылками на товары
	$time = (rand(5, 20) / 20);
	//$time = 0;
	$content = curl_get_contents($url_page, $url_base, $time, 1);
	$html = init_html($content['html']);
	sleep(rand(5, 20) / 20);
	// Парсим каталог и сохраняем в ассоциативный массив
	if ($i == 1) {
		$id = 10880000; // задаем стартовый id для категорий
		// 1-й уровень каталога
		foreach ($html->find('div.tree ul', 0)->children() as $li1) {
			$id++;
			$category['name'] = $li1->find('a', 0)->plaintext;
			$category['id'] = $id;
			$category['parent_id'] = "";
			$categories[] = $category;
			// 2-й уровень каталога
			$parent1_id = $id;
			foreach ($li1->find("ul", 0)->children() as $li2) {
				$id++;
				$category['name'] = $li2->find('a', 0)->plaintext;
				$category['id'] = $id;
				$category['parent_id'] = $parent1_id;
				$categories[] = $category;
				$parent2_id = $id;
				foreach ($li2->find("ul", 0)->children() as $li3) {
					$id++;
					$category['name'] = $li3->find('a', 0)->plaintext;
					$category['id'] = $id;
					$category['parent_id'] = $parent2_id;
					$categories[] = $category;
				}
			}
		}
		// проверка ассоциативного массива
		//echo "<br>проверка массива<br><br>";
		//foreach ($categories as $category) {
		//	echo $category['id'], " : ", $category['parent_id'], " : ", $category['name'], "<br>";
		//}
	}
	// если на странице уже нет карточек с товарами, то цикл завершается
	if ($i > 1 && $html->find('h1.search-result__title-notfound', 0)->plaintext != '') {
		$html->clear(); // подчищаем
		unset($html);
		unset($content);
		break;
	}
	$prod_items = $html->find('.item-card'); // сохраняем содержимое карточки на странице
	foreach ($prod_items as $element) {
		// сохраняем url-товара
		$url_product['url'] = 'https://kaspi.kz' . $element->find('.item-card__image-wrapper', 0)->attr['href'];
		$url_product['referer'] = $url_page; // Сохраняем referer для последующего парсинга товаров
		//парсим цену товара ₸
		$url_product['price'] = (int)trim(str_replace(['₸', ' '], '', $element->find('div.item-card__debet', 0)->find('span.item-card__prices-price', 0)->plaintext));
		// вырезаем из url id товара
		$id_edit = str_replace('/?c=750000000', '', $url_product['url']);
		$len = strlen($id_edit);
		$pos_id = strrpos($url_product['url'], '-') + 1;
		$url_product['id'] = (int)substr($id_edit, $pos_id, $len - $pos_id);
		$url_products[] = $url_product;
	}
	$html->clear(); // подчищаем
	unset($html);
	unset($content);
	$url_base = $url_page; // адрес страницы для поля REFERER для парсинга следующ. стран. ставим предыдущий адрес
}
echo ($i - 1) . ' страниц ссылок спарсено. Всего ' . count($url_products) . ' ссылок на товары<br>';

// проверка спарсенных ссылок на товары
//echo "<br>проверка ссылок на товары<br><br>";
//foreach ($url_products as $url_product) 
//echo $url_product['url'], "<br> ", $url_product['referer'], "<br>цена: ", $url_product['price'], "<br>id: ", $url_product['id'], "<br> ";


// Парсинг данных о товаре по ссылкам
//! включить цикл после тестирования
foreach ($url_products as $url_product) {
	//$url_product = $url_products[4];
	$time = sleep(rand(5, 20) / 20);
	$content = curl_get_contents($url_product['url'], $url_product['referer'], $time, 1);
	$html = init_html($content['html']);
	sleep(rand(5, 20) / 20);
	// id товара id
	$offer['id'] = $url_product['id'];
	// цена товара price
	$offer['price'] = $url_product['price'];
	// находим все скрипты
	foreach ($html->find('script') as $script) {
		// Находим скрипт, где есть название и категории
		if (strpos($script->innertext, 'window.digitalData.product') !== false) {
			$script_arr = json_decode(str_replace(['window.digitalData.product=', ';'], '', $script->innertext), true);
			// название товара name
			$offer['name'] = $script_arr['name'];
			// категория categoryId	
			foreach ($categories as $category) {
				if ($category['name'] == $script_arr['category'][2])
					$offer['categoryId'] = $category['id'];
			}
			// url изображения товара picture (array)
		} elseif ((strpos($script->innertext, 'promotions=[];BACKEND.analytics.promoView') !== false)) {
			$script_arr = json_decode(str_replace(["var promotions=[];BACKEND.analytics.promoView={'promotions':promotions};BACKEND.components.item=", ";"], "", $script->innertext), true);
			$picture = array();
			foreach ($script_arr['galleryImages'] as $image) {
				$picture[] = $image['large'];
			}
			$offer['picture'] = $picture;
		}
	}
	// описание description 
	//? заменить <br> на &nbsp
	$offer['description'] = trim($html->find('div.item__description-text', 0)->innertext);
	echo "<br>Описание:<br>", $offer['description'];
	echo "<br>";
	// характеристики param (array ассоциативный) 
	//? добавить условие &nbspсм  и &nbspкг
	$parameters = array();
	$j = 1;
	$dl1_arr = array();
	foreach ($html->find('.specifications-list__spec') as $parametr) {
		//echo "<br> считаем параметры", $param->innertext, "<br>";
		echo $parametr->find('.specifications-list__spec-term-text', 0)->plaintext, '<br>';
		echo $parametr->find('.specifications-list__spec-definition', 0)->plaintext, '<br>';
		$param['name'] = str_replace('&nbsp', ' ', trim($parametr->find('.specifications-list__spec-term-text', 0)->plaintext));
		$param['value'] = str_replace('&nbsp', ' ', trim($parametr->find('.specifications-list__spec-definition', 0)->plaintext));
		// корректировка характеристик с см и кг
		if ((strpos($param['value'], ' см') != false) && (strpos($param['name'], ' см') === false)) {
			$param['value'] = str_replace(' см', '', $param['value']);
			$param['name'] .= ', см';
		}
		if ((strpos($param['value'], 'кг') != false) && (strpos($param['name'], ' кг') === false)) {
			$param['value'] = str_replace(' кг', '', $param['value']);
			$param['name'] .= ', кг';
		}
		$parameters[] = $param;
	}
	$offer['param'] = $parameters;
	// сохраняем все спарсенные данные в массив offers
	$offers[] = $offer;
}
//! Сохраняем в yml файл
$out = '<?xml version="1.0" encoding="UTF-8"?>';
$out .= '<yml_catalog date="' . date('Y-m-d H:i') . '">' . "\r\n";
$out .= '<shop>' . "\r\n";
// Короткое название магазина, должно содержать не более 20 символов
$out .= '<name>Мебельный магазин Раутель</name>' . "\r\n";

// Полное наименование компании, владеющей магазином
$out .= '<company>Мебельный магазин Раутель</company>' . "\r\n";

// URL главной страницы магазина
$out .= '<url>https://rautel.kz/</url>' . "\r\n";
// Список курсов валют магазина
$out .= '<currencies>' . "\r\n";
$out .= '<currency id="KZT" rate="1"/>' . "\r\n";
$out .= '</currencies>' . "\r\n";
// Список категорий магазина
$out .= '<categories>' . "\r\n";
foreach ($categories as $row) {
	$out .= '<category id="' . $row['id'];
	if ($row['parent_id'] != "") $out .= '" parentId="' . $row['parent_id'];
	$out .= '">' . $row['name'] . '</category>' . "\r\n";
}
$out .= '</categories>' . "\r\n";

$out .= '<offers>' . "\r\n";
foreach ($offers as $row) {
	$out .= '<offer id="' . $row['id'] . '" available="true">' . "\r\n";
	// Цена
	$out .= '<price>' . $row['price'] . '</price>' . "\r\n";
	// Валюта товара
	$out .= '<currencyId>KZT</currencyId>' . "\r\n";
	// ID категории
	$out .= '<categoryId>' . $row['categoryId'] . '</categoryId>' . "\r\n";
	// Изображения товара
	foreach ($row['picture'] as $picture) $out .= '<picture>' . $picture . '</picture>' . "\r\n";
	// Название товара
	$out .= '<name>' . $row['name'] . '</name>' . "\r\n";
	// Описание товара, максимум 3000 символов
	$out .= '<description><![CDATA[' . stripslashes($row['description']) . ']]></description>' . "\r\n";
	// Параметры
	foreach ($row['param'] as $param)
		$out .= '<param name="' . $param['name'] . '" unit="">' . $param['value'] . '</param>' . "\r\n";
	$out .= '</offer>' . "\r\n";
}

$out .= '</offers>' . "\r\n";

$out .= '</shop>' . "\r\n";
$out .= '</yml_catalog>' . "\r\n";

file_put_contents(__DIR__ . '/kaspi.xml', $out);
echo '<br>Потраченное время на скрпт: - ' . (time() - $time_start) . ' сек.<br>';

// ! Сохранение xml на ftp

// настройки сервера
$ftp_server = 'ip';
$ftp_user_name = 'name';
$ftp_user_pass = 'password';
// локальное нахожение файла
$file_local = __DIR__ . '/kaspi.xml';
// директория ftp куда будем сохранять файл
$directory = "directory";
// название файла сохраняемое на ftp
$file_ftp = "kaspi.xml";
$file_ftp_copy = "kaspi_copy.xml";

//создаем ftp соединение
$conn_id = ftp_connect($ftp_server);
// входим при помощи логина и пароля
$login_result = ftp_login($conn_id, $ftp_user_name, $ftp_user_pass);
// проверяем подключение ((!$conn_id) ||
if (!$login_result) {
	echo "FTP connection has failed!";
	echo "Attempted to connect to $ftp_server for user: $ftp_user_name";
	exit;
} else {
	echo "Connected to $ftp_server, for user: $ftp_user_name";
}
echo "Пассивный режим: ", ftp_pasv($conn_id, true);

// изменяем текущую директорию
$dir = ftp_chdir($conn_id, $directory);
if (!$dir) {
	echo "Не удалось сменить директорию<br>";
}

// Переименовываем старую версию xml файла 
if (!ftp_rename($conn_id, $file_ftp, $file_ftp_copy)) echo "Ошибка создания копии";

// загружаем файл FTP_ASCII FTP_BINARY
$upload = ftp_put($conn_id, $file_ftp, $file_local, FTP_BINARY);
if (!$upload) {
	echo "Error: FTP upload has failed!";
} else {
	echo "Good: Uploaded $file_ftp to $ftp_server";
}
// закрытие ftp соединение
ftp_close($conn_id);
