#This file pulls in only the minimum necessary to let unmigrated specs still work

# Define the main module namespace for use by the helper modules
module PuppetlabsSpec
  # FIXTURE_DIR represents the standard locations of all fixture data. Normally
  # this represents <project>/spec/fixtures. This will be used by the fixtures
  # library to find relative fixture data.
  FIXTURE_DIR = File.join("spec", "fixtures") unless defined?(FIXTURE_DIR)
end

# Require all necessary helper libraries so they can be used later
require 'puppetlabs_spec_helper/puppetlabs_spec/files'
require 'puppetlabs_spec_helper/puppetlabs_spec/fixtures'
#require 'puppetlabs_spec_helper/puppetlabs_spec/puppet_internals'
require 'puppetlabs_spec_helper/puppetlabs_spec/matchers'

RSpec.configure do |config|
  # Include PuppetlabsSpec helpers so they can be called at convenience
  config.extend PuppetlabsSpec::Files
  config.extend PuppetlabsSpec::Fixtures
  config.include PuppetlabsSpec::Fixtures

  config.parser = 'future' if ENV['FUTURE_PARSER'] == 'yes'
  config.strict_variables = true if ENV['STRICT_VARIABLES'] == 'yes'
  config.stringify_facts = false if ENV['STRINGIFY_FACTS'] == 'no'
  config.trusted_node_data = true if ENV['TRUSTED_NODE_DATA'] == 'yes'
  config.ordering = ENV['ORDERING'] if ENV['ORDERING']

  # This will cleanup any files that were created with tmpdir or tmpfile
  config.after :each do
    PuppetlabsSpec::Files.cleanup
  end
end

