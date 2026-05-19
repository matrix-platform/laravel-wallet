<?php //>

namespace Tests;

use Carbon\Carbon;
use MatrixPlatform\Models\Currency;
use MatrixPlatform\Models\Wallet;
use MatrixPlatform\Support\Wallets;
use Tests\Models\TestMember;

class WalletFeatureTestCase extends FeatureTestCase {

    protected function currency(string $code = 'USD', int $precision = 2, ?int $ranking = null, ?Carbon $enable = null, ?Carbon $disable = null): Currency {
        $attrs = [
            'title' => $code,
            'code' => $code,
            'precision' => $precision,
            'enable_time' => $enable ?? now()->subMinute(),
            'disable_time' => $disable,
        ];

        if ($ranking !== null) {
            $attrs['ranking'] = $ranking;
        }

        return Currency::forceCreate($attrs);
    }

    protected function member(?string $username = null): TestMember {
        return TestMember::forceCreate(['username' => $username ?? 'u' . uniqid()]);
    }

    protected function wallet(int $balance = 0, int $frozen = 0, int $precision = 2, string $code = 'USD', ?TestMember $member = null): Wallet {
        $member ??= $this->member();

        if (!Currency::where('code', $code)->exists()) {
            $this->currency($code, $precision);
        }

        $wallet = Wallets::get($member, $code);
        $wallet->balance = $balance;
        $wallet->frozen = $frozen;
        $wallet->save();
        $wallet->refresh();
        $wallet->setRelation('member', $member);

        return $wallet;
    }

}
