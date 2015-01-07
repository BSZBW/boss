#!/usr/bin/perl

use strict;
use Data::Dumper;
use Getopt::Long;

sub usage {
	print<<EOD;

usage: vf <option>

option: one of
 start      start Apache
 stop       stop  Apache
 clean      cleanup vufind cache
 clog       cleanup vufind cache and logfiles
 find [arg] find [arg] in project
 lang [arg] find [arg] in languages and local/languages
 sync       synchronize libadmin files and cleanup vufind cache
 help	    print this text and stop
 
EOD
	exit;
}

my $opt = shift @ARGV or usage;

if ( $opt eq 'stop' ) {
	print "stopping Apache\n";
	exec 'sudo service apache2 stop';
}
if ( $opt eq 'start' ) {
	print "starting Apache\n";
	exec 'sudo service apache2 start';
}
if ( $opt eq 'clean' ) {
	print "cleaning up VuFind cache\n";
	exec '/usr/local/vufind/httpd/cli/removeLocalCache_debian.sh';
}
if ( $opt eq 'clog' ) {
	print "cleaning up VuFind cache and log files\n";
	exec '/usr/local/vufind/httpd/cli/removeLocalCache_debian.sh logs';
}
if ( $opt eq 'sync' ) {
	print "synching Libadmin files\n";
	exec '/usr/local/vufind/httpd/cli/sync_debian.sh';
}
if ( $opt eq 'find' ) {
	do_search('.');
}
if ( $opt eq 'lang' ) {
	do_search('languages/en.ini languages/de.ini languages/fr.ini languages/it.ini local/languages');
}
usage;

sub do_search {
	my $dirs = shift;
	my $arg = shift @ARGV or usage;
	my $line = '-' x 30;
	print $line, "\n";
	my $cmd = qq|find $dirs -exec grep '$arg' {} \\; -printf "%p\\n$line\\n"|;
	exec $cmd;
}

