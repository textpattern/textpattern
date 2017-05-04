require 'spec_helper'

describe 'elasticsearch::instance', :type => 'define' do

  default_params = { }

  on_supported_os.each do |os, facts|

    let(:title) { 'es-01' }
    context "on #{os}" do

      case facts[:osfamily]
      when 'Debian'
        let(:defaults_path) { '/etc/default' }
        let(:pkg_ext) { 'deb' }
        let(:pkg_prov) { 'dpkg' }
        case facts[:operatingsystem]
        when 'Debian'
          if facts[:operatingsystemmajrelease].to_i >= 8
            let(:initscript) { 'systemd' }
          else
            let(:initscript) { 'Debian' }
          end
        when 'Ubuntu'
          if facts[:operatingsystemmajrelease].to_i >= 15
            let(:initscript) { 'systemd' }
          else
            let(:initscript) { 'Debian' }
          end
        end
      when 'RedHat'
        let(:defaults_path) { '/etc/sysconfig' }
        let(:pkg_ext) { 'rpm' }
        let(:pkg_prov) { 'rpm' }
        if facts[:operatingsystemmajrelease].to_i >= 7
          let(:initscript) { 'systemd' }
        else
          let(:initscript) { 'RedHat' }
        end
      when 'Suse'
        let(:defaults_path) { '/etc/sysconfig' }
        let(:pkg_ext) { 'rpm' }
        let(:pkg_prov) { 'rpm' }
        let(:initscript) { 'systemd' }
      end

      let(:facts) do
        facts.merge({ 'scenario' => '', 'common' => '' })
      end

      let (:params) do
        default_params.merge({ })
      end

      let(:title) { 'es-01' }
      let(:pre_condition) { 'class {"elasticsearch": }'  }

      context "Service" do

          it { should contain_elasticsearch__service('es-01').with(:init_template => "elasticsearch/etc/init.d/elasticsearch.#{initscript}.erb", :init_defaults => {"CONF_DIR"=>"/etc/elasticsearch/es-01", "CONF_FILE"=>"/etc/elasticsearch/es-01/elasticsearch.yml", "LOG_DIR"=>"/var/log/elasticsearch/es-01", "ES_HOME"=>"/usr/share/elasticsearch"}) }

      end

    end

  end

  let :facts do {
    :operatingsystem => 'CentOS',
    :kernel => 'Linux',
    :osfamily => 'RedHat',
    :operatingsystemmajrelease => '6',
    :scenario => '',
    :common => '',
    :hostname => 'foo'
  } end

  let(:title) { 'es-01' }
  let(:pre_condition) { 'class {"elasticsearch": }'  }


  context "Config file" do

    let :params do {
      :config => { }
    } end

    it { should contain_datacat_fragment('main_config_es-01') }
    it { should contain_datacat('/etc/elasticsearch/es-01/elasticsearch.yml') }
    it { should contain_datacat_collector('/etc/elasticsearch/es-01/elasticsearch.yml') }
    it { should contain_file('/etc/elasticsearch/es-01/elasticsearch.yml') }

  end

  context "service restarts" do

    context "does not restart when restart_on_change is false" do
      let :params do {
        :config => { 'node' => { 'name' => 'test' }  },
      } end
      let(:pre_condition) { 'class {"elasticsearch": config => { }, restart_on_change => false }'  }
      it { should contain_datacat_fragment('main_config_es-01') }
      it { should contain_datacat('/etc/elasticsearch/es-01/elasticsearch.yml').without_notify }

    end

    context "should happen restart_on_change is true (default)" do
      let :params do {
        :config => { 'node' => { 'name' => 'test' }  },
      } end
      let(:pre_condition) { 'class {"elasticsearch": config => { }}'  }

      it { should contain_datacat_fragment('main_config_es-01') }
      it { should contain_datacat('/etc/elasticsearch/es-01/elasticsearch.yml').with(:notify => "Elasticsearch::Service[es-01]") }

    end

  end

  context "Config dir" do

    context "default" do
      let(:pre_condition) { 'class {"elasticsearch": }'  }
      it { should contain_exec('mkdir_configdir_elasticsearch_es-01') }
      it { should contain_file('/etc/elasticsearch/es-01').with(:ensure => 'directory') }
      it { should contain_datacat_fragment('main_config_es-01') }
      it { should contain_datacat('/etc/elasticsearch/es-01/elasticsearch.yml') }

      it { should contain_file('/etc/elasticsearch/es-01/logging.yml') }
      it { should contain_file('/usr/share/elasticsearch/scripts') }
      it { should contain_file('/etc/elasticsearch/es-01/scripts').with(:target => '/usr/share/elasticsearch/scripts') }
    end

    context "Set in main class" do
      let(:pre_condition) { 'class {"elasticsearch": configdir => "/etc/elasticsearch-config" }'  }

      it { should contain_exec('mkdir_configdir_elasticsearch_es-01') }
      it { should contain_file('/etc/elasticsearch-config').with(:ensure => 'directory') }
      it { should contain_file('/usr/share/elasticsearch/templates_import').with(:ensure => 'directory') }
      it { should contain_file('/etc/elasticsearch-config/es-01').with(:ensure => 'directory') }
      it { should contain_datacat_fragment('main_config_es-01') }
      it { should contain_datacat('/etc/elasticsearch-config/es-01/elasticsearch.yml') }

      it { should contain_file('/etc/elasticsearch-config/es-01/logging.yml') }
      it { should contain_file('/usr/share/elasticsearch/scripts') }
      it { should contain_file('/etc/elasticsearch-config/es-01/scripts').with(:target => '/usr/share/elasticsearch/scripts') }
    end

    context "set in instance" do
      let(:pre_condition) { 'class {"elasticsearch": }'  }
      let :params do {
        :configdir => '/etc/elasticsearch-config/es-01'
      } end

      it { should contain_exec('mkdir_configdir_elasticsearch_es-01') }
      it { should contain_file('/etc/elasticsearch').with(:ensure => 'directory') }
      it { should contain_file('/etc/elasticsearch-config/es-01').with(:ensure => 'directory') }
      it { should contain_datacat_fragment('main_config_es-01') }
      it { should contain_datacat('/etc/elasticsearch-config/es-01/elasticsearch.yml') }

      it { should contain_file('/etc/elasticsearch-config/es-01/logging.yml') }
      it { should contain_file('/usr/share/elasticsearch/scripts') }
      it { should contain_file('/etc/elasticsearch-config/es-01/scripts').with(:target => '/usr/share/elasticsearch/scripts') }
    end

  end


  context "data directory" do
    let(:pre_condition) { 'class {"elasticsearch": }'  }

    context "default" do
      it { should contain_exec('mkdir_datadir_elasticsearch_es-01') }
      it { should contain_file('/usr/share/elasticsearch/data/es-01').with( :ensure => 'directory') }
      it { should contain_file('/usr/share/elasticsearch/data').with( :ensure => 'directory') }
    end

    context "single from main config " do
      let(:pre_condition) { 'class {"elasticsearch": datadir => "/var/lib/elasticsearch-data" }'  }

      it { should contain_exec('mkdir_datadir_elasticsearch_es-01') }
      it { should contain_file('/var/lib/elasticsearch-data').with( :ensure => 'directory') }
      it { should contain_file('/var/lib/elasticsearch-data/es-01').with( :ensure => 'directory') }
    end

    context "single from instance config" do
      let(:pre_condition) { 'class {"elasticsearch": }'  }
      let :params do {
        :datadir => '/var/lib/elasticsearch/data'
      } end

      it { should contain_exec('mkdir_datadir_elasticsearch_es-01') }
      it { should contain_file('/var/lib/elasticsearch/data').with( :ensure => 'directory') }

    end

    context "multiple from main config" do
      let(:pre_condition) { 'class {"elasticsearch": datadir => [ "/var/lib/elasticsearch-data01", "/var/lib/elasticsearch-data02"] }'  }

      it { should contain_exec('mkdir_datadir_elasticsearch_es-01') }
      it { should contain_file('/var/lib/elasticsearch-data01').with( :ensure => 'directory') }
      it { should contain_file('/var/lib/elasticsearch-data01/es-01').with( :ensure => 'directory') }
      it { should contain_file('/var/lib/elasticsearch-data02').with( :ensure => 'directory') }
      it { should contain_file('/var/lib/elasticsearch-data02/es-01').with( :ensure => 'directory') }
    end

    context "multiple from instance config" do
      let(:pre_condition) { 'class {"elasticsearch": }'  }
      let :params do {
        :datadir => ['/var/lib/elasticsearch-data/01', '/var/lib/elasticsearch-data/02']
      } end

      it { should contain_exec('mkdir_datadir_elasticsearch_es-01') }
      it { should contain_file('/var/lib/elasticsearch-data/01').with( :ensure => 'directory') }
      it { should contain_file('/var/lib/elasticsearch-data/02').with( :ensure => 'directory') }
    end

   context "Conflicting setting path.data" do
     let(:pre_condition) { 'class {"elasticsearch": }'  }
     let :params do {
       :datadir => '/var/lib/elasticsearch/data',
       :config  => { 'path.data' => '/var/lib/elasticsearch/otherdata' }
     } end

      it { should contain_exec('mkdir_datadir_elasticsearch_es-01') }
      it { should contain_file('/var/lib/elasticsearch/data').with( :ensure => 'directory') }
      it { should_not contain_file('/var/lib/elasticsearch/otherdata').with( :ensure => 'directory') }
   end

   context "Conflicting setting path => data" do
     let(:pre_condition) { 'class {"elasticsearch": }'  }
     let :params do {
       :datadir => '/var/lib/elasticsearch/data',
       :config  => { 'path' => { 'data' => '/var/lib/elasticsearch/otherdata' } }
     } end

      it { should contain_exec('mkdir_datadir_elasticsearch_es-01') }
      it { should contain_file('/var/lib/elasticsearch/data').with( :ensure => 'directory') }
      it { should_not contain_file('/var/lib/elasticsearch/otherdata').with( :ensure => 'directory') }
   end

   context "With other path options defined" do
     let(:pre_condition) { 'class {"elasticsearch": }'  }
     let :params do {
       :datadir => '/var/lib/elasticsearch/data',
       :config  => { 'path' => { 'home' => '/var/lib/elasticsearch' } }
     } end

      it { should contain_exec('mkdir_datadir_elasticsearch_es-01') }
      it { should contain_file('/var/lib/elasticsearch/data').with( :ensure => 'directory') }
   end


  end

  context "logs directory" do
    let(:pre_condition) { 'class {"elasticsearch": }'  }

    context "default" do
      it { should contain_file('/var/log/elasticsearch/es-01').with( :ensure => 'directory') }
      it { should contain_file('/var/log/elasticsearch/').with( :ensure => 'directory') }
    end

    context "single from main config " do
      let(:pre_condition) { 'class {"elasticsearch": logdir => "/var/log/elasticsearch-logs" }'  }

      it { should contain_file('/var/log/elasticsearch-logs').with( :ensure => 'directory') }
      it { should contain_file('/var/log/elasticsearch-logs/es-01').with( :ensure => 'directory') }
    end

    context "single from instance config" do
      let(:pre_condition) { 'class {"elasticsearch": }'  }
      let :params do {
        :logdir => '/var/log/elasticsearch/logs-a'
      } end

      it { should contain_file('/var/log/elasticsearch/logs-a').with( :ensure => 'directory') }

    end

   context "Conflicting setting path.logs" do
     let(:pre_condition) { 'class {"elasticsearch": }'  }
     let :params do {
       :logdir => '/var/log/elasticsearch/logs-a',
       :config  => { 'path.logs' => '/var/log/elasticsearch/otherlogs' }
     } end

      it { should contain_file('/var/log/elasticsearch/logs-a').with( :ensure => 'directory') }
      it { should_not contain_file('/var/log/elasticsearch/otherlogs').with( :ensure => 'directory') }
   end

   context "Conflicting setting path => logs" do
     let(:pre_condition) { 'class {"elasticsearch": }'  }
     let :params do {
       :logdir => '/var/log/elasticsearch/logs-a',
       :config  => { 'path' => { 'logs' => '/var/log/elasticsearch/otherlogs' } }
     } end

      it { should contain_file('/var/log/elasticsearch/logs-a').with( :ensure => 'directory') }
      it { should_not contain_file('/var/log/elasticsearch/otherlogs').with( :ensure => 'directory') }
   end

   context "With other path options defined" do
     let(:pre_condition) { 'class {"elasticsearch": }'  }
     let :params do {
       :logdir => '/var/log/elasticsearch/logs-a',
       :config  => { 'path' => { 'home' => '/var/log/elasticsearch' } }
     } end

      it { should contain_file('/var/log/elasticsearch/logs-a').with( :ensure => 'directory') }
   end


  end


  context "Logging" do

    let(:pre_condition) { 'class {"elasticsearch": }'  }

    context "default" do
      it { should contain_file('/etc/elasticsearch/es-01/logging.yml').with_content(/^logger.index.search.slowlog: TRACE, index_search_slow_log_file$/).with(:source => nil) }
    end

    context "from main class" do

      context "config" do
        let(:pre_condition) { 'class {"elasticsearch": logging_config => { "index.search.slowlog" => "DEBUG, index_search_slow_log_file" } }'  }

        it { should contain_file('/etc/elasticsearch/es-01/logging.yml').with_content(/^logger.index.search.slowlog: DEBUG, index_search_slow_log_file$/).with(:source => nil) }
      end

      context "logging file " do
        let(:pre_condition) { 'class {"elasticsearch": logging_file => "puppet:///path/to/logging.yml" }'  }

        it { should contain_file('/etc/elasticsearch/es-01/logging.yml').with(:source => 'puppet:///path/to/logging.yml', :content => nil) }
      end

    end

    context "from instance" do

      let(:pre_condition) { 'class {"elasticsearch": }'  }

      context "config" do
        let :params do {
          :logging_config => { 'index.search.slowlog' => 'INFO, index_search_slow_log_file' }
        } end

        it { should contain_file('/etc/elasticsearch/es-01/logging.yml').with_content(/^logger.index.search.slowlog: INFO, index_search_slow_log_file$/).with(:source => nil) }
      end

      context "logging file " do
        let :params do {
          :logging_file => 'puppet:///path/to/logging.yml'
        } end

        it { should contain_file('/etc/elasticsearch/es-01/logging.yml').with(:source => 'puppet:///path/to/logging.yml', :content => nil) }
      end

    end

  end

  context "running as an other user" do

    let(:pre_condition) { 'class {"elasticsearch": elasticsearch_user => "myesuser", elasticsearch_group => "myesgroup" }'  }

    it { should contain_file('/usr/share/elasticsearch/data/es-01').with(:owner => 'myesuser') }
    it { should contain_file('/etc/elasticsearch/es-01').with(:owner => 'myesuser', :group => 'myesgroup') }
    it { should contain_datacat('/etc/elasticsearch/es-01/elasticsearch.yml').with(:owner => 'myesuser', :group => 'myesgroup') }
    it { should contain_file('/etc/elasticsearch/es-01/elasticsearch.yml').with(:owner => 'myesuser', :group => 'myesgroup') }
    it { should contain_file('/etc/elasticsearch/es-01/logging.yml').with(:owner => 'myesuser', :group => 'myesgroup') }
  end

  context "setting different service status then main class" do

    let(:pre_condition) { 'class {"elasticsearch": status => "enabled" }'  }

    context "status option" do

      let :params do {
        :status => 'running'
      } end

      it { should contain_service('elasticsearch-instance-es-01').with(:ensure => 'running', :enable => false) }

    end

  end

  context "init_template" do

    context "default" do
      let(:pre_condition) { 'class {"elasticsearch": }'  }

      it { should contain_elasticsearch__service('es-01').with(:init_template => 'elasticsearch/etc/init.d/elasticsearch.RedHat.erb') }
    end

    context "override in main class" do
      let(:pre_condition) { 'class {"elasticsearch": init_template => "elasticsearch/etc/init.d/elasticsearch.systemd.erb" }'  }

      it { should contain_elasticsearch__service('es-01').with(:init_template => 'elasticsearch/etc/init.d/elasticsearch.systemd.erb') }
    end

  end
end
