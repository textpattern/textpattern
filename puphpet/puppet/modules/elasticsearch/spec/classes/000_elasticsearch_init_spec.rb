require 'spec_helper'

describe 'elasticsearch', :type => 'class' do

  default_params = {
    :config  => { 'node.name' => 'foo' }
  }

  on_supported_os.each do |os, facts|

    context "on #{os}" do

      case facts[:osfamily]
      when 'Debian'
        let(:defaults_path) { '/etc/default' }
        let(:pkg_ext) { 'deb' }
        let(:pkg_prov) { 'dpkg' }
        let(:version_add) { '' }
        if facts[:lsbmajdistrelease] >= '8'
          test_pid = true
        else
          test_pid = false
        end
      when 'RedHat'
        let(:defaults_path) { '/etc/sysconfig' }
        let(:pkg_ext) { 'rpm' }
        let(:pkg_prov) { 'rpm' }
        let(:version_add) { '-1' }
        if facts[:operatingsystemmajrelease] >= '7'
          test_pid = true
        else
          test_pid = false
        end
      when 'Suse'
        let(:defaults_path) { '/etc/sysconfig' }
        let(:pkg_ext) { 'rpm' }
        let(:pkg_prov) { 'rpm' }
        let(:version_add) { '-1' }
      end

      let(:facts) do
        facts.merge({ 'scenario' => '', 'common' => '' })
      end

      let (:params) do
        default_params.merge({ })
      end

      context 'main class tests' do

        # init.pp
        it { should compile.with_all_deps }
        it { should contain_class('elasticsearch') }
        it { should contain_anchor('elasticsearch::begin') }
        it { should contain_class('elasticsearch::params') }
        it { should contain_class('elasticsearch::package').that_requires('Anchor[elasticsearch::begin]') }
        it { should contain_class('elasticsearch::config').that_requires('Class[elasticsearch::package]') }

        # Base directories
        it { should contain_file('/etc/elasticsearch') }
        it { should contain_file('/usr/share/elasticsearch/templates_import') }
        it { should contain_file('/usr/share/elasticsearch/scripts') }
        it { should contain_file('/usr/share/elasticsearch') }
        it { should contain_file('/usr/share/elasticsearch/lib') }
	it { should contain_augeas("#{defaults_path}/elasticsearch") }

        it { should contain_exec('remove_plugin_dir') }

        # Base files
        if test_pid == true
          it { should contain_file('/usr/lib/tmpfiles.d/elasticsearch.conf') }
        end

	# file removal from package
	it { should contain_file('/etc/init.d/elasticsearch').with(:ensure => 'absent') }
	it { should contain_file('/lib/systemd/system/elasticsearch.service').with(:ensure => 'absent') }
	it { should contain_file('/etc/elasticsearch/elasticsearch.yml').with(:ensure => 'absent') }
	it { should contain_file('/etc/elasticsearch/logging.yml').with(:ensure => 'absent') }
      end

      context 'package installation' do

        context 'via repository' do

          context 'with default settings' do

            it { should contain_package('elasticsearch').with(:ensure => 'present') }
            it { should_not contain_package('my-elasticsearch').with(:ensure => 'present') }

          end

          context 'with specified version' do

            let (:params) {
              default_params.merge({
                :version => '1.0'
              })
            }

            it { should contain_package('elasticsearch').with(:ensure => "1.0#{version_add}") }
          end

          if facts[:osfamily] == 'RedHat'
            context 'Handle special CentOS/RHEL package versioning' do

              let (:params) {
                default_params.merge({
                  :version => '1.1-2'
                })
              }

              it { should contain_package('elasticsearch').with(:ensure => "1.1-2") }
            end
          end

          context 'with specified package name' do

            let (:params) {
              default_params.merge({
                :package_name => 'my-elasticsearch'
              })
            }

            it { should contain_package('my-elasticsearch').with(:ensure => 'present') }
            it { should_not contain_package('elasticsearch').with(:ensure => 'present') }
          end

          context 'with auto upgrade enabled' do

            let (:params) {
              default_params.merge({
                :autoupgrade => true
              })
            }

            it { should contain_package('elasticsearch').with(:ensure => 'latest') }
          end

        end

        context 'when setting package version and package_url' do

          let (:params) {
            default_params.merge({
              :version     => '0.90.10',
              :package_url => "puppet:///path/to/some/elasticsearch-0.90.10.#{pkg_ext}"
            })
          }

          it { expect { should raise_error(Puppet::Error) } }

        end

        context 'via package_url setting' do

          context 'using puppet:/// schema' do

            let (:params) {
              default_params.merge({
                :package_url => "puppet:///path/to/package.#{pkg_ext}"
              })
            }

            it { should contain_file("/opt/elasticsearch/swdl/package.#{pkg_ext}").with(:source => "puppet:///path/to/package.#{pkg_ext}", :backup => false) }
            it { should contain_package('elasticsearch').with(:ensure => 'present', :source => "/opt/elasticsearch/swdl/package.#{pkg_ext}", :provider => "#{pkg_prov}") }
          end

          context 'using http:// schema' do

            let (:params) {
              default_params.merge({
                :package_url => "http://www.domain.com/path/to/package.#{pkg_ext}"
              })
            }

            it { should contain_exec('create_package_dir_elasticsearch').with(:command => 'mkdir -p /opt/elasticsearch/swdl') }
            it { should contain_file('/opt/elasticsearch/swdl').with(:purge => false, :force => false, :require => "Exec[create_package_dir_elasticsearch]") }
            it { should contain_exec('download_package_elasticsearch').with(:command => "wget --no-check-certificate -O /opt/elasticsearch/swdl/package.#{pkg_ext} http://www.domain.com/path/to/package.#{pkg_ext} 2> /dev/null", :require => 'File[/opt/elasticsearch/swdl]') }
            it { should contain_package('elasticsearch').with(:ensure => 'present', :source => "/opt/elasticsearch/swdl/package.#{pkg_ext}", :provider => "#{pkg_prov}") }
          end

          context 'using http:// schema with proxy_url' do

            let (:params) {
              default_params.merge({
                :package_url  => "http://www.domain.com/path/to/package.#{pkg_ext}",
                :proxy_url    => "http://proxy.example.com:12345/",
              })
            }
            it { should contain_exec('download_package_elasticsearch').with(:environment => ["use_proxy=yes","http_proxy=http://proxy.example.com:12345/","https_proxy=http://proxy.example.com:12345/",]) }
          end

          context 'using https:// schema' do

            let (:params) {
              default_params.merge({
                :package_url => "https://www.domain.com/path/to/package.#{pkg_ext}"
              })
            }

            it { should contain_exec('create_package_dir_elasticsearch').with(:command => 'mkdir -p /opt/elasticsearch/swdl') }
            it { should contain_file('/opt/elasticsearch/swdl').with(:purge => false, :force => false, :require => 'Exec[create_package_dir_elasticsearch]') }
            it { should contain_exec('download_package_elasticsearch').with(:command => "wget --no-check-certificate -O /opt/elasticsearch/swdl/package.#{pkg_ext} https://www.domain.com/path/to/package.#{pkg_ext} 2> /dev/null", :require => 'File[/opt/elasticsearch/swdl]') }
            it { should contain_package('elasticsearch').with(:ensure => 'present', :source => "/opt/elasticsearch/swdl/package.#{pkg_ext}", :provider => "#{pkg_prov}") }
          end

          context 'using ftp:// schema' do

            let (:params) {
              default_params.merge({
                :package_url => "ftp://www.domain.com/path/to/package.#{pkg_ext}"
              })
            }

            it { should contain_exec('create_package_dir_elasticsearch').with(:command => 'mkdir -p /opt/elasticsearch/swdl') }
            it { should contain_file('/opt/elasticsearch/swdl').with(:purge => false, :force => false, :require => 'Exec[create_package_dir_elasticsearch]') }
            it { should contain_exec('download_package_elasticsearch').with(:command => "wget --no-check-certificate -O /opt/elasticsearch/swdl/package.#{pkg_ext} ftp://www.domain.com/path/to/package.#{pkg_ext} 2> /dev/null", :require => 'File[/opt/elasticsearch/swdl]') }
            it { should contain_package('elasticsearch').with(:ensure => 'present', :source => "/opt/elasticsearch/swdl/package.#{pkg_ext}", :provider => "#{pkg_prov}") }
          end

          context 'using file:// schema' do

            let (:params) {
              default_params.merge({
                :package_url => "file:/path/to/package.#{pkg_ext}"
              })
            }

            it { should contain_exec('create_package_dir_elasticsearch').with(:command => 'mkdir -p /opt/elasticsearch/swdl') }
            it { should contain_file('/opt/elasticsearch/swdl').with(:purge => false, :force => false, :require => 'Exec[create_package_dir_elasticsearch]') }
            it { should contain_file("/opt/elasticsearch/swdl/package.#{pkg_ext}").with(:source => "/path/to/package.#{pkg_ext}", :backup => false) }
            it { should contain_package('elasticsearch').with(:ensure => 'present', :source => "/opt/elasticsearch/swdl/package.#{pkg_ext}", :provider => "#{pkg_prov}") }
          end

        end

      end # package

      context 'when setting the module to absent' do

        let (:params) {
          default_params.merge({
            :ensure => 'absent'
          })
        }

        it { should contain_package('elasticsearch').with(:ensure => 'purged') }
        it { should contain_file('/usr/share/elasticsearch/plugins').with(:ensure => 'absent') }

      end

      context 'When managing the repository' do

        let (:params) {
          default_params.merge({
            :manage_repo => true,
            :repo_version => '1.0'
          })
        }
        case facts[:osfamily]
        when 'Debian'
          it { should contain_class('elasticsearch::repo').that_requires('Anchor[elasticsearch::begin]') }
          it { should contain_class('apt') }
          it { should contain_apt__source('elasticsearch').with(:release => 'stable', :repos => 'main', :location => 'http://packages.elastic.co/elasticsearch/1.0/debian') }
        when 'RedHat'
          it { should contain_class('elasticsearch::repo').that_requires('Anchor[elasticsearch::begin]') }
          it { should contain_yumrepo('elasticsearch').with(:baseurl => 'http://packages.elastic.co/elasticsearch/1.0/centos', :gpgkey => 'http://packages.elastic.co/GPG-KEY-elasticsearch', :enabled => 1) }
        when 'SuSE'
          it { should contain_class('elasticsearch::repo').that_requires('Anchor[elasticsearch::begin]') }
          it { should contain_exec('elasticsearch_suse_import_gpg') }
          it { should contain_zypprepo('elasticsearch').with(:baseurl => 'http://packages.elastic.co/elasticsearch/1.0/centos') }
        end

      end

      context 'when not supplying a repo_version' do
        let (:params) {
          default_params.merge({
            :manage_repo => true,
          })
        }
        it { expect { should raise_error(Puppet::Error, 'Please fill in a repository version at $repo_version') } }
      end

      context "Running a a different user" do

        let (:params) {
          default_params.merge({
            :elasticsearch_user => 'myesuser',
            :elasticsearch_group => 'myesgroup'
          })
        }

        it { should contain_file('/etc/elasticsearch').with(:owner => 'myesuser', :group => 'myesgroup') }
        it { should contain_file('/var/log/elasticsearch').with(:owner => 'myesuser') }
        it { should contain_file('/usr/share/elasticsearch').with(:owner => 'myesuser', :group => 'myesgroup') }
        # it { should contain_file('/usr/share/elasticsearch/plugins').with(:owner => 'myesuser', :group => 'myesgroup') }
        it { should contain_file('/usr/share/elasticsearch/data').with(:owner => 'myesuser', :group => 'myesgroup') }
        it { should contain_file('/var/run/elasticsearch').with(:owner => 'myesuser') } if facts[:osfamily] == 'RedHat'
      end

    end

  end

end
