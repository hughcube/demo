<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Contracts\Support\DeferrableProvider;
use Jenssegers\Agent\AgentServiceProvider as BaseAgentServiceProvider;

class AgentServiceProvider extends BaseAgentServiceProvider implements DeferrableProvider
{
}
