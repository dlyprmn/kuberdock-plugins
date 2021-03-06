package KuberDock::API;

use strict;
use warnings FATAL => 'all';

use LWP::UserAgent;
use Config::Tiny;
use Net::SSL;

use KuberDock::KCLI;
use KuberDock::JSON;
use Data::Dumper;

sub new {
    my $class = shift;
    my $self = {
        _server => shift || undef,
        _username => shift || undef,
        _password => shift || undef,
    };

    my $config = Config::Tiny->read(KuberDock::KCLI::getConfPath());
    $self->{_server} = $self->{_server} || $config->{global}->{url};
    $self->{_username} = $self->{_username} || $config->{defaults}->{user};
    $self->{_password} = $self->{_password} || $config->{defaults}->{password};
    $self->{_token} = $config->{defaults}->{token};

    return bless $self, $class;
}

sub validateYaml {
    my ($self, $template) = @_;
    my $json = KuberDock::JSON->new;

    return $self->request('/api/predefined-apps/validate-template', 'POST', $json->encode({template => $template}));
}

sub installTemplate {
    my ($self, $template_id) = @_;

    return $self->request('/api/predefined-apps/' . $template_id, 'PUT', '{"search_available":true}');
}

sub uninstallTemplate {
    my ($self, $template_id) = @_;

    return $self->request('/api/predefined-apps/' . $template_id, 'PUT', '{"search_available":false}');
}

sub getPackages {
    my ($self) = @_;

    return $self->request('/api/pricing/packages', 'GET');
}

sub getPackageKubes {
    my ($self, $packageId) = @_;

    return $self->request('/api/pricing/packages/' . $packageId . '/kubes', 'GET');
}

sub getPackageKubesById {
    my ($self, $packageId) = @_;

    return $self->request('/api/pricing/packages/' . $packageId . '/kubes-by-id', 'GET');
}

sub getPackagesKubes {
    my ($self) = @_;
    my @data = ();
    my $packages;

    $packages = $self->getPackages();

    foreach(@{$packages}) {
        $_->{kubes} = $self->getPackageKubes($_->{id}) || [];
        push(@data, $_);
    }

    return [@data];
}

sub getDefaults {
    my ($self) = @_;

    return {
        kubeType => $self->request('/api/pricing/kubes/default', 'GET')->{id},
        packageId => $self->request('/api/pricing/packages/default', 'GET')->{id},
    };
}

sub setDefaults {
    my ($self, $data) = @_;

    $self->request('/api/pricing/kubes/' . $data->{kubeType}, 'PUT', '{"is_default":"1"}');
    $self->request('/api/pricing/packages/' . $data->{packageId}, 'PUT', '{"is_default":"1"}');
}

sub createUser() {
    my ($self, $data) = @_;

    return $self->request('/api/users/all', 'POST', $data);
}

sub updateUser() {
    my ($self, $username) = @_;

    return $self->request('/api/users/all/' . $username, 'PUT');
}

sub getUser() {
    my ($self, $username) = @_;

    return $self->request('/api/users/all/' . $username, 'GET');
}

sub undeleteUser() {
    my ($self, $username) = @_;

    return $self->request('/api/users/undelete/' . $username, 'POST');
}

sub updatePod() {
    my ($self, $data) = @_;
    my $json = KuberDock::JSON->new;
    my $decoded = $json->decode($data);

    return $self->request('/api/podapi/' . $decoded->{id}, 'PUT', $data);
}

sub getToken() {
    my ($self) = @_;

    return $self->request('/api/auth/token', 'GET', {}, 1);
}

sub request {
    my ($self, $url, $requestType, $data, $plain) = @_;
    my $agent = LWP::UserAgent->new(ssl_opts => { verify_hostname => 0 },);
    $plain = defined $plain ? $plain : 0;
    my $endpoint = $self->{_server} . $url;

    if(!grep {$_ eq $requestType} ('GET', 'POST', 'PUT')) {
        die 'Unknown request type';
    }

    if ($self->{_token} && !$plain) {
        $endpoint .= '?token=' . $self->{_token};
    }

    my $req = HTTP::Request->new($requestType => $endpoint);

    if(!grep {$_ eq $requestType} ('GET')) {
        $req->header('content-type' => 'application/json');
    }

    if (!$self->{_token} || $plain) {
        $req->authorization_basic( $self->{_username}, $self->{_password} );
    }

    if(grep {$_ eq $requestType} ('POST', 'PUT')) {
        $req->content($data);
    }

    my $resp = $agent->request($req);

    if($resp->is_success) {
        return $self->getData($resp->decoded_content);
    } elsif($resp->{_rc} eq '400') {
        my $response->{status} = 'ERROR';
        $response->{message} = $resp->decoded_content;
        return $response;
    } else {
        die 'Cannot connect to KuberDock server';
    }
}

sub getData {
    my ($self, $data) = @_;
    my $json = KuberDock::JSON->new;
    my $decoded = $json->decode($data);

    if($decoded->{status} eq 'OK' && defined $decoded->{data} || defined $decoded->{token}) {
        return $decoded->{data} || $decoded->{token};
    } elsif($decoded->{status} eq 'error' && defined $decoded->{data}) {
        return $decoded->{data};
    } elsif(defined $decoded->{message}) {
        die $decoded->{message};
    }
}

1;