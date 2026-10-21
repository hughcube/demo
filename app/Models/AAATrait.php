<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/5/10
 * Time: 2:43 下午
 */

namespace App\Models;

use HughCube\Laravel\Knight\Database\Eloquent\Builder;
use HughCube\Laravel\Knight\Database\Eloquent\Traits\Model as KnightModel;
use HughCube\Laravel\Knight\Database\Eloquent\Traits\OptimisticLock;
use HughCube\Laravel\Knight\Traits\GetKnightSortValueTrait;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Traits\Tappable;

/**
 * @method Builder valid()
 * @method static Builder query()
 */
trait AAATrait
{
    use Tappable;
    use KnightModel;
    use OptimisticLock;
    use GetKnightSortValueTrait;

    /**
     * 空字符串转为 null
     */
    protected function convertEmptyStringsToNull(?string $value): ?string
    {
        return ('' === $value || null === $value) ? null : $value;
    }

    public function getModelCachePrefix(): ?string
    {
        return 'm1';
    }

    public function getCache(): null|Repository
    {
        return Cache::store();
    }

    public function scopeValid($query): Builder
    {
        return $query;
    }

    public function toDateTime($date = null): null|Carbon
    {
        /** @phpstan-ignore-next-line */
        return Carbon::tryParse($date);
    }
}
