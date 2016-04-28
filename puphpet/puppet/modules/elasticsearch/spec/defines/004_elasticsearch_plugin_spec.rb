require 'spec_helper'

describe 'elasticsearch::plugin', :type => 'define' do

  let(:title) { 'mobz/elasticsearch-head/1.0.0' }
  let :facts do {
    :operatingsystem => 'CentOS',
    :kernel => 'Linux',
    :osfamily => 'RedHat',
    :operatingsystemmajrelease => '6',
    :scenario => '',
    :common => ''
  } end
  let(:pre_condition) { 'class {"elasticsearch": config => { "node" => {"name" => "test" }}}'}

  context 'with module_dir' do

    context "Add a plugin" do

      let :params do {
        :ensure     => 'present',
        :module_dir => 'head',
        :instances  => 'es-01'
      } end

      it { should contain_elasticsearch__plugin('mobz/elasticsearch-head/1.0.0') }
      it { should contain_elasticsearch_plugin('mobz/elasticsearch-head/1.0.0') }
    end

    context "Remove a plugin" do

      let :params do {
        :ensure     => 'absent',
        :module_dir => 'head',
        :instances  => 'es-01'
      } end

      it { should contain_elasticsearch__plugin('mobz/elasticsearch-head/1.0.0') }
      it { should contain_elasticsearch_plugin('mobz/elasticsearch-head/1.0.0').with(:ensure => 'absent') }
    end

  end

  context 'with url' do

    context "Add a plugin with full name" do

      let :params do {
        :ensure     => 'present',
        :instances  => 'es-01',
        :url        => 'https://github.com/mobz/elasticsearch-head/archive/master.zip',
      } end

      it { should contain_elasticsearch__plugin('mobz/elasticsearch-head/1.0.0') }
      it { should contain_elasticsearch_plugin('mobz/elasticsearch-head/1.0.0').with(:ensure => 'present', :url => 'https://github.com/mobz/elasticsearch-head/archive/master.zip') }
    end

  end

  context "offline plugin install" do

      let(:title) { 'head' }
      let :params do {
        :ensure     => 'present',
        :instances  => 'es-01',
        :source     => 'puppet:///path/to/my/plugin.zip',
      } end

      it { should contain_elasticsearch__plugin('head') }
      it { should contain_file('/opt/elasticsearch/swdl/plugin.zip').with(:source => 'puppet:///path/to/my/plugin.zip', :before => 'Elasticsearch_plugin[head]') }
      it { should contain_elasticsearch_plugin('head').with(:ensure => 'present', :source => '/opt/elasticsearch/swdl/plugin.zip') }

  end
  
end
