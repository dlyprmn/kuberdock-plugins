package Cpanel::API::KuberDock;

# Cpanel/API/KuberDock.pm

use strict;

use Data::Dumper;
use Cpanel::Wrap;

our $VERSION = '1.0';

sub getAdminData {
    my ($args, $result) = @_;

    my $data = Cpanel::Wrap::send_cpwrapd_request(
        'namespace' => 'KuberDock',
        'module' => 'Module',
        'function' => 'getAdminData',
        'data' => $args->get('data'),
    );

    if($data->{error}) {
        $result->error($data->{data});
    } elsif(defined $data->{data}) {
        $result->data($data->{data});
    }
}

sub createUser {
    my ($args, $result) = @_;

    my $data = Cpanel::Wrap::send_cpwrapd_request(
        'namespace' => 'KuberDock',
        'module' => 'Module',
        'function' => 'createUser',
        'data' => $args->get('data'),
    );

    if($data->{error}) {
        $result->error($data->{data});
    } elsif(defined $data->{data}) {
        $result->data($data->{data});
    }
}

sub updatePod {
    my ($args, $result) = @_;

    my $data = Cpanel::Wrap::send_cpwrapd_request(
        'namespace' => 'KuberDock',
        'module' => 'Module',
        'function' => 'updatePod',
        'data' => $args->get('data'),
    );

    if($data->{error}) {
        $result->error($data->{data});
    } elsif(defined $data->{data}) {
        $result->data($data->{data});
    }
}

1;

__END__
=head1 NAME

Cpanel::API::KuberDock

=head1 DESCRIPTION

UAPI functions related to KuberDock.

=head2 getConfigData

=head3 Purpose

Return a KuberDock plugin config data

=head3 Arguments

  none

=head3 Returns

 {..} JSON

=cut

