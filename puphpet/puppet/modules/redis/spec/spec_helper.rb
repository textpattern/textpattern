require 'rubygems'
require 'puppetlabs_spec_helper/module_spec_helper'

def centos_facts
  {
    :operatingsystem => 'CentOS',
    :osfamily        => 'RedHat',
  }
end

def debian_facts
  {
    :operatingsystem => 'Debian',
    :osfamily        => 'Debian',
  }
end
