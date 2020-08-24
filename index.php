<?php
header("Content-type: text/html; charset=utf-8");
header('User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/84.0.4147.135 Safari/537.36');
require ('./phpQuery.php');

$url = 'https://www.domofond.ru/arenda-kvartiry-sankt_peterburg-c3414?RentalRate=Month&Page=';

$array_apartments = [];

$document = new Document();
$content = $document->getContent($url, 1);
$last_page = $document->getLenPage($content);

for($current_page = 1; $current_page<3; $current_page++){
    $content = $document->getContent($url, $current_page);

    $documentParser = new CardParser();
    $cards = $document->getCards($content);

    foreach ($cards as $key=>$card){
        $card = pq($card);
        $address = $documentParser->getAddress($card);
        $price = $documentParser->getPrice($card);
        $pledge = $documentParser->getPledge($card);
        $rooms = $documentParser->getRooms($card);

        $array_apartments[$key]['address'] = $address;
        $array_apartments[$key]['price'] = $price;
        $array_apartments[$key]['pledge'] = $pledge;
        $array_apartments[$key]['rooms'] = $rooms;
    }
}

class CardParser {
    public $content;

    public function getAddress($card){
        $address = $card->find('span.long-item-card__address___PVI5p')->text();
        $address = str_replace("Санкт-Петербург,", '',$address);
        return $address;
    }

    public function getPrice($card){
        $price = $card->find('span.long-item-card__price___3A6JF')->text();
        return $price;
    }

    public function getPledge($card){
        $pledge = $card->find('div.additional-price-info__additionalPriceInfo___lBqNv span')->text();
        return $pledge;
    }

    public function getRooms($card){
        $rooms = $card->find('div.long-item-card__informationHeaderRight___3bkKw span')->text();
        return $rooms;
    }
}


class Document {
    public $url;

    public function getContent($url, $page) {
        $file = file_get_contents($url . $page);
        $document = phpQuery::newDocument($file);
        return $document;
    }

    public function getLenPage($content) {
        $last_page = $content->find('ul.pagination__mainPages___2v12k li:last() a')->text();
        return $last_page;
    }

    public function getCards($content){
        $cards = $content->find('a.long-item-card__item___ubItG');
        return $cards;
    }
}



echo "<pre>";
var_dump($array_apartments);
echo "</pre>";
