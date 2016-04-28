#
class ntp::config inherits ntp {

  if $ntp::keys_enable {
    $directory = ntp_dirname($ntp::keys_file)
    file { $directory:
      ensure => directory,
      owner  => 0,
      group  => 0,
      mode   => '0755',
    }
  }

  file { $ntp::config:
    ensure  => file,
    owner   => 0,
    group   => 0,
    mode    => '0644',
    content => template($ntp::config_template),
  }

  if $ntp::logfile {
    file { $ntp::logfile:
      ensure => 'file',
      owner  => 'ntp',
      group  => 'ntp',
      mode   => '0664',
    }
  }

}
