#!/usr/bin/perl
use Socket;

# set this to the hostname where Eventum is setup
my $domain = '';
# set this to the script that is used to record new check-ins
my $ping_url = '/eventum/cvs_ping.php';

# load up the commit message (we use it more than once)
my @commit;
my $last_message_seen = 0;
# files will be saved here
my $params;
my $filename;
my $old_version;
my $new_version;

# remove the arguments
my $username = shift @ARGV;
my $argument = shift @ARGV;
my @pieces = split ' ', $argument;
my $module_name = shift @pieces;
while (my $piece = shift @pieces) {
    @params = split ',', $piece;
    $filename = $params[0];
    $filename =~ s/\s/\//i;
    $old_version = $params[1];
    $new_version = $params[2];
    push(@files, $filename);
    push(@old_versions, $old_version);
    push(@new_versions, $new_version);
}

while (<>) {
    if (/^log message:$/i) {
        $log_message_seen = 1;
    } elsif ($log_message_seen) {
        push (@commit, $_);
    }
}

my $commit_msg = join('\n', @commit);

# this pattern is used to match issue numbers in the commit message
my $pattern = '\((((issue)|(bug))\s*:?)\s*(.*)\)';
my @issues;

# get closed bug numbers
foreach my $line (@commit) {
    if ($line =~ m/$pattern/ig) {
        $line =~ s/^.*$pattern.*$/$5/ig;
        $line =~ s/^(issue)|(bug):?\s*//ig;
        my @tmp = split(/,/, $line);
        foreach my $item (@tmp) {
            chomp $item;
            $item =~ s/[\#\s]//g;
            push (@issues, $item);
        }
    }
}

# deep magic, but perl in a nutshell said it's ok ;)
$total = @issues;

# quit if there are no issues to log in this commit message
if ($total <= 0) {
    exit 0;
}

socket(SH, PF_INET, SOCK_STREAM, getprotobyname('tcp')) || die $!;
my $dest = sockaddr_in(80, inet_aton($domain));
connect(SH, $dest) || die $!;

# build the url
$username = encode_base64($username);
chomp($username);
$module_name = encode_base64($module_name);
chomp($module_name);
$commit_msg = encode_base64($commit_msg);
chomp($commit_msg);
$ping_url = $ping_url . "?module=$module_name&username=$username&commit_msg=$commit_msg";

$i = 0;
foreach my $file (@files) {
    $file = encode_base64($file);
    chomp($file);
    $ping_url = $ping_url . "&files[$i]=" . $file;
    $i++;
}
$i = 0;
foreach my $old_version (@old_versions) {
    $old_version = encode_base64($old_version);
    chomp($old_version);
    $ping_url = $ping_url . "&old_versions[$i]=" . $old_version;
    $i++;
}
$i = 0;
foreach my $new_version (@new_versions) {
    $new_version = encode_base64($new_version);
    chomp($new_version);
    $ping_url = $ping_url . "&new_versions[$i]=" . $new_version;
    $i++;
}
foreach my $item (@issues) {
    $item = encode_base64($item);
    chomp($item);
    $ping_url = $ping_url . "&issue[]=" . $item;
}

my $data = "GET $ping_url HTTP/1.1
Host: $domain

";
print SH $data;

close(SH);


# the next code was stolen from MIME::Base64 to make 
# this script portable across perl versions
use integer;

sub encode_base64 ($;$)
{
    my $res = "";
    my $eol = $_[1];
    $eol = "\n" unless defined $eol;
    pos($_[0]) = 0;                          # ensure start at the beginning

    $res = join '', map( pack('u',$_)=~ /^.(\S*)/, ($_[0]=~/(.{1,45})/gs));

    $res =~ tr|` -_|AA-Za-z0-9+/|;               # `# help emacs
    # fix padding at the end
    my $padding = (3 - length($_[0]) % 3) % 3;
    $res =~ s/.{$padding}$/'=' x $padding/e if $padding;
    # break encoded string into lines of no more than 76 characters each
    #if (length $eol) {
    #    $res =~ s/(.{1,76})/$1$eol/g;
    #}
    return $res;
}

exit 0;
