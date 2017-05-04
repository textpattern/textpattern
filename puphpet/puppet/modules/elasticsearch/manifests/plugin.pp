# == Define: elasticsearch::plugin
#
# This define allows you to install arbitrary Elasticsearch plugins
# either by using the default repositories or by specifying an URL
#
# All default values are defined in the elasticsearch::params class.
#
#
# === Parameters
#
# [*module_dir*]
#   Directory name where the module will be installed
#   Value type is string
#   Default value: None
#   This variable is deprecated
#
# [*ensure*]
#   Whether the plugin will be installed or removed.
#   Set to 'absent' to ensure a plugin is not installed
#   Value type is string
#   Default value: present
#   This variable is optional
#
# [*url*]
#   Specify an URL where to download the plugin from.
#   Value type is string
#   Default value: None
#   This variable is optional
#
# [*source*]
#   Specify the source of the plugin.
#   This will copy over the plugin to the node and use it for installation.
#   Useful for offline installation
#   Value type is string
#   This variable is optional
#
# [*proxy_host*]
#   Proxy host to use when installing the plugin
#   Value type is string
#   Default value: None
#   This variable is optional
#
# [*proxy_port*]
#   Proxy port to use when installing the plugin
#   Value type is number
#   Default value: None
#   This variable is optional
#
# [*instances*]
#   Specify all the instances related
#   value type is string or array
#
# [*install_options*]
#   Pass options to the plugin installer
#
# === Examples
#
# # From official repository
# elasticsearch::plugin{'mobz/elasticsearch-head': module_dir => 'head'}
#
# # From custom url
# elasticsearch::plugin{ 'elasticsearch-jetty':
#  module_dir => 'elasticsearch-jetty',
#  url        => 'https://oss-es-plugins.s3.amazonaws.com/elasticsearch-jetty/elasticsearch-jetty-0.90.0.zip',
# }
#
# === Authors
#
# * Matteo Sessa <mailto:matteo.sessa@catchoftheday.com.au>
# * Dennis Konert <mailto:dkonert@gmail.com>
# * Richard Pijnenburg <mailto:richard.pijnenburg@elasticsearch.com>
#
define elasticsearch::plugin(
  $instances,
  $module_dir      = undef,
  $ensure          = 'present',
  $url             = undef,
  $source          = undef,
  $proxy_host      = undef,
  $proxy_port      = undef,
  $install_options = undef
) {

  include elasticsearch

  $notify_service = $elasticsearch::restart_on_change ? {
    false   => undef,
    default => Elasticsearch::Service[$instances],
  }

  if ($module_dir != undef) {
    warning("module_dir settings is deprecated for plugin ${name}. The directory is now auto detected.")
  }

  # set proxy by override or parse and use proxy_url from
  # elasticsearch::proxy_url or use no proxy at all
  
  if ($proxy_host != undef and $proxy_port != undef) {
    $proxy = "-DproxyPort=${proxy_port} -DproxyHost=${proxy_host}"
  }
  elsif ($elasticsearch::proxy_url != undef) {
    $proxy_host_from_url = regsubst($elasticsearch::proxy_url, '(http|https)://([^:]+)(|:\d+).+', '\2')
    $proxy_port_from_url = regsubst($elasticsearch::proxy_url, '(?:http|https)://[^:/]+(?::([0-9]+))?(?:/.*)?', '\1')
    
    # validate parsed values before using them
    if (is_string($proxy_host_from_url) and is_integer($proxy_port_from_url)) {
      $proxy = "-DproxyPort=${proxy_port_from_url} -DproxyHost=${proxy_host_from_url}"
    }
  }
  else {
    $proxy = undef
  }

  if ($source != undef) {

    $filenameArray = split($source, '/')
    $basefilename = $filenameArray[-1]

    $file_source = "${elasticsearch::package_dir}/${basefilename}"

    file { $file_source:
      ensure => 'file',
      source => $source,
      before => Elasticsearch_plugin[$name],
    }

  } elsif ($url != undef) {
    validate_string($url)
  }

  case $ensure {
    'installed', 'present': {

      elasticsearch_plugin { $name:
        ensure          => 'present',
        source          => $file_source,
        url             => $url,
        proxy_args      => $proxy,
        plugin_dir      => $::elasticsearch::plugindir,
        install_options => $install_options,
        notify          => $notify_service,
      }

    }
    'absent': {
      elasticsearch_plugin { $name:
        ensure => absent,
      }
    }
    default: {
      fail("${ensure} is not a valid ensure command.")
    }
  }
}
