class ntp (
  $autoupdate        = $ntp::params::autoupdate,
  $broadcastclient   = $ntp::params::broadcastclient,
  $config            = $ntp::params::config,
  $config_template   = $ntp::params::config_template,
  $disable_auth      = $ntp::params::disable_auth,
  $disable_monitor   = $ntp::params::disable_monitor,
  $fudge             = $ntp::params::fudge,
  $driftfile         = $ntp::params::driftfile,
  $leapfile          = $ntp::params::leapfile,
  $logfile           = $ntp::params::logfile,
  $iburst_enable     = $ntp::params::iburst_enable,
  $keys_enable       = $ntp::params::keys_enable,
  $keys_file         = $ntp::params::keys_file,
  $keys_controlkey   = $ntp::params::keys_controlkey,
  $keys_requestkey   = $ntp::params::keys_requestkey,
  $keys_trusted      = $ntp::params::keys_trusted,
  $minpoll           = $ntp::params::minpoll,
  $maxpoll           = $ntp::params::maxpoll,
  $package_ensure    = $ntp::params::package_ensure,
  $package_manage    = $ntp::params::package_manage,
  $package_name      = $ntp::params::package_name,
  $panic             = $ntp::params::panic,
  $peers             = $ntp::params::peers,
  $preferred_servers = $ntp::params::preferred_servers,
  $restrict          = $ntp::params::restrict,
  $interfaces        = $ntp::params::interfaces,
  $servers           = $ntp::params::servers,
  $service_enable    = $ntp::params::service_enable,
  $service_ensure    = $ntp::params::service_ensure,
  $service_manage    = $ntp::params::service_manage,
  $service_name      = $ntp::params::service_name,
  $stepout           = $ntp::params::stepout,
  $tinker            = $ntp::params::tinker,
  $udlc              = $ntp::params::udlc,
  $udlc_stratum      = $ntp::params::udlc_stratum,
) inherits ntp::params {

  validate_bool($broadcastclient)
  validate_absolute_path($config)
  validate_string($config_template)
  validate_bool($disable_auth)
  validate_bool($disable_monitor)
  validate_absolute_path($driftfile)
  if $logfile { validate_absolute_path($logfile) }
  if $leapfile { validate_absolute_path($leapfile) }
  validate_bool($iburst_enable)
  validate_bool($keys_enable)
  validate_re($keys_controlkey, ['^\d+$', ''])
  validate_re($keys_requestkey, ['^\d+$', ''])
  validate_array($keys_trusted)
  if $minpoll { validate_numeric($minpoll, 16, 3) }
  if $maxpoll { validate_numeric($maxpoll, 16, 3) }
  validate_string($package_ensure)
  validate_bool($package_manage)
  validate_array($package_name)
  if $panic { validate_numeric($panic, 65535, 0) }
  validate_array($preferred_servers)
  validate_array($restrict)
  validate_array($interfaces)
  validate_array($servers)
  validate_array($fudge)
  validate_bool($service_enable)
  validate_string($service_ensure)
  validate_bool($service_manage)
  validate_string($service_name)
  if $stepout { validate_numeric($stepout, 65535, 0) }
  validate_bool($tinker)
  validate_bool($udlc)
  validate_array($peers)

  if $autoupdate {
    notice('autoupdate parameter has been deprecated and replaced with package_ensure.  Set this to latest for the same behavior as autoupdate => true.')
  }

  # Anchor this as per #8040 - this ensures that classes won't float off and
  # mess everything up.  You can read about this at:
  # http://docs.puppetlabs.com/puppet/2.7/reference/lang_containment.html#known-issues
  anchor { 'ntp::begin': } ->
  class { '::ntp::install': } ->
  class { '::ntp::config': } ~>
  class { '::ntp::service': } ->
  anchor { 'ntp::end': }

}
