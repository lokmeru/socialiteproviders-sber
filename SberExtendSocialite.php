<?php

namespace SocialiteProviders\Sber;

use SocialiteProviders\Manager\SocialiteWasCalled;

class SberExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('sber', Provider::class);
    }
}
