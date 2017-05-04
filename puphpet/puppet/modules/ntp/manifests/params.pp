class ntp::params {

  $autoupdate        = false
  $config_template   = 'ntp/ntp.conf.erb'
  $keys_enable       = false
  $keys_controlkey   = ''
  $keys_requestkey   = ''
  $keys_trusted      = []
  $logfile           = undef
  $minpoll           = undef
  $leapfile          = undef
  $package_ensure    = 'present'
  $peers             = []
  $preferred_servers = []
  $service_enable    = true
  $service_ensure    = 'running'
  $service_manage    = true
  $stepout           = undef
  $udlc              = false
  $udlc_stratum      = '10'
  $interfaces        = []
  $disable_auth      = false
  $disable_monitor   = true
  $broadcastclient   = false

  # Allow a list of fudge options
  $fudge             = []

  $default_config       = '/etc/ntp.conf'
  $default_keys_file    = '/etc/ntp/keys'
  $default_driftfile    = '/var/lib/ntp/drift'
  $default_package_name = ['ntp']
  $default_service_name = 'ntpd'

  $package_manage = $::osfamily ? {
    'FreeBSD' => false,
    default   => true,
  }

  if str2bool($::is_virtual) {
    $tinker = true
    $panic  = 0
  }
  else {
    $tinker = false
    $panic  = undef
  }

  case $::osfamily {
    'AIX': {
      $config          = $default_config
      $keys_file       = '/etc/ntp.keys'
      $driftfile       = '/etc/ntp.drift'
      $package_name    = [ 'bos.net.tcp.client' ]
      $restrict        = [
        'default nomodify notrap nopeer noquery',
        '127.0.0.1',
      ]
      $service_name    = 'xntpd'
      $iburst_enable   = true
      $servers         = [
        '0.debian.pool.ntp.org',
        '1.debian.pool.ntp.org',
        '2.debian.pool.ntp.org',
        '3.debian.pool.ntp.org',
      ]
      $maxpoll         = undef
    }
    'Debian': {
      $config          = $default_config
      $keys_file       = $default_keys_file
      $driftfile       = $default_driftfile
      $package_name    = $default_package_name
      $restrict        = [
        '-4 default kod nomodify notrap nopeer noquery',
        '-6 default kod nomodify notrap nopeer noquery',
        '127.0.0.1',
        '::1',
      ]
      $service_name    = 'ntp'
      $iburst_enable   = true
      $servers         = [
        '0.debian.pool.ntp.org',
        '1.debian.pool.ntp.org',
        '2.debian.pool.ntp.org',
        '3.debian.pool.ntp.org',
      ]
      $maxpoll         = undef
    }
    'RedHat': {
      $config          = $default_config
      $keys_file       = $default_keys_file
      $driftfile       = $default_driftfile
      $package_name    = $default_package_name
      $service_name    = $default_service_name
      $maxpoll         = undef

      case $::operatingsystem {
        'Fedora': {
          $restrict        = [
            'default nomodify notrap nopeer noquery',
            '127.0.0.1',
            '::1',
          ]
          $iburst_enable   = true
          $servers         = [
            '0.fedora.pool.ntp.org',
            '1.fedora.pool.ntp.org',
            '2.fedora.pool.ntp.org',
            '3.fedora.pool.ntp.org',
          ]
        }
        default: {
          $restrict        = [
            'default kod nomodify notrap nopeer noquery',
            '-6 default kod nomodify notrap nopeer noquery',
            '127.0.0.1',
            '-6 ::1',
          ]
          $iburst_enable   = false
          $servers         = [
            '0.centos.pool.ntp.org',
            '1.centos.pool.ntp.org',
            '2.centos.pool.ntp.org',
          ]
        }
      }
    }
    'Suse': {
      if $::operatingsystem == 'SLES' {
        case $::operatingsystemmajrelease {
          '10': {
            $service_name  = 'ntp'
            $keys_file     = '/etc/ntp.keys'
            $package_name  = [ 'xntp' ]
          }
          '11': {
            $service_name  = 'ntp'
            $keys_file     = $default_keys_file
            $package_name  = $default_package_name
          }
          '12': {
            $service_name  = 'ntpd'
            $keys_file     = '/etc/ntp.keys'
            $package_name  = $default_package_name
          }
          default: {
            fail("The ${module_name} module is not supported on an ${::operatingsystem} ${::operatingsystemmajrelease} distribution.")
          }
        }
      } else {
        $service_name  = 'ntp'
        $keys_file     = $default_keys_file
        $package_name  = $default_package_name
      }
      $config          = $default_config
      $driftfile       = '/var/lib/ntp/drift/ntp.drift'
      $restrict        = [
        'default kod nomodify notrap nopeer noquery',
        '-6 default kod nomodify notrap nopeer noquery',
        '127.0.0.1',
        '-6 ::1',
      ]
      $iburst_enable   = false
      $servers         = [
        '0.opensuse.pool.ntp.org',
        '1.opensuse.pool.ntp.org',
        '2.opensuse.pool.ntp.org',
        '3.opensuse.pool.ntp.org',
      ]
      $maxpoll         = undef
    }
    'FreeBSD': {
      $config          = $default_config
      $driftfile       = '/var/db/ntpd.drift'
      $keys_file       = $default_keys_file
      $package_name    = ['net/ntp']
      $restrict        = [
        'default kod nomodify notrap nopeer noquery',
        '-6 default kod nomodify notrap nopeer noquery',
        '127.0.0.1',
        '-6 ::1',
      ]
      $service_name    = $default_service_name
      $iburst_enable   = true
      $servers         = [
        '0.freebsd.pool.ntp.org',
        '1.freebsd.pool.ntp.org',
        '2.freebsd.pool.ntp.org',
        '3.freebsd.pool.ntp.org',
      ]
      $maxpoll         = 9
    }
    'Archlinux': {
      $config          = $default_config
      $keys_file       = $default_keys_file
      $driftfile       = '/var/lib/ntp/ntp.drift'
      $package_name    = $default_package_name
      $service_name    = $default_service_name
      $restrict        = [
        'default kod nomodify notrap nopeer noquery',
        '-6 default kod nomodify notrap nopeer noquery',
        '127.0.0.1',
        '-6 ::1',
      ]
      $iburst_enable   = false
      $servers         = [
        '0.arch.pool.ntp.org',
        '1.arch.pool.ntp.org',
        '2.arch.pool.ntp.org',
        '3.arch.pool.ntp.org',
      ]
      $maxpoll         = undef
    }
    'Solaris': {
      $config        = '/etc/inet/ntp.conf'
      $driftfile     = '/var/ntp/ntp.drift'
      $keys_file     = '/etc/inet/ntp.keys'
      if $::operatingsystemrelease =~ /^(5\.10|10|10_u\d+)$/
      {
        # Solaris 10
        $package_name = [ 'SUNWntpr', 'SUNWntpu' ]
        $restrict     = [
          'default nomodify notrap nopeer noquery',
          '127.0.0.1',
        ]
      } else {
        # Solaris 11...
        $package_name = [ 'service/network/ntp' ]
        $restrict     = [
          'default kod nomodify notrap nopeer noquery',
          '-6 default kod nomodify notrap nopeer noquery',
          '127.0.0.1',
          '-6 ::1',
        ]
      }
      $service_name  = 'network/ntp'
      $iburst_enable = false
      $servers       = [
        '0.pool.ntp.org',
        '1.pool.ntp.org',
        '2.pool.ntp.org',
        '3.pool.ntp.org',
      ]
      $maxpoll       = undef
    }
  # Gentoo was added as its own $::osfamily in Facter 1.7.0
    'Gentoo': {
      $config          = $default_config
      $keys_file       = $default_keys_file
      $driftfile       = $default_driftfile
      $package_name    = ['net-misc/ntp']
      $service_name    = $default_service_name
      $restrict        = [
        'default kod nomodify notrap nopeer noquery',
        '-6 default kod nomodify notrap nopeer noquery',
        '127.0.0.1',
        '-6 ::1',
      ]
      $iburst_enable   = false
      $servers         = [
        '0.gentoo.pool.ntp.org',
        '1.gentoo.pool.ntp.org',
        '2.gentoo.pool.ntp.org',
        '3.gentoo.pool.ntp.org',
      ]
      $maxpoll         = undef
    }
    'Linux': {
    # Account for distributions that don't have $::osfamily specific settings.
    # Before Facter 1.7.0 Gentoo did not have its own $::osfamily
      case $::operatingsystem {
        'Gentoo': {
          $config          = $default_config
          $keys_file       = $default_keys_file
          $driftfile       = $default_driftfile
          $service_name    = $default_service_name
          $package_name    = ['net-misc/ntp']
          $restrict        = [
            'default kod nomodify notrap nopeer noquery',
            '-6 default kod nomodify notrap nopeer noquery',
            '127.0.0.1',
            '-6 ::1',
          ]
          $iburst_enable   = false
          $servers         = [
            '0.gentoo.pool.ntp.org',
            '1.gentoo.pool.ntp.org',
            '2.gentoo.pool.ntp.org',
            '3.gentoo.pool.ntp.org',
          ]
          $maxpoll         = undef
        }
        default: {
          fail("The ${module_name} module is not supported on an ${::operatingsystem} distribution.")
        }
      }
    }
    default: {
      fail("The ${module_name} module is not supported on an ${::osfamily} based system.")
    }
  }
}
