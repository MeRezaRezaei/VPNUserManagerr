#!/usr/bin/expect

set UserName [lindex $argv 0]; # Grab the first command line parameter
set Password [lindex $argv 1]; # Grab the first command line parameter

set timeout -1

spawn adduser $UserName --no-create-home --force-badname --gecos GECOS

expect "Enter new UNIX password: "
send -- "$Password\n"

expect "Retype new UNIX password: "
send -- "$Password\n"

expect eof
