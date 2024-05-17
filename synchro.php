<?php
// synchro WWFF from csv file to mysql db
// https://wwff.co/wwff-data/wwff_directory.csv
//
//
//     0        1     2     3      4    5      6      7       8       9          10       11        12      13       14     15     16       17       18           19         20      21      22     23        24      25                                             
//
// reference,status,name,program,dxcc,state,county,continent,iota,iaruLocator,latitude,longitude,IUCNcat,validFrom,validTo,notes,lastMod,changeLog,reviewFlag,specialFlags,website,country,region,dxccEnum,qsoCount,lastAct
// 1SFF-0001,active,Spratly,1SFF,1S,1S,1S,AS,-,OJ58XO,8.61946,111.92416,n/a,0000-00-00,0000-00-00,"IOTA AS-051","2023-08-23 by M0YMA - Updated","2023-08-23 by M0YMA - Updated: Lat/Lon, Locator<br>2023-04-21 by ON4BB - Updated: Website, State, County<br>2023-03-13 by OK2APY - Updated: Website, DXCC, Lat/Lon, Locator, Notes",0,,-,"Spratly Archipelago",-,,,
// 3AFF-0001,active,"Reserve sous-marine du Larvotto",3AFF,3A,3A,3A,EU,-,JN33RR,43.74560,7.43761,n/a,0000-00-00,0000-00-00,-,"2023-08-23 by M0YMA - Updated","2023-08-23 by M0YMA - Updated: Lat/Lon, Locator<br>2023-03-13 by OK2APY - Updated: Name, Website, Lat/Lon, Locator",0,,https://www.protectedplanet.net/145542,Monaco,-,260,226,2010-07-24
// 3AFF-0002,active,"Corail rouge",3AFF,3A,3A,3A,EU,-,JN33RR,43.73326,7.43332,"Cat IV",0000-00-00,0000-00-00,"Marine Protected Areas","2023-08-23 by M0YMA - Updated","2023-08-23 by M0YMA - Updated: Lat/Lon, Locator<br>2023-03-13 by OK2APY - Updated: Name, Website, IUCN category, Lat/Lon, Locator, Notes",0,,https://www.protectedplanet.net/306190,Monaco,-,260,,
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
echo "Syncing WWFF ... ";
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
$datovy_soubor_cesta = dirname(__FILE__) ."/data_files/wwff_directory.csv";

// download actual .csv file from wwff.co
download_file("https://wwff.co/wwff-data/wwff_directory.csv", $datovy_soubor_cesta);

echo "csv file donwloaded ... importing to db now ... wait ... ";
flush();

require 'settings/db_credentials.php';
$mysqli = new mysqli($host, $user, $pass, $db, $port);   // mysql db credentials
$mysqli->set_charset($charset);
$mysqli->query("SET collation_connection = $collation");

// odstraneni predesle _pre tabulky pokud existuje
$mysqli->query(" DROP TABLE IF EXISTS `wwff_area_pre`; ");

// založení nové tabulky
$mysqli->query(" CREATE TABLE IF NOT EXISTS wwff_area_pre LIKE wwff_area ");

$pocet = 0;
$pocetvyrazenych = 0;

$file = fopen($datovy_soubor_cesta, "r");
fgets($file); // preskoceni jednoho radku v nactenem CSV souboru
while (($row = fgetcsv($file)) !== FALSE) {
    $row[24]=intval($row[24]); // prevod na cislo u qsoCount (pocet spojeni)
    if ($row[25]=="") $row[25]="1980-01-01" ;  // doplneni datumu posledni aktivace pokud neni vyplnen vubec
    // pokud je WWFF aktivni tak se naimpoprtuje do db
    if($row[1]=="active") {
        $stmt = $mysqli->prepare("INSERT INTO wwff_area_pre (reference, status, name, program, dxcc, latitude, longitude, qsoCount, lastAct) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssssss", $row[0], $row[1], $row[2], $row[3], $row[4], $row[10], $row[11], $row[24], $row[25]);
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
$mysqli->query(" TRUNCATE TABLE wwff_area; ");
$mysqli->query(" INSERT INTO wwff_area SELECT * FROM wwff_area_pre; ");
$mysqli->query(" DROP TABLE IF EXISTS `wwff_area_pre`; ");

echo " ... done!<br /><br /><br />";
echo '<a href="index.php">... go to main page ...</a></center>';

$mysqli->close();
