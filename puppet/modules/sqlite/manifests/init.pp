class sqlite {

	package { 'sqlite':
		ensure => present,
		require => Exec['yum-update']
	}

}