#!/usr/bin/expect

set UserName [lindex $argv 0]; # Grab the first command line parameter

set timeout -1

spawn userdel $UserName

expect eof
