<?php

// synchro GMA from csv file to mysql db
// https://storage.sota.org.uk/summitslist.csv
//
//    0       1     2             3         4           5                   6           7       8           9            10               11
//
// created: 12.05.2024 - 04.06.48
// Reference,Name,Height (m), Longitude, Latitude, Maidenhead Locator, valid from, valid to, deleted, Activations, last activated by, last date,
// 4L/SZ-001,"Schchara",5201,43.11280000,43.00030000,LN13NA,20181031,21991231,0,0,,
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
echo "Syncing GMA ... ";
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
$datovy_soubor_cesta = dirname(__FILE__) ."/data_files/gma_summits.csv";

// download actual .csv file from wwff.co
download_file("https://www.cqgma.org/gma_summits.csv", $datovy_soubor_cesta);

echo "csv file donwloaded ... saving to db now ... wait ... ";
flush();

require 'settings/db_credentials.php';
$mysqli = new mysqli($host, $user, $pass, $db, $port);   // mysql db credentials
$mysqli->set_charset($charset);
$mysqli->query("SET collation_connection = $collation");

// odstraneni _old tabulky uplne nakonec
$mysqli->query(" DROP TABLE IF EXISTS `gma_area_old`; ");
$mysqli->query(" DROP TABLE IF EXISTS `gma_area_pre`; ");

// založení nové tabulky
$mysqli->query(" CREATE TABLE IF NOT EXISTS gma_area_pre LIKE gma_area ");

$pocet = 0;
$pocetvyrazenych = 0;

$actualdate = date('Y-m-d');
echo "<br />Dnes je $actualdate.<br /><br />";

$file = fopen($datovy_soubor_cesta, "r");
fgets($file); // preskoceni jednoho radku v nactenem CSV souboru
fgets($file); // preskoceni jednoho radku v nactenem CSV souboru
while (($row = fgetcsv($file)) !== FALSE) {
    $row[1] = iconv('UTF-8','ASCII//TRANSLIT',$row[1]);
    $row[11] = date("Y-m-d",strtotime($row[11])); // lastact - datum posledni aktivace - prevod formatu na spravny
    $validfrom = date("Y-m-d",strtotime($row[6])); // datum od kdy je reference platna
    $validto   = date("Y-m-d",strtotime($row[7])); // datum do kdy je reference platna
 
    // pokud je GMA vrchol platny (tedy je k dnešku platný - tak se naimpoprtuje do db)
    if($actualdate >= $validfrom && $actualdate <= $validto) {
        $stmt = $mysqli->prepare("INSERT INTO gma_area_pre (reference, name, altitude, longitude, latitude, activation, lastact) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssss", $row[0], $row[1], $row[2], $row[3], $row[4], $row[9], $row[11]);
        $stmt->execute();
        $pocet++;
        //je nejaky zaznam oznaceny jako DELETED?
        //if ($row[8]==1) echo "deleted: 1<br />";
    } else {
      $pocetvyrazenych++;
      //echo $row[0]." ".$row[1].": datum je NOK - plati od ".$validfrom." do ".$validto."<br />";
    }
    //if($pocet>(20-1)) break; // po x radcich se import zastavi, pouzivam na testovani funkcnosti a ladeni
}

echo "<br />$pocet records wrote! $pocetvyrazenych isnt valid for import!";

// vyprazdneni aktualni tabulky, pote kopirovani z _pre a pak smazani _pre)
$mysqli->query(" TRUNCATE TABLE gma_area; ");
$mysqli->query(" INSERT INTO gma_area SELECT * FROM gma_area_pre; ");
$mysqli->query(" DROP TABLE IF EXISTS `gma_area_pre`; ");

echo " ... done!<br /><br /><br />";
echo '<a href="index.php">... go to main page ...</a></center>';

$mysqli->close();