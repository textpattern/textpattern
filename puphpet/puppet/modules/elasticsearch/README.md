#Elasticsearch Puppet module

####Table of Contents

1. [Overview](#overview)
2. [Module description - What the module does and why it is useful](#module-description)
3. [Setup - The basics of getting started with Elasticsearch](#setup)
  * [The module manages the following](#the-module-manages-the-following)
  * [Requirements](#requirements)
4. [Usage - Configuration options and additional functionality](#usage)
5. [Advanced features - Extra information on advanced usage](#advanced-features)
6. [Limitations - OS compatibility, etc.](#limitations)
7. [Development - Guide for contributing to the module](#development)
8. [Support - When you need help with this module](#support)



##Overview

This module manages Elasticsearch (http://www.elasticsearch.org/overview/elasticsearch/)

##Module description

The elasticsearch module sets up Elasticsearch instances and can manage plugins and templates.

This module has been tested against all versions of ES 1.x and 2.x

##Setup

###The module manages the following

* Elasticsearch repository files.
* Elasticsearch package.
* Elasticsearch configuration file.
* Elasticsearch service.
* Elasticsearch plugins.
* Elasticsearch templates.

###Requirements

* The [stdlib](https://forge.puppetlabs.com/puppetlabs/stdlib) Puppet library.
* [ceritsc/yum](https://forge.puppetlabs.com/ceritsc/yum) For yum version lock.
* [richardc/datacat](https://forge.puppetlabs.com/richardc/datacat)
* [Augeas](http://augeas.net/)

#### Repository management
When using the repository management you will need the following dependency modules:

* Debian/Ubuntu: [Puppetlabs/apt](http://forge.puppetlabs.com/puppetlabs/apt)
* OpenSuSE: [Darin/zypprepo](https://forge.puppetlabs.com/darin/zypprepo)

##Usage

###Main class

####Install a specific version

```puppet
class { 'elasticsearch':
  version => '1.4.2'
}
```

Note: This will only work when using the repository.

####Automatic upgrade of the software ( default set to false )
```puppet
class { 'elasticsearch':
  autoupgrade => true
}
```

####Removal/decommissioning
```puppet
class { 'elasticsearch':
  ensure => 'absent'
}
```

####Install everything but disable service(s) afterwards
```puppet
class { 'elasticsearch':
  status => 'disabled'
}
```

###Instances

This module works with the concept of instances. For service to start you need to specify at least one instance.

####Quick setup
```puppet
elasticsearch::instance { 'es-01': }
```

This will set up its own data directory and set the node name to `$hostname-$instance_name`

####Advanced options

Instance specific options can be given:

```puppet
elasticsearch::instance { 'es-01':
  config => { },        # Configuration hash
  init_defaults => { }, # Init defaults hash
  datadir => [ ],       # Data directory
}
```

See [Advanced features](#advanced-features) for more information

###Plug-ins

Install [a variety of plugins](http://www.elasticsearch.org/guide/en/elasticsearch/reference/current/modules-plugins.html#known-plugins). Note that `module_dir` is where the plugin will install itself to and must match that published by the plugin author; it is not where you would like to install it yourself.

####From official repository
```puppet
elasticsearch::plugin{'lmenezes/elasticsearch-kopf':
  instances  => 'instance_name'
}
```
####From custom url
```puppet
elasticsearch::plugin{ 'jetty':
  url        => 'https://oss-es-plugins.s3.amazonaws.com/elasticsearch-jetty/elasticsearch-jetty-1.2.1.zip',
  instances  => 'instance_name'
}
```

####Using a proxy
You can also use a proxy if required by setting the `proxy_host` and `proxy_port` options:
```puppet
elasticsearch::plugin { 'lmenezes/elasticsearch-kopf',
  instances  => 'instance_name',
  proxy_host => 'proxy.host.com',
  proxy_port => 3128
}
```

#####Plugin name could be:
* `elasticsearch/plugin/version` for official elasticsearch plugins (download from download.elasticsearch.org)
* `groupId/artifactId/version`   for community plugins (download from maven central or oss sonatype)
* `username/repository`          for site plugins (download from github master)

####Upgrading plugins
When you specify a certain plugin version, you can upgrade that plugin by specifying the new version.

```puppet
elasticsearch::plugin { 'elasticsearch/elasticsearch-cloud-aws/2.1.1':
}
```

And to upgrade, you would simply change it to

```puppet
elasticsearch::plugin { 'elasticsearch/elasticsearch-cloud-aws/2.4.1':
}
```

Please note that this does not work when you specify 'latest' as a version number.

####ES 2.x official plugins
For the Elasticsearch commercial plugins you can refer them to the simple name.

See the [Plugin installation](https://www.elastic.co/guide/en/elasticsearch/plugins/current/installation.html) for more details.

###Scripts

Install [scripts](http://www.elastic.co/guide/en/elasticsearch/reference/current/modules-scripting.html) to be used by Elasticsearch.
These scripts are shared across all defined instances on the same host.

```puppet
elasticsearch::script { 'myscript':
  ensure => 'present',
  source => 'puppet:///path/to/my/script.groovy'
}
```

###Templates

#### Add a new template using a file

This will install and/or replace the template in Elasticsearch:

```puppet
elasticsearch::template { 'templatename':
  file => 'puppet:///path/to/template.json'
}
```

#### Add a new template using content

This will install and/or replace the template in Elasticsearch:

```puppet
elasticsearch::template { 'templatename':
  content => '{"template":"*","settings":{"number_of_replicas":0}}'
}
```

#### Delete a template

```puppet
elasticsearch::template { 'templatename':
  ensure => 'absent'
}
```

#### Host

By default it uses localhost:9200 as host. you can change this with the `host` and `port` variables

```puppet
elasticsearch::template { 'templatename':
  host => $::ipaddress,
  port => 9200
}
```

###Bindings / Clients

Install a variety of [clients/bindings](http://www.elasticsearch.org/guide/en/elasticsearch/client/community/current/clients.html):

####Python

```puppet
elasticsearch::python { 'rawes': }
```

####Ruby
```puppet
elasticsearch::ruby { 'elasticsearch': }
```

###Connection Validator

This module offers a way to make sure an instance has been started and is up and running before
doing a next action. This is done via the use of the `es_instance_conn_validator` resource.
```puppet
es_instance_conn_validator { 'myinstance' :
  server => 'es.example.com',
  port   => '9200',
}
```

A common use would be for example :

```puppet
class { 'kibana4' :
  require => Es_Instance_Conn_Validator['myinstance'],
}
```

###Package installation

There are 2 different ways of installing the software

####Repository

This option allows you to use an existing repository for package installation.
The `repo_version` corresponds with the major version of Elasticsearch.

```puppet
class { 'elasticsearch':
  manage_repo  => true,
  repo_version => '1.4',
}
```

####Remote package source

When a repository is not available or preferred you can install the packages from a remote source:

#####http/https/ftp
```puppet
class { 'elasticsearch':
  package_url       => 'https://download.elasticsearch.org/elasticsearch/elasticsearch/elasticsearch-1.4.2.deb',
  proxy_url         => 'http://proxy.example.com:8080/',
}
```
Setting proxy_url to a location will enable download using the provided proxy
server. This parameter is also used by elasticsearch::plugin. Setting the port
in the proxy_url is mandatory. proxy_url defaults to undef (proxy disabled). 

#####puppet://
```puppet
class { 'elasticsearch':
  package_url => 'puppet:///path/to/elasticsearch-1.4.2.deb'
}
```

#####Local file
```puppet
class { 'elasticsearch':
  package_url => 'file:/path/to/elasticsearch-1.4.2.deb'
}
```

###Java installation

Most sites will manage Java separately; however, this module can attempt to install Java as well.
This is done by using the [puppetlabs-java](https://forge.puppetlabs.com/puppetlabs/java) module.

```puppet
class { 'elasticsearch':
  java_install => true
}
```

Specify a particular Java package/version to be installed:

```puppet
class { 'elasticsearch':
  java_install => true,
  java_package => 'packagename'
}
```

###Service management

Currently only the basic SysV-style [init](https://en.wikipedia.org/wiki/Init) and [Systemd](http://en.wikipedia.org/wiki/Systemd) service providers are supported, but other systems could be implemented as necessary (pull requests welcome).


####Defaults File

The *defaults* file (`/etc/defaults/elasticsearch` or `/etc/sysconfig/elasticsearch`) for the Elasticsearch service can be populated as necessary. This can either be a static file resource or a simple key value-style  [hash](http://docs.puppetlabs.com/puppet/latest/reference/lang_datatypes.html#hashes) object, the latter being particularly well-suited to pulling out of a data source such as Hiera.

#####file source
```puppet
class { 'elasticsearch':
  init_defaults_file => 'puppet:///path/to/defaults'
}
```
#####hash representation
```puppet
$config_hash = {
  'ES_HEAP_SIZE' => '30g',
}

class { 'elasticsearch':
  init_defaults => $config_hash
}
```

Note: `init_defaults` hash can be passed to the main class and to the instance.

##Advanced features

###Package version pinning

The module supports pinning the package version to avoid accidental upgrades that are not done by Puppet.
To enable this feature:

```puppet
class { 'elasticsearch':
  package_pin => true,
  version     => '1.5.2',
}
```

In this example we pin the package version to 1.5.2.


###Data directories

There are 4 different ways of setting data directories for Elasticsearch.
In every case the required configuration options are placed in the `elasticsearch.yml` file.

####Default
By default we use:

`/usr/share/elasticsearch/data/$instance_name`

Which provides a data directory per instance.


####Single global data directory

```puppet
class { 'elasticsearch':
  datadir => '/var/lib/elasticsearch-data'
}
```
Creates the following for each instance:

`/var/lib/elasticsearch-data/$instance_name`

####Multiple Global data directories

```puppet
class { 'elasticsearch':
  datadir => [ '/var/lib/es-data1', '/var/lib/es-data2']
}
```
Creates the following for each instance:
`/var/lib/es-data1/$instance_name`
and
`/var/lib/es-data2/$instance_name`


####Single instance data directory

```puppet
class { 'elasticsearch': }

elasticsearch::instance { 'es-01':
  datadir => '/var/lib/es-data-es01'
}
```
Creates the following for this instance:
`/var/lib/es-data-es01`

####Multiple instance data directories

```puppet
class { 'elasticsearch': }

elasticsearch::instance { 'es-01':
  datadir => ['/var/lib/es-data1-es01', '/var/lib/es-data2-es01']
}
```
Creates the following for this instance:
`/var/lib/es-data1-es01`
and
`/var/lib/es-data2-es01`


###Main and instance configurations

The `config` option in both the main class and the instances can be configured to work together.

The options in the `instance` config hash will merged with the ones from the main class and override any duplicates.

#### Simple merging

```puppet
class { 'elasticsearch':
  config => { 'cluster.name' => 'clustername' }
}

elasticsearch::instance { 'es-01':
  config => { 'node.name' => 'nodename' }
}
elasticsearch::instance { 'es-02':
  config => { 'node.name' => 'nodename2' }
}

```

This example merges the `cluster.name` together with the `node.name` option.

#### Overriding

When duplicate options are provided, the option in the instance config overrides the ones from the main class.

```puppet
class { 'elasticsearch':
  config => { 'cluster.name' => 'clustername' }
}

elasticsearch::instance { 'es-01':
  config => { 'node.name' => 'nodename', 'cluster.name' => 'otherclustername' }
}

elasticsearch::instance { 'es-02':
  config => { 'node.name' => 'nodename2' }
}
```

This will set the cluster name to `otherclustername` for the instance `es-01` but will keep it to `clustername` for instance `es-02`

####Configuration writeup

The `config` hash can be written in 2 different ways:

##### Full hash writeup

Instead of writing the full hash representation:
```puppet
class { 'elasticsearch':
  config                 => {
   'cluster'             => {
     'name'              => 'ClusterName',
     'routing'           => {
        'allocation'     => {
          'awareness'    => {
            'attributes' => 'rack'
          }
        }
      }
    }
  }
}
```
##### Short hash writeup
```puppet
class { 'elasticsearch':
  config => {
    'cluster' => {
      'name' => 'ClusterName',
      'routing.allocation.awareness.attributes' => 'rack'
    }
  }
}
```


##Limitations

This module has been built on and tested against Puppet 3.2 and higher.

The module has been tested on:

* Debian 6/7/8
* CentOS 6/7
* Ubuntu 12.04, 14.04
* OpenSuSE 13.x

Other distro's that have been reported to work:

* RHEL 6
* OracleLinux 6
* Scientific 6

Testing on other platforms has been light and cannot be guaranteed.

##Development


##Support

Need help? Join us in [#elasticsearch](https://webchat.freenode.net?channels=%23elasticsearch) on Freenode IRC or on the [discussion forum](https://discuss.elastic.co/).
