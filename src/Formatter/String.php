<?php
namespace Transphporm\Formatter;
class String {
	public function uppercase($val) {
		return strtoupper($val);
	}

	public function lowercase($val) {
		return strtolower($val);
	}

	public function titlecase($val) {
		return ucwords($val);
	}

}