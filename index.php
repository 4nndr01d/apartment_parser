<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require('./phpQuery.php');

header("Content-type: text/html; charset=utf-8");
header('User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/84.0.4147.135 Safari/537.36');

$start = microtime(true);

$url = 'https://www.domofond.ru/arenda-kvartiry-komi-r77?RentalRate=Month&Page=';

define("user", "root");
define("pass", "");
define("database", "parser");

$document = new Document();
$content = $document->getContent($url, 1);
$last_page = $document->getLenPage($content);

print_r("Количество квартир для обработки: " . $document->getCountApartment($content) . "\n");
$insert_card = "insert into domofond (address, price, pledge, rooms, url) values";

$apartment_number = 1;

for ($current_page = 1; $current_page <= $last_page; $current_page++) {
    $content = $document->getContent($url, $current_page);
    print_r('Парсим страницу:' . $current_page . " из " . $last_page . "\n");
    $cardParser = new CardParser();
    $cards = $document->getCards($content);

    foreach ($cards as $card) {
        $card = pq($card);
        $error = $cardParser->checkError($card);
        if ($cardParser->checkError($card)) {
            print_r("Была найдена ошибка\n");
            continue;
        }

        $address = $cardParser->getAddress($card);
        $price = $cardParser->getPrice($card);
        $pledge = $cardParser->getPledge($card);
        $rooms = $cardParser->getRooms($card);
        $url = $card->attr("href");
        $url = "https://www.domofond.ru" . $url;
        $insert_card = $insert_card . " ('" . $address . "', '" . $price . "', '" . $pledge . "', '" . $rooms . "', '" . $url . "'),";
        $apartment_number++;
    }
    if ($apartment_number == 1000) {
        $DataBase = new DataBase();
        print_r("Происходит добавление данных в базу данных\n");

        $DataBase->insertData($insert_card);

        $insert_card = "insert into domofond (address, price, pledge, rooms, url) values";
        $apartment_number = 1;
    } else {
        $DataBase = new DataBase();
        if ($current_page == $last_page) {
            print_r("Последняя страница\n");
            $DataBase->insertData($insert_card);
        }
    }


}

$time = microtime(true) - $start;

class DataBase
{
    public function insertData($insert_card)
    {
        $connect = mysqli_connect("127.0.0.1", user, pass, database);
        $insert_card[strlen($insert_card) - 1] = ';';
        mysqli_query($connect, $insert_card);
        mysqli_close($connect);
    }
}

class CardParser
{

    public function checkError($card)
    {
        $error = $card->find('div.search-results__currentItemError___R_ET_')->text();
        print_r($error);
        if ($error) {
            return true;
        } else {
            return false;
        }
    }

    public function getAddress($card)
    {
        $address = $card->find('span.long-item-card__address___PVI5p')->text();
        $address = trim($address);
        return $address;
    }


    public function getPrice($card)
    {
        $price = $card->find('span.long-item-card__price___3A6JF')->text();
        return $price;
    }

    public function getPledge($card)
    {
        $pledge = $card->find('div.additional-price-info__additionalPriceInfo___lBqNv span:last()')->text();
        return $pledge;
    }

    public function getRooms($card)
    {
        $rooms = $card->find('div.long-item-card__informationHeaderRight___3bkKw span')->text();
        return $rooms;
    }
}


class Document
{
    public $url;
    public $content;

    public function getContent($url, $page)
    {
        $file = file_get_contents($url . $page);
        $document = phpQuery::newDocument($file);
        return $document;
    }

    public function getCountApartment($content)
    {
        $count_apartment = $content->find('span.search-results__totalCount___39aQE')->text();
        $count_apartment = preg_replace('/предложени./', '', $count_apartment);
        return $count_apartment;
    }

    public function getLenPage($content)
    {
        $last_page = $content->find('ul.pagination__mainPages___2v12k li:last() a')->text();
        return $last_page;
    }

    public function getCards($content)
    {
        $cards = $content->find('a.long-item-card__item___ubItG');
        return $cards;
    }
}

