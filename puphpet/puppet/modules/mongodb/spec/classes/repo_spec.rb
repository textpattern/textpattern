require 'spec_helper'

describe 'mongodb::repo', :type => :class do

  context 'when deploying on Debian' do
    let :facts do
      {
        :osfamily        => 'Debian',
        :operatingsystem => 'Debian',
        :lsbdistid       => 'Debian',
      }
    end

    it {
      is_expected.to contain_class('mongodb::repo::apt')
    }
  end

  context 'when deploying on CentOS' do
    let :facts do
      {
        :osfamily        => 'RedHat',
        :operatingsystem => 'CentOS',
      }
    end

    it {
      is_expected.to contain_class('mongodb::repo::yum')
    }
  end

  context 'when yumrepo has a proxy set' do
    let :facts do
      {
        :osfamily        => 'RedHat',
        :operatingsystem => 'RedHat',
      }
    end
    let :params do
      {
        :proxy => 'http://proxy-server:8080',
        :proxy_username => 'proxyuser1',
        :proxy_password => 'proxypassword1',
      }
    end
    it {
      is_expected.to contain_class('mongodb::repo::yum')
    }
    it do
      should contain_yumrepo('mongodb').with({
        'enabled' => '1',
        'proxy' => 'http://proxy-server:8080',
        'proxy_username' => 'proxyuser1',
        'proxy_password' => 'proxypassword1',
        })
    end
  end
end
