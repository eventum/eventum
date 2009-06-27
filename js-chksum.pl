#!/usr/bin/perl -w
# Scan templates and append checksum of each file to the JavaScript file URL.
# Author: Elan Ruusamäe <glen@delfi.ee>

use strict;
use File::Find ();

my %cache;
sub checksum {
	my ($file) = @_;

	return $cache{$file} if exists $cache{$file};

	open(my $fh, '<', 'htdocs/'.$file) or die $!;
	my $checksum = do {
		local $/;  # slurp!
		unpack('%32C*', <$fh>) % 65535;
	};
	close($fh);
	return $cache{$file} = sprintf '%x', $checksum;
}

sub put {
	my ($file, $contents) = @_;
	open(my $fh, '>', $file) or die $!;
	print $fh $contents;
	close($fh);
}

sub process_file {
	my ($file) = @_;

	my @lines;
	open(my $fh, '<', $file) or die $!;
	while (<$fh>) {
		if (my ($tag, $script) = $_ =~ /(<script.*src="{\$rel_url})([^"]+)/i) {
			my ($pre, $post) = ($`, $');
			if ($script !~ /\?/) {
				$_ = $pre. $tag. $script .'?c='.checksum($script). $post;
			}
		}
		push(@lines, $_);
	}
	close($fh);

	put($file, join('', @lines));
}

my @files;
File::Find::find({
	wanted => sub {
    /^.*\.tpl\.html\z/s && push(@files, $File::Find::name);
	}
}, 'templates');

foreach (@files) {
	process_file($_);
}

__END__
 1019  grep -r js/ . 
 1020  clean
 1021  grep -r js/ . 
 1022  grep -ri .script.*js/ . 
 1023  grep -ri script.*js/ . 
 1024  vi `grep -ri script.*js/ .  -l`
 1025  bzr diff|diffcol 
 1026  cleand
 1027  clean
 1028  grep -ri '<script'|grep -v type=
 1029  grep -ri '<script' . |grep -v type=
 1030  vi `grep -ri '<script' . |grep -v type= |cut -d: -f1|sort -u`
 1031  vi `grep --exclude-dir=.bzr -ri '<script' . |grep -v type= |cut -d: -f1|sort -u`
 1032  grep -r openNotification templates
 1033  grep -r script templates
 1034  :
 1035  bzr diff|diffcol 
 1036  bzr ci -m '- unify script tags and use the w3c recommended syntax'
 1037  bzr push
 1038  bzr pull
 1039  l js/
 1040  `grep --exclude-dir=.bzr -ri '<script' . |grep -v type= |cut -d: -f1|sort -u`
 1041  grep --exclude-dir=.bzr -ri '<script' . |grep -v type= 
 1042  clean
 1043  grep --exclude-dir=.bzr -ri '<script' . |grep -v type= 
 1044  vi ./templates/manage/email_alias.tpl.html ./templates/view_form.tpl.html ./templates/post.tpl.html ./templates/removed_emails.tpl.html ./templates/edit_custom_fields.tpl.html 
 1045  clean
 1046  grep --exclude-dir=.bzr -ri '<script' . |grep -v type= 
 1047  vi ./templates/close.tpl.html ./templates/edit_custom_fields.tpl.html 
 1048  clean
 1049  grep --exclude-dir=.bzr -ri '<script' . |grep -v type= 
 1050  bzr diff|diffcol 
 1051  bzr ci -m '- more w3c recommended syntax'
 1052  bzr push
 1053  clean
 1054  grep --exclude-dir=.bzr -ri '<script' .
 1055  grep --exclude-dir=.bzr -ri '<script.*src="[^"]+"' .
 1056  grep --exclude-dir=.bzr -Eri '<script.*src="[^"]+"' .
 1057  grep --exclude-dir=.bzr -Eri '<script.*src="[^"]+"' .|grep -v rel_url
 1058  grep --exclude-dir=.bzr -Eri '<script.*src="[^"]+"' .|grep -v rel_url|cut -d: -f1
 1059  vi `grep --exclude-dir=.bzr -Eri '<script.*src="[^"]+"' .|grep -v rel_url|cut -d: -f1`
 1060  vi /usr/share/php/Smarty/plugins/function.popup_init.php 
 1061  clean
 1062  grep --exclude-dir=.bzr -Eri '<script.*src="[^"]+"' .
 1063  grep --exclude-dir=.bzr -Eri '<script.*src="[^"]+"' .|grep -v rel_url
 1064  bzr diff|diffcol 
 1065  bzr ci -m '- $rel_url in all js scripts'
 1066  grep --exclude-dir=.bzr -Eri '<script.*src="[^"]+"' .
 1067  grep --exclude-dir=.bzr -Eri '<script.*src="{$rel_url}[^"]+"' .
 1068  grep --exclude-dir=.bzr -Eri '<script.*src="{\$rel_url}[^"]+"' .
 1069  md5sum -b js/dynCalendar.js 
 1070  cksum js/dynCalendar.js 
 1071  cksum js/dynCalendar.js |perl -ne '/(\d+)/ and printf "%x\n", $1'
 1072  la js/dynCalendar.js
 1073  cksum js/dynCalendar.js | perl -ne '/(\d+)/ and printf "%x\n", $1'
 1074  h > js-chksum.pl
