<?php
//Скрипт выводитвсю доступную информацию о продукции, такую как раздел, кодировка, температура, цена для указанного раздела
header("Content-Type: text/html; charset=utf-8");
?>
    <p>Скрипт выводит всю доступную информацию о продукции, такую как раздел, кодировка, температура, цена для указанного раздела</p>
    <form action="">
        <p>ID раздела <input type="text" name="id" value="<?=$_GET['id']?>"/></p>
        <p>TV продукции <input type="text" placeholder="15,58,60" name="tv" value="<?=$_GET['tv']?>"/></p>
        <p>БД <select name="db" id="">
                <option value="prod">prod</option>
                <option value="loc" selected>dev</option>
                <option value="su" selected>su</option>
                <option value="au" selected>au</option>
            </select></p>
        <input type="submit" value="Начать выгрузку"/>
    </form>
<?php

//входящие данные
if(empty($_GET['id']))
    die();
if(empty($_GET['tv']))
    die('Не указан список TV (1,2,3,4)');
$_GET['tv'] = trim($_GET['tv']);
$_GET['tv'] = str_replace(' ', '', $_GET['tv']);


if($_GET['db'] == 'prod')
    require_once $_SERVER['DOCUMENT_ROOT'].'/=settingos/db_prod.php';
if($_GET['db'] == 'loc')
    require_once $_SERVER['DOCUMENT_ROOT'].'/=settingos/db_loc.php';
if($_GET['db'] == 'su')
    require_once $_SERVER['DOCUMENT_ROOT'].'/=settingos/db_su.php';
if($_GET['db'] == 'au')
    require_once $_SERVER['DOCUMENT_ROOT'].'/=settingos/db_au.php';

//скрипт
$products = getProducts( array($_GET['id']) );
if(count($products) < 1 )
    die('продукция не найдена');

//выводим список
echo '<textarea style="width: 100%; height: 520px;">';
foreach($products as $product){
    //выводим папки до продукции
    if(isset($printParents[$product['parent']]))
        $path = $printParents[$product['parent']];
    else{
        $path = printPaths(getParents($product['id']));
        $printParents[$product['parent']] = $path; //сохраняем в кеше
    }
    echo $path."\t";

    //parent
    echo $product['id']."\t";

    //pagetitle
    echo $product['pagetitle']."\t";

    //выводим TV
    echo implode("\t", getTV($product, $_GET['tv']) );

    echo "\n";
}
echo '</textarea>';

echo '<h3>Запросов к БД: '. $querys.'</h3>';

/*
 * вытаскивает TV по номеру документа
 * @param array $product array(id => id, foreignTable => foreignTable)
 * @param string $tvs 15,19,60 TV для вывода
 * @return array $tv  array(15 => val, 60 => '', 18 => val)
 * */
function getTV($product, $tvs){
    global $db, $querys;
    if(empty($tvs))
        die('getTV не указан параметр 2 string $tvs');

    //заранее создаем массив TV в нужном порядке
    $tvsExploded = explode(',', $tvs);
    foreach($tvsExploded as $val)
        $tvsArr[$val] = '';

    if($product['foreignTable'] == 0){
        $sql = 'SELECT * FROM `modx_site_tmplvar_contentvalues` WHERE `contentid` = '.$product['id'].' AND `tmplvarid` IN('.$tvs.')';
        //echo '<pre>'; print_r($sql); die();
        $query = $db->query($sql); $querys++;
        if($query->num_rows < 1)
            return $tvsArr;
        while($row = $query->fetch_assoc()){
            if(isset($tvsArr[$row['tmplvarid']]))
                $tvsArr[$row['tmplvarid']] = $row['value'];
        }
    }else{
        $query = $db->query('SELECT `ptc`.`tmplvarid`, `ptc`.`value`, `ptd`.`value` as `data` FROM `product_tmplvar_contentvalues` `ptc` LEFT JOIN `product_tmplvar_data` `ptd` ON `ptd`.`id` = `ptc`.`value` WHERE `ptc`.`contentid` = '.$product['id'].' AND `ptc`.`tmplvarid` IN('.$tvs.')'); $querys++;
        if($query->num_rows < 1)
            return $tvsArr;
        while($row = $query->fetch_assoc()){
            if(isset($tvsArr[$row['tmplvarid']])) {
                if($row['tmplvarid'] == 15 || $row['tmplvarid'] == 19)
                    $tvsArr[$row['tmplvarid']] = $row['value'];
                else
                    $tvsArr[$row['tmplvarid']] = $row['data'];
            }
        }
    }

    return $tvsArr;
}

/*
 * пробегаемся по все разделам/подразделам в поисках продукции
 * @param array $folders ID папки вида array('123','124')
 * @return array $products  array([pagetitle => pagetitle, id => id, foreignTable => foreignTable, parent => parent],[],[])
 * */
function getProducts($folders = array(), $products = array()){
    global $db, $querys;

    if(count($folders) < 1)
        return $products;

    $folder = array_pop($folders);
    $sql = 'SELECT `parent`,`pagetitle`,`isfolder`, `id`,`template`, `foreignTable` FROM `modx_site_content` WHERE `parent` = '.$folder;
    $query = $db->query($sql); $querys++;
    if($query->num_rows == 0)
        return getProducts($folders, $products);
    while($element = $query->fetch_assoc()){
        if($element['isfolder'] == 1){
            $folders[] = $element['id'];
            continue;
        }
        if($element['template'] == 16)
        {
            $products[] = array('pagetitle' => $element['pagetitle'], 'id' => $element['id'], 'foreignTable' => $element['foreignTable'], 'parent' => $element['parent']);
            continue;
        }
    }
    return getProducts($folders, $products);
}

/*
 * пробегается по всем родителям ID и возвращает массив родителей
 * @param int $id ID элемента
 * @return array родители
 * */
function getParents($id, $arr = array(), $level = 0){
    global $db, $querys;
    if(empty($id) || $level >= 5)
        return array_reverse($arr);
    $level++;
    $query = $db->query('SELECT `hidemenu`,`parent`,`id`,`pagetitle` FROM `modx_site_content` WHERE `id` = '.$id); $querys++;
    $res = $query->fetch_assoc();
    $arr[] = array_reverse($res);
    if($res['parent'] == 0)
        return $arr;
    else {
        return getParents($res['parent'], $arr, $level);
    }
}

/*
 * пробегается по getParents и выводит строку вида pagetitle/pagetitle/pagetitle
 * @param   array   $arr    массив функции getParents
 * @return  string      pagetitle/pagetitle/pagetitle
 * */
function printPaths($arr){
    if(!is_array($arr))
        return '--';
    foreach($arr as $val) {
        if($val['hidemenu'] == 1)
            continue;
        $path .= $val[pagetitle] . '/';
    }
    return trim($path, '/');
}