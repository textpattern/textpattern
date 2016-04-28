module Puppet::Parser::Functions
  newfunction(
      :try_get_value,
      :type => :rvalue,
      :arity => -2,
      :doc => <<-eos
Looks up into a complex structure of arrays and hashes and returns a value
or the default value if nothing was found.

Key can contain slashes to describe path components. The function will go down
the structure and try to extract the required value.

$data = {
  'a' => {
    'b' => [
      'b1',
      'b2',
      'b3',
    ]
  }
}

$value = try_get_value($data, 'a/b/2', 'not_found', '/')
=> $value = 'b3'

a -> first hash key
b -> second hash key
2 -> array index starting with 0

not_found -> (optional) will be returned if there is no value or the path did not match. Defaults to nil.
/ -> (optional) path delimiter. Defaults to '/'.

In addition to the required "key" argument, "try_get_value" accepts default
argument. It will be returned if no value was found or a path component is
missing. And the fourth argument can set a variable path separator.
  eos
  ) do |args|
    path_lookup = lambda do |data, path, default|
      debug "Try_get_value: #{path.inspect} from: #{data.inspect}"
      if data.nil?
        debug "Try_get_value: no data, return default: #{default.inspect}"
        break default
      end
      unless path.is_a? Array
        debug "Try_get_value: wrong path, return default: #{default.inspect}"
        break default
      end
      unless path.any?
        debug "Try_get_value: value found, return data: #{data.inspect}"
        break data
      end
      unless data.is_a? Hash or data.is_a? Array
        debug "Try_get_value: incorrect data, return default: #{default.inspect}"
        break default
      end

      key = path.shift
      if data.is_a? Array
        begin
          key = Integer key
        rescue ArgumentError
          debug "Try_get_value: non-numeric path for an array, return default: #{default.inspect}"
          break default
        end
      end
      path_lookup.call data[key], path, default
    end

    data = args[0]
    path = args[1] || ''
    default = args[2]
    separator = args[3] || '/'

    path = path.split separator
    path_lookup.call data, path, default
  end
end
