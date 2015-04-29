class php {

	package { 'php55w':
		ensure => present,
		require => Exec['import-repo-webstatic']
	}
	
	package { 'php55w-common':
		ensure => present,
		require => Package['php55w']
	}

	package { 'php55w-devel':
		ensure => present,
		require => Package['php55w-common']
	}
	
	package { ['php55w-cli','php55w-mbstring','php55w-mcrypt','php55w-xml','php55w-xmlrpc','php55w-pdo']:
		ensure => present,
		require => Package['php55w-common']
	}

}