require 'spec_helper_acceptance'

# Here we put the more basic fundamental tests, ultra obvious stuff.

describe "Hiera tests" do

  describe "single instance" do

    it 'should run successfully' do
      write_hiera_config(['singleinstance'])
      pp = "class { 'elasticsearch': manage_repo => true, repo_version => '#{test_settings['repo_version']}', java_install => true }"

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

    describe "Elasticsearch serves requests on" do
      it {
        curl_with_retries("check ES on #{test_settings['port_a']}", default, "http://localhost:#{test_settings['port_a']}/?pretty=true", 0)
      }
    end

    describe file('/etc/elasticsearch/es-01/elasticsearch.yml') do
      it { should be_file }
      it { should contain 'name: es-01' }
    end

    describe file('/usr/share/elasticsearch/templates_import') do
      it { should be_directory }
    end

  end

  describe "single instance with plugin" do

    it 'should run successfully' do
      write_hiera_config(['singleplugin'])
      pp = "class { 'elasticsearch': manage_repo => true, repo_version => '#{test_settings['repo_version']}', java_install => true }"

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

    describe "Elasticsearch serves requests on" do
      it {
        curl_with_retries("check ES on #{test_settings['port_a']}", default, "http://localhost:#{test_settings['port_a']}/?pretty=true", 0)
      }
    end

    describe file('/etc/elasticsearch/es-01/elasticsearch.yml') do
      it { should be_file }
      it { should contain 'name: es-01' }
    end

    describe file('/usr/share/elasticsearch/templates_import') do
      it { should be_directory }
    end

    it 'make sure the directory exists' do
      shell('ls /usr/share/elasticsearch/plugins/head/', {:acceptable_exit_codes => 0})
    end

    it 'make sure elasticsearch reports it as existing' do
      curl_with_retries('validated plugin as installed', default, "http://localhost:#{test_settings['port_a']}/_nodes/?plugin | grep head", 0)
    end

  end

  describe "multiple instances" do


    it 'should run successfully' do
      write_hiera_config(['multipleinstances'])
      pp = "class { 'elasticsearch': manage_repo => true, repo_version => '#{test_settings['repo_version']}', java_install => true }"

      # Run it twice and test for idempotency
      apply_manifest(pp, :catch_failures => true)
      expect(apply_manifest(pp, :catch_failures => true).exit_code).to be_zero
    end

    describe service(test_settings['service_name_a']) do
      it { should be_enabled }
      it { should be_running }
    end

    describe service(test_settings['service_name_b']) do
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

    describe file(test_settings['pid_file_b']) do
      it { should be_file }
      its(:content) { should match /[0-9]+/ }
    end

    describe "make sure elasticsearch can serve requests #{test_settings['port_a']}" do
      it {
        curl_with_retries("check ES on #{test_settings['port_a']}", default, "http://localhost:#{test_settings['port_a']}/?pretty=true", 0)
      }
    end

    describe "make sure elasticsearch can serve requests #{test_settings['port_b']}" do
      it {
        curl_with_retries("check ES on #{test_settings['port_b']}", default, "http://localhost:#{test_settings['port_b']}/?pretty=true", 0)
      }
    end

    describe file('/etc/elasticsearch/es-01/elasticsearch.yml') do
      it { should be_file }
      it { should contain 'name: es-01' }
    end

    describe file('/etc/elasticsearch/es-02/elasticsearch.yml') do
      it { should be_file }
      it { should contain 'name: es-02' }
    end

  end


  describe "Cleanup" do

    it 'should run successfully' do
      pp = "class { 'elasticsearch': ensure => 'absent' }
           "

      apply_manifest(pp, :catch_failures => true)
    end

    describe file('/etc/elasticsearch/es-01') do
      it { should_not be_directory }
    end

    describe service(test_settings['service_name_a']) do
      it { should_not be_enabled }
      it { should_not be_running }
    end

  end


end
