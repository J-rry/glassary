<?php

$glossary;

//Получаем данные из CSV-файла в массив
if (($file = fopen('glossary.csv', 'r')) !== false) {
  while (($data = fgetcsv($file, 1000, ',')) !== false) {
    $glossary[] = $data;
  }
  fclose($file);
}

//print_r($glossary);

//Получаем алфавит из первых символов полученных терминов
$alphabet = array_unique(array_map(fn($term) => mb_strtoupper(mb_substr($term[0], 0, 1)), $glossary)); 

//Сортируем в алфавитном порядке
sort($alphabet);

//Получаем структуру типа Структура[буква алфавита] = [Термин1, Термин2, ...]
$pageStruct = array_reduce($alphabet, function($struct, $char) use ($glossary) {
  $struct[$char] = array_values(array_filter($glossary, fn($term) => mb_strtoupper(mb_substr($term[0], 0, 1)) === mb_strtoupper($char)));
  return $struct;
}, []);


function getSynonymPath($glossary, $synonym) {

  $isHaveCart = count(array_filter($glossary, fn($term) => mb_strtoupper($term[0]) === mb_strtoupper($synonym)));

  if($isHaveCart) {
    $firstLetter = mb_strtolower(mb_substr($synonym, 0, 1));
    $link = "/glossary/$firstLetter";
    return $link;
  }

  return false;
}

function getLink($glossary, $link) {
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
  $pageContent = array_reduce($pageStruct[$char], function($content, $charCart) use($glossary) {
    $term = $charCart[0];
    $specification = $charCart[1];
    $cartName = strtolower($term);

    $synonymsArray = mb_split(", ?", $charCart[2]);
    $synonyms = array_reduce($synonymsArray, function($list, $synonym) use ($glossary, $term, $cartName) {
      $list .= strlen($list) === 0 ? '' : ', ';
      if(getSynonymPath($glossary, $synonym) === false) {
        $list .= $synonym;
      } else {
        $href = getSynonymPath($glossary, $synonym);
        $list .= "<a href='$href#glossary-$cartName'>$synonym</a>";
      }

      return $list;
    }, "");

    $linksArray = mb_split(", ?", $charCart[3]);
    
    $links = array_reduce($linksArray, function($list, $link) use ($glossary) {
      $list .= strlen($list) === 0 ? '' : ', ';
      $list .= getLink($glossary, $link);

      return $list;
    }, "");
    

    $content .= "<div class='glossary-cart'>
                  <a href='##' name='glossary-$cartName'></a>
                  <h2 class='glossary-term'>$term</h2>
                  <p  class='glossary-specification'>$specification</p>
                  <div  class='glossary-synonyms'>Синонимы: $synonyms</div>
                  <div  class='glossary-links'>Ссылки: $links</div>
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
    <div class='glossary-library'></div>
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
    <title>Glossary</title>
</head>
<body>
<ul class="glossary">
  <?php foreach ($alphabet as $char): ?>
  <li><a href="<?= $path . mb_strtolower($char)?>" title=""><?= $char ?></a></li>
  <?php endforeach; ?>
</ul>
</body>
</html>