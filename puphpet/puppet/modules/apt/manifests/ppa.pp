# ppa.pp
define apt::ppa(
  $ensure         = 'present',
  $options        = $::apt::ppa_options,
  $release        = $::apt::xfacts['lsbdistcodename'],
  $package_name   = $::apt::ppa_package,
  $package_manage = false,
) {
  unless $release {
    fail('lsbdistcodename fact not available: release parameter required')
  }

  if $::apt::xfacts['lsbdistid'] == 'Debian' {
    fail('apt::ppa is not currently supported on Debian.')
  }

  $filename_without_slashes = regsubst($name, '/', '-', 'G')
  $filename_without_dots    = regsubst($filename_without_slashes, '\.', '_', 'G')
  $filename_without_ppa     = regsubst($filename_without_dots, '^ppa:', '', 'G')
  $sources_list_d_filename  = "${filename_without_ppa}-${release}.list"

  if $ensure == 'present' {
    if $package_manage {
      package { $package_name: }

      $_require = [File['sources.list.d'], Package[$package_name]]
    } else {
      $_require = File['sources.list.d']
    }

    $_proxy = $::apt::_proxy
    if $_proxy['host'] {
      if $_proxy['https'] {
        $_proxy_env = ["http_proxy=http://${$_proxy['host']}:${$_proxy['port']}", "https_proxy=https://${$_proxy['host']}:${$_proxy['port']}"]
      } else {
        $_proxy_env = ["http_proxy=http://${$_proxy['host']}:${$_proxy['port']}"]
      }
    } else {
      $_proxy_env = []
    }

    exec { "add-apt-repository-${name}":
      environment => $_proxy_env,
      command     => "/usr/bin/add-apt-repository ${options} ${name}",
      unless      => "/usr/bin/test -s ${::apt::sources_list_d}/${sources_list_d_filename}",
      user        => 'root',
      logoutput   => 'on_failure',
      notify      => Class['apt::update'],
      require     => $_require,
    }

    file { "${::apt::sources_list_d}/${sources_list_d_filename}":
      ensure  => file,
      require => Exec["add-apt-repository-${name}"],
    }
  }
  else {
    file { "${::apt::sources_list_d}/${sources_list_d_filename}":
      ensure => 'absent',
      notify => Class['apt::update'],
    }
  }
}
