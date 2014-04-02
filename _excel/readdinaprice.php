<?php

/** Error reporting */
error_reporting(E_ALL);
ini_set('display_errors', TRUE);
ini_set('display_startup_errors', TRUE);
date_default_timezone_set('Europe/London');

define('EOL',(PHP_SAPI == 'cli') ? PHP_EOL : '<br />');

date_default_timezone_set('Europe/London');

/** Include PHPExcel */
require_once dirname(__FILE__) . '/Classes/PHPExcel.php';


//// Create new PHPExcel object
//$objPHPExcel = new PHPExcel();
//
//$objReader = PHPExcel_IOFactory::createReader('Excel2007');
//$objPHPExcel = $objReader->load("19namedrange.xlsx");
//
//// Resolve range
//// echo date('H:i:s') , " Resolve range" , EOL;
//echo 'Cell A1: ' , $objPHPExcel->getActiveSheet()->getCell('A1')->getCalculatedValue() , EOL;
//echo 'Cell B1: ' , $objPHPExcel->getActiveSheet()->getCell('B1')->getCalculatedValue() , EOL;


//---------------------------------------------------------------------------
//версия с фильтрацией кодов ноутбуков
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

debug($retval);
function debug($str){

    echo "<pre>";
    print_r($str);
    echo "</pre>";
}







exit;









// ------------------------------------------------------------------------------------------------------------------
if (!file_exists("dinacom.xls")) {
	exit("dinacom.xls not found." . EOL);
}

//echo date('H:i:s') , " Load from Excel2007 file" , EOL;
$objReader = PHPExcel_IOFactory::createReader('Excel5');
$objPHPExcel = $objReader->load("dinacom.xls");
//$objPHPExcel = $objReader->load("19namedrange.xlsx");

//echo date('H:i:s') , " Iterate worksheets" , EOL;
foreach ($objPHPExcel->getWorksheetIterator() as $worksheet) {
	echo 'Worksheet - ' , $worksheet->getTitle() , EOL;

    echo "<table>";
	foreach ($worksheet->getRowIterator() as $row) {
        echo "<tr>";
//		echo '    Row number - ' , $row->getRowIndex() , EOL;

		$cellIterator = $row->getCellIterator();
		$cellIterator->setIterateOnlyExistingCells(false); // Loop all cells, even if it is not set
		foreach ($cellIterator as $cell) {
			if (!is_null($cell)) {
//				echo '        Cell - ' , $cell->getCoordinate() , ' - ' , $cell->getCalculatedValue() , EOL;
				echo "<td>".$cell->getCalculatedValue() , "</td>";
			}
		}
//        echo EOL;
        echo "</tr>";
	}
    echo "</table>";
}
















// Echo memory peak usage
//echo date('H:i:s') , " Peak memory usage: " , (memory_get_peak_usage(true) / 1024 / 1024) , " MB" , EOL;


//available file formats:
//switch (strtolower($pathinfo['extension'])) {
//				case 'xlsx':			//	Excel (OfficeOpenXML) Spreadsheet
//				case 'xlsm':			//	Excel (OfficeOpenXML) Macro Spreadsheet (macros will be discarded)
//				case 'xltx':			//	Excel (OfficeOpenXML) Template
//				case 'xltm':			//	Excel (OfficeOpenXML) Macro Template (macros will be discarded)
//					$extensionType = 'Excel2007';
//					break;
//				case 'xls':				//	Excel (BIFF) Spreadsheet
//				case 'xlt':				//	Excel (BIFF) Template
//					$extensionType = 'Excel5';
//					break;
//				case 'ods':				//	Open/Libre Offic Calc
//				case 'ots':				//	Open/Libre Offic Calc Template
//					$extensionType = 'OOCalc';
//					break;
//				case 'slk':
//					$extensionType = 'SYLK';
//					break;
//				case 'xml':				//	Excel 2003 SpreadSheetML
//					$extensionType = 'Excel2003XML';
//					break;
//				case 'gnumeric':
//					$extensionType = 'Gnumeric';
//					break;
//				case 'htm':
//				case 'html':
//					$extensionType = 'HTML';
//					break;
//				case 'csv':
//					// Do nothing
//					// We must not try to use CSV reader since it loads
//					// all files including Excel files etc.
//					break;