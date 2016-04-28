require 'spec_helper_acceptance'

case fact('osfamily')
when 'FreeBSD'
  packagename = 'net/ntp'
when 'Gentoo'
  packagename = 'net-misc/ntp'
when 'Linux'
  case fact('operatingsystem')
  when 'ArchLinux'
    packagename = 'ntp'
  when 'Gentoo'
    packagename = 'net-misc/ntp'
  end
when 'AIX'
  packagename = 'bos.net.tcp.client'
when 'Solaris'
  case fact('operatingsystemrelease')
  when '5.10'
    packagename = ['SUNWntpr','SUNWntpu']
  when '5.11'
    packagename = 'service/network/ntp'
  end
else
  if fact('operatingsystem') == 'SLES' and fact('operatingsystemmajrelease') == '12'
    servicename = 'ntpd'
  else
    servicename = 'ntp'
  end
end

if (fact('osfamily') == 'Solaris')
  config = '/etc/inet/ntp.conf'
else
  config = '/etc/ntp.conf'
end

describe "ntp class:", :unless => UNSUPPORTED_PLATFORMS.include?(fact('osfamily')) do
  it 'applies successfully' do
    pp = "class { 'ntp': }"

    apply_manifest(pp, :catch_failures => true) do |r|
      expect(r.stderr).not_to match(/error/i)
    end
  end

  describe 'autoconfig' do
    it 'raises a deprecation warning' do
      pp = "class { 'ntp': autoupdate => true }"

      apply_manifest(pp, :catch_failures => true) do |r|
        expect(r.stdout).to match(/autoupdate parameter has been deprecated and replaced with package_ensure/)
      end
    end
  end

  describe 'config' do
    it 'sets the ntp.conf location' do
      pp = "class { 'ntp': config => '/etc/antp.conf' }"
      apply_manifest(pp, :catch_failures => true)
    end

    describe file('/etc/antp.conf') do
      it { should be_file }
    end
  end

  describe 'config_template' do
    it 'sets up template' do
      modulepath = default['distmoduledir']
      shell("mkdir -p #{modulepath}/test/templates")
      shell("echo 'testcontent' >> #{modulepath}/test/templates/ntp.conf")
    end

    it 'sets the ntp.conf location' do
      pp = "class { 'ntp': config_template => 'test/ntp.conf' }"
      apply_manifest(pp, :catch_failures => true)
    end

    describe file("#{config}") do
      it { should be_file }
      its(:content) { should match 'testcontent' }
    end
  end

  describe 'driftfile' do
    it 'sets the driftfile location' do
      pp = "class { 'ntp': driftfile => '/tmp/driftfile' }"
      apply_manifest(pp, :catch_failures => true)
    end

    describe file("#{config}") do
      it { should be_file }
      its(:content) { should match 'driftfile /tmp/driftfile' }
    end
  end

  describe 'keys' do
    it 'enables the key parameters' do
      pp = <<-EOS
      class { 'ntp':
        keys_enable     => true,
        keys_file       => '/etc/ntp/keys',
        keys_controlkey => '/etc/ntp/controlkey',
        keys_requestkey => '1',
        keys_trusted    => [ '1', '2' ],
      }
      EOS
      # Rely on a shell command instead of a file{} here to avoid loops
      # within puppet when it tries to manage /etc/ntp/keys before /etc/ntp.
      shell("mkdir -p /etc/ntp && echo '1 M AAAABBBB' >> /etc/ntp/keys")
      apply_manifest(pp, :catch_failures => true)
    end

    describe file("#{config}") do
      it { should be_file }
      its(:content) { should match 'keys /etc/ntp/keys' }
      its(:content) { should match 'controlkey /etc/ntp/controlkey' }
      its(:content) { should match 'requestkey 1' }
      its(:content) { should match 'trustedkey 1 2' }
    end
  end

  describe 'package' do
    it 'installs the right package' do
      pp = <<-EOS
      class { 'ntp':
        package_ensure => present,
        package_name   => #{Array(packagename).inspect},
      }
      EOS
      apply_manifest(pp, :catch_failures => true)
    end

    Array(packagename).each do |package|
      describe package(package) do
        it { should be_installed }
      end
    end
  end

  describe 'panic => 0' do
    it 'disables the tinker panic setting' do
      pp = <<-EOS
      class { 'ntp':
        panic => 0,
      }
      EOS
      apply_manifest(pp, :catch_failures => true)
    end

    describe file("#{config}") do
      its(:content) { should match 'tinker panic 0' }
    end
  end

  describe 'panic => 1' do
    it 'enables the tinker panic setting' do
      pp = <<-EOS
      class { 'ntp':
        panic => 1,
      }
      EOS
      apply_manifest(pp, :catch_failures => true)
    end

    describe file("#{config}") do
      its(:content) { should match 'tinker panic 1' }
    end
  end

  describe 'udlc' do
    it 'adds a udlc' do
      pp = "class { 'ntp': udlc => true }"
      apply_manifest(pp, :catch_failures => true)
    end

    describe file("#{config}") do
      it { should be_file }
      its(:content) { should match '127.127.1.0' }
    end
  end

  describe 'udlc_stratum' do
    it 'sets the stratum value when using udlc' do
      pp = "class { 'ntp': udlc => true, udlc_stratum => 10 }"
      apply_manifest(pp, :catch_failures => true)
    end

    describe file("#{config}") do
      it { should be_file }
      its(:content) { should match 'stratum 10' }
    end
  end

end
