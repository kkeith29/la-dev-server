<?php

namespace app\commands;

use app\classes\cli;
use app\classes\command;
use app\classes\db;
use app\classes\db\table;
use app\classes\func;
use app\classes\ini;
use app\classes\manifest;
use app\classes\package as pkg;
use app\classes\path;
use app\classes\system;
use app\classes\template;
use app\exceptions\app as app_exception;
use app\exceptions\db as db_exception;

class package extends command {

	public function main() {
		cli::line('Default here');
	}

	public function version() {
		cli::line( 'Version: %s',pkg::version() );
	}

	public function install() {
		if ( !func::is_root() ) {
			throw new app_exception('Install requires root privileges');
		}
		if ( pkg::installed() && !$this->args->has('force') ) {
			throw new app_exception('Already installed, please run update or uninstall first');
		}

		$ini = new ini;

		//Server type questions
		$ini->master = cli::confirm('Is this the master server?','n');
		if ( !$ini->master ) {
			$ini->master_ip = cli::prompt('Master server API IP',array(
				'validation' => function( $value ) {
					if ( preg_match( '#^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}$#',$value ) !== 1 ) {
						cli::line('IP address invalid');
						return false;
					}
					return true;
				}
			));
			$ini->master_port = cli::prompt('Master server API port',array(
				'validation' => function( $value ) {
					$value = (int) $value;
					if ( $port <= 1024 ) {
						cli::line('Port number must be greater than 1024');
						return false;
					}
					return true;
				}
			));
			$ini->api_key_public  = cli::prompt('API Public Key');
			$ini->api_key_private = cli::prompt('API Private Key');
		}

		//Path information
		$paths = $ini->section('paths');
		$paths->bin = cli::prompt('Path for binary file',array(
			'default' => '/usr/local/bin'
		));
		$paths->bin = '/' . trim( $paths->bin,'/' ) . '/';
		$paths->data = cli::prompt('Path for data storage',array(
			'default' => '/var/' . APP_NAME
		));
		$paths->data = '/' . trim( $paths->data,'/' ) . '/';

		//API config
		cli::line('API Configuration:');
		cli::line();
		$api = $ini->section('api');
		$interfaces = system::network_interfaces();
		foreach( $interfaces as $name => &$label ) {
			$label = "{$name} - {$label}";
			unset( $label );
		}
		$api->interface = cli::menu( $interfaces,array(
			'title' => 'Choose server interface'
		) );
		$api->port = cli::prompt('Port for API server',array(
			'default' => '2100',
			'validation' => function( $value ) {
				$value = (int) $value;
				if ( $port <= 1024 ) {
					cli::line('Port number must be greater than 1024');
					return false;
				}
				return true;
			}
		));
		cli::line('SSL Certificate Info');
		$cert = $api->section('ssl_cert');
		$cert->country = cli::prompt('Country Code',array(
			'validation' => function( $value ) {
				$length = strlen( $value );
				if ( $length < 2 || $length > 4 ) {
					cli::line('Code is not valid');
					return false;
				}
				return true;
			}
		));
		$cert->country = strtoupper( $cert->country );
		$cert->state_province = cli::prompt('State/Province Code',array(
			'validation' => function( $value ) {
				if ( strlen( $value ) !== 2 ) {
					cli::line('Code is not valid');
					return false;
				}
				return true;
			}
		));
		$cert->state_province = strtoupper( $cert->state_province );
		$cert->city = cli::prompt('City');
		$cert->organization = cli::prompt('Organization');
		$cert->department = cli::prompt('Department');
		$cert->name = cli::prompt('Name');
		$cert->email = cli::prompt('Email');

		//Directory creation
		if ( !is_dir( PATH_ETC ) && func::exec( 'mkdir -p %s',PATH_ETC ) === false ) {
			throw new app_exception( 'Unable to create config directory: %s',PATH_ETC ); 
		}
		if ( !is_dir( $paths->bin ) && func::exec( 'mkdir -p %s',$paths->bin ) === false ) {
			throw new app_exception( 'Unable to create bin directory: %s',$paths->bin ); 
		}
		if ( !is_dir( $paths->data ) && func::exec( 'mkdir -p %s',$paths->data ) === false ) {
			throw new app_exception( 'Unable to create data directory: %s',$paths->data ); 
		}
		$db_file = $paths->data . FILE_CONFIG_DB;
		try {
			db::open( $db_file );
		}
		catch( db_exception $e ) {
			cli::line( 'Unable to create database: %s',$e->getMessage() );
			exit(1);
		}
		try {
			$log = db::forge()->table('api_log_entries');
			$log->column( 'message',table::data_type_text );
			$log->column( 'created_at',table::data_type_int );
			$log->create();
			unset( $log );
	
			if ( $ini->master ) {
				$users = db::forge()->table('users');
				$users->column( 'first_name',table::data_type_text );
				$users->column( 'last_name',table::data_type_text );
				$users->column( 'username',table::data_type_text );
				$users->column( 'email',table::data_type_text );
				$users->column( 'type',table::data_type_int );
				$users->with_timestamps();
				$users->create();
				unset( $users );
	
				$roles = db::forge()->table('roles');
				$roles->column( 'name',table::data_type_text );
				$roles->column( 'title',table::data_type_text );
				$roles->with_timestamps();
				$roles->create();
				unset( $roles );
	
				//permissions
				$perms = db::forge()->table('permissions');
				$perms->column( 'parent_permission_id',table::data_type_int );
				$perms->column( 'name',table::data_type_text );
				$perms->column( 'title',table::data_type_text );
				$perms->with_timestamps();
				$perms->create();
				unset( $perms );
	
				$user_role = db::forge()->table('users_roles');
				$user_role->column( 'user_id',table::data_type_int );
				$user_role->column( 'role_id',table::data_type_int );
				$user_role->create();
				unset( $user_role );
	
				//users roles (pvt)
				//roles permissions (pvt)
				//user permissions
	
				$servers = db::forge()->table('servers');
				$servers->column( 'name',table::data_type_text );
				$servers->column( 'ip_address',table::data_type_text );
				$servers->column( 'port',table::data_type_int );
				$servers->column( 'type',table::data_type_int );
				$servers->column( 'created_at',table::data_type_int );
				$servers->column( 'updated_at',table::data_type_int );
				$servers->create();
				unset( $servers );
			}
		}
		catch( db_exception $e ) {
			unlink( $db_file );
			cli::line( 'Unable to create necessary tables: %s',$e->getMessage() );
			exit(1);
		}

		$binary_path = $paths->bin . APP_NAME;
		$this->_install_binary( \Phar::running(false),$paths->data . PHAR_NAME,$binary_path );
		$ini->write( FILE_CONFIG_MAIN );
		cli::line( 'Config file written to \'%s\'',FILE_CONFIG_MAIN );
		cli::line('Installing API service');
		$service_tpl = template::fetch('service',array(
			'service_name' => API_SERVICE_NAME,
			'service_desc' => APP_NAME . ' API server',
			'binary_path'  => $binary_path,
			'binary_args'  => 'api:start'
		));
		if ( file_put_contents( PATH_API_SERVICE,$service_tpl ) === false ) {
			throw new app_exception( 'Unable to write service to: %s',PATH_API_SERVICE );
		}
		cli::line('Installed');
	}

	public function _install_binary( $phar_new,$phar_final,$binary_path ) {
		if ( func::exec( 'cp %s %s',$phar_new,$phar_final ) === false ) {
			throw new app_exception('Unable to move phar to data directory');
		}
		$binary_tpl = template::fetch('binary',array(
			'phar_path' => $phar_final
		));
		if ( file_put_contents( $binary_path,$binary_tpl ) === false ) {
			throw new app_exception('Unable to install binary file');
		}
		if ( func::exec( 'chmod a+x %s',$binary_path ) === false ) {
			throw new app_exception('Unable to set permissions for binary file');
		}
	}

	public function upgrade() {
		try {
			if ( !func::is_root() ) {
				throw new app_exception('Upgrade requires root privileges');
			}
			$manifest = new manifest( FILE_MANIFEST );
			$current = pkg::version();
			$latest = $manifest->latest_version(array(
				'dev' => ( pkg::is_dev() ? true : $this->args->has('dev') )
			));
			if ( !$this->args->has('force') ) {
				if ( version_compare( $latest,$current,'<=' ) ) {
					cli::line('No updates available');
					exit(0);
				}
				cli::line('Update found, downloading...');
			}
			else {
				cli::line('Force updating with latest update available');
			}
			$info = $manifest->version_info( $latest );
			$temp = func::make_temp(true);
			if ( $temp === false ) {
				throw new app_exception('Unable to create temp directory');
			}
			try {
				$phar_file = "{$temp}/{$info->name}";
				if ( file_put_contents( $phar_file,file_get_contents( $info->download ) ) === false ) {
					throw new app_exception('Unable to download update');
				}
				if ( sha1_file( $phar_file ) !== (string) $info->checksum ) {
					throw new app_exception('Phar is possibly corrupt, please try upgrade again.');
				}
				$phar = new \Phar( $phar_file );
				unset( $phar );
				if ( func::exec( 'rm -f %s',path::phar_file() ) === false ) {
					throw new app_exception('Unable to remove old phar');
				}
				if ( func::exec( 'rm -f %s',path::binary_file() ) === false ) {
					throw new app_exception('Unable to remove old binary');
				}
				$this->_install_binary( $phar_file,path::phar_file(),path::binary_file() );
				cli::line( 'Updated from %s to %s successfully',$current,$latest );
				if ( is_dir( $temp ) ) {
					func::exec( 'rm -rf %s',$temp );
				}
			}
			catch( \PharException $e ) {
				throw new app_exception('Unable to open phar, may be corrupt. Please try again.');
			}
			catch( app_exception $e ) {
				if ( is_dir( $temp ) ) {
					func::exec( 'rm -rf %s',$temp );
				}
				throw $e;
			}
			$this->_upgrade( $current );
		}
		catch( app_exception $e ) {
			cli::line('Unable to upgrade - Reason: %s',$e->getMessage());
		}
	}

	private function _upgrade( $prev_version ) {
		//do conversions between upgrades here
	}

	public function uninstall() {
		if ( !func::is_root() ) {
			throw new app_exception('Uninstall requires root privileges');
		}
		if ( func::exec( '%s status',PATH_API_SERVICE ) !== false && func::exec( '%s stop',PATH_API_SERVICE ) === false ) {
			throw new app_exception('Unable to stop api service');
		}
		if ( func::exec( 'rm -f %s',PATH_API_SERVICE ) === false ) {
			throw new app_exception('Unable to remove api service');
		}
		if ( func::exec( 'rm -rf %s',PATH_ETC ) === false ) {
			throw new app_exception('Unable to remove config directory');
		}
		if ( func::exec( 'rm -f %s',path::binary_file() ) === false ) {
			throw new app_exception('Unable to remove binary');
		}
		if ( func::exec( 'rm -rf %s',path::data() ) ) {
			throw new app_exception('Unable to remove data directory');
		}
		echo 'Uninstalled successfully' . NL;
	}

}

?>