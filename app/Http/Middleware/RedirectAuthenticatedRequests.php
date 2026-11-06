<?php

namespace App\Http\Middleware;

use App\Contracts\Responses\ResponseMakerInterface;
use Closure;
use Illuminate\Auth\Middleware\RedirectIfAuthenticated;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectAuthenticatedRequests extends RedirectIfAuthenticated
{
    public function __construct(
        private readonly ResponseMakerInterface $responseMaker,
    ) {}

    public function handle(Request $httpRequest, Closure $next, string ...$guards): Response
    {
        $authenticationResponse = parent::handle($httpRequest, $next, ...$guards);

        if ($httpRequest->expectsJson() && $authenticationResponse instanceof RedirectResponse) {
            return $this->responseMaker->createResponse(
                additionalResponseData: [
                    'redirect' => $authenticationResponse->getTargetUrl(),
                ],
            );
        }

        return $authenticationResponse;
    }
}
