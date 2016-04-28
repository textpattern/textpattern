# = Class: redis::preinstall
#
# This class provides anything required by the install class.
# Such as package repositories.
#
class redis::preinstall {
  if $::redis::manage_repo {
    case $::operatingsystem {
      'RedHat', 'CentOS', 'Scientific', 'OEL': {
        if $::operatingsystemmajrelease < '7' {
          $rpm_url = $::operatingsystemmajrelease ? {
            '5'    => "http://download.powerstack.org/5/${::architecture}/",
            '6'    => "http://download.powerstack.org/6/${::architecture}/",
            default => Fail['Operating system or release not supported.'],
          }

          $rpm_gpgkey = $::operatingsystemmajrelease ? {
            '5'    => 'https://raw.githubusercontent.com/santisaez/powerstack/master/RPM-GPG-KEY-powerstack',
            '6'    => 'https://raw.githubusercontent.com/santisaez/powerstack/master/RPM-GPG-KEY-powerstack',
            default => Fail['Operating system or release not supported.'],
          }

          yumrepo { 'powerstack':
            descr    => 'PowerStack for CentOS',
            baseurl  => $rpm_url,
            gpgkey   => $rpm_gpgkey,
            enabled  => 1,
            gpgcheck => 1;
          }
        }

        if $::operatingsystemmajrelease == '7' {
          require ::epel
        }
      }

      'Amazon': {
        $rpm_url = $::operatingsystemmajrelease ? {
          '3'    => "http://download.powerstack.org/6/${::architecture}/",
          default => Fail['Operating system or release version not supported.'],
        }

        $rpm_gpgkey = $::operatingsystemmajrelease ? {
          '3'    => 'https://raw.githubusercontent.com/santisaez/powerstack/master/RPM-GPG-KEY-powerstack',
          default => Fail['Operating system or release version not supported.'],
        }

        yumrepo { 'powerstack':
          descr    => 'PowerStack for CentOS',
          baseurl  => $rpm_url,
          gpgkey   => $rpm_gpgkey,
          enabled  => 1,
          gpgcheck => 1;
        }
      }

      'Debian': {
        include apt
        apt::key { 'dotdeb':
          id      => '89DF5277',
          content => 'http://www.dotdeb.org/dotdeb.gpg',
        }

        apt::source { 'dotdeb':
          location => 'http://packages.dotdeb.org',
          release  => $::lsbdistcodename,
          repos    => 'all',
          require  => Apt::Key['dotdeb'],
        }
      }

      'Ubuntu': {
        include apt
        apt::ppa { $::redis::ppa_repo: }
      }

      default: {
      }
    }
  }
}

