<?php
ini_set("display_errors", true);
date_default_timezone_set("Europe/London");

# Define tmp directory
$dir = dirname(__FILE__).'/tmp';
# Scan all files in directory into array while removing . and .. directories and global ping file
# We know only ping-files are in this directory, excluding more not necessary
$files = array_diff(scandir($dir), array('..', '.', 'ping.log.'));

# Create arrays per file generated by ping.sh
foreach($files as $filename) {
    # Strip ping.log from filename to retain only IP or hostname
    $name=str_replace("ping.log.","",$filename);

    # Loading temp file data, removing EOL character and skipping empty lines
    $tmpfiledata=file($dir."/".$filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    # Looping over tmpfiledata and adding the time and the status to general ping array
    # TODO: pingdata should be changed later to use single file with data points date-time, IP, hostname, pingresult separated by character like pipe
    foreach($tmpfiledata as $row) {
        $time=substr($row,0,5);
        $result=substr($row,6,6);
        $pingarray[$name][$time]=$result;
    }
}

$auto_refresh=1;

# Build time header array as we only look at the last 30 minutes
$timearray = array();
for($i=30;$i>0;$i--) {
    $timearray[] = date("H:i",time()-($i*60));
}

# We don't have the full hostname/IP details, using the filenames instead
function build_table ($title="Table", $servers) {
    # $servers is array to be used for this table in the order required, this /could/ include both IP and hostname, but better in pingarray itself
    # $title is the table title

    # import timearray and pingarray into this function
    global $timearray, $pingarray, $failurecount;

    $output = "<table border=\"2\">\n<caption><b>".htmlspecialchars($title)."</b></caption>\n";
    $output.= "<tr>";
    $output.= "<th style=\"min-width:220px\" width=\"220\">IP/Hostname</th>";
    # Loop over timearray to generate header
    foreach($timearray as $time) {
        # break up time into two rows
        $output.="<th><small>".str_replace(":","<br>",$time)."</small></th>";
    }
    #$output.= "<th>Status</th>";
    $output.= "</tr>\n";

    # Loop over servers array to build the actual table rows
    foreach($servers as $hostname) {
        $output.= "<tr>";
        $output.= "<td>".htmlspecialchars($hostname)."</td>";
        foreach($timearray as $time) {
            #Output the ping result of each corresponding header time if both the server exists in pingarray and the time as well
            if(array_key_exists($hostname, $pingarray) && array_key_exists($time, $pingarray[$hostname])) {
                if($pingarray[$hostname][$time] == "&#9989") {
                    $output.= "<td style=\"background-color:green;\">&nbsp;</td>";
                } else {
                    $output.= "<td style=\"background-color:red;\">&nbsp;</td>";
                    $failurecount++;
                }
            } else {
                $output.= "<td>&nbsp;</td>";
            }
        }
        #Status Column
        #Isn't this redundant as we have the ping results anyway and can refer to the last entry again?
        #if(ping($hostname)=='on') {
        #    $output.= "<td style=\"background-color:green\">ON</td>";
        #} else {
        #    $output.= "<td style=\"background-color:red\">OFF</td>";
        #}

        $output.="</tr>\n";
    }

    $output.= "</table>\n";

    return $output;
}

flush();

// function for status column - pings all hosts
function ping($host) {
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        //echo 'This is a server using Windows!';
        exec(sprintf('ping -n 1 -w 5 %s', escapeshellarg($host)), $res, $rval);
    } else {
        //echo 'This is a server not using Windows!';
        exec(sprintf('ping -c 1 -W 1 %s', escapeshellarg($host)), $res, $rval);
    }
    return $rval === 0;
}


//Prepare page tables and count failures
$failurecount=0;
$pagetables = build_table("Virtual Servers",array("192.168.99.101","192.168.99.102","192.168.99.103","192.168.99.104","192.168.99.105","192.168.99.106","192.168.99.107","192.168.99.108"));
$pagetables.= "<form action=\"\" method=\"post\">";
$pagetables.= "<button type=\"submit\" name=\"button\">Maintenance Mode</button>";
$pagetables.= "</form>";
$pagetables.= build_table("ESXi Hosts",array("192.168.99.5","192.168.99.6","192.168.99.7"));
$pagetables.= build_table("iDRAC",array("192.168.99.31","192.168.99.32","192.168.99.33"));
$pagetables.= build_table("External Servers",array("old.amatiglobal.com","h2553227.stratoserver.net","h2553231.stratoserver.net","apps3.amatiglobal.com"));

// html

echo "<!DOCTYPE html>\n";
echo "<html>\n";
echo "<head>\n";
if($failurecount>0) {
    echo "<title>&#9940; $failurecount - Server Monitor</title>\n";
} else {
    echo "<title>Server Monitor</title>\n";
}
if(isset($auto_refresh) && $auto_refresh==1) {
    echo "<meta http-equiv=\"refresh\" content=\"60;url=".$_SERVER['PHP_SELF']."\" />\n";
}
echo "</head>\n";
echo "<body>\n";
echo "<h2>Server Monitor</h2>";
echo "<p>The below tables show the last 30 ping attempts to each of the servers (one ping per minute).</p>";
echo "<p>This page auto refreshes once per minute. Last update: ".date("H:i:s")."</p>";
if (isset($_POST['button'])) { shell_exec("./mainMode.sh"); }
echo $pagetables;
echo "</body>\n";
echo "</html>\n";
?>

