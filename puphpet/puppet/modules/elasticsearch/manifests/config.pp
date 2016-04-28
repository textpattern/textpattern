# == Class: elasticsearch::config
#
# This class exists to coordinate all configuration related actions,
# functionality and logical units in a central place.
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
#   class { 'elasticsearch::config': }
#
# It is not intended to be used directly by external resources like node
# definitions or other modules.
#
#
# === Authors
#
# * Richard Pijnenburg <mailto:richard.pijnenburg@elasticsearch.com>
#
class elasticsearch::config {

  #### Configuration

  File {
    owner => $elasticsearch::elasticsearch_user,
    group => $elasticsearch::elasticsearch_group,
  }

  Exec {
    path => [ '/bin', '/usr/bin', '/usr/local/bin' ],
    cwd  => '/',
  }

  if ( $elasticsearch::ensure == 'present' ) {

    $notify_service = $elasticsearch::restart_on_change ? {
      true  => Class['elasticsearch::service'],
      false => undef,
    }

    file { $elasticsearch::configdir:
      ensure => directory,
      mode   => '0644',
    }

    file { $elasticsearch::logdir:
      ensure  => 'directory',
      group   => undef,
      mode    => '0644',
      recurse => true,
    }

    file { $elasticsearch::params::homedir:
      ensure  => 'directory',
    }

    file { $elasticsearch::datadir:
      ensure  => 'directory',
    }

    file { "${elasticsearch::homedir}/lib":
      ensure  => 'directory',
      recurse => true,
    }

    if $elasticsearch::params::pid_dir {
      file { $elasticsearch::params::pid_dir:
        ensure  => 'directory',
        group   => undef,
        recurse => true,
      }

      if ($elasticsearch::service_providers == 'systemd') {
        $user = $elasticsearch::elasticsearch_user
        $group = $elasticsearch::elasticsearch_group
        $pid_dir = $elasticsearch::params::pid_dir

        file { '/usr/lib/tmpfiles.d/elasticsearch.conf':
          ensure  => 'file',
          content => template("${module_name}/usr/lib/tmpfiles.d/elasticsearch.conf.erb"),
          owner   => 'root',
          group   => 'root',
        }
      }
    }


    file { "${elasticsearch::params::homedir}/templates_import":
      ensure => 'directory',
      mode   => '0644',
    }

    file { "${elasticsearch::params::homedir}/scripts":
      ensure => 'directory',
      mode   => '0644',
    }

    # Removal of files that are provided with the package which we don't use
    file { '/etc/init.d/elasticsearch':
      ensure => 'absent',
    }
    file { '/lib/systemd/system/elasticsearch.service':
      ensure => 'absent',
    }

    $new_init_defaults = { 'CONF_DIR' => $elasticsearch::configdir }
    if $elasticsearch::params::defaults_location {
      augeas { "${elasticsearch::params::defaults_location}/elasticsearch":
        incl    => "${elasticsearch::params::defaults_location}/elasticsearch",
        lens    => 'Shellvars.lns',
        changes => template("${module_name}/etc/sysconfig/defaults.erb"),
      }
    }

    file { '/etc/elasticsearch/elasticsearch.yml':
      ensure => 'absent',
    }
    file { '/etc/elasticsearch/logging.yml':
      ensure => 'absent',
    }

  } elsif ( $elasticsearch::ensure == 'absent' ) {

    file { $elasticsearch::plugindir:
      ensure => 'absent',
      force  => true,
      backup => false,
    }

  }

}
