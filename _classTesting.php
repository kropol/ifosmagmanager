<?php
//set_time_limit(0);

define('DEBUG',true);
//define('DEBUG',false);

//echo '<br><br><br><br><br>X75VB-TY006D<br>';
//echo 'DI3721I333781000B<br>';
//echo '368581<br>';
//echo 'asus<br>';
//echo 'D2H04EA. розетка - архивный товар<br>';
//echo 'EA6700. результат по центру<br>';
//echo 'GCM723G.   у хотлайна одиночные товары<br>';
//echo 'SV300S37A/60G.   2 результата по розетке<br>';
//echo 'N660 TF 2GD5/OC.   результат по магазинам с кривой версткой<br>';
//echo 'ZM-STG2.   цена с запятой<br>';
//echo 'PB614-D-CIS.   hotline nan<br>';
//echo 'TS500GSJ25M3 2 товара, один архивный (розетка)<br>';// тестировать выделение 1/2 зеленым цветом при наличии
//echo 'TS8GMP350B по запросу ничего не найдено (розетка)<br>';// тестировать выделение 1/2 зеленым цветом при наличии
//echo 'A8-5500 2 групповых результата (хотлайн)<br>';// тестировать выделение 1/2 зеленым цветом при наличии
//echo 'PG2301004R зена с запятой (хотлайн)<br>';// тестировать выделение 1/2 зеленым цветом при наличии


//echo <<<'EOOT'
//    <meta http-equiv="content-type" content="text/html; charset=utf-8" />
//EOOT;

        /*
         * cid  mid sku syncstate   title   objectTovarSerialized
         * комтеховский id
         * id уже добавленного товара в магазине
         * sku на всякий случай
         * статус синхронизированности перед началом изменения переводим его в 0, после успешной операции меняем на 1
         * еще title ($this->itemTitleText;// здесь есть код товара как минимум для ноутбуков) для поиска товара в базе по тексту
         * сериализованный экземпляр товара
         */

class page{
    private $data;
    private $inframe;
    protected $memCacheObject;
    // здесь можно закешировать объявить объект мадженты для кешированного хранения
    function __construct(){
        if(array_key_exists('action',$_GET)&&$_GET['action']=="inform") $this->inframe=1;
        if(!$this->inframe) echo '<meta http-equiv="content-type" content="text/html; charset=utf-8" /><body>';
    }

    function __destruct(){
        if(!$this->inframe) echo '<hr>';
        if(array_key_exists('action',$_GET)&&$_GET['action']!=""){
            $evalme="\$this->".$_GET["action"].'();';
            eval($evalme);
        }
        echo "</body>";
        if(isset($this->memCacheObject))$this->memCacheObject->close();
    }

    public function mget($key){
        if(isset($this->memCacheObject)){
            return $this->memCacheObject->get($key);
        }else{
            $this->memCacheObject=new Memcache();
            $this->memCacheObject->connect('127.0.0.1', 11211) or die("Could not connect to magetnoServer");
            return $this->memCacheObject->get($key);
        }
    }

    public function mset($key,$value){
        if(isset($this->memCacheObject)){
            $this->memCacheObject->set($key,$value);
        }else{
            $this->memCacheObject=new Memcache();
            $this->memCacheObject->connect('127.0.0.1', 11211) or die("Could not connect to magetnoServer");
            $this->memCacheObject->set($key,$value);
        }
    }

    public function button($name,$id,$urlfunc,$inline=0){
        if(!$this->inframe) echo '<a id="'.$id.'" href="'.$_SERVER['PHP_SELF'].'?action='.$urlfunc.'">'.$name.'</a>'.($inline?'':'<br>');
    }

    public function frame($width,$height,$name,$id){
        if(array_key_exists('action',$_GET)&&$_GET['action']!="inform") echo '
        <a href="'.$_SERVER['PHP_SELF'].'?action=inform" target="'.$name.'">'.$name.'</a>
        <br>
        <iframe style="width:'.$width.'px; height:'.$height.'px" name="'.$name.'" id="'.$id.'" frameborder="no">
		</iframe><br>';
    }

    public function create(){//создает образец
        $mag=new magento();
        $id=$mag->createProduct('tovarrr','descript full','descrsort',100500);
        $mag->addpic($id,"sgs.jpg");
//        echo file_get_contents(dirname(__FILE__).'/sgs.jpg');
    }

    public function getdinacompricenotesarray(){
        define('EOL',(PHP_SAPI == 'cli') ? PHP_EOL : '<br />');
        require_once dirname(__FILE__) . '/_excel/Classes/PHPExcel.php';

        $pricefilepath="\\\\dsrv\\data(prices)\\dinacom.xls";
        $retval=array();
        if (!file_exists($pricefilepath)) {
            exit($pricefilepath." not found." . EOL);
        }
        $objReader = PHPExcel_IOFactory::createReader('Excel5');
        $objPHPExcel = $objReader->load($pricefilepath);
        foreach ($objPHPExcel->getWorksheetIterator() as $worksheet) {
            foreach ($worksheet->getRowIterator() as $row) {
                $cellIterator = $row->getCellIterator();
                $cellIterator->setIterateOnlyExistingCells(false); // Loop all cells, even if it is not set
                foreach ($cellIterator as $cell) {
                    if (!is_null($cell)) {
                        $result='';
                        $celldata=$cell->getCalculatedValue();
                        if(strstr($celldata,"(")&&strstr($celldata,"Ноутбук ")){preg_match('/\((.*?)[) ]/',$celldata,$result);$retval[]=$result[1];/*echo $result[1], "<br>";*/}
                    }
                }
            }
        }
        return $retval;
    }

    public function _create(comGoodsItem $tovar){// $tovar->itemTitleText -- здесь есть код товара как минимум для ноутбуков
        $mag=new magento();
        $id=$mag->createProduct($tovar);// после создания товара id ему уже присвоен!!!
        
        if(is_array($tovar->agooglePics)){// заливаем картинки
            $comtehpicsenabledAlways=false;
            $googlepicscounter=0;
            foreach($tovar->agooglePics as $gpicarr){// считаем количество включенных картинок
                if($gpicarr[1])$googlepicscounter++;
            }
            if($googlepicscounter){// и решаем, добавлять ли комтеховскую
                if($comtehpicsenabledAlways)$mag->addpic($id,array_merge(array(array($tovar->itemComtehWatermarkedImageURL,1)),$tovar->agooglePics));
                else $mag->addpic($id,$tovar->agooglePics);
            }
            else $mag->addpic($id,$tovar->itemComtehWatermarkedImageURL);
        }
        
        $result='';// проверяем по динакомовскому прайсу наличие такого товара
        preg_match('/\((.*?)\)$/',$tovar->itemTitleText,$result);
        _myStrings::debug(array($result[1]=>in_array($result[1],$this->getdinacompricenotesarray())?"found":"not found"));
        if(!in_array($result[1],$this->getdinacompricenotesarray())){
//            $mag->setstock($id,0);// установка статуса наличия товара (магазинного)
            $mag->updatetovar($id,'stock_state','под заказ 2-3 дня');// установка статуса наличия товара (кастомное поле в искалеченной теме grayscale)
        }

        $tovar->tovarSyncState=1;
        $db=new db('localhost','root','');
        $db->insert($tovar);

        /*
         * cid  mid sku syncstate   title   objectTovarSerialized
         * комтеховский id
         * id уже добавленного товара в магазине
         * sku на всякий случай
         * статус синхронизированности перед началом изменения переводим его в 0, после успешной операции меняем на 1
         * еще title ($this->itemTitleText;// здесь есть код товара как минимум для ноутбуков) для поиска товара в базе по тексту
         * сериализованный экземпляр товара
         */
    }

    public function _list(){// todo после вызова можно всем товарам в синхронной базе проставить sku из результатов этого массива
        $mag=new magento();
        $result=$mag->listproducts(0);
        echo "product list: (".count($result).")<br>";
        foreach($result as &$tovar){
            $tovar["edit_images"]='<a href="/_classtesting.php?action=showmediainfo&tovarid='.$tovar["product_id"].'">редактировать картинки</a>';
            $tovar["edit_info"]='<a href="/_classtesting.php?action=showadditionaltovarinfo&tovarid='.
                                $tovar["product_id"].'">редактировать содержимое</a>';
//            $tovar["edit_images"]=$this->button('редактировать картинки','showmediainfo','showmediainfo&tovarid='.$tovar["product_id"],1);
//            $tovar["edit_info"]=$this->button('показать доп.инфо первого товара (from cache)',
//                                              'showadditionaltovarinfo','showadditionaltovarinfo&tovarid='.$tovar["product_id"],1);
        }
        _myStrings::debug($result);
        
    }

    public function removefirst(){
        $mag=new magento();
        $list=$mag->listproducts(0);
        $mag->removeProduct($list[0]['product_id']);
    }

    public function removeall(){
        $mag=new magento();
//        _myStrings::debug(serialize($mag));exit;
        $list=$mag->listproducts(0);
            $this->mset('removealllist',$list);
//            $memcache->set('magobj',$mag); не работает, жалуется на supplied argument is not a valid sdl resource
//            file_put_contents(dirname(__FILE__).'/_magobj',serialize($mag));// аналогично
//            $mag->removeProduct($list[0]['product_id']);
            $this->mset('removeallnextindex',0);
        _myStrings::debug('товаров: '.count($list));
        $this->reloc("removeall_continue");
    }

    public function removeall_continue(){
        $remindex=$this->mget('removeallnextindex');
        $mag=new magento();
//        $mag=unserialize(file_get_contents(dirname(__FILE__).'/_magobj'));
        _myStrings::debug('removeallnextindex='.$remindex);

        $list=$this->mget('removealllist');
        for($i=$remindex;$i<$remindex+1&&i<count($list);$i++){
            $mag->removeProduct($list[$i]['product_id']);
        }
        echo 'удалено '.$remindex.'/'.count($list);
        $this->mset('removeallnextindex',$remindex+1);
        if($remindex<=(count($list)-1))$this->reloc("removeall_continue");
    }

    public function reloc($action){
        echo '<script type="text/javascript">
        window.location = "/_classtesting.php?action='.$action.'";
        </script> ';
    }

    public function inform(){
        $memcache=new Memcache();
        $memcache->connect('127.0.0.1', 11211) or die("Could not connect");
        echo $memcache->get('finished');
        $memcache->close();
        exit;
    }

    public function comtehfill(){
        echo ' ';
        $x=unserialize(file_get_contents(dirname(__FILE__).'/_cComtehObject'));
//        $x=new cComteh("http://comteh.com/products/show/c/11217/sc/KT08158.html");//ноуты
//        $x=new cComteh("http://comteh.com/products/show/c/33194/sc/10125.html");//процы
//        $x=new cComteh("http://comteh.com/products/show/c/20608/sc/KT13412.html");//телефоны
//        return;

        $x->fillexportarray();

        $counter=0;

//        while(true){
            $tmpobj=$x->getnextitem();
            if($tmpobj==-1){
                echo 'Заполнение завершено';
                return;
            }
            $this->_create($tmpobj);

            file_put_contents(dirname(__FILE__).'/_cComtehObject',serialize($x));
            echo 'Статус импорта товаров: '.$x->getprogresscounter().'<br>';
//            $this->reloc('comtehfill');
            return;
//        }


        _myStrings::debug("comtehfill() done");
    }

    public function comtehdisplay(){
        echo ' ';
        $x=new cComteh("http://comteh.com/products/show/c/11217/sc/KT08158.html",array(614));//ноуты
//        $x=new cComteh("http://comteh.com/products/show/c/33194/sc/10125.html",array(614));//процы
//        $x=new cComteh("http://comteh.com/products/show/c/20608/sc/KT13412.html",array(614));//телефоны
//        $x->printlinks();

        $x->printgoods();
    }

    public function getcomtehcookies(){
        _myStrings::request("http://comteh.com/profiles/sel_region/4/9.html","","comteh.txt");
    }

    public function comtehloadheaders(){/// todo модификация под другой магазин
        $x=new cComteh("http://comteh.com/products/show/c/11217/sc/KT08158.html",array(614));//ноуты
//        $x=new cComteh("http://comteh.com/products/show/c/11217/sc/KT08158.html",array(12,13));//ноуты
        echo file_put_contents(dirname(__FILE__).'/_cComtehObject',serialize($x));
        echo '<br>заголовки загружены<br>';
    }

    public function comtehshowloadedheaders(){
        $x=unserialize(file_get_contents(dirname(__FILE__).'/_cComtehObject'));

        if($_REQUEST['limitedlist']==1){
            $x->printgoods(true);
        }else{
            $x->printgoods();
        }
    }

    public function loadcharacteristics(){
        $x=unserialize(file_get_contents(dirname(__FILE__).'/_cComtehObject'));
        $amountOfGoods=0;
        foreach($x->pages as $page){
            foreach($page->content as $good){
                $amountOfGoods++;
            }
        }
//        echo $amountOfGoods.' товаров<br>';return;

        $counter=0;
        $currentPosition=0;
        foreach($x->pages as &$page){
            foreach($page->content as &$good){
                $currentPosition++;
                if($good->characteristicstable==" "){
                    $good->getcharacteristics();
                    $counter++;
                }
//                echo 'currentposition=='.$currentPosition.' $counter=='.$counter.'<br>';
                if($counter>9){
                    file_put_contents(dirname(__FILE__).'/_cComtehObject',serialize($x));
                    echo 'Статус обработки таблиц характеристик: '.$currentPosition.'/'.$amountOfGoods.'<br>';
//                    echo '<script type="text/javascript">
//                    window.location = "/_classtesting.php?action=loadcharacteristics";
//                    </script> ';
                    $this->reloc('loadcharacteristics');
                    return;
                }
            }
        }
        if($counter!=0)file_put_contents(dirname(__FILE__).'/_cComtehObject',serialize($x));
        echo '<br>все таблицы характеристик загружены<br>';
    }

    public function firstGoodCharacteristics(){
        $x=unserialize(file_get_contents(dirname(__FILE__).'/_cComtehObject'));
        echo 'картинок загружено: '.count($x->pages[0]->content[0]->agooglePics).'<br>';
        echo '<pre>';print_r($x->pages[0]->content[0]->agooglePics);exit;
        echo $x->pages[0]->content[0]->characteristicstable.'<br>';
//        echo $x->pages[0]->content[0]->itemComtehWatermarkedImageURL.'<br>';
    }

    public function loadMoreGoogleImages(){
        if(!$_REQUEST['comtehid'])return;
//        $comtehGoodid=base64_decode($_REQUEST['comtehid']);
        $comtehGoodid=($_REQUEST['comtehid']);

        $x=unserialize(file_get_contents(dirname(__FILE__).'/_cComtehObject'));
        foreach($x->pages as $page){
            foreach($page->content as $good){
                if($good->itemComtehID==$comtehGoodid){
                    $pics=$good->getGooglePics(true);
                    echo '<div id="result">';
                    $good->showonlypics();
                    echo '</div>';

                    break;
                }
            }
        }
        file_put_contents(dirname(__FILE__).'/_cComtehObject',serialize($x));
    }

    public function loadGoogleImages(){
        $x=unserialize(file_get_contents(dirname(__FILE__).'/_cComtehObject'));
        $amountOfGoods=0;
        foreach($x->pages as $page){
            foreach($page->content as $good){
                $amountOfGoods++;
            }
        }
//        echo $amountOfGoods.' товаров<br>';return;

        $counter=0;
        $currentPosition=0;
        foreach($x->pages as &$page){
            foreach($page->content as &$good){
                $currentPosition++;
                if(count($good->agooglePics)==0){
                    $good->getGooglePics();
                    $counter++;
                }
                if($counter>9){
                    file_put_contents(dirname(__FILE__).'/_cComtehObject',serialize($x));
                    echo 'Статус обработки гуглокартинок: '.$currentPosition.'/'.$amountOfGoods.'<br>';
                     $this->reloc('loadGoogleImages');
                    return;
                }
            }
        }
        if($counter!=0)file_put_contents(dirname(__FILE__).'/_cComtehObject',serialize($x));
        echo '<br>все гуглокартинки загружены<br>';
    }

    public function modify(){// in $b64json     функция удаления картинок (до импорта)
        $b64json=$_REQUEST['params'];
        $file = '';
        $lock = '';
        $code = '';
        $picID= '';

        try {
            $json = json_decode(base64_decode($b64json));
            $code = $json->code;
            $picID = $json->picid;
        } catch (Exception $e) {
            echo "<div id='result'>error</div>";
            return;
        }

//        echo "<div id='result'>".$code." ".$picID."</div>";exit;
//        echo "<div id='result'>";
//        echo '<pre>';print_r($_REQUEST);
//        echo $_SERVER['REQUEST_METHOD']."</div>";
//        exit;

        $x=unserialize(file_get_contents(dirname(__FILE__).'/_cComtehObject'));
//        while (!$file = fopen(dirname(__FILE__).'/_cComtehObject', 'w+')) {
//            sleep(1/2);
//        }
//        $lock=flock($file,LOCK_EX);
//        $x=unserialize(stream_get_contents($file));
        foreach($x->pages as &$page){
            foreach($page->content as &$good){
                if(count($good->agooglePics)>0 && $good->itemComtehID==$code){
                    $good->agooglePics[$picID][1]=0;
                    break;
                }
            }
        }
//        fwrite($file,serialize($x));
//        fclose($file);
        file_put_contents(dirname(__FILE__).'/_cComtehObject',serialize($x));
        echo "<div id='result'>ok</div>";
    }

    public function showadditionaltovarinfo($id='',$magobj=''){// вся текстовая инфа о товаре
        if($id!='')$_REQUEST['tovarid']=$id;
        if(!isset($_REQUEST['tovarid']))return;
        $mag=$id?$magobj:new magento();
        $result=$mag->showadditionaltovarinfo($_REQUEST['tovarid']);

        $stockstatus=$mag->getstock($result["sku"]);
//        $stockstatus=$mag->getstock($_REQUEST['tovarid']);// так тоже работает, хотя в документации трубеют ску
        echo "Наличие: ".($stockstatus[0]['is_in_stock']?"есть ":" под заказ ")." &nbsp;&nbsp;&nbsp;&nbsp;";
        echo '<a href="/_classtesting.php?action=updatetovar&tovarid='.$_REQUEST['tovarid'].'&sku='.$result["sku"].'&target=stock&newstate=1">в наличии</a> &nbsp;&nbsp;&nbsp;';
        echo '<a href="/_classtesting.php?action=updatetovar&tovarid='.$_REQUEST['tovarid'].'&sku='.$result["sku"].'&target=stock&newstate=0">под заказ</a><br>';
//        _myStrings::debug($stockstatus);

        echo '<form action="/_classTesting.php?action=updatetovar&tovarid='.$_REQUEST['tovarid'].'&sku='.$result['sku'].'&target=price" method="POST">
                <input name="newstate" placeholder="Новая цена">
                <input type="submit" value="установить"></input><br>
            </form>';

        echo '<form action="/_classTesting.php?action=updatetovar&tovarid='.$_REQUEST['tovarid'].'&sku='.$result['sku'].'&target=stock_state" method="POST">
                <input name="newstate" placeholder="Новаый customstockstate">
                <input type="submit" value="установить"></input><br>
            </form>';

        echo '<a href="/_classtesting.php?action=updatetovar&tovarid='.$_REQUEST['tovarid'].'&sku='.$result["sku"].'&target=status&newstate=1">включить</a> &nbsp; &nbsp; &nbsp;';
        echo '<a href="/_classtesting.php?action=updatetovar&tovarid='.$_REQUEST['tovarid'].'&sku='.$result["sku"].'&target=status&newstate=2">выключить</a><br>';
//        $result['status']+='<a href="/_classtesting.php?action=updatetovar&sku='.$result["sku"].'&target=status&newstate=2"> выключить </a>';
//        $result['status']+='<a href="/_classtesting.php?action=updatetovar&sku='.$result["sku"].'&target=status&newstate=1"> включить </a>';
        _myStrings::debug($result);
    }

    public function updatestock(){
        if(!isset($_REQUEST['tovarid']))return;

    }

    public function showmediainfo(){// массив картинок товара
        if(!isset($_REQUEST['tovarid']))return;
        $mag=new magento();
        $result=$mag->showmediainfo($_REQUEST['tovarid']);
//        $result=$mag->removepic(543,"/i/m/image_869.jpg");
        $counter=0;
        foreach($result as $i){
            echo '<img src="'.$i['url'].'" width="200px" id="pic'.$counter.
                 '" onclick="picremoverimported('."'".$_REQUEST['tovarid']."','".base64_encode($i['file'])."','pic".$counter++."'".')">';
        }
        _myStrings::debug($result);

    }

    public function picremover(){// in btoa(JSON.stringify({"tovarid":id,"path":path}))     функция удаления картинок (уже импортированных)
//        echo '<pre>';print_r($_REQUEST);exit;
        $b64json=$_REQUEST['params'];
        $goodid = '';
        $picurl= '';

        try {
            $json = json_decode(base64_decode($b64json));
            $goodid = $json->tovarid;
            $picurl = base64_decode($json->path);
        } catch (Exception $e) {
            echo "<div id='result'>error</div>";
            return;
        }

//        echo "<div id='result'>'.$goodid.'\n'.$picurl.'</div>";
//        return;
        $mag=new magento();
        $result=$mag->removepic($goodid,$picurl);// todo если картинка была основной, сделать следующую таковой
        if($result)echo "<div id='result'>ok</div>";
        else echo "<div id='result'>error</div>";

    }

    public function updatetovar(){
//        echo '<pre>';print_r($_REQUEST);exit;
//        $b64json=$_REQUEST['params'];

        if(!isset($_REQUEST['sku']))return;
        $sku = $_REQUEST['sku'];// SKU!!!!!!!!!!!!!!!!!!!!!!!!!!
        $target = $_REQUEST['target'];
        $newstate = $_REQUEST['newstate'];

        if($target=='status'){
            $mag=new magento();
            $result=$mag->updatetovar($sku,$target,$newstate);
            echo "<div id='result'> результат обновления: ".($result?"ok":"ошибка")."</div>";
            $this->showadditionaltovarinfo($_REQUEST['tovarid'],$mag);
        }
        if($target=='price'){// todo при обновлении цены старую заносить в поле oldprice
            $mag=new magento();
            $result=$mag->updatetovar($sku,$target,$newstate);
            echo "<div id='result'> результат обновления: ".($result?"ok":"ошибка")."</div>";
            $this->showadditionaltovarinfo($_REQUEST['tovarid'],$mag);
        }
        if($target=='stock'){
            $mag=new magento();
            $result=$mag->setstock($sku,$newstate);
            echo "<div id='result'> результат обновления статуса наличия товара: ".($result?"ok":"ошибка")."</div>";
            $this->showadditionaltovarinfo($_REQUEST['tovarid'],$mag);
        }
        if($target=='stock_state'){
            $mag=new magento();
//            $result=$mag->setstock($sku,$newstate);
            $result=$mag->updatetovar($_REQUEST['tovarid'] , $target , $newstate);// установка статуса наличия товара (кастомное поле в искалеченной теме grayscale)
            echo "<div id='result'> результат обновления статуса customstockstate товара: ".$result."</div>";
            $this->showadditionaltovarinfo($_REQUEST['tovarid'],$mag);
        }

//        try {
//            $json = json_decode(base64_decode($b64json));
//            $goodid = $json->tovarid;
//            $picurl = base64_decode($json->path);
//        } catch (Exception $e) {
//            echo "<div id='result'>error</div>";
//            return;
//        }
//
//        $mag=new magento();
//        $result=$mag->removepic($goodid,$picurl);
//        if($result)echo "<div id='result'>ok</div>";
//        else echo "<div id='result'>error</div>";

    }


}

class comGoodsItem{
    public $podrobneeURL;

    public $itemCharacteristicsURL;

    public $characteristicstable;

    public $itemComtehWatermarkedImageURL;

    public $itemTitleText;

    public $itemShortDescriptionText;

    public $itemComtehID;

    public $price;

    public $oldprice;

    public $agooglePics=array();// array of arrays array( link , bool(enabled) )

    public $magentoID;// id товара, если он уже был добавлен в магазин

    public $magentoSKU=false;// sku товара, если он уже был добавлен в магазин и обработан

    public $tovarSyncState;// состояние синхронизации товара в магазине и управляющей базе

    public $magentoCategoriesArray;// массив id категорий товара в мадженте

    
    function  __construct($rawitemdata,$categoriesArray){

        if($rawitemdata==false){
            $ar=get_object_vars($this);
            foreach($ar as $i=>$j){
                    $this->$i=false;
                }
            return;
        }

        $this->magentoCategoriesArray=$categoriesArray;

        $this->podrobneeURL=_myStrings::between($rawitemdata,' href="','" title="');

        $this->itemCharacteristicsURL=str_replace(
            'detail',
            'characteristics',
            $this->podrobneeURL
        ).'#tabs_anchor';

//        $this->characteristicstable=_myStrings::betweenLeave(_myStrings::request($this->itemCharacteristicsURL,"","comteh.txt"),'<table class="specs">','</table>');
        $this->characteristicstable=" ";// заполняется отдельно итеративным способом

//        echo $this->characteristicstable;exit;

        $this->itemComtehWatermarkedImageURL='http://comteh.com'
                                             ._myStrings::between($rawitemdata,'<img src="','" ');

        $this->itemTitleText=_myStrings::between($rawitemdata,'" title="','">');

        $matches=array();

        preg_match('/<div class=\"margintop10\">\s*(.*)<a href=\"/',$rawitemdata,$matches);

        if(count($matches)-1==-1){

            $this->itemShortDescriptionText=" ";
//            _myStrings::debug($rawitemdata);
//            exit;
        }
        else        $this->itemShortDescriptionText=$matches[count($matches)-1];

        $this->itemComtehID=_myStrings::between($rawitemdata,'<div id="product-code" class="d_none">','</div>');

        $this->price=_myStrings::between($rawitemdata,' name="product_price" value="','" />');

        $ar=get_object_vars($this);

        foreach($ar as $i=>$j){
            if($i=='oldprice'||
               $i=='magentoID'||
               $i=='agooglePics'||
               $i=='magentoCategoryID'||
               $i=='magentoSubCategoryID'||
               $i=='tovarSyncState'||
               $i=='magentoSKU')continue;
            if(empty($j)){
                echo '<h1>Error in productitemconstructor:<br>Field='.$i.'<br>src=</h1>'.addslashes($rawitemdata);
                echo '<h1>';
                echo '<a id="cookITup" href="'.$_SERVER['PHP_SELF'].'?action=getcomtehcookies">Получить кукизы комтеха</a><br>';
                echo'</h1>';
                exit;
            }
        }

    }

    public function showAllFields(){

        echo $this->podrobneeURL;

        echo '<br>';

        echo $this->itemCharacteristicsURL;

        echo '<br>';

        echo $this->itemComtehWatermarkedImageURL;

        echo '<br>';

        echo '<div id="'.$this->itemComtehID.'">';
        echo '<img src="'.$this->itemComtehWatermarkedImageURL.'">';

        $counter=0;
        foreach($this->agooglePics as $pic){
            if($pic[1]){
                echo '<img src="'.$pic[0].'" width="200px" id="'.$this->itemComtehID.'ppic'.$counter.
                     '" onclick="pichider('."'".$this->itemComtehID.'ppic'.$counter."'".')">';
            }
            $counter++;
        }

//        echo '<img src="'.$this->itemComtehWatermarkedImageURL.'" width="129px" height="129px">';

        echo '<a href="/_classtesting.php?action=loadMoreGoogleImages&comtehid='.$this->itemComtehID.
             '" onclick="picmoreloader('."'".$this->itemComtehID."'".');return false">больше картинок</a>';
//        echo '<a href="/_classtesting.php?action=loadMoreGoogleImages&comtehid='.$this->itemComtehID.'">больше картинок</a>';
        echo '</div>';
        echo '<br>';

        echo $this->itemTitleText;// здесь есть код товара как минимум для ноутбуков

        echo '<br>';

        echo $this->itemShortDescriptionText;

        echo '<br>';

        echo $this->itemComtehID;

        echo '<br>';

        echo $this->price;

        echo '<br>------------------------------------------------------------------------------------------------------------<br>';

    }

    public function showonlypics(){
        echo '<img src="'.$this->itemComtehWatermarkedImageURL.'">';
        $counter=0;
        foreach($this->agooglePics as $pic)if($pic[1]){
            echo '<img src="'.$pic[0].'" width="200px" id="'.$this->itemComtehID.'ppic'.$counter.
                 '" onclick="pichider('."'".$this->itemComtehID.'ppic'.$counter++."'".')">';
        }
    }

    public function getcharacteristics(){// занимает много времени, так что будем это далать потом в серии рекурсивных запросов
        $this->characteristicstable=_myStrings::betweenLeave(_myStrings::request($this->itemCharacteristicsURL,"","comteh.txt"),'<table class="specs">','</table>');
    }

    public function getGooglePics($dopolnitelnieKartinki=false){
        $result='';
        preg_match('/\((.*?)\)$/',$this->itemTitleText,$result);
        $code=$result[1];// код товара

        $pics=array();
        if($dopolnitelnieKartinki){
            $pics=$this->googleimageapis($code,count($this->agooglePics));
            $pics=array_merge(
                $pics,
                $this->googleimageapis($code,count($this->agooglePics)+count($pics))
            );
            $pics=array_merge(
                $pics,
                $this->googleimageapis($code,count($this->agooglePics)+count($pics))
            );
        }else{
            $pics=$this->googleimageapis($code);
        }

//        echo '<pre>';print_r($pics);exit;
        foreach($pics as $pic){
             $this->agooglePics[]=array ( $pic , true );
        }

        return $pics;

    }

    protected  function googleimageapis($searchRequest,$start=0){//  <=4-string url`s array
        $searchRequest=str_replace(" ","+",$searchRequest);
        $data=_myStrings::request('http://ajax.googleapis.com/ajax/services/search/images?v=1.0&q='.$searchRequest.'&start='.$start,"","ggl.txt");
        $data=json_decode($data);
        $aurls=array();
        foreach($data->responseData->results as $result){
            $aurls[]=$result->url;
        }
        return $aurls;
    }


}

class magento{
    private $session;
    private $client;
//    private $category=614;

    function __construct(){
//        echo "started getting<br>";$s=time();
        
//        $client = new SoapClient('http://localhost/api/v2_soap/?wsdl');
//        $session = $client->login('s', '123456');

//        $this->client = new Zend_XmlRpc_Client('http://localhost/api/xmlrpc/');
//        $this->session = $this->client->call('login', array('s', '123456'));
//        exit;

        $this->client = new SoapClient('http://localhost/api/?wsdl');/// todo модификация под другой магазин
        $this->session = $this->client->login('s', '123456');
        
//        file_put_contents(dirname(__FILE__).'/_magentoSessionObject',serialize($this->session));exit;
//        $this->session = unserialize(file_get_contents(dirname(__FILE__).'/_magentoSessionObject'));//   вываливается по Session expired
//        echo "finished getting in ".(time()-$s)." seconds<br>"; exit;
    }

    function __destruct(){
        $this->client->endSession($this->session);
    }

    public function createCategory($name,$id){
    }

//    public function createProduct($name,$descriptionFull,$descriptionShort,$price,$id,$category,$subcategory=0){
    public function createProduct(comGoodsItem $tovar){
        if(count($tovar->magentoCategoriesArray)<1){
            myStrings::debug('$tovar->magentoCategoryID не установлены для товара '.$tovar->itemTitleText.'<br> при вызове magento::createProduct');
            exit;
        }
        if(!file_exists(dirname(__FILE__).'/_attributeSetsObject')){
            $attributeSets = $this->client->call($this->session, 'product_attribute_set.list');//можно закешроввать
            file_put_contents(dirname(__FILE__).'/_attributeSetsObject',serialize($attributeSets));exit;
        }
        $attributeSets = unserialize(file_get_contents(dirname(__FILE__).'/_attributeSetsObject'));

        $attributeSet = current($attributeSets);

        $list=$this->listproducts(0);/// todo можно попрофилировать этот момент по этамам заливки товара и затратам времени
        $nextid=$list[0]['product_id']+1;

        $result = $this->client->call($this->session, 'catalog_product.create', array('simple', $attributeSet['set_id'], 'product_sku_'.$nextid.rand(600,100500), array(
            'categories' => $tovar->magentoCategoriesArray,
            'websites' => array(1),
            'stock_data'=>array('qty'=>123),
            'name' => $tovar->itemTitleText,
            'description' => $tovar->characteristicstable,
            'short_description' => $tovar->itemShortDescriptionText,
            'weight' => '10',
            'status' => '1',
            'visibility' => '4',
            'price' => $tovar->price,
            'tax_class_id' => 1,
            'meta_title' => $tovar->itemTitleText,
            'meta_keyword' => $tovar->itemTitleText,
            'meta_description' => $tovar->itemTitleText
        )));
        $tovar->magentoID=$result; // сохраняем id в товар

        $productId = $result;
        _myStrings::debug("Product created. id=".$productId);
        return $productId;
    }

    public function removeProduct($id){
//        $result=$this->client->call($this->session, 'catalog_category.removeProduct',array('categoryId' => $this->category, 'product' => $id));
//        $result=$this->client->call($this->session, 'catalog_product.delete',$id);

        $multicall=array(
            array('catalog_category.removeProduct',array('categoryId' => $this->category, 'product' => $id)),
            array('catalog_product.delete',$id)
        );
        $result=$this->client->multiCall($this->session,$multicall);

        _myStrings::debug("deleting product id=$id: ");
        _myStrings::debug($result);
    }

    public function listproducts($onlyprint=1){// по умолчанию вернет не только массив результатов, но и выведет его на страницу
        $result=$this->client->call($this->session, 'catalog_product.list');
        if($onlyprint){
            echo "product list: (".count($result).")<br>";
            _myStrings::debug($result);
        }
        return $result;
    }

    public function addpic($productId,$pic,$label="",$multi=false){
        if(is_array($pic)){// рекурсивная обработка для нескольких изображений
            $multicall=array();
            foreach($pic as $i){
                if($i[1])$multicall[]=$this->addpic($productId,$i[0],'',true);
            }
            $this->client->multiCall($this->session,$multicall);
            return;
        }

        $file = array(
            'content' => base64_encode(file_get_contents((strlen(parse_url($pic,PHP_URL_SCHEME))>1)?$pic:(dirname(__FILE__).'/'.$pic))),
//            'content' => base64_encode(file_get_contents($pic)),
            'mime' => 'image/jpeg'
        );

        try {
            if($multi) return array(// готовим мультизапрос, чтобы все картинки ушли одним запросом
                'catalog_product_attribute_media.create',
                array(
                     $productId,
                     array(
                         'file' => $file,
                         'label' => $label,
                         'position' => '100',
                         'types' => array('image',
                                          'thumbnail',
                                          'small_image'),
                         'exclude' => 0)
                )            );
//            -------------------------------------------------------------------------
            $this->client->call(
                $this->session,
                'catalog_product_attribute_media.create',
                array(
                     $productId,
                     array(
                         'file' => $file,
                         'label' => $label,
                         'position' => '100',
                         'types' => array('image',
                                          'thumbnail',
                                          'small_image'),
                         'exclude' => 0)
                )
            );
        } catch (Exception $e) {
            echo "<br>Ошибка при добавлении картинки ".$pic."<br>".$e->getMessage()."<br>".
                 (strlen(parse_url($pic,PHP_URL_SCHEME))>1)?$pic:(dirname(__FILE__).'/'.$pic)."<br>";
        }
    }

    public function removepic($id,$picfile){//[0] => Array
        $result=$this->client->call(            // (
            $this->session,                     //     [file] => /i/m/image_869.jpg
            'catalog_product_attribute_media.remove',
            array('product' => $id, 'file' => $picfile)      //'/b/l/blackberry8100_2.jpg')
        );
        return $result;
    }

    public function showadditionaltovarinfo($id){
        $result=$this->client->call(
            $this->session,
            'catalog_product.info',$id
        );
        return $result;
    }

    public function getstock($sku){//sku!!!!!!!!!!!!
        $result=$this->client->call(
            $this->session,
            'product_stock.list',$sku
        );
        return $result;
    }

    public function setstock($sku,$stockstatus){//sku!!!!!!!!!!!!
        $result=$this->client->call(
            $this->session,
            'product_stock.update',array(
                                        $sku,
                                        array('qty'=>100, 'is_in_stock'=>$stockstatus)
                              )
        );
        return $result;
    }

    public function showmediainfo($id){
        $pics=array();
        $result=$this->client->call(
            $this->session,
            'catalog_product_attribute_media.list',$id
        );
//        foreach($result as $i){
//            $pics[]=array("file"=>$i[])
//        }
        return $result;
    }

    public function updatetovar($sku,$target,$newstate){
        $result=$this->client->call(
            $this->session,
            'catalog_product.update',array(
                                          $sku,// да, по документации именно sku
                                          array(
                                            $target=>$newstate
                                          )
                                     )
        );
        _myStrings::debug(func_get_args());// принт массива аргументов функции для временной отладки
//        foreach($result as $i){
//            $pics[]=array("file"=>$i[])
//        }
        return $result;
    }

}

class lnk{
    public $url;

    public $status;

    public $id;

    function __construct($_url,$_status,$_id){

        $this->url=$_url;

        $this->status=$_status;

        $this->id=$_id;
    }

    public function show(){

        echo $this->status.":".$this->url."<br>";

    }
}

class comPage{
    public $content;// array of goods (objects)

    public $reflinks;

    function __construct($url,$cookiefile,$categoriesArray){

        $rawPage=_myStrings::request($url,"",$cookiefile);//_myStrings::debug($rawPage);exit;

        $reflinksdata=_myStrings::between($rawPage,'<div class="pager">','</div>');

        $arr=explode('<a href="',$reflinksdata);

        $arr=array_filter(
            $arr,
            function($inp){
                return intval(
                           _myStrings::split2(
                               $inp,
                               '">',
                               1)
                       )!=0;
            }
        );

        $this->reflinks=array_map(
            function($inp){                return new lnk(preg_replace('/\".*$/','',$inp),"0","0");            },
            $arr
        );

        $this->reflinks[]=new lnk($url,"1","0");

        $rawitemsArray=explode('<a class="img_prod_new"',$rawPage);

        array_shift($rawitemsArray);

        foreach($rawitemsArray as $e){
           $this->content[]=new comGoodsItem($e,$categoriesArray);
        }
    }

//    public function printlinks(){
//
//        array_map(
//            function($elem){
//                echo $elem->status.":".$elem->url."<br>";
//            },
//            $this->reflinks
//        );
//    }

    public function printgoods(){

        if(!is_array($this->content)){
            _myStrings::debug('nonarray value start in printgoods');
            var_dump($this->content);
            _myStrings::debug('nonarray value end');
        }

        array_map(
            function($e){
               $e->showAllFields();
            },
            $this->content
        );
    }

    public function exporttgoods(){

        if(!is_array($this->content)){
            _myStrings::debug('nonarray value start in exportgoods');
            var_dump($this->content);
            _myStrings::debug('nonarray value end');
        }

        return $this->content;
    }

}

class cComteh{
    public $pages;// array of compage objects

    private $reflinks;

    private $working=true;// ограничитель для отлова неконтроллируемого парсинга

    private $counter=0;

    private $goodscollectionforexportingtopage;

    private $getnextitemgenerator=0;

    protected $categoriesArray;

    
    function __construct($url,$categoriesArray){

        $this->categoriesArray=$categoriesArray;

        $this->getpage($url);

        $this->parse();// будет вызывать getpage до тех пор, пока не закончатся необработанные ссылки или working не изменится на false
    }

    private function getpage($url){

//        _myStrings::debug('Getpage:>>>>>>>'.$url);

        $tmppage=new comPage($url,"comteh.txt",$this->categoriesArray);

        foreach($tmppage->reflinks as $link){
            $this->addlink($link);
        }

        $this->pages[]=$tmppage;

        $this->counter++;

        if($this->counter>70)$this->working=false;
    }

    private function addlink($add){

        if(count($this->reflinks)){

            foreach($this->reflinks as $link){

                if($link->url==$add->url){

                    if($link->status=="1") return;

                    if($add->status=="1"){

                        _myStrings::debug('modifying '.$link->url);

                        $link->status="1";

                        return;
                    }

                    return;
                }
            }
        }

        $this->reflinks[]=$add;
    }

    private function parse(){

        while($this->zerolinksleft() && $this->working){

                $this->getpage($this->markandreturnnextzerolink());
        }
    }

    private function zerolinksleft(){

        foreach($this->reflinks as $link){

            if($link->status=="0")return true;
        }

        return false;
    }

    private function markandreturnnextzerolink(){

        foreach($this->reflinks as $link){
            if($link->status=="0"){
                $link->status="1";
                return $link->url;
            }
        }

        return "!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!! error in markandreturnnextzerolink";
    }


    public function printgoods($limitedlistfordebugging=false){
        if ($limitedlistfordebugging) {
//            echo "limitedlist";exit;

            $counter=0;
            foreach($this->pages as $i){
                $i->printgoods();
                if(++$counter>1)break;
            }
            return;
        }

        array_map(
            function($e){
                $e->printgoods();
            },
            $this->pages
        );
    }

    public function printlinks(){

        foreach($this->reflinks as $link){
            $link->show();
        }
    }

    public function fillexportarray(){
        if(count($this->goodscollectionforexportingtopage)>0)return;
//        echo count($this->goodscollectionforexportingtopage);exit;
        foreach($this->pages as $page){
            $items=$page->exporttgoods();
            foreach($items as $item){
                $this->goodscollectionforexportingtopage[]=$item;
            }
        }
        _myStrings::debug("fillexportarray() done. array length: ".count($this->goodscollectionforexportingtopage));
    }

    public function getnextitem(){
        if($this->getnextitemgenerator<count($this->goodscollectionforexportingtopage))
            return $this->goodscollectionforexportingtopage[$this->getnextitemgenerator++];
        $this->getnextitemgenerator=0;
        return -1;
    }

    public function getprogresscounter(){
        return $this->getnextitemgenerator."/".count($this->goodscollectionforexportingtopage);
    }
}

class db{//gets host login pass in constuctor
    protected $session;
    protected $dbase='magentoSync';
    protected $tableByDefault='importedgoods';
    function __construct($host,$login,$pass){
        $this->session=mysqli_connect($host, $login, $pass) or die(mysqli_error($this->session));
        _myStrings::debug('connected ok');
//        $retvalue=mysqli_query($this->session,'drop database if exists '.$this->dbase) or die(mysqli_error($this->session));// отладочное удаление базы
        $ret=mysqli_select_db($this->session,$this->dbase) or $this->createdbandtable();
        _myStrings::debug('db selection status: '.($ret?'ok':'error'));
    }
    function __destruct(){
        @mysqli_close($this->session);
    }
    public function createdbandtable(){
        _myStrings::debug('db deleting started');
        $retvalue=mysqli_query($this->session,'drop database if exists '.$this->dbase) or die(mysqli_error($this->session));
        _myStrings::debug('db drop status: '.($retvalue?'ok':'error').'');

        _myStrings::debug('db creation started');
        $retvalue=mysqli_query($this->session,'create database '.$this->dbase) or die(mysqli_error($this->session));
        _myStrings::debug('db creation status: '.($retvalue?'ok':'error').'');

        $ret=mysqli_select_db($this->session,$this->dbase);
        _myStrings::debug('db selection status: '.($ret?'ok':'error').'');

        $retvalue=mysqli_query($this->session,"
                create table {$this->tableByDefault} (
                cid             varchar(20) NOT NULL default '',
                mid                  int(7) NOT NULL default 0,
                sku             varchar(15) NOT NULL default '',
                syncstate            int(1) NOT NULL default 1,
                title                  text NOT NULL default '',
                objectTovarSerialized  text NOT NULL default '',
                id                  int(11) NOT NULL auto_increment,
                PRIMARY KEY  (id)
            ) charset utf8 COLLATE utf8_general_ci") or die(mysqli_error($this->session));
        _myStrings::debug('table creation status: '.($retvalue?'ok':'error').'');
    }

    public function insert(comGoodsItem $tovar){
        _myStrings::debug('db->insertion started');
        $retvalue=mysqli_query($this->session,"insert into {$this->tableByDefault} (cid, mid, sku,syncstate,title,objectTovarSerialized)
            values ('".
                               $tovar->itemComtehID."','".
                               $tovar->magentoID."','".
                               $tovar->magentoSKU."','".
                               '1'."','".
                               $tovar->itemTitleText."','".
                               serialize($tovar)."'
             )"
        ) or die(mysqli_error($this->session));
        _myStrings::debug('db->insert query status: '.($retvalue?'ok':'error').'');
        return $retvalue?true:false;
    }
    public function findByMID($mid){// magento tovar id
        _myStrings::debug('db->findByMID started');
        $retvalue=mysqli_query($this->session,"select * from {$this->tableByDefault} where title like '".$mid."'") or die(mysqli_error($this->session));
        _myStrings::debug('db->findByMID done status '.($retvalue?'ok':'error').'');
        if(is_bool($retvalue))return new comGoodsItem(false,null);
        else{
            $arr=mysqli_fetch_assoc($retvalue);
            return unserialize($arr['objectTovarSerialized']);
        }
    }
    public function findByTitleText($titleText){// magento tovar name (title)
        _myStrings::debug('db->findByTitleText started');
        $retvalue=mysqli_query($this->session,"select * from {$this->tableByDefault} where mid like '%".$titleText."%'") or die(mysqli_error($this->session));
        _myStrings::debug('db->findByTitleText done status '.($retvalue?'ok':'error').'');
        if(is_bool($retvalue))return new comGoodsItem(false,null);
        else{
            $arr=mysqli_fetch_assoc($retvalue);
            return unserialize($arr['objectTovarSerialized']);
        }
    }
    public function updatestate(comGoodsItem $tovar){
        _myStrings::debug('db->updatestate started');
        $retvalue=mysqli_query($this->session,"update {$this->tableByDefault} set
            cid='".$tovar->itemComtehID.",'
            sku='".$tovar->magentoSKU.",'
            syncstate='".$tovar->tovarSyncState.",'
            title='".$tovar->itemTitleText .",'
            objectTovarSerialized='".serialize($tovar)."'
            where mid=".$tovar->magentoID) or die(mysqli_error($this->session));
        if(is_bool($retvalue))_myStrings::debug('db->updatestate done status: '.($retvalue?'ok':'error').'');
        return $retvalue?true:false;
    }
    public function del(comGoodsItem $tovar){
        _myStrings::debug('db->del started');
        $retvalue=mysqli_query($this->session,"delete from {$this->tableByDefault} where mid='".$tovar->magentoID."'") or die(mysqli_error($this->session));
        if(is_bool($retvalue))_myStrings::debug('db->del done status: '.($retvalue?'ok':'error').'');
        return $retvalue?true:false;
    }
}

class _myStrings{

    public static function debug($str){

        echo "<pre>";
//        echo $str;
        print_r($str);
        echo "</pre>";
    }

    public static function request($url,$referrer="",$CookieFile="",$IntoThisFile="",$post = 0,$fileto=0)
{
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url); // отправляем на
//	if(!$fileto)curl_setopt($ch, CURLOPT_HEADER, 1); // 1 - перед страницей покажет заголовки
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // возвратить то что вернул сервер
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); // следовать за редиректами
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 120);// таймаут4
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
//        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.1; rv:16.0) Gecko/20100101 Firefox/16.0");
        curl_setopt($ch, CURLOPT_REFERER, $referrer);
	curl_setopt($ch, CURLOPT_COOKIEFILE,  dirname(__FILE__).'/'.$CookieFile);
        curl_setopt($ch, CURLOPT_COOKIEJAR, dirname(__FILE__).'/'.$CookieFile); // сохранять куки в файл
//	curl_setopt($ch, CURLOPT_COOKIE,  dirname(__FILE__).'/'.$CookieFile);
        if($fileto!==0)
        {
            $file=fopen(dirname(__FILE__).'/'.$IntoThisFile,'w+');
            curl_setopt($ch, CURLOPT_FILE, $file);
            curl_setopt($ch, CURLOPT_POST, $post!==0 ); // использовать данные в post
            if($post)       curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
            curl_exec($ch);
            //if($post)       echo "<p>Ответ  curl: ".curl_error($ch)."<p>";//.print_r(curl_getinfo($ch))."<p>";
            //if($post)       echo "<p>postData=".$post."<p>";
            fclose($file);
            curl_close($ch);
            return "Закачка завершена<br>";
        }
        else{
           curl_setopt($ch, CURLOPT_POST, $post!==0 ); // использовать данные в post
            if($post)       curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
            $data = curl_exec($ch);
//            echo 'CURL ERROR DATA: '.curl_error($ch);
            //if($post)       echo "<p>Ответ  curl: ".curl_error($ch)."<p>";//.print_r(curl_getinfo($ch))."<p>";
            //if($post)       echo "<p>postData=".$post."<p>";
            curl_close($ch);
//            return html_entity_decode(htmlentities($data,ENT_QUOTES,"cp1251"),ENT_QUOTES,"UTF-8");
            return $data;
        }

}


    public static function _split($src,$delim,$index){
    //$txt="CPU AMD sAM3 Athlon";
    //for($i=0;$i<5;$i++)    echo "$i "._split($txt," ",$i)."<p>";

        if(!stristr($src,$delim))return "";

        $arr=explode($delim,$src);
        if(count($arr)>1 and $index==1){
            for($i=2;$i<count($arr);$i++){
                if($i!=count($arr))       $arr[$index].=$delim;
                $arr[$index].=$arr[$i];
            }
        }

        if(count($arr)-1<$index and DEBUG){

            echo '<script>alert("Error in _split:\nsrc='.addslashes($src).'\ndelim='.addslashes($delim).'\nindex='.$index.'")</script>';

            return "-----------------------error---------------------------------error---------------------------------------------------error------------------------------------";
        }
        return $arr[$index];
    }

    public static function split2($src,$delim,$index,$last=0){

        if(!stristr($src,$delim))return "";

        $arr=explode($delim,$src);
//        if($delim=='">'){var_dump($arr); echo "<br>src=".$src."   delim=".$delim."   index=".$index.'<br>';}
        if($last==0){
            if(count($arr)<=$index){
                if(DEBUG) echo '<script>alert("Error in split2:\nsrc='.addslashes($src).'\ndelim='.addslashes($delim).'\nindex='.$index.'")</script>';
                return "-----------------------error---------------------------------error---------------------------------------------------error------------------------------------";
            }
            else return $arr[$index];
        }
        else return $arr[count($arr)-1];
    }

    public static function split3($src,$delim,$index){//1 - от пробела        до делителя
    //$txt="CPU AMD sAM3 Athlon";
    //echo split3($txt," sAM3",1)."<p>";
        if($index==1) return _myStrings::split2(_myStrings::split2($src,$delim,0)," ",0,1);
        else return between($src,$delim," ");
    }
    public static function between($src,$ot,$do){
        return _myStrings::_split(_myStrings::_split($src,$ot,1),$do,0);
    }
    public static function betweenRight($src,$ot,$do){
       return _myStrings::split2(_myStrings::split2($src,$ot,1,1),$do,0);
    }
    public static function betweenLeave($src,$ot,$do){
        return $ot._myStrings::_split(_myStrings::_split($src,$ot,1),$do,0).$do;
    }

}
function de($str){
        echo "<pre>";
        print_r($str);
        echo "</pre>";
    }



$str=<<<'EOD'
EOD;



$p=new page();
//$p->frame(500,300,'состояние','f1');

$p->button('очистить','clean','');
$p->button('создать продукт','prodnew','create');
$p->button('загрузить заголовки ноутов из комтеха (to cache)','comtehloadheaders','comtehloadheaders');
$p->button('показать список продуктов','prodlist','_list');
$p->button('удалить первый товар','prodrem','removefirst');
$p->button('удалить все товары','prodremall','removeall');
$p->button('заполнить из комтеха (from cache)','comtehfill','comtehfill');
$p->button('показать список заголовков ноутов из комтеха','comtehdisplay','comtehdisplay');
$p->button('показать заголовки ноутов (from cache)','comtehshowloadedheaders','comtehshowloadedheaders');
$p->button('показать заголовки ноутов (from cache) limitedlist','comtehshowloadedheaders','comtehshowloadedheaders&limitedlist=1');
$p->button('циклическая подгрузка характеристик (to cache)','loadcharacteristics','loadcharacteristics');
$p->button('циклическая подгрузка гуглокартинок (to cache)','loadGoogleImages','loadGoogleImages');
$p->button('показать характеристики первого товара (from cache)','firstGoodCharacteristics','firstGoodCharacteristics');
$p->button('показать доп.инфо первого товара (from magento)','showadditionaltovarinfo','showadditionaltovarinfo&tovarid=543');
$p->button('показать доп.медиа.инфо залитого товара (from magento)','showmediainfo','showmediainfo&tovarid=545');













//echo _myStrings::request("http://comteh.com/products/show/c/11217/sc/KT08158.html","","comteh.txt");
//echo file_get_contents("http://comteh.com/products/show/c/11217/sc/KT08158.html");
//http://comteh.com/profiles/sel_region/4/9.html

//pichider('KT79025ppic1')
echo '
<script type="text/javascript">
    function picremoverimported(id,path,currentimgid){
//    alert("id="+id+"\\n"+"path="+atob(path))
//    return


    var fon=document.createElement("div")
    fon.setAttribute("id","fon")
    fon.setAttribute("style","background:RGBA(140,140,140,0.9);width:100%;height:100%;z-index:100;position:fixed;margin:0;left:0;top:0;")
    document.body.appendChild(fon)

    var senddata=btoa(JSON.stringify({"tovarid":id,"path":path}))
    _xsend(
        "/_classtesting.php?action=picremover",
        "POST",
        function(domdata){
            var result=domdata.querySelector("#result").innerHTML;
//            alert(result)
//            return
            if(result=="ok"){
                console.log("picremover result:"+result);
                document.querySelector("#"+currentimgid).setAttribute("style" , "display:none;");
            }else
                console.log("picremover result:"+result);
            document.body.removeChild(document.querySelector("#fon"))
        },
        "params="+senddata
    )
//    document.querySelector("#"+id).setAttribute("style" , "display:none;")
}

function pichider(id){
    var code=id.match(/^(.*?)ppic/)[1]
    var picid=id.match(/ppic(.*)/)[1]
    var senddata=btoa(JSON.stringify({"code":code,"picid":picid}))
    _xsend(
        "/_classtesting.php?action=modify",
        "POST",
        function(domdata){
            var result=domdata.querySelector("#result").innerHTML;
            if(result=="ok"){
                console.log("picdelete result:"+result);
                document.querySelector("#"+id).setAttribute("style" , "display:none;");
            }else
                console.log("picdelete result:"+result);
        },
        "params="+senddata
    )
//    document.querySelector("#"+id).setAttribute("style" , "display:none;")
}

function picmoreloader(id){
//        echo <a href="/_classtesting.php?action=loadMoreGoogleImages&id=.$this->itemComtehID.>больше картинок</a>;
    senddata="none"
    _xsend(
        "/_classtesting.php?action=loadMoreGoogleImages&comtehid="+id,
        "POST",
        function(domdata){
            var result=domdata.querySelectorAll("#result img");

            var lst=document.querySelectorAll("#"+id+" img")
            for(var i=0;i<lst.length;i++){
                console.log("-------------removing "+lst[i].src)
                document.querySelector("#"+id).removeChild(lst[i])
            }

            for(var i=0;i<result.length;i++){
                console.log("-------------appending "+result[i].src)
                document.querySelector("#"+id).appendChild(result[i])
            }

        },
        "params="+senddata
    )

}

function _xsend(addr,type,callback=function(x){;},postdata=""){
    var xmlhttp = getXmlHttp()
    xmlhttp.open(type, addr, false);//!!!!!!!!!!!!!!!sync version!!!!!!!!!!!!!!!!!!!!!!!!!
    xmlhttp.setRequestHeader("Content-Type","application/x-www-form-urlencoded");
    xmlhttp.onreadystatechange = function() {
        if (xmlhttp.readyState == 4) {
            if (xmlhttp.status == 200) {
                var div = window.content.document.createElement("div");
                div.innerHTML=xmlhttp.responseText;
                var domdata = div;
                callback(domdata)
            }
        }
    };
    type=="GET"?xmlhttp.send(null):xmlhttp.send(postdata);
}

function getXmlHttp(){
  var xmlhttp;
  try {
    xmlhttp = new ActiveXObject("Msxml2.XMLHTTP");
  } catch (e) {
    try {
      xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
    } catch (E) {
      xmlhttp = false;
    }
  }
  if (!xmlhttp && typeof XMLHttpRequest!="undefined") {
    xmlhttp = new XMLHttpRequest();
  }
  return xmlhttp;
}


</script>';
?>

<!--<script>-->
<!--    document.cookie-->
<!--</script>-->