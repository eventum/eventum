#!/usr/bin/perl -w
# Scan templates and append checksum of each file to the JavaScript file URL.
# Author: Elan Ruusamäe <glen@delfi.ee>

use strict;
use File::Find ();

my %cache;
sub checksum {
	my ($file) = @_;

	return $cache{$file} if exists $cache{$file};

	$file = "htdocs/$file";
	open(my $fh, '<', $file) or die "Can't open $file: $!";
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
		if (my ($tag, $script) = $_ =~ /(<(?:script.+src|link.+rel="stylesheet".+href)="{\$core\.rel_url})([^"]+)/i) {
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
