# = Class: redis
#
# This class installs redis
#
# == Parameters:
#
# [*activerehashing*]
#   Enable/disable active rehashing.
#
#   Default:  true
#
# [*appendfsync*]
#   Adjust fsync mode.
#   Valid options: always, everysec, no.
#
#   Default:  everysec
#
# [*appendonly*]
#   Enable/disable appendonly mode.
#
#   Default:  false
#
# [*auto_aof_rewrite_min_size*]
#   Adjust minimum size for auto-aof-rewrite.
#
#   Default: 64min
#
# [*auto_aof_rewrite_percentage*]
#   Adjust percentatge for auto-aof-rewrite.
#
#   Default: 100
#
# [*bind*]
#   Configure which IP address to listen on.
#
#   Default: 127.0.0.1
#
# [*config_dir*]
#   Directory containing the configuration files.
#
#   Default: OS dependant
#
# [*config_dir_mode*]
#   Adjust mode for directory containing configuration files.
#
#   Default: 0755
#
# [*config_file*]
#   Adjust main configuration file.
#
#   Default: OS dependant
#
# [*config_file_mode*]
#   Adjust permissions for configuration files.
#
#   Default: 0644
#
# [*config_group*]
#   Adjust filesystem group for config files.
#
#   Default: OS dependant
#
# [*config_owner*]
#   Adjust filesystem owner for config files.
#
#   Default: OS dependant
#
# [*conf_template*]
#   Define which template to use.
#
#   Default: redis/redis.conf.erb
#
# [*daemonize*]
#   Have Redis run as a daemon.
#
#   Default: true
#
# [*databases*]
#   Set the number of databases.
#
#   Default: 16
#
# [*dbfilename*]
#   The filename where to dump the DB
#
#   Default: dump.rdb
#
# [*extra_config_file*]
#   Description
#
#   Default: undef
#
# [*hash_max_ziplist_entries*]
#   Set max ziplist entries for hashes.
#
#   Default: 512
#
# [*hash_max_ziplist_value*]
#   Set max ziplist values for hashes.
#
#   Default: 64
#
# [*hz*]
#   Set redis background tasks frequency
#
#   Default: 10
#
# [*list_max_ziplist_entries*]
#   Set max ziplist entries for lists.
#
#   Default: 512
#
# [*list_max_ziplist_value*]
#   Set max ziplist values for lists.
#
#   Default: 64
#
# [*log_dir*]
#   Specify directory where to write log entries.
#
#   Default: /var/log/redis
#
# [*log_dir_mode*]
#   Adjust mode for directory containing log files.
#
#   Default: 0755
#
# [*log_file*]
#   Specify file where to write log entries.
#
#   Default: /var/log/redis/redis.log
#
# [*log_level*]
#   Specify the server verbosity level.
#
#   Default: notice
#
# [*manage_repo*]
#   Enable/disable upstream repository configuration.
#
#   Default: false
#
# [*masterauth*]
#   If the master is password protected (using the "requirepass" configuration
#   directive below) it is possible to tell the slave to authenticate before
#   starting the replication synchronization process, otherwise the master will
#   refuse the slave request.
#
#   Default: undef
#
# [*maxclients*]
#   Set the max number of connected clients at the same time.
#
#   Default: 10000
#
# [*maxmemory*]
#   Don't use more memory than the specified amount of bytes.
#
#   Default: undef
#
# [*maxmemory_policy*]
#   How Redis will select what to remove when maxmemory is reached.
#   You can select among five behaviors:
#
#   volatile-lru -> remove the key with an expire set using an LRU algorithm
#   allkeys-lru -> remove any key accordingly to the LRU algorithm
#   volatile-random -> remove a random key with an expire set
#   allkeys-random -> remove a random key, any key
#   volatile-ttl -> remove the key with the nearest expire time (minor TTL)
#   noeviction -> don't expire at all, just return an error on write operations
#
#   Default: undef
#
# [*maxmemory_samples*]
#   Select as well the sample size to check.
#
#   Default: undef
#
# [*no_appendfsync_on_rewrite*]
#   If you have latency problems turn this to 'true'. Otherwise leave it as
#   'false' that is the safest pick from the point of view of durability.
#
#   Default: false
#
# [*notify_service*]
#   You may disable service reloads when config files change if you
#   have an external service (e.g. Monit) to manage it for you.
#
#   Default: true
#
# [*package_ensure*]
#   Default action for package.
#
#   Default: present
#
# [*package_name*]
#   Upstream package name.
#
#   Default: OS dependant
#
# [*pid_file*]
#   Where to store the pid.
#
#   Default: /var/run/redis/redis-server.pid
#
# [*port*]
#   Configure which port to listen on.
#
#   Default: 6379
#
# [*ppa_repo*]
#   Specify upstream (Ubuntu) PPA entry.
#
#   Default: ppa:chris-lea/redis-server
#
# [*rdbcompression*]
#   Enable/disable compression of string objects using LZF when dumping.
#
#   Default: true
#
# [*repl_ping_slave_period*]
#   Slaves send PINGs to server in a predefined interval. It's possible
#   to change this interval with the repl_ping_slave_period option.
#
#   Default: 10
#
# [*repl_timeout*]
#   Set the replication timeout for:
#
#   1) Bulk transfer I/O during SYNC, from the point of view of slave.
#   2) Master timeout from the point of view of slaves (data, pings).
#   3) Slave timeout from the point of view of masters (REPLCONF ACK pings).
#
#   Default: 60
#
# [*requirepass*]
#   Require clients to issue AUTH <PASSWORD> before processing any
#   other commands.
#
#   Default: undef
#
#[*save_db_to_disk*]
#   Set if save db to disk.
#
#   Default: true
#
# [*service_manage*]
#   Specify if the service should be part of the catalog.
#
#   Default: true
#
# [*service_enable*]
#   Enable/disable daemon at boot.
#
#   Default: true
#
# [*service_ensure*]
#   Specify if the server should be running.
#
#   Default: running
#
# [*service_group*]
#   Specify which group to run as.
#
#   Default: OS dependant
#
# [*service_hasrestart*]
#   Does the init script support restart?
#
#   Default: OS dependant
#
# [*service_hasstatus*]
#   Does the init script support status?
#
#   Default: OS dependant
#
# [*service_name*]
#   Specify the service name for Init or Systemd.
#
#   Default: OS dependant
#
# [*service_provider*]
#   Specify the service provider to use
#
#   Default: undef
#
# [*service_user*]
#   Specify which user to run as.
#
#   Default: OS dependant
#
# [*set_max_intset_entries*]
#   The following configuration setting sets the limit in the size of the
#   set in order to use this special memory saving encoding.
#
#   Default: 512
#
# [*slave_read_only*]
#   You can configure a slave instance to accept writes or not.
#
#   Default: true
#
# [*slave_serve_stale_data*]
#   When a slave loses its connection with the master, or when the replication
#   is still in progress, the slave can act in two different ways:
#
#   1) if slave-serve-stale-data is set to 'yes' (the default) the slave will
#      still reply to client requests, possibly with out of date data, or the
#      data set may just be empty if this is the first synchronization.
#
#   2) if slave-serve-stale-data is set to 'no' the slave will reply with
#      an error "SYNC with master in progress" to all the kind of commands
#      but to INFO and SLAVEOF.
#
#   Default: true
#
# [*slaveof*]
#   Use slaveof to make a Redis instance a copy of another Redis server.
#
#   Default: undef
#
# [*slowlog_log_slower_than*]
#   Tells Redis what is the execution time, in microseconds, to exceed
#   in order for the command to get logged.
#
#   Default: 10000
#
# [*slowlog_max_len*]
#   Tells Redis what is the length to exceed in order for the command
#   to get logged.
#
#   Default: 1024
#
# [*syslog_enabled*]
#   Enable/disable logging to the system logger.
#
#   Default: undef
#
# [*syslog_facility*]
#   Specify the syslog facility.
#   Must be USER or between LOCAL0-LOCAL7.
#
#   Default: undef
#
# [*timeout*]
#   Close the connection after a client is idle for N seconds (0 to disable).
#
#   Default: 0
#
# [*ulimit*]
#   Limit the use of system-wide resources.
#
#   Default: 65536
#
# [*workdir*]
#   The DB will be written inside this directory, with the filename specified
#   above using the 'dbfilename' configuration directive.
#
#   Default: /var/lib/redis/
#
# [*zset_max_ziplist_entries*]
#   Set max entries for sorted sets.
#
#   Default: 128
#
# [*zset_max_ziplist_value*]
#   Set max values for sorted sets.
#
#   Default: 64
#
# [*cluster_enabled*]
#   Enables redis 3.0 cluster functionality
#
#   Default: false
#
# [*cluster_config_file*]
#   Config file for saving cluster nodes configuration. This file is never touched by humans.
#   Only set if cluster_enabled is true
#
#   Default: nodes.conf
#
# [*cluster_node_timeout*]
#   Node timeout
#   Only set if cluster_enabled is true
#
#   Default: 5000
#
# == Actions:
#   - Install and configure Redis
#
# == Sample Usage:
#
#   class { 'redis': }
#
#   class { 'redis':
#     manage_repo => true;
#   }
#
class redis (
  $activerehashing             = $::redis::params::activerehashing,
  $appendfsync                 = $::redis::params::appendfsync,
  $appendonly                  = $::redis::params::appendonly,
  $auto_aof_rewrite_min_size   = $::redis::params::auto_aof_rewrite_min_size,
  $auto_aof_rewrite_percentage = $::redis::params::auto_aof_rewrite_percentage,
  $bind                        = $::redis::params::bind,
  $conf_template               = $::redis::params::conf_template,
  $config_dir                  = $::redis::params::config_dir,
  $config_dir_mode             = $::redis::params::config_dir_mode,
  $config_file                 = $::redis::params::config_file,
  $config_file_mode            = $::redis::params::config_file_mode,
  $config_group                = $::redis::params::config_group,
  $config_owner                = $::redis::params::config_owner,
  $daemonize                   = $::redis::params::daemonize,
  $databases                   = $::redis::params::databases,
  $dbfilename                  = $::redis::params::dbfilename,
  $extra_config_file           = $::redis::params::extra_config_file,
  $hash_max_ziplist_entries    = $::redis::params::hash_max_ziplist_entries,
  $hash_max_ziplist_value      = $::redis::params::hash_max_ziplist_value,
  $hz                          = $::redis::params::hz,
  $list_max_ziplist_entries    = $::redis::params::list_max_ziplist_entries,
  $list_max_ziplist_value      = $::redis::params::list_max_ziplist_value,
  $log_dir                     = $::redis::params::log_dir,
  $log_dir_mode                = $::redis::params::log_dir_mode,
  $log_file                    = $::redis::params::log_file,
  $log_level                   = $::redis::params::log_level,
  $manage_repo                 = $::redis::params::manage_repo,
  $masterauth                  = $::redis::params::masterauth,
  $maxclients                  = $::redis::params::maxclients,
  $maxmemory                   = $::redis::params::maxmemory,
  $maxmemory_policy            = $::redis::params::maxmemory_policy,
  $maxmemory_samples           = $::redis::params::maxmemory_samples,
  $no_appendfsync_on_rewrite   = $::redis::params::no_appendfsync_on_rewrite,
  $notify_service              = $::redis::params::notify_service,
  $package_ensure              = $::redis::params::package_ensure,
  $package_name                = $::redis::params::package_name,
  $pid_file                    = $::redis::params::pid_file,
  $port                        = $::redis::params::port,
  $ppa_repo                    = $::redis::params::ppa_repo,
  $rdbcompression              = $::redis::params::rdbcompression,
  $repl_ping_slave_period      = $::redis::params::repl_ping_slave_period,
  $repl_timeout                = $::redis::params::repl_timeout,
  $requirepass                 = $::redis::params::requirepass,
  $save_db_to_disk             = $::redis::params::save_db_to_disk,
  $service_enable              = $::redis::params::service_enable,
  $service_ensure              = $::redis::params::service_ensure,
  $service_group               = $::redis::params::service_group,
  $service_hasrestart          = $::redis::params::service_hasrestart,
  $service_hasstatus           = $::redis::params::service_hasstatus,
  $service_manage              = $::redis::params::service_manage,
  $service_name                = $::redis::params::service_name,
  $service_provider            = $::redis::params::service_provider,
  $service_user                = $::redis::params::service_user,
  $set_max_intset_entries      = $::redis::params::set_max_intset_entries,
  $slave_read_only             = $::redis::params::slave_read_only,
  $slave_serve_stale_data      = $::redis::params::slave_serve_stale_data,
  $slaveof                     = $::redis::params::slaveof,
  $slowlog_log_slower_than     = $::redis::params::slowlog_log_slower_than,
  $slowlog_max_len             = $::redis::params::slowlog_max_len,
  $syslog_enabled              = $::redis::params::syslog_enabled,
  $syslog_facility             = $::redis::params::syslog_facility,
  $timeout                     = $::redis::params::timeout,
  $ulimit                      = $::redis::params::ulimit,
  $workdir                     = $::redis::params::workdir,
  $zset_max_ziplist_entries    = $::redis::params::zset_max_ziplist_entries,
  $zset_max_ziplist_value      = $::redis::params::zset_max_ziplist_value,
  $cluster_enabled             = $::redis::params::cluster_enabled,
  $cluster_config_file         = $::redis::params::cluster_config_file,
  $cluster_node_timeout        = $::redis::params::cluster_node_timeout,
) inherits redis::params {
  anchor { 'redis::begin': }
  anchor { 'redis::end': }

  include redis::preinstall
  include redis::install
  include redis::config
  include redis::service

  if $::redis::notify_service {
    Anchor['redis::begin'] ->
    Class['redis::preinstall'] ->
    Class['redis::install'] ->
    Class['redis::config'] ~>
    Class['redis::service'] ->
    Anchor['redis::end']
  } else {
    Anchor['redis::begin'] ->
    Class['redis::preinstall'] ->
    Class['redis::install'] ->
    Class['redis::config'] ->
    Class['redis::service'] ->
    Anchor['redis::end']
  }

  # Sanity check
  if $::redis::slaveof {
    if $::redis::bind =~ /^127.0.0./ {
      fail "Replication is not possible when binding to ${::redis::bind}."
    }
  }
}

