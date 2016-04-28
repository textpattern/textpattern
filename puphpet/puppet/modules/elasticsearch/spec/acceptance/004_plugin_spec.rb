require 'spec_helper_acceptance'

describe "elasticsearch plugin define:" do

  shell("mkdir -p #{default['distmoduledir']}/another/files")
  shell("cp /tmp/elasticsearch-bigdesk.zip #{default['distmoduledir']}/another/files/elasticsearch-bigdesk.zip")

  describe "Install a plugin from official repository" do

    it 'should run successfully' do
      pp = "class { 'elasticsearch': config => { 'node.name' => 'elasticsearch001', 'cluster.name' => '#{test_settings['cluster_name']}' }, manage_repo => true, repo_version => '#{test_settings['repo_version']}', java_install => true }
            elasticsearch::instance { 'es-01': config => { 'node.name' => 'elasticsearch001', 'http.port' => '#{test_settings['port_a']}' } }
            elasticsearch::plugin{'mobz/elasticsearch-head': module_dir => 'head', instances => 'es-01' }
           "

      # Run it twice and test for idempotency
      apply_manifest(pp, :catch_failures => true)
      expect(apply_manifest(pp, :catch_failures => true).exit_code).to be_zero
    end

    describe service(test_settings['service_name_a']) do
      it { should be_enabled }
      it { should be_running }
    end

    describe package(test_settings['package_name']) do
      it { should be_installed }
    end

    describe file(test_settings['pid_file_a']) do
      it { should be_file }
      its(:content) { should match /[0-9]+/ }
    end

    it 'make sure the directory exists' do
      shell('ls /usr/share/elasticsearch/plugins/head/', {:acceptable_exit_codes => 0})
    end

    it 'make sure elasticsearch reports it as existing' do
      curl_with_retries('validated plugin as installed', default, "http://localhost:#{test_settings['port_a']}/_nodes/?plugin | grep head", 0)
    end

  end
  describe "Install a plugin from custom git repo" do
    it 'should run successfully' do
    end

    it 'make sure the directory exists' do
    end

    it 'make sure elasticsearch reports it as existing' do
    end

  end

  if fact('puppetversion') =~ /3\.[2-9]\./

    describe "Install a non existing plugin" do

      it 'should run successfully' do
        pp = "class { 'elasticsearch': config => { 'node.name' => 'elasticearch001', 'cluster.name' => '#{test_settings['cluster_name']}' }, manage_repo => true, repo_version => '#{test_settings['repo_version']}', java_install => true }
              elasticsearch::instance { 'es-01': config => { 'node.name' => 'elasticsearch001', 'http.port' => '#{test_settings['port_a']}' } }
              elasticsearch::plugin{'elasticsearch/non-existing': module_dir => 'non-existing', instances => 'es-01' }
        "
        #  Run it twice and test for idempotency
        apply_manifest(pp, :expect_failures => true)
      end

    end

  else
    # The exit codes have changes since Puppet 3.2x
    # Since beaker expectations are based on the most recent puppet code All runs on previous versions fails.
  end

  describe "module removal" do

    it 'should run successfully' do
      pp = "class { 'elasticsearch': ensure => 'absent' }
            elasticsearch::instance{ 'es-01': ensure => 'absent' }
           "

      apply_manifest(pp, :catch_failures => true)
    end

    describe file('/etc/elasticsearch/es-01') do
      it { should_not be_directory }
    end

    describe package(test_settings['package_name']) do
      it { should_not be_installed }
    end

    describe service(test_settings['service_name_a']) do
      it { should_not be_enabled }
      it { should_not be_running }
    end

  end


  describe "install plugin while running ES under user 'root'" do

    it 'should run successfully' do
      pp = "class { 'elasticsearch': config => { 'node.name' => 'elasticsearch001', 'cluster.name' => '#{test_settings['cluster_name']}' }, manage_repo => true, repo_version => '#{test_settings['repo_version']}', java_install => true, elasticsearch_user => 'root', elasticsearch_group => 'root' }
            elasticsearch::instance { 'es-01': config => { 'node.name' => 'elasticsearch001', 'http.port' => '#{test_settings['port_a']}' } }
            elasticsearch::plugin{'lmenezes/elasticsearch-kopf': module_dir => 'kopf', instances => 'es-01' }
      "

      # Run it twice and test for idempotency
      apply_manifest(pp, :catch_failures => true)
      expect(apply_manifest(pp, :catch_failures => true).exit_code).to be_zero
    end

    describe service(test_settings['service_name_a']) do
      it { should be_enabled }
      it { should be_running }
    end

    describe package(test_settings['package_name']) do
      it { should be_installed }
    end

    describe file(test_settings['pid_file_a']) do
      it { should be_file }
      its(:content) { should match /[0-9]+/ }
    end

    it 'make sure the directory exists' do
      shell('ls /usr/share/elasticsearch/plugins/kopf/', {:acceptable_exit_codes => 0})
    end

    it 'make sure elasticsearch reports it as existing' do
      curl_with_retries('validated plugin as installed', default, "http://localhost:#{test_settings['port_a']}/_nodes/?plugin | grep kopf", 0)
    end

  end


  describe "module removal" do

    it 'should run successfully' do
      pp = "class { 'elasticsearch': ensure => 'absent' }
            elasticsearch::instance{ 'es-01': ensure => 'absent' }
           "

      apply_manifest(pp, :catch_failures => true)
    end

    describe file('/etc/elasticsearch/es-01') do
      it { should_not be_directory }
    end

    describe package(test_settings['package_name']) do
      it { should_not be_installed }
    end

    describe service(test_settings['service_name_a']) do
      it { should_not be_enabled }
      it { should_not be_running }
    end

  end

  describe 'plugin upgrading' do

    describe 'Setup first plugin' do
      it 'should run successful' do
        pp = "class { 'elasticsearch': config => { 'node.name' => 'elasticsearch001', 'cluster.name' => '#{test_settings['cluster_name']}' }, manage_repo => true, repo_version => '#{test_settings['repo_version']}', java_install => true, elasticsearch_user => 'root', elasticsearch_group => 'root' }
              elasticsearch::instance { 'es-01': config => { 'node.name' => 'elasticsearch001', 'http.port' => '#{test_settings['port_a']}' } }
              elasticsearch::plugin{'elasticsearch/elasticsearch-cloud-aws/2.1.1': module_dir => 'cloud-aws', instances => 'es-01' }
        "

        # Run it twice and test for idempotency
        apply_manifest(pp, :catch_failures => true)
        expect(apply_manifest(pp, :catch_failures => true).exit_code).to be_zero

      end

      it 'make sure the directory exists' do
        shell('ls /usr/share/elasticsearch/plugins/cloud-aws/', {:acceptable_exit_codes => 0})
      end

      it 'make sure elasticsearch reports it as existing' do
        curl_with_retries('validated plugin as installed', default, "http://localhost:#{test_settings['port_a']}/_nodes/?plugin | grep cloud-aws | grep 2.1.1", 0)
      end

    end

    describe "Upgrade plugin" do
      it 'Should run succesful' do
        pp = "class { 'elasticsearch': config => { 'node.name' => 'elasticsearch001', 'cluster.name' => '#{test_settings['cluster_name']}' }, manage_repo => true, repo_version => '#{test_settings['repo_version']}', java_install => true, elasticsearch_user => 'root', elasticsearch_group => 'root' }
              elasticsearch::instance { 'es-01': config => { 'node.name' => 'elasticsearch001', 'http.port' => '#{test_settings['port_a']}' } }
              elasticsearch::plugin{'elasticsearch/elasticsearch-cloud-aws/2.2.0': module_dir => 'cloud-aws', instances => 'es-01' }
        "

        # Run it twice and test for idempotency
        apply_manifest(pp, :catch_failures => true)
        expect(apply_manifest(pp, :catch_failures => true).exit_code).to be_zero

      end

      it 'make sure elasticsearch reports it as existing' do
        curl_with_retries('validated plugin as installed', default, "http://localhost:#{test_settings['port_a']}/_nodes/?plugin | grep cloud-aws | grep 2.2.0", 0)
      end
    end

  end

  describe "offline install via puppetmaster" do
      it 'Should run succesful' do
        pp = "class { 'elasticsearch': config => { 'node.name' => 'elasticsearch001', 'cluster.name' => '#{test_settings['cluster_name']}' }, manage_repo => true, repo_version => '#{test_settings['repo_version']}', java_install => true, elasticsearch_user => 'root', elasticsearch_group => 'root' }
              elasticsearch::instance { 'es-01': config => { 'node.name' => 'elasticsearch001', 'http.port' => '#{test_settings['port_a']}' } }
              elasticsearch::plugin{'bigdesk': source => 'puppet:///modules/another/elasticsearch-bigdesk.zip', instances => 'es-01' }
        "

        # Run it twice and test for idempotency
        apply_manifest(pp, :catch_failures => true)
        expect(apply_manifest(pp, :catch_failures => true).exit_code).to be_zero

      end

      it 'make sure elasticsearch reports it as existing' do
        curl_with_retries('validated plugin as installed', default, "http://localhost:#{test_settings['port_a']}/_nodes/?plugin | grep bigdesk", 0)
      end

  end

  describe "module removal" do

    it 'should run successfully' do
      pp = "class { 'elasticsearch': ensure => 'absent' }
            elasticsearch::instance{ 'es-01': ensure => 'absent' }
           "

      apply_manifest(pp, :catch_failures => true)
    end

    describe file('/etc/elasticsearch/es-01') do
      it { should_not be_directory }
    end

    describe package(test_settings['package_name']) do
      it { should_not be_installed }
    end

    describe service(test_settings['service_name_a']) do
      it { should_not be_enabled }
      it { should_not be_running }
    end

  end

  describe "install via url" do
      it 'Should run succesful' do
        pp = "class { 'elasticsearch': config => { 'node.name' => 'elasticsearch001', 'cluster.name' => '#{test_settings['cluster_name']}' }, manage_repo => true, repo_version => '#{test_settings['repo_version']}', java_install => true }
              elasticsearch::instance { 'es-01': config => { 'node.name' => 'elasticsearch001', 'http.port' => '#{test_settings['port_a']}' } }
              elasticsearch::plugin{'HQ': url => 'https://github.com/royrusso/elasticsearch-HQ/archive/v2.0.3.zip', instances => 'es-01' }
        "

        # Run it twice and test for idempotency
        apply_manifest(pp, :catch_failures => true)
        expect(apply_manifest(pp, :catch_failures => true).exit_code).to be_zero

      end

      it 'make sure elasticsearch reports it as existing' do
        curl_with_retries('validated plugin as installed', default, "http://localhost:#{test_settings['port_a']}/_nodes/?plugin | grep HQ", 0)
      end

  end

  describe "module removal" do

    it 'should run successfully' do
      pp = "class { 'elasticsearch': ensure => 'absent' }
            elasticsearch::instance{ 'es-01': ensure => 'absent' }
           "

      apply_manifest(pp, :catch_failures => true)
    end

    describe file('/etc/elasticsearch/es-01') do
      it { should_not be_directory }
    end

    describe package(test_settings['package_name']) do
      it { should_not be_installed }
    end

    describe service(test_settings['service_name_a']) do
      it { should_not be_enabled }
      it { should_not be_running }
    end

  end


end
