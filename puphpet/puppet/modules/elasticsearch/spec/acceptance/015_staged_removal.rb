require 'spec_helper_acceptance'

describe "elasticsearch class:" do

  describe "Setup" do

    it 'should run successfully' do
      pp = "class { 'elasticsearch': config => { 'cluster.name' => '#{test_settings['cluster_name']}'}, manage_repo => true, repo_version => '#{test_settings['repo_version']}', java_install => true }
            elasticsearch::instance { 'es-01': config => { 'node.name' => 'elasticsearch001', 'http.port' => '#{test_settings['port_a']}' } }
            elasticsearch::instance { 'es-02': config => { 'node.name' => 'elasticsearch002', 'http.port' => '#{test_settings['port_b']}' } }
           "

      # Run it twice and test for idempotency
      apply_manifest(pp, :catch_failures => true)
      expect(apply_manifest(pp, :catch_failures => true).exit_code).to be_zero
    end
  end

  describe "First removal of instance 1" do

    it 'should run successfully' do
      pp = "class { 'elasticsearch': config => { 'cluster.name' => '#{test_settings['cluster_name']}'}, manage_repo => true, repo_version => '#{test_settings['repo_version']}', java_install => true }
            elasticsearch::instance{ 'es-01': ensure => 'absent' }
            elasticsearch::instance { 'es-02': config => { 'node.name' => 'elasticsearch002', 'http.port' => '#{test_settings['port_b']}' } }
           "

      apply_manifest(pp, :catch_failures => true)
    end

  end

  describe "Second removal of instance 1" do

    it 'should run successfully' do
      pp = "class { 'elasticsearch': config => { 'cluster.name' => '#{test_settings['cluster_name']}'}, manage_repo => true, repo_version => '#{test_settings['repo_version']}', java_install => true }
            elasticsearch::instance{ 'es-01': ensure => 'absent' }
            elasticsearch::instance { 'es-02': config => { 'node.name' => 'elasticsearch002', 'http.port' => '#{test_settings['port_b']}' } }
           "

      apply_manifest(pp, :catch_failures => true)
    end

  end

  describe "First removal of the rest" do

    it 'should run successfully' do
      pp = "class { 'elasticsearch': ensure => 'absent' }
            elasticsearch::instance{ 'es-02': ensure => 'absent' }
           "

      apply_manifest(pp, :catch_failures => true)
    end

  end

  describe "Second removal of the rest" do

    it 'should run successfully' do
      pp = "class { 'elasticsearch': ensure => 'absent' }
            elasticsearch::instance{ 'es-02': ensure => 'absent' }
           "

      apply_manifest(pp, :catch_failures => true)
    end

  end

end
