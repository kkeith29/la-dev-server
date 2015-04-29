class tools {

	package { ['curl','vim-enhanced','htop']:
		ensure => present,
		require => Exec['yum-update']
	}

}