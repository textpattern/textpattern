class puphpet_apache (
  $apache,
  $php,
  $hhvm
) {

  include ::puphpet::apache::params
  include ::apache::params

  # this variable is required by apache module templates
  if array_true($apache['settings'], 'version')
    and $apache['settings']['version'] in ['2.2', '22', 2.2, 22]
  {
    $apache_version = '2.2'
  } else {
    $apache_version = '2.4'
  }

  if $apache_version == '2.4' {
    $apache_name = $::osfamily ? {
      'Debian' => $::apache::params::apache_name,
      'Redhat' => 'httpd24u'
    }

    $apache_mod = $::osfamily ? {
      'Debian' => 'mod',
      'Redhat' => 'mod24u'
    }

    class { 'puphpet::apache::repo': }
  } else {
    $apache_name = $::osfamily ? {
      'Debian' => $::apache::params::apache_name,
      'Redhat' => 'httpd'
    }

    $apache_mod = $::osfamily ? {
      'Debian' => 'mod',
      'Redhat' => 'mod'
    }
  }

  $modules = delete($apache['modules'], ['pagespeed', 'ssl'])

  if array_true($php, 'install') and ! ($php['settings']['version'] in ['5.3', '53']) {
    $php_engine    = true
    $php_fcgi_port = '9000'
  } elsif array_true($hhvm, 'install') {
    $php_engine    = true
    $php_fcgi_port = array_true($hhvm['server_ini'], 'hhvm.server.port') ? {
      true    => $hhvm['server_ini']['hhvm.server.port'],
      default => '9000'
    }
  } else {
    $php_engine    = false
  }

  $sethandler = $php_engine ? {
    true    => "proxy:fcgi://127.0.0.1:${php_fcgi_port}",
    default => 'default-handler'
  }

  $www_root      = $puphpet::apache::params::www_root
  $webroot_user  = 'www-data'
  $webroot_group = 'www-data'

  # centos 2.4 installation creates webroot automatically,
  # requiring us to manually set owner and permissions via exec
  exec { 'Create apache webroot':
    command => "mkdir -p ${www_root} && \
                chown root:${webroot_group} ${www_root} && \
                chmod 775 ${www_root} && \
                touch /.puphpet-stuff/apache-webroot-created",
    creates => '/.puphpet-stuff/apache-webroot-created',
    require => [
      Group[$webroot_group],
      Class['apache']
    ],
  }

  $sendfile = array_true($apache['settings'], 'sendfile') ? {
    true    => 'On',
    default => 'Off'
  }

  $settings = delete(merge($apache['settings'], {
    'apache_name'    => $apache_name,
    'default_vhost'  => false,
    'mpm_module'     => 'worker',
    'conf_template'  => $::apache::params::conf_template,
    'sendfile'       => $sendfile,
    'apache_version' => $apache_version
  }), 'version')

  create_resources('class', { 'apache' => $settings })

  if ! defined(Puphpet::Firewall::Port['80']) {
    puphpet::firewall::port { '80': }
  }

  if ! defined(Puphpet::Firewall::Port['443']) {
    puphpet::firewall::port { '443': }
  }

  if $php_engine {
    $default_vhost_directories = {'default' => {
      'provider'        => 'directory',
      'path'            => $puphpet::apache::params::default_vhost_dir,
      'options'         => ['Indexes', 'FollowSymlinks', 'MultiViews'],
      'allow_override'  => ['All'],
      'require'         => ['all granted'],
      'files_match'     => {'php_match' => {
        'provider'   => 'filesmatch',
        'path'       => '\.php$',
        'sethandler' => $sethandler,
      }},
      'custom_fragment' => '',
    }}
  } else {
    $default_vhost_directories = {'default' => {
      'provider'        => 'directory',
      'path'            => $puphpet::apache::params::default_vhost_dir,
      'options'         => ['Indexes', 'FollowSymlinks', 'MultiViews'],
      'allow_override'  => ['All'],
      'require'         => ['all granted'],
      'files_match'     => {},
      'custom_fragment' => '',
    }}
  }

  if array_true($apache['settings'], 'default_vhost') {
    $default_vhosts = {
      'default_vhost_80'  => {
        'servername'    => 'default',
        'docroot'       => $puphpet::apache::params::default_vhost_dir,
        'port'          => 80,
        'directories'   => $default_vhost_directories,
        'default_vhost' => true,
      },
      'default_vhost_443' => {
        'servername'    => 'default',
        'docroot'       => $puphpet::apache::params::default_vhost_dir,
        'port'          => 443,
        'directories'   => $default_vhost_directories,
        'default_vhost' => true,
        'ssl'           => 1,
      },
    }
  } else {
    $default_vhosts = {}
  }

  # config file could contain no vhosts key
  $vhosts_merged = array_true($apache, 'vhosts') ? {
    true    => merge($apache['vhosts'], $default_vhosts),
    default => $default_vhosts,
  }

  each( $vhosts_merged ) |$key, $vhost| {
    exec { "exec mkdir -p ${vhost['docroot']} @ key ${key}":
      command => "mkdir -m 775 -p ${vhost['docroot']}",
      user    => $webroot_user,
      group   => $webroot_group,
      creates => $vhost['docroot'],
      require => Exec['Create apache webroot'],
    }

    $allowed_ciphers = [
      'ECDHE-RSA-AES256-GCM-SHA384', 'ECDHE-RSA-AES128-GCM-SHA256',
      'DHE-RSA-AES256-GCM-SHA384', 'DHE-RSA-AES128-GCM-SHA256',
      'ECDHE-RSA-AES256-SHA384', 'ECDHE-RSA-AES128-SHA256', 'ECDHE-RSA-AES256-SHA',
      'ECDHE-RSA-AES128-SHA', 'DHE-RSA-AES256-SHA256', 'DHE-RSA-AES128-SHA256',
      'DHE-RSA-AES256-SHA', 'DHE-RSA-AES128-SHA', 'ECDHE-RSA-DES-CBC3-SHA',
      'EDH-RSA-DES-CBC3-SHA', 'AES256-GCM-SHA384', 'AES128-GCM-SHA256', 'AES256-SHA256',
      'AES128-SHA256', 'AES256-SHA', 'AES128-SHA', 'DES-CBC3-SHA',
      'HIGH', '!aNULL', '!eNULL', '!EXPORT', '!DES', '!MD5', '!PSK', '!RC4'
    ]

    $ssl = array_true($vhost, 'ssl')
    $ssl_cert = array_true($vhost, 'ssl_cert') ? {
      true    => $vhost['ssl_cert'],
      default => $puphpet::params::ssl_cert_location
    }
    $ssl_key = array_true($vhost, 'ssl_key') ? {
      true    => $vhost['ssl_key'],
      default => $puphpet::params::ssl_key_location
    }
    $ssl_chain = array_true($vhost, 'ssl_chain') ? {
      true    => $vhost['ssl_chain'],
      default => undef
    }
    $ssl_certs_dir = array_true($vhost, 'ssl_certs_dir') ? {
      true    => $vhost['ssl_certs_dir'],
      default => undef
    }
    $ssl_protocol = array_true($vhost, 'ssl_protocol') ? {
      true    => $vhost['ssl_protocol'],
      default => 'TLSv1 TLSv1.1 TLSv1.2',
    }
    $ssl_cipher = array_true($vhost, 'ssl_cipher') ? {
      true    => $vhost['ssl_cipher'],
      default => join($allowed_ciphers, ':'),
    }

    $ssl_cert_real = ($ssl_cert == 'LETSENCRYPT') ? {
      true    => "/etc/letsencrypt/live/${vhost['servername']}/cert.pem",
      default => $ssl_cert,
    }
    $ssl_key_real = ($ssl_key == 'LETSENCRYPT') ? {
      true    => "/etc/letsencrypt/live/${vhost['servername']}/privkey.pem",
      default => $ssl_key,
    }
    $ssl_chain_real = ($ssl_chain == 'LETSENCRYPT') ? {
      true    => "/etc/letsencrypt/live/${vhost['servername']}/chain.pem",
      default => $ssl_chain,
    }
    $ssl_certs_dir_real = ($ssl_certs_dir == 'LETSENCRYPT') ? {
      true    => "/etc/letsencrypt/live/${vhost['servername']}",
      default => $ssl_certs_dir,
    }

    if array_true($vhost, 'directories') {
      $directories_hash   = $vhost['directories']
      $files_match        = template('puphpet/apache/files_match.erb')
      $directories_merged = merge($vhost['directories'], hash_eval($files_match))
    } else {
      $directories_merged = []
    }

    $vhost_custom_fragment = array_true($vhost, 'custom_fragment') ? {
      true    => file($vhost['custom_fragment']),
      default => '',
    }

    $vhost_merged = merge($vhost, {
      'directories'     => values_no_error($directories_merged),
      'ssl'             => $ssl,
      'ssl_cert'        => $ssl_cert_real,
      'ssl_key'         => $ssl_key_real,
      'ssl_chain'       => $ssl_chain_real,
      'ssl_certs_dir'   => $ssl_certs_dir_real,
      'ssl_protocol'    => $ssl_protocol,
      'ssl_cipher'      => "\"${ssl_cipher}\"",
      'custom_fragment' => $vhost_custom_fragment,
      'manage_docroot'  => false
    })

    create_resources(::apache::vhost, { "${key}" => $vhost_merged })

    if ! defined(Puphpet::Firewall::Port["${vhost['port']}"]) {
      puphpet::firewall::port { "${vhost['port']}": }
    }
  }

  if $::osfamily == 'debian' {
    file { '/var/run/apache2/ssl_mutex':
      ensure  => directory,
      group   => $webroot_group,
      mode    => '0775',
      require => Class['apache'],
      notify  => Service['httpd'],
    }
  }

  if ('proxy_fcgi' in $apache['modules']) {
    include puphpet::apache::proxy_fcgi
  }

  # mod_pagespeed needs some extra love
  if 'pagespeed' in $apache['modules'] {
    class { 'puphpet::apache::modpagespeed': }
  }

  # CentOS will be using IUS which has different prefix for packages
  if $::osfamily == 'redhat' and $apache_version == '2.4' {
    # Install all available modules by default
    each( ["${apache_name}-filesystem", "${apache_name}-tools", "${apache_mod}_ldap",
           "${apache_mod}_proxy_html", "${apache_mod}_session"] ) |$package|
    {
      if ! defined(Package[$package]) {
        package { $package:
          ensure  => present,
          require => Class['apache'],
          notify  => Service['httpd'],
        }
      }
    }

    class { 'apache::mod::ssl':
      package_name => "${apache_mod}_ssl",
    }
  }

  each( $modules ) |$module| {
    if ! defined(Apache::Mod[$module]) {
      apache::mod { $module: }
    }
  }

  class { 'puphpet::ssl_cert':
    require => Class['apache'],
    notify  => Service['httpd'],
  }

  $default_vhost_index_file =
    "${puphpet::apache::params::default_vhost_dir}/index.html"

  $default_vhost_source_file =
    '/vagrant/puphpet/puppet/modules/puphpet/files/webserver_landing.html'

  exec { "Set ${default_vhost_index_file} contents":
    command => "cat ${default_vhost_source_file} > ${default_vhost_index_file} && \
                chmod 644 ${default_vhost_index_file} && \
                chown root ${default_vhost_index_file} && \
                chgrp ${webroot_group} ${default_vhost_index_file} && \
                touch /.puphpet-stuff/default_vhost_index_file_set",
    returns => [0, 1],
    creates => '/.puphpet-stuff/default_vhost_index_file_set',
    require => Exec['Create apache webroot'],
  }

}
