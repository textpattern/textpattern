$LOAD_PATH.unshift(File.join(File.dirname(__FILE__),"..","..",".."))

Puppet::Type.type(:elasticsearch_plugin).provide(:plugin) do
  desc "A provider for the resource type `elasticsearch_plugin`,
        which handles plugin installation"

  os = Facter['osfamily'].value
  if os == 'OpenBSD'
    commands :plugin => '/usr/local/elasticsearch/bin/plugin'
    commands :es => '/usr/local/elasticsearch/bin/elasticsearch'
    commands :javapathhelper => '/usr/local/bin/javaPathHelper'
  else
    commands :plugin => '/usr/share/elasticsearch/bin/plugin'
    commands :es => '/usr/share/elasticsearch/bin/elasticsearch'
  end

  def exists?
    es_version
    if !File.exists?(pluginfile)
      debug "Plugin file #{pluginfile} does not exist"
      return false
    elsif File.exists?(pluginfile) && readpluginfile != pluginfile_content
      debug "Got #{readpluginfile} Expected #{pluginfile_content}. Removing for reinstall"
      self.destroy
      return false
    else
      debug "Plugin exists"
      return true
    end
  end

  def pluginfile_content
    return @resource[:name] if is1x?

    if @resource[:name].split("/").count == 1 # Official plugin
      version = plugin_version(@resource[:name])
      return "#{@resource[:name]}/#{version}"
    else
      return @resource[:name]
    end
  end

  def pluginfile
    File.join(@resource[:plugin_dir], plugin_name(@resource[:name]), '.name')
  end

  def writepluginfile
    File.open(pluginfile, 'w') do |file|
      file.write pluginfile_content
    end
  end

  def readpluginfile
    f = File.open(pluginfile)
    f.readline
  end

  def install1x
    if !@resource[:url].nil?
      commands = [ plugin_name(@resource[:name]), '--url', @resource[:url] ]
    elsif !@resource[:source].nil?
      commands = [ plugin_name(@resource[:name]), '--url', "file://#{@resource[:source]}" ]
    else
      commands = [ @resource[:name] ]
    end
    commands
  end

  def install2x
    if !@resource[:url].nil?
      commands = [ @resource[:url] ]
    elsif !@resource[:source].nil?
      commands = [ "file://#{@resource[:source]}" ]
    else
      commands = [ @resource[:name] ]
    end
    commands
  end

  def install_options
    return @resource[:install_options].join(' ') if @resource[:install_options].is_a?(Array)
    return @resource[:install_options]
  end

  def create
    es_version
    commands = []
    commands << @resource[:proxy_args].split(' ') if @resource[:proxy_args]
    commands << install_options if @resource[:install_options]
    commands << 'install'
    commands << '--batch' if is22x?
    commands << install1x if is1x?
    commands << install2x if is2x?
    debug("Commands: #{commands.inspect}")
    
    retry_count = 3
    retry_times = 0
    begin
      plugin(commands)
    rescue Puppet::ExecutionFailure => e
      retry_times += 1
      debug("Failed to install plugin. Retrying... #{retry_times} of #{retry_count}")
      sleep 2
      retry if retry_times < retry_count
      raise "Failed to install plugin. Received error: #{e.inspect}"
    end

    writepluginfile
  end

  def destroy
    plugin(['remove', @resource[:name]])
  end

  def es_version
    return @es_version if @es_version
    es_save = ENV['ES_INCLUDE']
    java_save = ENV['JAVA_HOME']

    os = Facter['osfamily'].value
    if os == 'OpenBSD'
      ENV['JAVA_HOME'] = javapathhelper('-h', 'elasticsearch').chomp
      ENV['ES_INCLUDE'] = '/etc/elasticsearch/elasticsearch.in.sh'
    end
    begin
      version = es('-version')
    rescue
      ENV['ES_INCLUDE'] = es_save if es_save
      ENV['JAVA_HOME'] = java_save if java_save
      raise "Unknown ES version. Got #{version.inspect}"
    ensure
      ENV['ES_INCLUDE'] = es_save if es_save
      ENV['JAVA_HOME'] = java_save if java_save
      @es_version = version.scan(/\d+\.\d+\.\d+(?:\-\S+)?/).first
      debug "Found ES version #{@es_version}"
    end
  end

  def is1x?
    Puppet::Util::Package.versioncmp(@es_version, '2.0.0') < 0
  end

  def is2x?
    (Puppet::Util::Package.versioncmp(@es_version, '2.0.0') >= 0) && (Puppet::Util::Package.versioncmp(@es_version, '3.0.0') < 0)
  end

  def is22x?
    (Puppet::Util::Package.versioncmp(@es_version, '2.2.0') >= 0) && (Puppet::Util::Package.versioncmp(@es_version, '3.0.0') < 0)
  end


  def plugin_version(plugin_name)
    vendor, plugin, version = plugin_name.split('/')
    return @es_version if is2x? && version.nil?
    return version.scan(/\d+\.\d+\.\d+(?:\-\S+)?/).first unless version.nil?
    return false
  end

  def plugin_name(plugin_name)

    vendor, plugin, version = plugin_name.split('/')

    endname = vendor if plugin.nil? # If its a single name plugin like the ES 2.x official plugins
    endname = plugin.gsub(/(elasticsearch-|es-)/, '') unless plugin.nil?

    return endname.downcase if is2x?
    return endname

  end

end
