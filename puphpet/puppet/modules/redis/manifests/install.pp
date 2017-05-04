# = Class: redis::install
#
# This class installs the application.
#
class redis::install {
  unless defined(Package['$::redis::package_name']) {
    ensure_resource('package', $::redis::package_name, {
      'ensure' => $::redis::package_ensure
    })
  }
}

