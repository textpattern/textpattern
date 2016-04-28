source ENV['GEM_SOURCE'] || 'https://rubygems.org'

def location_for(place, fake_version = nil)
  if place =~ /^(git[:@][^#]*)#(.*)/
    [fake_version, { :git => $1, :branch => $2, :require => false }].compact
  elsif place =~ /^file:\/\/(.*)/
    ['>= 0', { :path => File.expand_path($1), :require => false }]
  else
    [place, { :require => false }]
  end
end

group :development, :unit_tests do
  # rspec must be v2 for ruby 1.8.7
  if RUBY_VERSION >= '1.8.7' and RUBY_VERSION < '1.9'
    gem 'rspec', '~> 2.0'
  else
    gem 'rspec', '~> 3.1.0',     :require => false
  end

  gem 'rake', '~> 10.1.0',       :require => false
  gem 'rspec-puppet', '~> 2.2',  :require => false
  gem 'mocha',                   :require => false
  # keep for its rake task for now
  gem 'puppetlabs_spec_helper',  :require => false
  gem 'puppet-lint',             :require => false
  gem 'metadata-json-lint',      :require => false
  gem 'pry',                     :require => false
  gem 'simplecov',               :require => false
end

beaker_version = ENV['BEAKER_VERSION']
beaker_rspec_version = ENV['BEAKER_RSPEC_VERSION']
group :system_tests do
  if beaker_version
    gem 'beaker', *location_for(beaker_version)
  end
  if beaker_rspec_version
    gem 'beaker-rspec', *location_for(beaker_rspec_version)
  else
    gem 'beaker-rspec', :require => false
  end
  gem 'serverspec', :require => false
  gem 'beaker-puppet_install_helper', :require => false
end

facterversion = ENV['GEM_FACTER_VERSION'] || ENV['FACTER_GEM_VERSION']
if facterversion
  gem 'facter', *location_for(facterversion)
else
  gem 'facter', :require => false
end

puppetversion = ENV['GEM_PUPPET_VERSION'] || ENV['PUPPET_GEM_VERSION']
if puppetversion
  gem 'puppet', *location_for(puppetversion)
else
  gem 'puppet', :require => false
end

# vim:ft=ruby
