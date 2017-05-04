require 'rake'
require 'puppetlabs_spec_helper/rake_tasks'

RSpec::Core::RakeTask.new(:spec_verbose) do |t|
  t.pattern = 'spec/*/*_spec.rb'
  t.rspec_opts = File.read('spec/spec.opts').chomp || ""
end

task :test do
  Rake::Task[:spec_prep].invoke
  Rake::Task[:spec_verbose].invoke
  Rake::Task[:spec_clean].invoke
end


task :default => [:spec, :lint]
