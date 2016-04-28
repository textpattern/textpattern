# = Class: redis::params
#
# This class provides a number of parameters.
#
class redis::params {
  # Generic
  $manage_repo = false

  # redis.conf.erb
  $activerehashing              = true
  $appendfsync                  = 'everysec'
  $appendonly                   = false
  $auto_aof_rewrite_min_size    = '64min'
  $auto_aof_rewrite_percentage  = 100
  $bind                         = '127.0.0.1'
  $conf_template                = 'redis/redis.conf.erb'
  $databases                    = 16
  $dbfilename                   = 'dump.rdb'
  $extra_config_file            = undef
  $hash_max_ziplist_entries     = 512
  $hash_max_ziplist_value       = 64
  $hz                           = 10
  $list_max_ziplist_entries     = 512
  $list_max_ziplist_value       = 64
  $log_dir                      = '/var/log/redis'
  $log_file                     = '/var/log/redis/redis.log'
  $log_level                    = 'notice'
  $maxclients                   = 10000
  $maxmemory                    = undef
  $maxmemory_policy             = undef
  $maxmemory_samples            = undef
  $no_appendfsync_on_rewrite    = false
  $notify_service               = true
  $pid_file                     = '/var/run/redis/redis-server.pid'
  $port                         = 6379
  $rdbcompression               = true
  $requirepass                  = undef
  $save_db_to_disk              = true
  $sentinel_auth_pass           = undef
  $sentinel_config_file_mode    = '0644'
  $sentinel_config_group        = 'root'
  $sentinel_config_owner        = 'redis'
  $sentinel_conf_template       = 'redis/redis-sentinel.conf.erb'
  $sentinel_down_after          = 30000
  $sentinel_failover_timeout    = 180000
  $sentinel_master_name         = 'mymaster'
  $sentinel_parallel_sync       = 1
  $sentinel_port                = 26379
  $sentinel_quorum              = 2
  $sentinel_service_name        = 'redis-sentinel'
  $sentinel_working_dir         = '/tmp'
  $sentinel_init_template       = 'redis/redis-sentinel.init.erb'
  $sentinel_pid_file            = '/var/run/redis/redis-sentinel.pid'
  $sentinel_notification_script = undef
  $service_provider             = undef
  $set_max_intset_entries       = 512
  $slowlog_log_slower_than      = 10000
  $slowlog_max_len              = 1024
  $syslog_enabled               = undef
  $syslog_facility              = undef
  $timeout                      = 0
  $ulimit                       = 65536
  $workdir                      = '/var/lib/redis/'
  $zset_max_ziplist_entries     = 128
  $zset_max_ziplist_value       = 64

  # redis.conf.erb - replication
  $masterauth             = undef
  $repl_ping_slave_period = 10
  $repl_timeout           = 60
  $slave_read_only        = true
  $slave_serve_stale_data = true
  $slaveof                = undef

  # redis.conf.erb - redis 3.0 clustering
  $cluster_enabled        = false
  $cluster_config_file    = 'nodes.conf'
  $cluster_node_timeout   = 5000

  case $::osfamily {
    'Debian': {
      $config_dir                = '/etc/redis'
      $config_dir_mode           = '0755'
      $config_file               = '/etc/redis/redis.conf'
      $config_file_mode          = '0644'
      $config_group              = 'root'
      $config_owner              = 'redis'
      $daemonize                 = true
      $log_dir_mode              = '0755'
      $package_ensure            = 'present'
      $package_name              = 'redis-server'
      $sentinel_config_file      = '/etc/redis/redis-sentinel.conf'
      $sentinel_config_file_orig = '/etc/redis/redis-sentinel.conf.puppet'
      $sentinel_init_script      = '/etc/init.d/redis-sentinel'
      $sentinel_package_name     = 'redis-server'
      $sentinel_package_ensure   = 'present'
      $service_manage            = true
      $service_enable            = true
      $service_ensure            = 'running'
      $service_group             = 'redis'
      $service_hasrestart        = true
      $service_hasstatus         = false
      $service_name              = 'redis-server'
      $service_user              = 'redis'
      $ppa_repo                  = 'ppa:chris-lea/redis-server'
    }

    'RedHat': {
      $config_dir                = '/etc/redis'
      $config_dir_mode           = '0755'
      $config_file               = '/etc/redis.conf'
      $config_file_mode          = '0644'
      $config_group              = 'root'
      $config_owner              = 'redis'
      $daemonize                 = false
      $log_dir_mode              = '0755'
      $package_ensure            = 'present'
      $package_name              = 'redis'
      $sentinel_config_file      = '/etc/redis-sentinel.conf'
      $sentinel_config_file_orig = '/etc/redis-sentinel.conf.puppet'
      $sentinel_init_script      = undef
      $sentinel_package_name     = 'redis'
      $sentinel_package_ensure   = 'present'
      $service_manage            = true
      $service_enable            = true
      $service_ensure            = 'running'
      $service_group             = 'redis'
      $service_hasrestart        = true
      $service_hasstatus         = true
      $service_name              = 'redis'
      $service_user              = 'redis'
      $ppa_repo                  = undef
    }

    'FreeBSD': {
      $config_dir                = '/usr/local/etc/redis'
      $config_dir_mode           = '0755'
      $config_file               = '/usr/local/etc/redis.conf'
      $config_file_mode          = '0644'
      $config_group              = 'wheel'
      $config_owner              = 'redis'
      $daemonize                 = true
      $log_dir_mode              = '0755'
      $package_ensure            = 'present'
      $package_name              = 'redis'
      $sentinel_config_file      = '/usr/local/etc/redis-sentinel.conf'
      $sentinel_config_file_orig = '/usr/local/etc/redis-sentinel.conf.puppet'
      $sentinel_init_script      = undef
      $sentinel_package_name     = 'redis'
      $sentinel_package_ensure   = 'present'
      $service_manage            = true
      $service_enable            = true
      $service_ensure            = 'running'
      $service_group             = 'redis'
      $service_hasrestart        = true
      $service_hasstatus         = true
      $service_name              = 'redis'
      $service_user              = 'redis'
      $ppa_repo                  = undef
    }

    'Suse': {
      $config_dir                = '/etc/redis'
      $config_dir_mode           = '0750'
      $config_file               = '/etc/redis/redis-server.conf'
      $config_file_mode          = '0644'
      $config_group              = 'redis'
      $config_owner              = 'redis'
      $daemonize                 = true
      $log_dir_mode              = '0750'
      $package_ensure            = 'present'
      $package_name              = 'redis'
      $sentinel_config_file      = '/etc/redis/redis-sentinel.conf'
      $sentinel_config_file_orig = '/etc/redis/redis-sentinel.conf.puppet'
      $sentinel_init_script      = undef
      $sentinel_package_name     = 'redis'
      $sentinel_package_ensure   = 'present'
      $service_manage            = true
      $service_enable            = true
      $service_ensure            = 'running'
      $service_group             = 'redis'
      $service_hasrestart        = true
      $service_hasstatus         = true
      $service_name              = 'redis'
      $service_user              = 'redis'
      $ppa_repo                  = undef
    }

    default: {
      fail "Operating system ${::operatingsystem} is not supported yet."
    }
  }
}

