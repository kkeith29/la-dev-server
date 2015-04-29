<?php

namespace app\traits\db;

trait soft_delete {

	public function _trashed_only() {
		if ( !$this->soft_delete ) {
			throw new app_exception('Trashed records only exist when using soft deletes');
		}
		$this->config['trashed_only'] = true;
		return $this;
	}

	public function _with_trashed() {
		if ( !$this->soft_delete ) {
			throw new app_exception('Trashed records only exist when using soft deletes');
		}
		$this->config['with_trashed'] = true;
		return $this;
	}

	public function _force_delete() {
		
	}

	public function _restore() {

	}

}

?>