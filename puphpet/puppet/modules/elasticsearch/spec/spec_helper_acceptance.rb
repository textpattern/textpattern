require 'beaker-rspec'
require 'pry'
require 'securerandom'
require_relative 'spec_acceptance_integration'

def test_settings
  RSpec.configuration.test_settings
end

RSpec.configure do |c|
  c.add_setting :test_settings, :default => {}
end

files_dir = ENV['files_dir'] || '/home/jenkins/puppet'

proxy_host = ENV['BEAKER_PACKAGE_PROXY'] || ''

if !proxy_host.empty?
  gem_proxy = "http_proxy=#{proxy_host}" unless proxy_host.empty?

  hosts.each do |host|
    on host, "echo 'export http_proxy='#{proxy_host}'' >> /root/.bashrc"
    on host, "echo 'export https_proxy='#{proxy_host}'' >> /root/.bashrc"
    on host, "echo 'export no_proxy=\"localhost,127.0.0.1,localaddress,.localdomain.com,#{host.name}\"' >> /root/.bashrc"
  end
else
  gem_proxy = ''
end

hosts.each do |host|

  # Install Puppet
  if host.is_pe?
    install_pe
  else
    puppetversion = ENV['VM_PUPPET_VERSION']
    on host, "#{gem_proxy} gem install puppet --no-ri --no-rdoc --version '~> #{puppetversion}'"
    on host, "mkdir -p #{host['distmoduledir']}"

    if fact('osfamily') == 'Suse'
      install_package host, 'rubygems ruby-devel augeas-devel libxml2-devel'
      on host, "#{gem_proxy} gem install ruby-augeas --no-ri --no-rdoc"
    end

    if host[:type] == 'aio'
      on host, "mkdir -p /var/log/puppetlabs/puppet"
    end

  end

  if ENV['ES_VERSION']

    case fact('osfamily')
      when 'RedHat'
        if ENV['ES_VERSION'][0,1] == '1'
          ext='noarch.rpm'
        else
          ext='rpm'
        end
      when 'Debian'
        ext='deb'
      when  'Suse'
        ext='rpm'
    end

    url = get_url
    RSpec.configuration.test_settings['snapshot_package'] = url.gsub('$EXT$', ext)

  else

    case fact('osfamily')
      when 'RedHat'
        scp_to(host, "#{files_dir}/elasticsearch-1.3.1.noarch.rpm", '/tmp/elasticsearch-1.3.1.noarch.rpm')
      when 'Debian'
        case fact('lsbmajdistrelease')
          when '6'
            scp_to(host, "#{files_dir}/elasticsearch-1.1.0.deb", '/tmp/elasticsearch-1.1.0.deb')
          else
            scp_to(host, "#{files_dir}/elasticsearch-1.3.1.deb", '/tmp/elasticsearch-1.3.1.deb')
        end
      when 'Suse'
        case fact('operatingsystem')
          when 'OpenSuSE'
            scp_to(host, "#{files_dir}/elasticsearch-1.3.1.noarch.rpm", '/tmp/elasticsearch-1.3.1.noarch.rpm')
        end
    end

    scp_to(host, "#{files_dir}/elasticsearch-bigdesk.zip", "/tmp/elasticsearch-bigdesk.zip")
    scp_to(host, "#{files_dir}/elasticsearch-kopf.zip", "/tmp/elasticsearch-kopf.zip")

  end

  # on debian/ubuntu nodes ensure we get the latest info
  # Can happen we have stalled data in the images
  if fact('osfamily') == 'Debian'
    on host, "apt-get update"
  end
  if fact('osfamily') == 'RedHat'
    on host, "yum -y update"
  end

end

RSpec.configure do |c|
  # Project root
  proj_root = File.expand_path(File.join(File.dirname(__FILE__), '..'))

  # Readable test descriptions
  c.formatter = :documentation

  # Configure all nodes in nodeset
  c.before :suite do
    # Install module and dependencies
    puppet_module_install(:source => proj_root, :module_name => 'elasticsearch')
    hosts.each do |host|

      copy_hiera_data_to(host, 'spec/fixtures/hiera/hieradata/')
      on host, puppet('module','install','puppetlabs-java'), { :acceptable_exit_codes => [0,1] }
      on host, puppet('module','install','richardc-datacat'), { :acceptable_exit_codes => [0,1] }

      if fact('osfamily') == 'Debian'
        on host, puppet('module','install','puppetlabs-apt', '--version=1.8.0'), { :acceptable_exit_codes => [0,1] }
      end
      if fact('osfamily') == 'Suse'
        on host, puppet('module','install','darin-zypprepo'), { :acceptable_exit_codes => [0,1] }
      end
      if fact('osfamily') == 'RedHat'
        on host, puppet('module', 'upgrade', 'puppetlabs-stdlib'), {  :acceptable_exit_codes => [0,1] }
        on host, puppet('module', 'install', 'ceritsc-yum'), { :acceptable_exit_codes => [0,1] }
      end

      if host.is_pe?
        on(host, 'sed -i -e "s/PATH=PATH:\/opt\/puppet\/bin:/PATH=PATH:/" ~/.ssh/environment')
      end

      on(host, 'mkdir -p etc/puppet/modules/another/files/')

    end
  end

  c.after :suite do
    if ENV['ES_VERSION']
      hosts.each do |host|
        timestamp = Time.now
        log_dir = File.join('./spec/logs', timestamp.strftime("%F_%H_%M_%S"))
        FileUtils.mkdir_p(log_dir) unless File.directory?(log_dir)
        scp_from(host, '/var/log/elasticsearch', log_dir)
      end
    end
  end

end

require_relative 'spec_acceptance_common'
