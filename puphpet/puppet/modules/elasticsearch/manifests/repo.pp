# == Class: elasticsearch::repo
#
# This class exists to install and manage yum and apt repositories
# that contain elasticsearch official elasticsearch packages
#
#
# === Parameters
#
# This class does not provide any parameters.
#
#
# === Examples
#
# This class may be imported by other classes to use its functionality:
#   class { 'elasticsearch::repo': }
#
# It is not intended to be used directly by external resources like node
# definitions or other modules.
#
#
# === Authors
#
# * Phil Fenstermacher <mailto:phillip.fenstermacher@gmail.com>
# * Richard Pijnenburg <mailto:richard.pijnenburg@elasticsearch.com>
#
class elasticsearch::repo {

  Exec {
    path      => [ '/bin', '/usr/bin', '/usr/local/bin' ],
    cwd       => '/',
  }

  case $::osfamily {
    'Debian': {
      include ::apt
      Class['apt::update'] -> Package[$elasticsearch::package_name]

      apt::source { 'elasticsearch':
        location    => "http://packages.elastic.co/elasticsearch/${elasticsearch::repo_version}/debian",
        release     => 'stable',
        repos       => 'main',
        key         => $::elasticsearch::repo_key_id,
        key_source  => $::elasticsearch::repo_key_source,
        include_src => false,
      }
    }
    'RedHat', 'Linux': {
      yumrepo { 'elasticsearch':
        descr    => 'elasticsearch repo',
        baseurl  => "http://packages.elastic.co/elasticsearch/${elasticsearch::repo_version}/centos",
        gpgcheck => 1,
        gpgkey   => $::elasticsearch::repo_key_source,
        enabled  => 1,
      }
    }
    'Suse': {
      exec { 'elasticsearch_suse_import_gpg':
        command => "rpmkeys --import ${::elasticsearch::repo_key_source}",
        unless  => "test $(rpm -qa gpg-pubkey | grep -i '${::elasticsearch::repo_key_id}' | wc -l) -eq 1 ",
        notify  => [ Zypprepo['elasticsearch'] ],
      }

      zypprepo { 'elasticsearch':
        baseurl     => "http://packages.elastic.co/elasticsearch/${elasticsearch::repo_version}/centos",
        enabled     => 1,
        autorefresh => 1,
        name        => 'elasticsearch',
        gpgcheck    => 1,
        gpgkey      => $::elasticsearch::repo_key_source,
        type        => 'yum',
      }
    }
    default: {
      fail("\"${module_name}\" provides no repository information for OSfamily \"${::osfamily}\"")
    }
  }

  # Package pinning

    case $::osfamily {
      'Debian': {
        include ::apt

        if ($elasticsearch::package_pin == true and $elasticsearch::version != false) {
          apt::pin { $elasticsearch::package_name:
            ensure   => 'present',
            packages => $elasticsearch::package_name,
            version  => $elasticsearch::version,
            priority => 1000,
          }
        }

      }
      'RedHat', 'Linux': {

        if ($elasticsearch::package_pin == true and $elasticsearch::version != false) {
          yum::versionlock { "0:elasticsearch-${elasticsearch::pkg_version}.noarch":
            ensure => 'present',
          }
        }
      }
      default: {
        warning("Unable to pin package for OSfamily \"${::osfamily}\".")
      }
    }
}
