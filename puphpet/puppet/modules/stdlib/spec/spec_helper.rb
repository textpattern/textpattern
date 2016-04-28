#! /usr/bin/env ruby -S rspec
dir = File.expand_path(File.dirname(__FILE__))
$LOAD_PATH.unshift File.join(dir, 'lib')

# So everyone else doesn't have to include this base constant.
module PuppetSpec
  FIXTURE_DIR = File.join(dir = File.expand_path(File.dirname(__FILE__)), "fixtures") unless defined?(FIXTURE_DIR)
end

require 'puppet'
require 'rspec-puppet'
require 'puppetlabs_spec_helper/module_spec_helper'
require 'monkey_patches/alias_should_to_must'
require 'mocha/api'
#require 'puppetlabs_spec_helper/module_spec_helper'
require 'puppetlabs_spec_helper_clone'

# hack to enable all the expect syntax (like allow_any_instance_of) in rspec-puppet examples
RSpec::Mocks::Syntax.enable_expect(RSpec::Puppet::ManifestMatchers)

RSpec.configure do |config|
  config.module_path = File.join(File.dirname(File.expand_path(__FILE__)), 'fixtures', 'modules')
  config.manifest_dir = File.join(File.dirname(File.expand_path(__FILE__)), 'fixtures', 'manifests')
  config.environmentpath = spec_path = File.expand_path(File.join(Dir.pwd, 'spec'))

  config.add_setting :puppet_future
  #config.puppet_future = (ENV['FUTURE_PARSER'] == 'yes' or Puppet.version.to_f >= 4.0)
  config.puppet_future = Puppet.version.to_f >= 4.0

  config.before :each do
    # Ensure that we don't accidentally cache facts and environment between
    # test cases.  This requires each example group to explicitly load the
    # facts being exercised with something like
    # Facter.collection.loader.load(:ipaddress)
    Facter.clear
    Facter.clear_messages

    RSpec::Mocks.setup
  end

  config.after :each do
    RSpec::Mocks.verify
    RSpec::Mocks.teardown
  end
end

# Helper class to test handling of arguments which are derived from string
class AlsoString < String
end
