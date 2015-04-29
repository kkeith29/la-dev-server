stage { 'pre':
	before => Stage['main']
}
class { 'bootstrap':
	stage => 'pre'
}

include bootstrap
include tools
include iptables
include php
include sqlite