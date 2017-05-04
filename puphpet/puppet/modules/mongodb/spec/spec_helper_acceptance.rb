#! /usr/bin/env ruby -S rspec
require 'beaker-rspec'

UNSUPPORTED_PLATFORMS = []

unless ENV['RS_PROVISION'] == 'no' or ENV['BEAKER_provision'] == 'no'
  if hosts.first.is_pe?
    install_pe
    on hosts, 'mkdir -p /etc/puppetlabs/facter/facts.d'
  else
    install_puppet
    on hosts, 'mkdir -p /etc/facter/facts.d'
    on hosts, '/bin/touch /etc/puppet/hiera.yaml'
  end
  hosts.each do |host|
    on host, "mkdir -p #{host['distmoduledir']}"
  end
end

RSpec.configure do |c|
  # Project root
  proj_root = File.expand_path(File.join(File.dirname(__FILE__), '..'))

  # Readable test descriptions
  c.formatter = :documentation

  # Configure all nodes in nodeset
  c.before :suite do
    hosts.each do |host| 
      copy_module_to(host, :source => proj_root, :module_name => 'mongodb')
    end
    on hosts, 'puppet module install puppetlabs-stdlib'
    on hosts, 'puppet module install puppetlabs-apt'
    case fact('osfamily')
    when 'RedHat'
      on hosts, 'puppet module install stahnma-epel'
      apply_manifest_on hosts, 'include epel'
      if fact('operatingsystemrelease') =~ /^7/
        on hosts, 'yum install -y iptables-services'
      end
      on hosts, 'service iptables stop'
    end
  end
end
