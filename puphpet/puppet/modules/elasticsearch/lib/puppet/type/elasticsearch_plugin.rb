Puppet::Type.newtype(:elasticsearch_plugin) do

  @doc = "Plugin installation type"
  
  ensurable do
    defaultvalues
    defaultto :present
  end

  newparam(:name, :namevar => true) do
    desc 'An arbitrary name used as the identity of the resource.'
  end

  newparam(:url) do
    desc 'Url of the package'
  end

  newparam(:source) do
    desc 'Source of the package. puppet:// or file:// resource'
  end

  newparam(:proxy_args) do
    desc 'Proxy Host'
  end

  newparam(:plugin_dir) do
    desc 'Plugin directory'
    defaultto '/usr/share/elasticsearch/plugins'
  end

  newparam(:install_options) do
    desc 'Installation options'
  end

end
