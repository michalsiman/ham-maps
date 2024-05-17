<?php
//
// synchro SOTA wwff from csv file to mysql db
// https://storage.sota.org.uk/summitslist.csv
//
//      0             1           2         3         4    5       6      7         8         9       10        11         12       13          14             15             16                     
//
// SOTA Summits List (Date=11/05/2024)
// SummitCode,AssociationName,RegionName,SummitName,AltM,AltFt,GridRef1,GridRef2,Longitude,Latitude,Points,BonusPoints,ValidFrom,ValidTo,ActivationCount,ActivationDate,ActivationCall
// 3Y/BV-001,"Bouvet Island","Bouvetøya (Bouvet Island)",Olavtoppen,780,2559,3.3565,-54.4104,3.35650,-54.41040,10,3,01/03/2018,31/12/2099,0,,
//
//



ini_set('display_errors', 1);
@ini_set('zlib.output_compression',0);
@ini_set('implicit_flush',1);
@ob_end_clean();
set_time_limit(0);
ini_set('user_agent', 'My-Application/2.5');
ini_set('memory_limit', '1024M'); // or you could use 1G
header( 'Content-type: text/html; charset=utf-8' );
// ----------

echo '<center><img src="img/load-loading.gif" alt="Loading ..."><br />';
echo "Syncing SOTA ... ";
flush();

function download_file($url, $path) {

    $newfilename = $path;
    $file = fopen ($url, "rb");
    if ($file) {
      $newfile = fopen ($newfilename, "wb");
  
      if ($newfile)
      while(!feof($file)) {
        fwrite($newfile, fread($file, 1024 * 8 ), 1024 * 8 );
      }
    }
  
    if ($file) {
      fclose($file);
    }
    if ($newfile) {
      fclose($newfile);
    }
}

// locale storage of file with wwff directory
$datovy_soubor_cesta = dirname(__FILE__) ."/data_files/summitslist.csv";

// download actual .csv file from wwff.co
download_file("https://storage.sota.org.uk/summitslist.csv", $datovy_soubor_cesta);

echo "csv file donwloaded ... importing to db now ... wait ... ";
flush();

require 'settings/db_credentials.php';
$mysqli = new mysqli($host, $user, $pass, $db, $port);   // mysql db credentials
$mysqli->set_charset($charset);
$mysqli->query("SET collation_connection = $collation");

// odstraneni predesle _pre tabulky pokud existuje
$mysqli->query(" DROP TABLE IF EXISTS `sota_area_pre`; ");

// založení nové tabulky
$mysqli->query(" CREATE TABLE IF NOT EXISTS sota_area_pre LIKE sota_area ");

$pocet = 0;
$pocetvyrazenych = 0;

$actualdate = date('Y-m-d');
echo "<br /><br />Todays date $actualdate.<br /><br />";

$file = fopen($datovy_soubor_cesta, "r");
fgets($file); // preskoceni jednoho radku v nactenem CSV souboru
fgets($file); // preskoceni jednoho radku v nactenem CSV souboru
while (($row = fgetcsv($file)) !== FALSE) {
    //$row[3] = iconv('UTF-8','ASCII//TRANSLIT',$row[1]);
    $row[15] = date("Y-m-d",strtotime(str_replace('/', '-', $row[15]))); // lastact - datum posledni aktivace - prevod formatu na spravny
    $validfrom = date("Y-m-d",strtotime(str_replace('/', '-', $row[12]))); // datum od kdy je reference platna
    $validto = date("Y-m-d",strtotime(str_replace('/', '-', $row[13]))); // datum do kdy je reference platna

    // pokud je SOTA vrchol platny (tedy je k dnešku platný - tak se naimpoprtuje do db)
    if($actualdate >= $validfrom && $actualdate <= $validto) {
        $stmt = $mysqli->prepare("INSERT INTO sota_area_pre (reference, name, altitude, longitude, latitude, activation, lastact) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssss", $row[0], $row[3], $row[4], $row[8], $row[9], $row[14], $row[15]);
        $stmt->execute();
        $pocet++;
        //je nejaky zaznam oznaceny jako DELETED?
        //if ($row[8]==1) echo "deleted: 1<br />";
    } else {
      $pocetvyrazenych++;
      //echo $row[0]." ".$row[3].": datum je NOK - plati od ".$validfrom." do ".$validto."<br />"; // zobrazi zaznamy ktere jsou jiz neplatne (podle datumu)
    }
    //if($pocet>(20-1)) break; // po x radcich se import zastavi, pouzivam na testovani funkcnosti a ladeni
}

echo "<br />$pocet records wrote! $pocetvyrazenych isnt valid for import!";

// vyprazdneni aktualni tabulky, pote kopirovani z _pre a pak smazani _pre)
$mysqli->query(" TRUNCATE TABLE sota_area; ");
$mysqli->query(" INSERT INTO sota_area SELECT * FROM sota_area_pre; ");
$mysqli->query(" DROP TABLE IF EXISTS `sota_area_pre`; ");

echo " ... done!<br /><br /><br />";
echo '<a href="index.php">... go to main page ...</a></center>';

$mysqli->close();