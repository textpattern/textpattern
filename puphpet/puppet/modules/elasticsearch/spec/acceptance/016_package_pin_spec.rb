require 'spec_helper_acceptance'

describe "Package pinning:" do

  if fact('osfamily') != 'Suse'

    describe "Pinning enabled" do

      describe "Setup" do

        it 'should run successful' do
          write_hiera_config('')
          pp = "class { 'elasticsearch': config => { 'cluster.name' => '#{test_settings['cluster_name']}'}, manage_repo => true, repo_version => '#{test_settings['repo_version']}', version => '#{test_settings['install_package_version']}', java_install => true }
                elasticsearch::instance { 'es-01': config => { 'node.name' => 'elasticsearch001', 'http.port' => '#{test_settings['port_a']}' } }
               "
          # Run it twice and test for idempotency
          apply_manifest(pp, :catch_failures => true)
          expect(apply_manifest(pp, :catch_failures => true).exit_code).to be_zero
        end

        describe package(test_settings['package_name']) do
          it { should be_installed.with_version(test_settings['install_version']) }
        end

      end # end setup

      describe "Run upgrade" do
        it 'should run fine' do
          case fact('osfamily')
          when 'Debian'
            shell('apt-get update && apt-get -y install elasticsearch')
          when 'RedHat'
            shell('yum -y update elasticsearch')
          end
        end
      end

      describe "check installed package" do

        describe package(test_settings['package_name']) do
          it { should be_installed.with_version(test_settings['install_version']) }
        end

      end

      describe "Upgrade" do

        it 'should run successful' do
          pp = "class { 'elasticsearch': config => { 'cluster.name' => '#{test_settings['cluster_name']}'}, manage_repo => true, repo_version => '#{test_settings['repo_version']}', version => '#{test_settings['upgrade_package_version']}', java_install => true }
                elasticsearch::instance { 'es-01': config => { 'node.name' => 'elasticsearch001', 'http.port' => '#{test_settings['port_a']}' } }
               "
          # Run it twice and test for idempotency
          apply_manifest(pp, :catch_failures => true)
          expect(apply_manifest(pp, :catch_failures => true).exit_code).to be_zero
        end

        describe package(test_settings['package_name']) do
          it { should be_installed.with_version(test_settings['upgrade_version']) }
        end

      end # end setup

      describe "Run upgrade" do
        it 'should run fine' do
          case fact('osfamily')
          when 'Debian'
            shell('apt-get update && apt-get -y install elasticsearch')
          when 'RedHat'
            shell('yum -y update elasticsearch')
          end
        end
      end

      describe "check installed package" do

        describe package(test_settings['package_name']) do
          it { should be_installed.with_version(test_settings['upgrade_version']) }
        end

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

end
