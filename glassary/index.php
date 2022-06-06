<?php

$glassary;

//Получаем данные из CSV-файла в массив
if (($file = fopen('glassary.csv', 'r')) !== false) {
  while (($data = fgetcsv($file, 1000, ',')) !== false) {
    $glassary[] = $data;
  }
  fclose($file);
}

//print_r($glassary);

//Получаем алфавит из первых символов полученных терминов
$alphabet = array_unique(array_map(fn($term) => mb_strtoupper(mb_substr($term[0], 0, 1)), $glassary)); 

//Сортируем в алфавитном порядке
sort($alphabet);

//Получаем структуру типа Структура[буква алфавита] = [Термин1, Термин2, ...]
$pageStruct = array_reduce($alphabet, function($struct, $char) use ($glassary) {
  $struct[$char] = array_values(array_filter($glassary, fn($term) => mb_strtoupper(mb_substr($term[0], 0, 1)) === mb_strtoupper($char)));
  return $struct;
}, []);


function getSynonymPath($glassary, $synonym) {

  $isHaveCart = count(array_filter($glassary, fn($term) => mb_strtoupper($term[0]) === mb_strtoupper($synonym)));

  if($isHaveCart) {
    $firstLetter = mb_strtolower(mb_substr($synonym, 0, 1));
    $link = "/glassary/$firstLetter";
    return $link;
  }

  return false;
}

function getLink($glassary, $link) {
  if(strlen($link) !== 0) {
    $linkData = mb_split("\|", $link);
    return "<a href='$linkData[1]'>$linkData[0]</a>";
  }
  return '';
}

//Создаём структуру из страниц для каждой буквы
foreach($alphabet as $char) {
  
  $dirName = './' . mb_strtolower($char);

  //Создаём дирректорию
  if(!is_dir($dirName)) {
    mkdir($dirName, 0777, true);
  }

  //Инициализируем карточки для страница
  $pageContent = array_reduce($pageStruct[$char], function($content, $charCart) use($glassary) {
    $term = $charCart[0];
    $specification = $charCart[1];
    $cartName = strtolower($term);

    $synonymsArray = mb_split(", ?", $charCart[2]);
    $synonyms = array_reduce($synonymsArray, function($list, $synonym) use ($glassary, $term, $cartName) {
      $list .= strlen($list) === 0 ? '' : ', ';
      if(getSynonymPath($glassary, $synonym) === false) {
        $list .= $synonym;
      } else {
        $href = getSynonymPath($glassary, $synonym);
        $list .= "<a href='$href#glassary-$cartName'>$synonym</a>";
      }

      return $list;
    }, "");

    $linksArray = mb_split(", ?", $charCart[3]);
    
    $links = array_reduce($linksArray, function($list, $link) use ($glassary) {
      $list .= strlen($list) === 0 ? '' : ', ';
      $list .= getLink($glassary, $link);

      return $list;
    }, "");
    

    $content .= "<div class='glassary-cart'>
                  <a href='##' name='glassary-$cartName'></a>
                  <h2 class='glassary-term'>$term</h2>
                  <p  class='glassary-specification'>$specification</p>
                  <div  class='glassary-synonyms'>Синонимы: $synonyms</div>
                  <div  class='glassary-links'>Ссылки: $links</div>
                </div>";
  return $content;
  }, "");

  //Шаблон страницы и добавление в него карточек
  $page = "<!DOCTYPE html>
  <head>
      <link rel='stylesheet' href='/styles/style.css'>
      <title>$char</title>
  </head>
  <body>
    <div class='glassary-library'></div>
    $pageContent
  </body>
  </html>";

  //Создаём файл с необходимым контентом
  $newFile = fopen($dirName . '/' . 'index.php', 'w+');
    fwrite($newFile, $page);
    fclose($newFile);
}

$path = explode('?', $_SERVER['REQUEST_URI'])[0];
//$path = $_SERVER['REQUEST_URI'];

//Создаём страницу глассария
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/styles/style.css">
    <title>Glassary</title>
</head>
<body>
<ul class="glassary">
  <?php foreach ($alphabet as $char): ?>
  <li><a href="<?= $path . mb_strtolower($char)?>" title=""><?= $char ?></a></li>
  <?php endforeach; ?>
</ul>
</body>
</html>