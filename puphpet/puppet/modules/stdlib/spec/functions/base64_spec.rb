require 'spec_helper'

describe 'base64' do
  it { is_expected.not_to eq(nil) }
  it { is_expected.to run.with_params().and_raise_error(Puppet::ParseError) }
  it { is_expected.to run.with_params("one").and_raise_error(Puppet::ParseError) }
  it { is_expected.to run.with_params("one", "two", "three").and_raise_error(Puppet::ParseError) }
  it { is_expected.to run.with_params("one", "two").and_raise_error(Puppet::ParseError, /first argument must be one of/) }
  it { is_expected.to run.with_params("encode", ["two"]).and_raise_error(Puppet::ParseError, /second argument must be a string/) }
  it { is_expected.to run.with_params("encode", 2).and_raise_error(Puppet::ParseError, /second argument must be a string/) }

  it { is_expected.to run.with_params("encode", "thestring").and_return("dGhlc3RyaW5n\n") }
  it { is_expected.to run.with_params("encode", "a very long string that will cause the base64 encoder to produce output with multiple lines").and_return("YSB2ZXJ5IGxvbmcgc3RyaW5nIHRoYXQgd2lsbCBjYXVzZSB0aGUgYmFzZTY0\nIGVuY29kZXIgdG8gcHJvZHVjZSBvdXRwdXQgd2l0aCBtdWx0aXBsZSBsaW5l\ncw==\n") }
  it { is_expected.to run.with_params("decode", "dGhlc3RyaW5n").and_return("thestring") }
  it { is_expected.to run.with_params("decode", "dGhlc3RyaW5n\n").and_return("thestring") }
end
