<?php

namespace app\classes\dns;

class record {

	const type_a     = 1;
	const type_ns    = 2;
	const type_cname = 5;
	const type_soa   = 6;
	const type_ptr   = 12;
	const type_mx    = 15;
	const type_txt   = 16;

	const class_in = 1;

	private static $type_names = array(
		self::type_a     => 'A',
		self::type_ns    => 'NS',
		self::type_cname => 'CNAME',
		self::type_soa   => 'SOA',
		self::type_ptr   => 'PTR',
		self::type_mx    => 'MX',
		self::type_txt   => 'TXT'
	);
	private static $class_names = array(
		self::class_in => 'IN'
	);

}

?>