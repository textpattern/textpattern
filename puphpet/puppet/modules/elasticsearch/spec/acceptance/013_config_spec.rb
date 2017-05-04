require 'spec_helper_acceptance'

describe "elasticsearch class:" do

  describe "Setup single instance" do

    it 'should run successfully' do
      pp = "class { 'elasticsearch': config => { 'cluster.name' => 'foobar' }, manage_repo => true, repo_version => '#{test_settings['repo_version']}', java_install => true }
            elasticsearch::instance { 'es-01': config => { 'node.name' => 'elasticsearch001', 'http.port' => '#{test_settings['port_a']}', 'node.master' => true, 'node.data' => false, 'index' => { 'routing' => { 'allocation' => { 'include' => 'tag1', 'exclude' => [ 'tag2', 'tag3' ] } } }, 'node' => { 'rack' => 46 }, 'boostrap.mlockall' => true, 'cluster.name' => '#{test_settings['cluster_name']}' } }
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

    describe "Elasticsearch serves requests on" do
      it {
        curl_with_retries("check ES on #{test_settings['port_a']}", default, "http://localhost:#{test_settings['port_a']}/?pretty=true", 0)
      }
    end

    describe file('/etc/elasticsearch/es-01/elasticsearch.yml') do
      it { should be_file }
      it { should contain 'name: elasticsearch001' }
      it { should contain 'master: true' }
      it { should contain 'data: false' }
      it { should contain "cluster:\n  name: #{test_settings['cluster_name']}" }
      it { should contain 'rack: 46' }
      it { should contain "index: \n  routing: \n    allocation: \n      exclude: \n             - tag2\n             - tag3\n      include: tag1" }
    end

    describe file('/usr/share/elasticsearch/templates_import') do
      it { should be_directory }
    end

    describe file('/usr/share/elasticsearch/scripts') do
      it { should be_directory }
    end

    describe file('/etc/elasticsearch/es-01/scripts') do
      it { should be_symlink }
    end

  end

  describe "Cleanup" do

    it 'should run successfully' do
      pp = "class { 'elasticsearch': ensure => 'absent' }
            elasticsearch::instance{ 'es-01': ensure => 'absent' }
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
