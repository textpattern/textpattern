#! /usr/bin/env ruby -S rspec
require 'spec_helper'

describe "the plugin_dir function" do
  let(:scope) { PuppetlabsSpec::PuppetInternals.scope }

  it "should exist" do
    expect(Puppet::Parser::Functions.function("plugin_dir")).to eq("function_plugin_dir")
  end

  it "should raise a ParseError if there is less than 1 argument" do
    expect { scope.function_plugin_dir([]) }.to raise_error(Puppet::ParseError)
  end

  it "should raise a ParseError if there are more than 2 arguments" do
    expect { scope.function_plugin_dir(['a', 'b', 'c']) }.to raise_error(Puppet::ParseError)
  end

  it "should complain about non-string first argument" do
    expect { scope.function_plugin_dir([[]]) }.to raise_error(Puppet::ParseError)
  end

  list = [
    { 'name' => 'mobz/elasticsearch-head',  'dir' => 'head' },
    { 'name' => 'lukas-vlcek/bigdesk/2.4.0', 'dir' => 'bigdesk' },
    { 'name' => 'elasticsearch/elasticsearch-cloud-aws/2.5.1', 'dir' => 'cloud-aws' },
    { 'name' => 'com.sksamuel.elasticsearch/elasticsearch-river-redis/1.1.0', 'dir' => 'river-redis' },
    { 'name' => 'com.github.lbroudoux.elasticsearch/amazon-s3-river/1.4.0', 'dir' => 'amazon-s3-river' },
    { 'name' => 'elasticsearch/elasticsearch-lang-groovy/2.0.0', 'dir' => 'lang-groovy' },
    { 'name' => 'royrusso/elasticsearch-HQ', 'dir' => 'HQ' },
    { 'name' => 'polyfractal/elasticsearch-inquisitor', 'dir' => 'inquisitor' },
    { 'name' => 'mycustomplugin', 'dir' => 'mycustomplugin' },
  ]

  describe "passing plugin name" do

    list.each do |plugin|

      it "should return #{plugin['dir']} directory name for #{plugin['name']}" do
        result = scope.function_plugin_dir([plugin['name']])
        expect(result).to eq(plugin['dir'])
      end

    end
  end

end
