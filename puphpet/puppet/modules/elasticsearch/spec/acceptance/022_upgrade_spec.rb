require 'spec_helper_acceptance'

describe "elasticsearch 2x:" do

  shell("mkdir -p #{default['distmoduledir']}/another/files")
  shell("cp /tmp/elasticsearch-kopf.zip #{default['distmoduledir']}/another/files/elasticsearch-kopf.zip")

  describe 'upgrading', :upgrade => true do

    describe 'Setup 2.0.0' do
      it 'should run successful' do
        pp = "class { 'elasticsearch': config => { 'node.name' => 'elasticsearch001', 'cluster.name' => '#{test_settings['cluster_name']}' }, manage_repo => true, repo_version => '#{test_settings['repo_version2x']}', java_install => true, version => '2.0.0' }
              elasticsearch::instance { 'es-01': config => { 'node.name' => 'elasticsearch001', 'http.port' => '#{test_settings['port_a']}' } }
        "

        # Run it twice and test for idempotency
        apply_manifest(pp, :catch_failures => true)
        expect(apply_manifest(pp, :catch_failures => true).exit_code).to be_zero

      end

      it 'make sure elasticsearch runs with the correct version' do
        curl_with_retries('Correct version', default, "http://localhost:#{test_settings['port_a']}/ | grep 2.0.0", 0)
      end


    end

    describe "Upgrade to 2.0.1" do
      it 'Should run succesful' do
        pp = "class { 'elasticsearch': config => { 'node.name' => 'elasticsearch001', 'cluster.name' => '#{test_settings['cluster_name']}' }, manage_repo => true, repo_version => '#{test_settings['repo_version2x']}', java_install => true, version => '2.0.1' }
              elasticsearch::instance { 'es-01': config => { 'node.name' => 'elasticsearch001', 'http.port' => '#{test_settings['port_a']}' } }
        "

        # Run it twice and test for idempotency
        apply_manifest(pp, :catch_failures => true)
        expect(apply_manifest(pp, :catch_failures => true).exit_code).to be_zero

      end

      it 'make sure elasticsearch runs with the correct version' do
        curl_with_retries('correct version', default, "http://localhost:#{test_settings['port_a']}/ | grep 2.0.1", 0)
      end
    end

  end

end
