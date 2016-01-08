#!/bin/bash

echo -n Username:
read username
echo -n Password: 
read -s password
echo
echo -n Seconds between updates:
read seconds
echo

re='^[0-9]+$'
if ! [[ $seconds =~ $re ]] ; then
   echo "error: Not a number" >&2; exit 1
fi

timecount(){
                sec=$seconds
                while [ $sec -ge 0 ]; do
                        echo -ne "00:0$min:$sec\033[0K\r"
                        sec=$((sec-1))
                        sleep 1
                done
}

DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"

cd $DIR

svn info

echo "Updating each $seconds seconds"
last=""
while [ true ]; do
 timecount
 out=$(svn up --username $username --password $password)
 if ! [[ $out == $last ]] ; then
    echo $out" "$(date) 
    last=$out
 fi
 # >> self-update.log 2>&1
done


