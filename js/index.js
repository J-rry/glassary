const body = document.querySelector("body");

//const siteUrl =  document.location.protocol + "://" + document.location.host + "/";
const glossaryData = "glossary/data.php";
const glossaryDataUpdate = "glossary/update.php";
//const glossaryAddress = siteUrl + glossaryData;

fetch(glossaryData)
  .then(response => response.json())
  .then(data => glossaryInit(data))
  .catch((err) => console.log(err));

//Класс Термина
class Term {
  constructor(term, specification, synonyms = '', links = '', finded = false) {
    this.term = term;
    this.specification = specification;
    this.synonyms = synonyms;
    this.links = links;
    this.finded = finded;
  }
  //Метод добавления синонимов
  addSynonym(...synonym) {
    this.synonyms = [...this.synonyms, ...synonym];
  }
  //Метод добавления ссылки
  addLink(...link) {
    this.links = [...this.links, ...link];
  }
  //Получить имя термина
  getTerm() {
    return this.term;
  }
  //Получить описание термина
  getSpecification() {
    return this.specification;
  }
  //Получить синонимы термина
  getSynonyms() {
    return this.synonyms;
  }
  //Получить ссылки на термин
  getLinks() {
    return this.links;
  }

  addLink() {
    const pageName = document.title;
    const pageAddress = document.location.pathname;
    const link = pageName + "|" + pageAddress;
    const isLinkAlreadyAdded = this.links.split(", ").indexOf(link) === -1 ? true : false;
    
    if(isLinkAlreadyAdded) {
      this.links += this.links.length !== 0 ? ', ' : '';
      this.links += link;
    }
  }

  get isFinded() {
    return this.finded;
  }
  set isFinded($value) {
    this.finded = $value;
  }
}

//Класс глоссария
class Glossary {
  constructor(glossary = []) {
    this.glossary = glossary;
  }
  //Добавление нового термина(можно сразу несколько)
  addTerm(...term) {
    this.glossary = [...this.glossary, ...term];
  }
  //Получить Список терминов
  list() {
    return this.glossary;
  }
  //Получить термин по id в глассарии
  getElementById(id) {
    return this.glossary[id] || null;
  }
  //Получить термин по его имени
  getElementByTerm(term) {
    return this.glossary.find((element) => element.getTerm() === term) || null;
  }
  //Добавить термину html-обёртку(в type указывается тип обёртки)
  setTermWrappByType(term, number = 1, type = 3) {
    switch (type) {
      case 1:
        return `<abbr title="${this.getElementByTerm(
          term
        ).getSpecification()}">${term}</abbr>`;
      case 2:
        return `<a href="##" title="${this.getElementByTerm(
          term
        ).getSpecification()}">${term}</a>`;
      case 3:
        const lowerCaseTerm = term.toLowerCase();
        const link = `/glossary/${lowerCaseTerm[0]}#glossary-${lowerCaseTerm}`;
        return `<abbr title="${this.getElementByTerm(
          term
        ).getSpecification()}">${term}</abbr><sup>[<a href="${link}" title="${term}">${number}</a>]</sup>`;
    }
  }

  sendDataUpdate() {
    const data = this.glossary.reduce((data, term) => {
      data.push([
        term.getTerm(), 
        term.getSpecification(), 
        term.getSynonyms(), 
        term.getLinks()
      ]);
      return data;
    }, []);

    const options = {
        method: 'post',
        body: JSON.stringify(data),
         headers: {
               'X-Requested-With': 'XMLHttpRequest',
                'content-type': 'application/x-www-form-urlencoded'
          },
    }; 

    fetch(glossaryDataUpdate, options)
    .then(response => response.text())
    .then((data) => console.log(data));
  }
}

function glossaryInit (data) {
  //Создаём новый глоссарий
  const glossary = new Glossary();

  //Наполняем глоссарий
  data.forEach(term => {
    glossary.addTerm(new Term(term[0], term[1], term[2], term[3]));
  });

  const wrapAllTermsOnPage = () => {

    $isAtLeastOneTermFinded = false;
    $termsOnPageCount = 0;
    //Массив, в котором будут содержаться все найденные текстовые ноды, в которых были найдены термины.
    //А также ндополнительная информация для последующего "оборачивания"
    const content = [];
    //Уровень углублённости ноды
    let floor = 0;

    //Основная функция, реализующая поиск текстовых нод и терминов внутри
    const findTextNode = (elem, i = 0) => {
      elem.childNodes.forEach((node) => {
        if (node.children) {
          i++;
          floor = i;
          findTextNode(node, i);
          i--;
        } else {
          //Проверяем, текстовая ли нода
          //Проверяем, является ли нода текстовым содержимым, чтобы отсечь элементы разметки(табуляцию и т п)
          if (node.nodeName === "#text" && node.textContent.trim().length > 0) {
            //Массив всех слов в текстовой ноде
            wordsInNode = node.textContent.trim().split(/\W/);
            //Массив найденных терминов в ноде
            containsTerms = wordsInNode.filter((word) =>
              glossary.getElementByTerm(word) && !glossary.getElementByTerm(word).isFinded
            );
            //Проверяем, есть ли в ноде термины
            if (containsTerms.length) {
              let nodeNewText = node.textContent;
              let newTextSplited = [];

              containsTerms.forEach((term, id) => {
                $termsOnPageCount++;
                const wrappedTerm = glossary.setTermWrappByType(term, $termsOnPageCount);


                $isAtLeastOneTermFinded = true;
                glossary.getElementByTerm(term).isFinded = true;
                glossary.getElementByTerm(term).addLink();

                //Если текст содержит только один термин, находим и заменяем его на "обёрнутый"
                if (id === 0) {
                  nodeNewText = nodeNewText.replace(term, wrappedTerm);
                  //Если терминов в ноде больше одного
                } else {
                  //Предыдущий "обёрнутый" термин
                  let previousTerm = glossary.setTermWrappByType(
                    containsTerms[id - 1]
                  );
                  //Разбиваем ноду на 2 части, по "обёрнутому" термину
                  let [firstPart, secondPart] = nodeNewText.split(previousTerm);
                  //Оборачиваем термин во второй части
                  nodeNewText = secondPart.replace(term, wrappedTerm);
                  //Если элементов больше 2х, удаляем последний элемент массива
                  if (id > 1) newTextSplited.pop();
                  //Добавляем в массив первую часть ноды, обёрнутый термин, и новую вторую часть
                  newTextSplited = [
                    ...newTextSplited,
                    firstPart,
                    previousTerm,
                    nodeNewText,
                  ];
                }
              });

              nodeNewText =
                containsTerms.length > 1 ? newTextSplited.join("") : nodeNewText;

              //Добавляем в массив данные для последующей замены
              content.push({
                floor: floor, //уровень углубления ноды
                node: node.parentNode, //Родительская нода
                old: node.textContent, //Старое содержимое ноды
                new: nodeNewText, //Новое содержимое ноды
              });
            }
          }
        }
      });
    };

    findTextNode(body);

    if ($isAtLeastOneTermFinded) {
      //Сортируем замены, от самых глубоких
      content.sort((el1, el2) => el2.floor - el1.floor);

      //Производим замену старого содержимого нод, на новое
      content.forEach((text) => {
        nodeHTML = text.node.innerHTML;
        text.node.innerHTML = nodeHTML.replace(text.old, text.new);
      });
    }

    return $isAtLeastOneTermFinded;
  };

  if (wrapAllTermsOnPage()) {
    glossary.sendDataUpdate();
  }
  
}

//Добавляем новые термины в Гласарий
// glossary.addTerm(
//   new Term(
//     "HTML",
//     "Стандартизированный язык гипертекстовой разметки документов для просмотра веб-страниц в браузере. Веб-браузеры получают HTML документ от сервера по протоколам HTTP/HTTPS или открывают с локального диска, далее интерпретируют код в интерфейс, который будет отображаться на экране монитора"
//   ),
//   new Term(
//     "CSS",
//     "Формальный язык описания внешнего вида документа (веб-страницы), написанного с использованием языка разметки (чаще всего HTML или XHTML). Также может применяться к любым XML-документам, например, к SVG или XUL."
//   ),
//   new Term(
//     "accusantium",
//     "Льзованием языка разметки ет применяться к любым XML-документам, например, к SVG или XUL."
//   ),
//   new Term(
//     "PHP",
//     "PHP — скриптовый язык общего назначения, интенсивно применяемый для разработки веб-приложений. В настоящее время поддерживается подавляющим большинством хостинг-провайдеров и является одним из лидеров среди языков, применяющихся для создания динамических веб-сайтов"
//   )
// );