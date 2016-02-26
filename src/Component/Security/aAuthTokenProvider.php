<?php

namespace bblue\ruby\Component\Security;

use bblue\ruby\Component\Core\AbstractRequest;
use bblue\ruby\Component\Request\iInternalRequest;
use bblue\ruby\Entities\User;

abstract class aAuthTokenProvider implements iAuthTokenProvider
{
	protected function prepareToken(AuthToken $token, iInternalRequest $request, User $user)
    {
        $token->setClientAddress($request->getClientAddress());
        $token->setUserAgent($request->getUserAgent());
        $token->setLoginHash($user->getLoginHash());
        $token->setUser($user);
        return $token;
    }
}