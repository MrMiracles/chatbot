<?php

include_once('data.php');


$data = new data();

$data->filter_by_keyword("appel", false);
$data->filter_by_keyword("kiwi", false);
$data->filter_by_keyword("peer", false);
$data->filter_by_keyword("banaan", false);
$data->search('%O%');


$pok = $data->get_responses();

if(!$pok) {
    echo "Niks helaas pindakaas";
} else {
    print_r($data->get_responses());

}




?>