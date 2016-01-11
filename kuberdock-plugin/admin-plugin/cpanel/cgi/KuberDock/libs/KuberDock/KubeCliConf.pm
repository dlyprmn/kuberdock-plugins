package KuberDock::KubeCliConf;

use strict;
use warnings FATAL => 'all';
use Template;
use Data::Dumper;

use constant KUBE_CLI_CONF_ROOT_FILE => '/root/.kubecli.conf';
use constant KUBE_CLI_CONF_ETC_FILE => '/etc/kubecli.conf';
use constant KUBERDOCK_TEMPLATE_PATH => '/usr/local/cpanel/whostmgr/docroot/cgi/KuberDock/templates';

sub new {
    my $class = shift;
    my $self = {
    };

    return bless $self, $class;
}

# we take login & password from /root/.kubecli.conf
# and url & registry - from /etc/kubecli.conf
sub read {
    my ($self) = @_;

    my $contentRoot = $self->readFile(KUBE_CLI_CONF_ROOT_FILE);
    my $contentEtc = $self->readFile(KUBE_CLI_CONF_ETC_FILE);

    my $data = {
        url => $self->getKey($contentEtc, 'url'),
        registry => $self->getKey($contentEtc, 'registry'),
        password => $self->getKey($contentRoot, 'password'),
        user => $self->getKey($contentRoot, 'user'),
    };

    return $data;
}

sub save {
    my ($self, $data) = @_;

    Template->new({
        INCLUDE_PATH => KUBERDOCK_TEMPLATE_PATH,
        INTERPOLATE  => 1,
        OUTPUT => KUBE_CLI_CONF_ROOT_FILE,
    })->process('kubecli/template_root.tmpl', $data);

    Template->new({
        INCLUDE_PATH => KUBERDOCK_TEMPLATE_PATH,
        INTERPOLATE  => 1,
        OUTPUT => KUBE_CLI_CONF_ETC_FILE,
    })->process('kubecli/template_etc.tmpl', $data);
}

sub getKey {
    my ($self, $content, $key) = @_;

    my ($string) = $content =~ /\r?\n$key = ([\w\d:\/\.]+)\r?\n/;

    return $string;
}

sub readFile {
    my ($self, $file) = @_;

    if(!-e $file) {
        print 'File not exists.';
        return;
    }

    my $data;
    {
        local $/;
        open my $fh, '<', $file || die 'File not founded.';
        $data = <$fh>;
        close $fh;
    }

    return $data;
}

1;