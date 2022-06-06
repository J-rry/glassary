<?php 
//var_dump(getData('POST'));
//print_r(json_decode(file_get_contents('php://input')));

//Получаем данные из CSV-файла в массив
$glossary = [];
if (($file = fopen('glossary.csv', 'r')) !== false) {
  while (($data = fgetcsv($file, 1000, ',')) !== false) {
    $glossary[] = $data;

  }
  fclose($file);
}
print_r(json_encode($glossary));