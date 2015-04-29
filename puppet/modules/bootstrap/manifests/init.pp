class bootstrap {
	exec {
		'yum-update':
			command => '/usr/bin/yum clean all; /usr/bin/yum -q -y update;',
			timeout => 1000;
		'import-repo':
			command => '/bin/rpm -Uvh http://packages.sw.be/rpmforge-release/rpmforge-release-0.5.3-1.el6.rf.x86_64.rpm',
			require => Exec['yum-update'];
		'import-repo-webstatic':
			command => '/bin/rpm -Uvh https://mirror.webtatic.com/yum/el6/latest.rpm',
			require => Exec['import-repo'];
	}
}