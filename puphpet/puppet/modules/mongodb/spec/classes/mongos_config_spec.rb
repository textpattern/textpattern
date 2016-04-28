require 'spec_helper'

describe 'mongodb::mongos::config' do

  describe 'it should create mongos configuration file' do

    let :facts do
      {
        :osfamily        => 'Debian',
        :operatingsystem => 'Debian',
      }
    end

    let :pre_condition do
      "class { 'mongodb::mongos': }"
    end

    it {
      is_expected.to contain_file('/etc/mongodb-shard.conf')
    }
  end

end
