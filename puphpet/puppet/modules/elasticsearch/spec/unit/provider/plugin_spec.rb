require 'spec_helper'

provider_class = Puppet::Type.type(:elasticsearch_plugin).provider(:plugin)

describe provider_class do

  let(:resource_name) { 'lmenezes/elasticsearch-kopf' }
  let(:resource) do
    Puppet::Type.type(:elasticsearch_plugin).new(
      :name     => resource_name,
      :ensure   => :present,
      :provider => 'plugin'
    )
  end

  let(:provider) do
    provider = provider_class.new
    provider.resource = resource
    provider
  end

  describe "ES 1.x" do
    before(:each) do
      provider_class.expects(:es).with('-version').returns("Version: 1.7.1, Build: b88f43f/2015-07-29T09:54:16Z, JVM: 1.7.0_79")
      allow(File).to receive(:open)
      provider.es_version
    end

    let(:shortname) { provider.plugin_name(resource_name) }

    describe 'install' do
      it 'installs plugin' do
        provider.expects(:plugin).with(['install', [ resource_name] ])
        provider.create
      end


      it 'with url' do
        resource[:url] = 'http://url/to/my/plugin.zip'
        provider.expects(:plugin).with(['install', [ shortname, '--url', 'http://url/to/my/plugin.zip' ] ])
        provider.create
      end

      it 'with local file' do
        resource[:source] = '/tmp/plugin.zip'
        provider.expects(:plugin).with(['install', [ shortname, '--url', 'file:///tmp/plugin.zip' ] ])
        provider.create
      end

      it 'with proxy' do
        resource[:proxy_args] = '-dproxyport=3128 -dproxyhost=localhost'
        provider.expects(:plugin).with([['-dproxyport=3128', '-dproxyhost=localhost'], 'install', [resource_name] ])
        provider.create
      end

    end

    describe 'removal' do
      it 'destroys' do
        provider.expects(:plugin).with(['remove', resource_name])
        provider.destroy
      end
    end

  end

  describe "ES 2.x" do

    before(:each) do
      allow(provider_class).to receive(:es).with('-version').and_return("Version: 2.0.0, Build: de54438/2015-10-22T08:09:48Z, JVM: 1.8.0_66")
      allow(File).to receive(:open)
      provider.es_version
    end

    let(:shortname) { provider.plugin_name(resource_name) }

    describe 'install' do
      it 'installs plugin' do
        provider.expects(:plugin).with(['install', [ resource_name] ])
        provider.create
      end

      it 'with url' do
        resource[:url] = 'http://url/to/my/plugin.zip'
        provider.expects(:plugin).with(['install', [ 'http://url/to/my/plugin.zip' ] ])
        provider.create
      end

      it 'with local file' do
        resource[:source] = '/tmp/plugin.zip'
        provider.expects(:plugin).with(['install', [ 'file:///tmp/plugin.zip' ] ])
        provider.create
      end

      it 'with proxy' do
        resource[:proxy_args] = '-dproxyport=3128 -dproxyhost=localhost'
        provider.expects(:plugin).with([['-dproxyport=3128', '-dproxyhost=localhost'], 'install', [resource_name] ])
        provider.create
      end
    end

    describe 'removal' do
      it 'destroys' do
        provider.expects(:plugin).with(['remove', resource_name])
        provider.destroy
      end
    end

  end

end
