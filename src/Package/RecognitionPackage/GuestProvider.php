<?php

namespace bblue\ruby\Package\RecognitionPackage;

use bblue\ruby\Component\Core\iUserProvider;
use bblue\ruby\Entities\Guest;

final class GuestProvider implements iUserProvider
{
	public function getById($userId)
	{
		if($userId === Guest::GUEST_ID) {
			return new Guest;
		}
	}

	public function getByUsername($username)
	{
		if($username === Guest::GUEST_USERNAME) {
			return new Guest;
		}
	}
}