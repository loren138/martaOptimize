<?php

$handle = fopen("all.csv", "r");
    $first = fgets($handle);
for ($i = 0; $i < 32; $i++) {
	$dayFiles[$i] = fopen("train".$i.".csv", "w");
        fwrite($dayFiles[$i], $first);
}
$count = 0;
if ($handle) {
    while (($line = fgets($handle)) !== false) {
         $count++;
         $day = intval(substr($line, 9, 2),10);
         fwrite($dayFiles[$day], $line);
         if ($count % 10 == 0) {
             echo "Line $count \n";
         }
    }

    fclose($handle);
} else {
    // error opening the file.
} 

foreach ($dayFiles as $d) {
	fclose($d);
}
?>
