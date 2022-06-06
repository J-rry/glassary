<?php 

$updatedData = json_decode(file_get_contents('php://input'));

function dataUpdate($updatedData) {
  if (($file = fopen('glassary.csv', 'w+')) !== false) {
    foreach($updatedData as $data) {
      if (fputcsv($file, $data, ',') === false) {
        return false;
      }
    }
    fclose($file);
  }

}

dataUpdate($updatedData);