#!/bin/sh

cd /etc/init.d
echo "Stopping everything : "
for i in omk-* 
do
    echo "$i..."
    ./$i stop
done
sleep 2 
echo "Starting everything : "
for i in omk-* 
do
    echo "$i..."
    ./$i start
done
