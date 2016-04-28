require 'spec_helper'

describe 'elasticsearch', :type => 'class' do

  default_params = {
    :config => {},
    :manage_repo => true,
    :repo_version => '1.3',
    :version => '1.6.0'
  }

  on_supported_os.each do |os, facts|

    context "on #{os}" do


      let(:facts) do
        facts.merge({ 'scenario' => '', 'common' => '' })
      end

      let (:params) do
        default_params
      end

      context "Use anchor type for ordering" do

        let :params do
          default_params
        end

        it { should contain_class('elasticsearch::repo').that_requires('Anchor[elasticsearch::begin]') }
      end


      context "Use stage type for ordering" do

        let :params do
          default_params.merge({
            :repo_stage => 'setup'
          })
        end

        it { should contain_stage('setup') }
        it { should contain_class('elasticsearch::repo').with(:stage => 'setup') }

      end

      case facts[:osfamily]
      when 'Debian'
        context 'has apt repo parts' do
          it { should contain_apt__source('elasticsearch').with(:location => 'http://packages.elastic.co/elasticsearch/1.3/debian') }
        end
      when 'RedHat'
        context 'has yum repo parts' do
          it { should contain_yumrepo('elasticsearch').with(:baseurl => 'http://packages.elastic.co/elasticsearch/1.3/centos') }
        end
      when 'Suse'
        context 'has zypper repo parts' do
          it { should contain_exec('elasticsearch_suse_import_gpg').with(:command => 'rpmkeys --import http://packages.elastic.co/GPG-KEY-elasticsearch') }
          it { should contain_zypprepo('elasticsearch').with(:baseurl => 'http://packages.elastic.co/elasticsearch/1.3/centos') }
        end
      end

      context "Package pinning" do

        let :params do
          default_params.merge({
            :package_pin => true
          })
        end

        case facts[:osfamily]
        when 'Debian'
          context 'is supported' do
            it { should contain_apt__pin('elasticsearch').with(:packages => ['elasticsearch'], :version => '1.6.0') }
          end
        when 'RedHat'
          context 'is supported' do
            it { should contain_yum__versionlock('0:elasticsearch-1.6.0-1.noarch') }
          end
        else
          context 'is not supported' do
            pending("unable to test for warnings yet. https://github.com/rodjek/rspec-puppet/issues/108")
          end
        end

      end

      context "Override repo key ID" do

        let :params do
          default_params.merge({
            :repo_key_id => '46095ACC8548582C1A2699A9D27D666CD88E42B4'
          })
        end

        case facts[:osfamily]
        when 'Debian'
          context 'has override apt key' do
            it { is_expected.to contain_apt__source('elasticsearch').with({
              :key => '46095ACC8548582C1A2699A9D27D666CD88E42B4',
            })}
          end
        when 'Suse'
          context 'has override yum key' do
            it { is_expected.to contain_exec('elasticsearch_suse_import_gpg').with({
              :unless  => "test $(rpm -qa gpg-pubkey | grep -i '46095ACC8548582C1A2699A9D27D666CD88E42B4' | wc -l) -eq 1 ",
            })}
          end
        end

      end

      context "Override repo source URL" do

        let :params do
          default_params.merge({
            :repo_key_source => 'https://packages.elasticsearch.org/GPG-KEY-elasticsearch'
          })
        end

        case facts[:osfamily]
        when 'Debian'
          context 'has override apt key source' do
            it { is_expected.to contain_apt__source('elasticsearch').with({
              :key_source => 'https://packages.elasticsearch.org/GPG-KEY-elasticsearch',
            })}
          end
        when 'RedHat'
          context 'has override yum key source' do
            it { should contain_yumrepo('elasticsearch').with(:gpgkey => 'https://packages.elasticsearch.org/GPG-KEY-elasticsearch') }
          end
        when 'Suse'
          context 'has override yum key source' do
            it { should contain_exec('elasticsearch_suse_import_gpg').with(:command => 'rpmkeys --import https://packages.elasticsearch.org/GPG-KEY-elasticsearch') }
          end
        end

      end

    end
  end
end
