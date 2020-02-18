#!/bin/bash
IPS="192.168.99.101 192.168.99.102 192.168.99.103 192.168.99.104 192.168.99.105 192.168.99.106 192.168.99.107 192.168.99.108"
LOG=/var/www/html/tmp/ping.log
TIMESTAMP=$(date '+%H:%M')
MAXLINES=29
NUMBERLINES=0

for IP in $IPS; do
	NUMBERLINES=$(wc -l $LOG.$IP | awk '{ print $1 }')
	echo $NUMBERLINES
	echo $MAXLINES
	if (( $NUMBERLINES > $MAXLINES )); then
		sed -i 1d $LOG.$IP
	fi
done

for IP in $IPS; do
	echo "$IP-UP!" >> $LOG.$ip
done

for IP in $IPS; do
	ping -c 1 -i 0.5 $IP >/dev/null
	if [ "$?"  -ne 0 ]; then
		STATUS=$(cat $LOG.$IP)
			if  [[ $STATUS =~ "&#10060" ]]; then
				echo "`date`: ping failed. $IP host is down!"
			fi
		echo "$TIMESTAMP &#10060" >> $LOG.$IP
	else
		STATUS=$(cat $LOG.$IP)
			if [[ $STATUS =~ "&#9989" ]]; then
				echo "`date`: ping OK, $IP host is up!"
			fi
		echo "$TIMESTAMP &#9989" >> $LOG.$IP
	fi
done
