require 'spec_helper'

describe 'redis', :type => :class do
  let (:facts) { debian_facts }

  describe 'without parameters' do
    it { should create_class('redis') }
    it { should contain_class('redis::preinstall') }
    it { should contain_class('redis::install') }
    it { should contain_class('redis::config') }
    it { should contain_class('redis::service') }

    it { should contain_package('redis-server').with_ensure('present') }

    it { should contain_file('/etc/redis/redis.conf').with(
        'ensure' => 'present'
      )
    }

    it { should contain_service('redis-server').with(
        'ensure'     => 'running',
        'enable'     => 'true',
        'hasrestart' => 'true',
        'hasstatus'  => 'false'
      )
    }
  end

  describe 'with parameter activerehashing' do
    let (:params) {
      {
        :activerehashing => true
      }
    }

    it { should contain_file('/etc/redis/redis.conf').with(
        'content' => /activerehashing.*yes/
      )
    }
  end

  describe 'with parameter appendfsync' do
    let (:params) {
      {
        :appendfsync => '_VALUE_'
      }
    }

    it { should contain_file('/etc/redis/redis.conf').with(
        'content' => /appendfsync.*_VALUE_/
      )
    }
  end

  describe 'with parameter appendonly' do
    let (:params) {
      {
        :appendonly => true
      }
    }

    it { should contain_file('/etc/redis/redis.conf').with(
        'content' => /appendonly.*yes/
      )
    }
  end

  describe 'with parameter auto_aof_rewrite_min_size' do
    let (:params) {
      {
        :auto_aof_rewrite_min_size => '_VALUE_'
      }
    }

    it { should contain_file('/etc/redis/redis.conf').with(
        'content' => /auto-aof-rewrite-min-size.*_VALUE_/
      )
    }
  end

  describe 'with parameter auto_aof_rewrite_percentage' do
    let (:params) {
      {
        :auto_aof_rewrite_percentage => '_VALUE_'
      }
    }

    it { should contain_file('/etc/redis/redis.conf').with(
        'content' => /auto-aof-rewrite-percentage.*_VALUE_/
      )
    }
  end

  describe 'with parameter bind' do
    let (:params) {
      {
        :bind => '_VALUE_'
      }
    }

    it { should contain_file('/etc/redis/redis.conf').with(
        'content' => /bind.*_VALUE_/
      )
    }
  end

  describe 'with parameter: config_dir' do
    let (:params) { { :config_dir => '_VALUE_' } }

    it { should contain_file('_VALUE_').with_ensure('directory') }
  end

  describe 'with parameter: config_dir_mode' do
    let (:params) { { :config_dir_mode => '_VALUE_' } }

    it { should contain_file('/etc/redis').with_mode('_VALUE_') }
  end

  describe 'with parameter: log_dir_mode' do
    let (:params) { { :log_dir_mode => '_VALUE_' } }

    it { should contain_file('/var/log/redis').with_mode('_VALUE_') }
  end

  describe 'with parameter: config_file' do
    let (:params) { { :config_file => '_VALUE_' } }

    it { should contain_file('_VALUE_') }
  end

  describe 'with parameter: config_file_mode' do
    let (:params) { { :config_file_mode => '_VALUE_' } }

    it { should contain_file('/etc/redis/redis.conf').with_mode('_VALUE_') }
  end

  describe 'with parameter: config_group' do
    let (:params) { { :config_group => '_VALUE_' } }

    it { should contain_file('/etc/redis').with_group('_VALUE_') }
  end

  describe 'with parameter: config_owner' do
    let (:params) { { :config_owner => '_VALUE_' } }

    it { should contain_file('/etc/redis').with_owner('_VALUE_') }
  end

  describe 'with parameter daemonize' do
    let (:params) {
      {
        :daemonize => true
      }
    }

    it { should contain_file('/etc/redis/redis.conf').with(
        'content' => /daemonize.*yes/
      )
    }
  end

  describe 'with parameter databases' do
    let (:params) {
      {
        :databases => '_VALUE_'
      }
    }

    it { should contain_file('/etc/redis/redis.conf').with(
        'content' => /databases.*_VALUE_/
      )
    }
  end

  describe 'with parameter dbfilename' do
    let (:params) {
      {
        :dbfilename => '_VALUE_'
      }
    }

    it { should contain_file('/etc/redis/redis.conf').with(
        'content' => /dbfilename.*_VALUE_/
      )
    }
  end

  describe 'with parameter hash_max_ziplist_entries' do
    let (:params) {
      {
        :hash_max_ziplist_entries => '_VALUE_'
      }
    }

    it { should contain_file('/etc/redis/redis.conf').with(
        'content' => /hash-max-ziplist-entries.*_VALUE_/
      )
    }
  end

  describe 'with parameter hash_max_ziplist_value' do
    let (:params) {
      {
        :hash_max_ziplist_value => '_VALUE_'
      }
    }

    it { should contain_file('/etc/redis/redis.conf').with(
        'content' => /hash-max-ziplist-value.*_VALUE_/
      )
    }
  end

  describe 'with parameter list_max_ziplist_entries' do
    let (:params) {
      {
        :list_max_ziplist_entries => '_VALUE_'
      }
    }

    it { should contain_file('/etc/redis/redis.conf').with(
        'content' => /list-max-ziplist-entries.*_VALUE_/
      )
    }
  end

  describe 'with parameter list_max_ziplist_value' do
    let (:params) {
      {
        :list_max_ziplist_value => '_VALUE_'
      }
    }

    it { should contain_file('/etc/redis/redis.conf').with(
        'content' => /list-max-ziplist-value.*_VALUE_/
      )
    }
  end

  describe 'with parameter log_dir' do
    let (:params) {
      {
        :log_dir => '_VALUE_'
      }
    }

    it { should contain_file('_VALUE_').with(
        'ensure' => 'directory'
      )
    }
  end

  describe 'with parameter log_file' do
    let (:params) {
      {
        :log_file => '_VALUE_'
      }
    }

    it { should contain_file('/etc/redis/redis.conf').with(
        'content' => /logfile.*_VALUE_/
      )
    }
  end

  describe 'with parameter log_level' do
    let (:params) {
      {
        :log_level => '_VALUE_'
      }
    }

    it { should contain_file('/etc/redis/redis.conf').with(
        'content' => /loglevel.*_VALUE_/
      )
    }
  end

  describe 'with parameter: manage_repo' do
    let (:params) { { :manage_repo => true } }

    context 'on Debian' do
      let (:facts) {
        {
          :lsbdistcodename => 'wheezy',
          :lsbdistid       => 'Debian',
          :operatingsystem => 'Debian',
          :osfamily        => 'Debian'
        }
      }

      it { should create_apt__key('dotdeb') }
      it { should create_apt__source('dotdeb') }
    end

    context 'on Ubuntu' do
      let (:facts) {
        {
          :lsbdistcodename => 'raring',
          :lsbdistid       => 'Ubuntu',
          :operatingsystem => 'Ubuntu',
          :osfamily        => 'Debian'
        }
      }

      it { should create_apt__ppa('ppa:chris-lea/redis-server') }
    end

    context 'on RHEL 6' do
      let (:facts) {
        {
          :osfamily => 'RedHat',
          :operatingsystem => 'RedHat',
          :operatingsystemmajrelease => '6'
        }
      }

      it { should create_yumrepo('powerstack').with_enabled(1) }
    end

    context 'on RHEL 7' do
      let (:facts) {
        {
          :osfamily => 'RedHat',
          :operatingsystem => 'RedHat',
          :operatingsystemmajrelease => '7'
        }
      }

      it { should contain_class('epel') }
    end
  end

  describe 'with parameter masterauth' do
    let (:params) {
      {
        :masterauth => '_VALUE_'
      }
    }

    it { should contain_file('/etc/redis/redis.conf').with(
        'content' => /masterauth.*_VALUE_/
      )
    }
  end

  describe 'with parameter maxclients' do
    let (:params) {
      {
        :maxclients => '_VALUE_'
      }
    }

    it { should contain_file('/etc/redis/redis.conf').with(
        'content' => /maxclients.*_VALUE_/
      )
    }
  end

  describe 'with parameter maxmemory' do
    let (:params) {
      {
        :maxmemory => '_VALUE_'
      }
    }

    it { should contain_file('/etc/redis/redis.conf').with(
        'content' => /maxmemory.*_VALUE_/
      )
    }
  end

  describe 'with parameter maxmemory_policy' do
    let (:params) {
      {
        :maxmemory_policy => '_VALUE_'
      }
    }

    it { should contain_file('/etc/redis/redis.conf').with(
        'content' => /maxmemory-policy.*_VALUE_/
      )
    }
  end

  describe 'with parameter maxmemory_samples' do
    let (:params) {
      {
        :maxmemory_samples => '_VALUE_'
      }
    }

    it { should contain_file('/etc/redis/redis.conf').with(
        'content' => /maxmemory-samples.*_VALUE_/
      )
    }
  end

  describe 'with parameter notify_service' do
    let (:params) {
      {
        :notify_service => true
      }
    }

    it { should contain_file('/etc/redis/redis.conf').that_notifies('Service[redis-server]'
      )
    }
  end

  describe 'with parameter no_appendfsync_on_rewrite' do
    let (:params) {
      {
        :no_appendfsync_on_rewrite => true
      }
    }

    it { should contain_file('/etc/redis/redis.conf').with(
        'content' => /no-appendfsync-on-rewrite.*yes/
      )
    }
  end

  describe 'with parameter: package_ensure' do
    let (:params) { { :package_ensure => '_VALUE_' } }

    it { should contain_package('redis-server').with(
        'ensure' => '_VALUE_'
      )
    }
  end

  describe 'with parameter: package_name' do
    let (:params) { { :package_name => '_VALUE_' } }

    it { should contain_package('_VALUE_') }
  end

  describe 'with parameter pid_file' do
    let (:params) {
      {
        :pid_file => '_VALUE_'
      }
    }

    it { should contain_file('/etc/redis/redis.conf').with(
        'content' => /pidfile.*_VALUE_/
      )
    }
  end

  describe 'with parameter port' do
    let (:params) {
      {
        :port => '_VALUE_'
      }
    }

    it { should contain_file('/etc/redis/redis.conf').with(
        'content' => /port.*_VALUE_/
      )
    }
  end

  describe 'with parameter hz' do
    let (:params) {
      {
        :hz=> '_VALUE_'
      }
    }

    it { should contain_file('/etc/redis/redis.conf').with(
        'content' => /hz.*_VALUE_/
      )
    }
  end

  describe 'with parameter rdbcompression' do
    let (:params) {
      {
        :rdbcompression => true
      }
    }

    it { should contain_file('/etc/redis/redis.conf').with(
        'content' => /rdbcompression.*yes/
      )
    }
  end

  describe 'with parameter repl_ping_slave_period' do
    let (:params) {
      {
        :repl_ping_slave_period => 1
      }
    }

    it { should contain_file('/etc/redis/redis.conf').with(
        'content' => /repl-ping-slave-period.*1/
      )
    }
  end

  describe 'with parameter repl_timeout' do
    let (:params) {
      {
        :repl_timeout => 1
      }
    }

    it { should contain_file('/etc/redis/redis.conf').with(
        'content' => /repl-timeout.*1/
      )
    }
  end

  describe 'with parameter requirepass' do
    let (:params) {
      {
        :requirepass => '_VALUE_'
      }
    }

    it { should contain_file('/etc/redis/redis.conf').with(
        'content' => /requirepass.*_VALUE_/
      )
    }
  end

  describe 'with parameter save_db_to_disk' do
    context 'true' do
      let (:params) {
        {
          :save_db_to_disk => true
        }
      }

      it { should contain_file('/etc/redis/redis.conf').with(
          'content' => /^save/
        )
      }
    end

    context 'false' do
      let (:params) {
        {
          :save_db_to_disk => false
        }
      }

      it { should contain_file('/etc/redis/redis.conf').with(
          'content' => /^(?!save)/
        )
      }
    end
  end

  describe 'with parameter: service_manage (set to false)' do
    let (:params) { { :service_manage => false } }

    it { should_not contain_service('redis-server') }
  end

  describe 'with parameter: service_enable' do
    let (:params) { { :service_enable => true } }

    it { should contain_service('redis-server').with_enable(true) }
  end

  describe 'with parameter: service_ensure' do
    let (:params) { { :service_ensure => '_VALUE_' } }

    it { should contain_service('redis-server').with_ensure('_VALUE_') }
  end

  describe 'with parameter: service_group' do
    let (:params) { { :service_group => '_VALUE_' } }

    it { should contain_file('/var/log/redis').with_group('_VALUE_') }
  end

  describe 'with parameter: service_hasrestart' do
    let (:params) { { :service_hasrestart => true } }

    it { should contain_service('redis-server').with_hasrestart(true) }
  end

  describe 'with parameter: service_hasstatus' do
    let (:params) { { :service_hasstatus => true } }

    it { should contain_service('redis-server').with_hasstatus(true) }
  end

  describe 'with parameter: service_name' do
    let (:params) { { :service_name => '_VALUE_' } }

    it { should contain_service('_VALUE_').with_name('_VALUE_') }
  end

  describe 'with parameter: service_user' do
    let (:params) { { :service_user => '_VALUE_' } }

    it { should contain_file('/var/log/redis').with_owner('_VALUE_') }
  end

  describe 'with parameter set_max_intset_entries' do
    let (:params) {
      {
        :set_max_intset_entries => '_VALUE_'
      }
    }

    it { should contain_file('/etc/redis/redis.conf').with(
        'content' => /set-max-intset-entries.*_VALUE_/
      )
    }
  end

  describe 'with parameter slave_read_only' do
    let (:params) {
      {
        :slave_read_only => true
      }
    }

    it { should contain_file('/etc/redis/redis.conf').with(
        'content' => /slave-read-only.*yes/
      )
    }
  end

  describe 'with parameter slave_serve_stale_data' do
    let (:params) {
      {
        :slave_serve_stale_data => true
      }
    }

    it { should contain_file('/etc/redis/redis.conf').with(
        'content' => /slave-serve-stale-data.*yes/
      )
    }
  end

  describe 'with parameter: slaveof' do
    context 'binding to localhost' do
      let (:params) {
        {
          :bind    => '127.0.0.1',
          :slaveof => '_VALUE_'
        }
      }

      it do
        expect {
          should create_class('redis')
        }.to raise_error(Puppet::Error, /Replication is not possible/)
      end
    end

    context 'binding to external ip' do
      let (:params) {
        {
          :bind    => '10.0.0.1',
          :slaveof => '_VALUE_'
        }
      }

      it { should contain_file('/etc/redis/redis.conf').with(
        'content' => /^slaveof _VALUE_/
      )
    }
    end
  end

  describe 'with parameter slowlog_log_slower_than' do
    let (:params) {
      {
        :slowlog_log_slower_than => '_VALUE_'
      }
    }

    it { should contain_file('/etc/redis/redis.conf').with(
        'content' => /slowlog-log-slower-than.*_VALUE_/
      )
    }
  end

  describe 'with parameter slowlog_max_len' do
    let (:params) {
      {
        :slowlog_max_len => '_VALUE_'
      }
    }

    it { should contain_file('/etc/redis/redis.conf').with(
        'content' => /slowlog-max-len.*_VALUE_/
      )
    }
  end

  describe 'with parameter syslog_enabled' do
    let (:params) {
      {
        :syslog_enabled => true
      }
    }

    it { should contain_file('/etc/redis/redis.conf').with(
        'content' => /syslog-enabled yes/
      )
    }
  end

  describe 'with parameter syslog_facility' do
    let (:params) {
      {
        :syslog_enabled => true,
        :syslog_facility => '_VALUE_'
      }
    }

    it { should contain_file('/etc/redis/redis.conf').with(
        'content' => /syslog-facility.*_VALUE_/
      )
    }
  end

  describe 'with parameter timeout' do
    let (:params) {
      {
        :timeout => '_VALUE_'
      }
    }

    it { should contain_file('/etc/redis/redis.conf').with(
        'content' => /timeout.*_VALUE_/
      )
    }
  end

  describe 'with parameter workdir' do
    let (:params) {
      {
        :workdir => '_VALUE_'
      }
    }

    it { should contain_file('/etc/redis/redis.conf').with(
        'content' => /dir.*_VALUE_/
      )
    }
  end

  describe 'with parameter zset_max_ziplist_entries' do
    let (:params) {
      {
        :zset_max_ziplist_entries => '_VALUE_'
      }
    }

    it { should contain_file('/etc/redis/redis.conf').with(
        'content' => /zset-max-ziplist-entries.*_VALUE_/
      )
    }
  end

  describe 'with parameter zset_max_ziplist_value' do
    let (:params) {
      {
        :zset_max_ziplist_value => '_VALUE_'
      }
    }

    it { should contain_file('/etc/redis/redis.conf').with(
        'content' => /zset-max-ziplist-value.*_VALUE_/
      )
    }
  end

  describe 'with parameter cluster_enabled-false' do
    let (:params) {
      {
        :cluster_enabled => false
      }
    }

    it { should_not contain_file('/etc/redis/redis.conf').with(
        'content' => /cluster-enabled.*/
      )
    }
  end

  describe 'with parameter cluster_enabled-true' do
    let (:params) {
      {
        :cluster_enabled => true
      }
    }

    it { should contain_file('/etc/redis/redis.conf').with(
        'content' => /cluster-enabled.*yes/
      )
    }
  end

  describe 'with parameter cluster_config_file' do
    let (:params) {
      {
        :cluster_enabled => true,
        :cluster_config_file => '_VALUE_'
      }
    }

    it { should contain_file('/etc/redis/redis.conf').with(
        'content' => /cluster-config-file.*_VALUE_/
      )
    }
  end

  describe 'with parameter cluster_config_file' do
    let (:params) {
      {
        :cluster_enabled => true,
        :cluster_node_timeout => '_VALUE_'
      }
    }

    it { should contain_file('/etc/redis/redis.conf').with(
        'content' => /cluster-node-timeout.*_VALUE_/
      )
    }
  end

end

