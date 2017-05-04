require 'spec_helper'

describe 'postgresql::server::role', :type => :define do
  let :facts do
    {
      :osfamily => 'Debian',
      :operatingsystem => 'Debian',
      :operatingsystemrelease => '6.0',
      :kernel => 'Linux',
      :concat_basedir => tmpfilename('contrib'),
      :id => 'root',
      :path => '/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin',
    }
  end

  let :title do
    'test'
  end

  let :params do
    {
      :password_hash => 'new-pa$s',
    }
  end

  let :pre_condition do
   "class {'postgresql::server':}"
  end

  it { is_expected.to contain_postgresql__server__role('test') }
  it 'should have create role for "test" user with password as ****' do
    is_expected.to contain_postgresql_psql('CREATE ROLE test ENCRYPTED PASSWORD ****').with({
      'command'     => "CREATE ROLE \"test\" ENCRYPTED PASSWORD '$NEWPGPASSWD' LOGIN NOCREATEROLE NOCREATEDB NOSUPERUSER  CONNECTION LIMIT -1",
      'environment' => "NEWPGPASSWD=new-pa$s",
      'unless'      => "SELECT rolname FROM pg_roles WHERE rolname='test'",
    })
  end
  it 'should have alter role for "test" user with password as ****' do
    is_expected.to contain_postgresql_psql('ALTER ROLE test ENCRYPTED PASSWORD ****').with({
      'command'     => "ALTER ROLE \"test\" ENCRYPTED PASSWORD '$NEWPGPASSWD'",
      'environment' => "NEWPGPASSWD=new-pa$s",
      'unless'      => "SELECT usename FROM pg_shadow WHERE usename='test' and passwd='md5b6f7fcbbabb4befde4588a26c1cfd2fa'",
    })
  end
end
