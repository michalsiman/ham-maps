<?php
//
// synchro POTA from csv file to mysql db
// https://pota.app/all_parks_ext.csv
//
//       0        1      2         3            4            5           6        7
//
// "reference","name","active","entityId","locationDesc","latitude","longitude","grid"
// "US-0001","Acadia National Park","1","291","US-ME","44.31","-68.2034","FN54vh"
// "US-0002","Alagnak Wild River National Park","1","6","US-AK","59.0908","-156.463","BO19sc"
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
echo "Syncing POTA ... ";
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
$datovy_soubor_cesta = dirname(__FILE__) ."/data_files/pota_ext.csv";

// download actual .csv file from pota.app
download_file("https://pota.app/all_parks_ext.csv", $datovy_soubor_cesta);

echo "csv file donwloaded ... saving to db now ... wait ... ";
flush();

require 'settings/db_credentials.php';
$mysqli = new mysqli($host, $user, $pass, $db, $port);   // mysql db credentials
$mysqli->set_charset($charset);
$mysqli->query("SET collation_connection = $collation");

// odstraneni _pre tabulky pokud zbyla nejaka od minule
$mysqli->query(" DROP TABLE IF EXISTS `pota_area_pre`; ");

// založení nové tabulky
$mysqli->query(" CREATE TABLE IF NOT EXISTS pota_area_pre LIKE pota_area ");

$pocet = 0;
$pocetvyrazenych = 0;

$actualdate = date('Y-m-d');
echo "<br /><br />Todays date $actualdate.<br /><br />";

$file = fopen($datovy_soubor_cesta, "r");
fgets($file); // preskoceni jednoho radku v nactenem CSV souboru
while (($row = fgetcsv($file)) !== FALSE) {

  // pokud je SOTA vrchol platny (tedy je k dnešku platný - tak se naimpoprtuje do db)
    if($row[2]==1) {
        $stmt = $mysqli->prepare("INSERT INTO pota_area_pre (reference, name, latitude, longitude) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $row[0], $row[1], $row[5], $row[6]);
        $stmt->execute();
        $pocet++;
    } else {
      $pocetvyrazenych++;
      //echo $row[0]." ".$row[3].": datum je NOK - plati od ".$validfrom." do ".$validto."<br />"; // zobrazi zaznamy ktere jsou jiz neplatne (podle datumu)
    }
    //if($pocet>(20-1)) break; // po x radcich se import zastavi, pouzivam na testovani funkcnosti a ladeni
}

echo "<br />$pocet records wrote! $pocetvyrazenych isnt valid for import!";

// vyprazdneni aktualni tabulky, pote kopirovani z _pre a pak smazani _pre)
$mysqli->query(" TRUNCATE TABLE pota_area; ");
$mysqli->query(" INSERT INTO pota_area SELECT * FROM pota_area_pre; ");
$mysqli->query(" DROP TABLE IF EXISTS `pota_area_pre`; ");

echo " ... done!<br /><br /><br />";
echo '<a href="index.php">... go to main page ...</a></center>';

$mysqli->close();

