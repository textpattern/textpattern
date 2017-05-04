# = Class: redis::config
#
# This class provides configuration for Redis.
#
class redis::config {
  $daemonize                    = $::redis::daemonize
  $pid_file                     = $::redis::pid_file
  $port                         = $::redis::port
  $bind                         = $::redis::bind
  $timeout                      = $::redis::timeout
  $log_level                    = $::redis::log_level
  $log_file                     = $::redis::log_file
  $syslog_enabled               = $::redis::syslog_enabled
  $syslog_facility              = $::redis::syslog_facility
  $databases                    = $::redis::databases
  $rdbcompression               = $::redis::rdbcompression
  $dbfilename                   = $::redis::dbfilename
  $workdir                      = $::redis::workdir
  $slaveof                      = $::redis::slaveof
  $masterauth                   = $::redis::masterauth
  $slave_serve_stale_data       = $::redis::slave_serve_stale_data
  $slave_read_only              = $::redis::slave_read_only
  $repl_timeout                 = $::redis::repl_timeout
  $requirepass                  = $::redis::requirepass
  $save_db_to_disk              = $::redis::save_db_to_disk
  $maxclients                   = $::redis::maxclients
  $maxmemory                    = $::redis::maxmemory
  $maxmemory_policy             = $::redis::maxmemory_policy
  $maxmemory_samples            = $::redis::maxmemory_samples
  $appendonly                   = $::redis::appendonly
  $appendfsync                  = $::redis::appendfsync
  $no_appendfsync_on_rewrite    = $::redis::no_appendfsync_on_rewrite
  $auto_aof_rewrite_percentage  = $::redis::auto_aof_rewrite_percentage
  $auto_aof_rewrite_min_size    = $::redis::auto_aof_rewrite_min_size
  $slowlog_log_slower_than      = $::redis::slowlog_log_slower_than
  $slowlog_max_len              = $::redis::slowlog_max_len
  $hash_max_ziplist_entries     = $::redis::hash_max_ziplist_entries
  $hash_max_ziplist_value       = $::redis::hash_max_ziplist_value
  $hz                           = $::redis::hz
  $list_max_ziplist_entries     = $::redis::list_max_ziplist_entries
  $list_max_ziplist_value       = $::redis::list_max_ziplist_value
  $set_max_intset_entries       = $::redis::set_max_intset_entries
  $zset_max_ziplist_entries     = $::redis::zset_max_ziplist_entries
  $zset_max_ziplist_value       = $::redis::zset_max_ziplist_value
  $activerehashing              = $::redis::activerehashing
  $extra_config_file            = $::redis::extra_config_file
  $cluster_enabled              = $::redis::cluster_enabled
  $cluster_config_file          = $::redis::cluster_config_file
  $cluster_node_timeout         = $::redis::cluster_node_timeout

  if $::redis::notify_service {
    File {
      owner  => $::redis::config_owner,
      group  => $::redis::config_group,
      mode   => $::redis::config_file_mode,
      notify => Service[$::redis::service_name]
    }
  } else {
    File {
      owner => $::redis::config_owner,
      group => $::redis::config_group,
      mode  => $::redis::config_file_mode,
    }
  }

  file {
    $::redis::config_dir:
      ensure => directory,
      mode   => $::redis::config_dir_mode;

    $::redis::config_file:
      ensure  => present,
      content => template($::redis::conf_template);

    $::redis::log_dir:
      ensure => directory,
      group  => $::redis::service_group,
      mode   => $::redis::log_dir_mode,
      owner  => $::redis::service_user;
  }

  # Adjust /etc/default/redis-server on Debian systems
  case $::osfamily {
    'Debian': {
      file { '/etc/default/redis-server':
        ensure => present,
        group  => $::redis::config_group,
        mode   => $::redis::config_file_mode,
        owner  => $::redis::config_owner,
      }

      if $::redis::ulimit {
        augeas { 'redis ulimit' :
          context => '/files/etc/default/redis-server',
          changes => "set ULIMIT ${::redis::ulimit}",
        }
      }
    }

    default: {
    }
  }
}

