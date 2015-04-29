class iptables {

	service { 'iptables':
		ensure => stopped
	}

}